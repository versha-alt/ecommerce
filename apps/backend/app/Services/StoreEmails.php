<?php

namespace App\Services;

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

        return $this->deliver('test:'.Str::uuid(), null, $recipient, 'SMTP test', 'LEEKAV SMTP test', '<p>Your LEEKAV SMTP connection is working. This is a test email sent from Store Settings.</p>');
    }

    public function confirmation(array $order): array
    {
        $customer = Record::find($order['customer_id']);
        $recipient = $customer?->data['email'];
        if (! $recipient) {
            return ['status' => 'Skipped'];
        }
        $template = Record::where('resource', 'emails')->get()->first(fn ($record) => ($record->data['event'] ?? '') === 'Order confirmation' && ($record->data['status'] ?? '') === 'Active');
        $settings = Record::where('resource', 'settings')->firstOrFail()->data;
        $values = ['{{customer_name}}' => $order['customer_name'], '{{order_reference}}' => $order['reference'], '{{total}}' => 'KES '.number_format($order['total'], 2), '{{store_name}}' => $settings['store_name'] ?? 'LEEKAV'];
        $subject = strtr($template?->data['subject'] ?? 'Order confirmation · {{order_reference}}', $values);
        $body = $template?->data['body'] ?? '<p>Hi {{customer_name}},</p><p>Thank you for your order {{order_reference}}. Total: {{total}}.</p><p>Your order has been received. Payment will be collected according to your selected payment method.</p>';
        $body = strip_tags($body, '<p><br><strong><b><em><i><ul><ol><li><h2><h3><blockquote>');
        $body = nl2br(strtr($body, array_map(fn ($value) => htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $values)));

        return $this->deliver('order:'.$order['id'].':confirmation', $order['id'], $recipient, 'Order confirmation', $subject, $body);
    }

    private function deliver(string $key, ?string $orderId, string $recipient, string $event, string $subject, string $body): array
    {
        $id = (string) Str::uuid();
        $now = now();
        $inserted = DB::table('email_deliveries')->insertOrIgnore(['id' => $id, 'delivery_key' => $key, 'order_id' => $orderId, 'recipient' => $recipient, 'event' => $event, 'status' => 'Sending', 'created_at' => $now, 'updated_at' => $now]);
        if (! $inserted) {
            $existing = DB::table('email_deliveries')->where('delivery_key', $key)->first();

            return ['id' => $existing->id, 'status' => $existing->status, 'error' => $existing->error];
        }
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
            DB::table('email_deliveries')->where('id', $id)->update(['status' => $status, 'sent_at' => now(), 'updated_at' => now()]);

            return ['id' => $id, 'status' => $status];
        } catch (\Throwable $exception) {
            $error = 'Email could not be sent. Check SMTP host, port, sender and credentials, then try a test email again.';
            DB::table('email_deliveries')->where('id', $id)->update(['status' => 'Failed', 'error' => $error, 'updated_at' => now()]);

            return ['id' => $id, 'status' => 'Failed', 'error' => $error];
        }
    }
}
