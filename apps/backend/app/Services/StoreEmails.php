<?php

namespace App\Services;

use App\Jobs\SendStoreEmail;
use App\Models\CommerceRecord as Record;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreEmails
{
    public function test(string $recipient): array
    {
        $settings = Record::where('resource', 'settings')->firstOrFail()->data;
        if (($settings['mail_transport'] ?? '') !== 'SMTP') {
            throw ValidationException::withMessages(['mail_transport' => 'Save SMTP as the email transport before sending a test.']);
        }

        return $this->deliver('test:'.Str::uuid(), null, $recipient, 'SMTP test', 'LEEKAV SMTP test', '<p>Your LEEKAV SMTP connection is working. This is a test email sent from Store Settings.</p>', false);
    }

    public function newsletter(Record $subscriber): array
    {
        $settings = Record::where('resource', 'settings')->first()?->data ?? [];
        $name = $settings['store_name'] ?? 'LEEKAV';
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $body = '<h2>Welcome to '.$safeName.'</h2><p>Thanks for subscribing! You will hear from us about new arrivals, offers and thoughtful finds for your home.</p><p>If you would like to unsubscribe, contact us and we will remove you from our newsletter list.</p>';

        return $this->deliver('newsletter:'.$subscriber->id.':welcome', null, $subscriber->data['email'], 'Newsletter subscription', 'Welcome to '.$name, $body);
    }

    public function confirmation(array $order): array
    {
        $customer = Record::find($order['customer_id']);
        $recipient = $customer?->data['email'];
        if (! $recipient) {
            return ['status' => 'Skipped'];
        }
        $templates = Record::where('resource', 'emails')->get()->filter(fn ($record) => ($record->data['event'] ?? '') === 'Order confirmation');
        $template = $templates->first(fn ($record) => ($record->data['status'] ?? '') === 'Active');
        if ($templates->isNotEmpty() && ! $template) {
            return ['status' => 'Skipped'];
        }
        $settings = Record::where('resource', 'settings')->firstOrFail()->data;
        $values = ['{{customer_name}}' => $order['customer_name'], '{{order_reference}}' => $order['reference'], '{{total}}' => 'KES '.number_format($order['total'], 2), '{{store_name}}' => $settings['store_name'] ?? 'LEEKAV'];
        $subject = strtr($template?->data['subject'] ?? 'Order confirmation - {{order_reference}}', $values);
        $body = $template?->data['body'] ?? '<p>Hi {{customer_name}},</p><p>Thank you for your order {{order_reference}}. Total: {{total}}.</p><p>Your order has been received. Payment will be collected according to your selected payment method.</p>';
        $body = str_replace(['\\r\\n', '\\n', '\\r'], ["\n", "\n", "\n"], $body);
        $body = strip_tags($body, '<p><br><strong><b><em><i><ul><ol><li><h2><h3><blockquote>');
        $body = nl2br(strtr($body, array_map(fn ($value) => htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $values)));

        return $this->deliver('order:'.$order['id'].':confirmation', $order['id'], $recipient, 'Order confirmation', $subject, $body);
    }

    public function orderChanged(array $order, ?array $before): void
    {
        if ($before === null) {
            $settings = Record::where('resource', 'settings')->first()?->data ?? [];
            $recipient = $settings['email'] ?? '';
            if (filter_var($recipient, FILTER_VALIDATE_EMAIL) && ! str_ends_with(strtolower($recipient), '@example.com')) {
                $this->notification($order, 'Admin new order', 'new-order', $recipient);
            }

            return;
        }
        if (($before['status'] ?? null) !== ($order['status'] ?? null)) {
            $this->notification($order, 'Order status update', 'status:'.hash('sha256', json_encode($order['status_history'] ?? [$order['status']])));
        }
        if (($before['payment_status'] ?? null) !== ($order['payment_status'] ?? null) && ($order['payment_status'] ?? '') === 'Paid') {
            $this->notification($order, 'Payment successful', 'payment:'.hash('sha256', json_encode($order['payment_status_history'] ?? ['Paid'])));
        }
    }

    private function notification(array $order, string $event, string $key, ?string $recipient = null): array
    {
        $recipient ??= $order['customer_email'] ?? Record::find($order['customer_id'] ?? '')?->data['email'] ?? '';
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'Skipped'];
        }
        $templates = Record::where('resource', 'emails')->get()->filter(fn ($record) => ($record->data['event'] ?? '') === $event);
        $template = $templates->first(fn ($record) => ($record->data['status'] ?? '') === 'Active');
        if ($templates->isNotEmpty() && ! $template) {
            return ['status' => 'Skipped'];
        }
        $values = ['{{customer_name}}' => $order['customer_name'] ?? $order['name'] ?? '', '{{order_reference}}' => $order['reference'] ?? '', '{{total}}' => 'KES '.number_format($order['total'] ?? 0, 2), '{{order_status}}' => $order['status'] ?? '', '{{payment_status}}' => $order['payment_status'] ?? '', '{{store_name}}' => Record::where('resource', 'settings')->first()?->data['store_name'] ?? 'LEEKAV'];
        $subject = strtr($template?->data['subject'] ?? $event.' - {{order_reference}}', $values);
        $body = $template?->data['body'] ?? '<p>Hi {{customer_name}},</p><p>Order {{order_reference}}. Total: {{total}}.</p>';
        $body .= '<p>Order status: {{order_status}}<br>Payment status: {{payment_status}}</p>';
        $body = str_replace(['\\r\\n', '\\n', '\\r'], ["\n", "\n", "\n"], $body);
        $body = strip_tags($body, '<p><br><strong><b><em><i><ul><ol><li><h2><h3><blockquote>');
        $body = nl2br(strtr($body, array_map(fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'), $values)));

        return $this->deliver('order:'.$order['id'].':'.$key, $order['id'], $recipient, $event, $subject, $body);
    }

    private function deliver(string $key, ?string $orderId, string $recipient, string $event, string $subject, string $body, bool $queued = true): array
    {
        return DB::transaction(function () use ($key, $orderId, $recipient, $event, $subject, $body, $queued): array {
            $id = (string) Str::uuid();
            $inserted = DB::table('email_deliveries')->insertOrIgnore(['id' => $id, 'delivery_key' => $key, 'order_id' => $orderId, 'recipient' => $recipient, 'event' => $event, 'subject' => $subject, 'body' => $body, 'status' => 'Queued', 'created_at' => now(), 'updated_at' => now()]);
            if (! $inserted) {
                $existing = DB::table('email_deliveries')->where('delivery_key', $key)->first();
                if ($existing->status !== 'Failed' || ! DB::table('email_deliveries')->where('id', $existing->id)->where('status', 'Failed')->update(['status' => 'Queued', 'error' => null, 'subject' => $subject, 'body' => $body, 'updated_at' => now()])) {
                    return ['id' => $existing->id, 'status' => $existing->status, 'error' => $existing->error];
                }
                $id = $existing->id;
            }
            if (! $queued || config('queue.default') === 'sync') {
                return $this->sendDelivery($id);
            }
            SendStoreEmail::dispatch($id)->onQueue('emails');

            return ['id' => $id, 'status' => 'Queued'];
        });
    }

    public function sendDelivery(string $id, bool $retrying = false): array
    {
        $delivery = DB::table('email_deliveries')->where('id', $id)->first();
        if (! $delivery || in_array($delivery->status, ['Sent', 'Logged'], true)) {
            return ['id' => $id, 'status' => $delivery?->status ?? 'Skipped'];
        }
        DB::table('email_deliveries')->where('id', $id)->update(['status' => 'Sending', 'attempts' => DB::raw('attempts + 1'), 'updated_at' => now()]);
        $recipient = $delivery->recipient;
        $subject = $delivery->subject;
        $body = $delivery->body;
        try {
            $settings = Record::where('resource', 'settings')->firstOrFail()->data;
            $transport = $settings['mail_transport'] ?? 'Log (local preview)';
            if ($transport === 'Amazon SES') {
                throw new \RuntimeException('SES is not configured.');
            }
            $config = $transport === 'SMTP' ? ['transport' => 'smtp', 'host' => $settings['smtp_host'] ?? '', 'port' => (int) ($settings['smtp_port'] ?? 587), 'scheme' => (int) ($settings['smtp_port'] ?? 587) === 465 ? 'smtps' : 'smtp', 'username' => $settings['smtp_username'] ?? null, 'password' => ! empty($settings['smtp_password']) ? Crypt::decryptString($settings['smtp_password']) : null, 'timeout' => 8] : ['transport' => 'log'];
            $from = $settings['mail_from'] ?? config('mail.from.address');
            $name = $settings['store_name'] ?? 'LEEKAV';
            Mail::build($config)->html($body, function (Message $message) use ($recipient, $subject, $from, $name) {
                $message->to($recipient)->from($from, $name)->subject($subject);
            });
            $status = $transport === 'SMTP' ? 'Sent' : 'Logged';
            DB::table('email_deliveries')->where('id', $id)->update(['status' => $status, 'error' => null, 'sent_at' => now(), 'updated_at' => now()]);

            return ['id' => $id, 'status' => $status];
        } catch (\Throwable $exception) {
            $error = 'Email could not be sent. Check SMTP host, port, sender and credentials, then try a test email again.';
            DB::table('email_deliveries')->where('id', $id)->update(['status' => $retrying ? 'Retrying' : 'Failed', 'error' => $error, 'updated_at' => now()]);

            return ['id' => $id, 'status' => 'Failed', 'error' => $error];
        }
    }
}
