<?php

namespace App\Services;

use App\Models\CommerceRecord as Record;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CustomerPayments
{
    public function begin(Record $order, Record $method): Record
    {
        $data = $order->data;
        if (($method->data['status'] ?? '') !== 'Active') {
            app(Commerce::class)->fail('The selected payment method is disabled.');
        }
        $id = (string) Str::uuid();

        return Record::create(['id' => $id, 'resource' => 'payments', 'data' => ['name' => 'PAY-'.$id, 'payment_id' => 'PAY-'.$id, 'order_id' => $order->id, 'order_reference' => $data['reference'], 'customer_id' => $data['customer_id'] ?? null, 'customer_name' => $data['customer_name'] ?? $data['name'], 'customer_email' => $data['customer_email'] ?? '', 'payment_method_id' => $method->id, 'method' => $method->data['provider'], 'method_name' => $method->data['name'], 'currency' => $data['currency'] ?? 'KES', 'amount' => $data['total'], 'status' => 'Pending', 'reference' => null, 'refunded_amount' => 0, 'source' => 'Customer checkout', 'status_history' => [['from' => null, 'to' => 'Pending', 'at' => now()->toISOString(), 'actor' => 'Customer checkout']]]]);
    }

    public function event(array $input): array
    {
        Validator::make($input, ['event_id' => 'required|string|max:100', 'payment_id' => 'required|uuid', 'method' => 'required|string|max:80', 'reference' => 'required|string|max:120', 'amount' => 'required|numeric|min:0.01', 'currency' => 'required|in:KES', 'status' => 'required|in:Successful,Failed'])->validate();

        return app(Commerce::class)->idempotent('payment-event:'.$input['event_id'], $input, function () use ($input) {
            $commerce = app(Commerce::class);
            $payment = $commerce->find('payments', $input['payment_id'], true);
            $data = $payment->data;
            $order = $commerce->find('orders', $data['order_id'], true);
            if ($input['method'] !== $data['method'] || $input['currency'] !== ($data['currency'] ?? 'KES') || (int) round($input['amount'] * 100) !== (int) round($data['amount'] * 100)) {
                $commerce->fail('Payment event does not match the checkout amount, currency or method.');
            }
            if (! in_array($data['status'], ['Pending', 'Failed'], true)) {
                if (($data['reference'] ?? '') !== $input['reference']) {
                    abort(409, 'Transaction is already finalized with a different reference.');
                }

                return $payment->row();
            }
            $before = $payment->row();
            $data['reference'] = $input['reference'];
            $data['status'] = $input['status'];
            $data['provider_event_id'] = $input['event_id'];
            $data['processed_at'] = now()->toISOString();
            $data['status_history'][] = ['from' => $before['status'], 'to' => $data['status'], 'at' => now()->toISOString(), 'actor' => 'Verified payment event'];
            $payment->data = $data;
            $payment->version++;
            try {
                $payment->save();
            } catch (QueryException $exception) {
                if (str_contains($exception->getMessage(), 'transaction_reference')) {
                    abort(409, 'This provider transaction reference belongs to another payment.');
                }throw $exception;
            }
            if ($input['status'] === 'Successful') {
                $old = $order->row();
                $order->data = array_merge($order->data, ['payment_status' => 'Paid']);
                $order->version++;
                $order->save();
                $this->audit('Customer payment completed', 'orders', $old, $order->row());
            }
            $this->audit('Verified payment '.$input['status'], 'payments', $before, $payment->row());

            return $payment->row();
        });
    }

    public function synchronizeRefunds(Record $order, float $total): void
    {
        $remaining = (int) round($total * 100);
        foreach (Record::where('resource', 'payments')->oldest()->lockForUpdate()->get()->filter(fn ($payment) => ($payment->data['order_id'] ?? '') === $order->id && in_array($payment->data['status'], ['Successful', 'Paid', 'Refunded', 'Partially Refunded'], true)) as $payment) {
            $before = $payment->row();
            $data = $payment->data;
            $amount = (int) round($data['amount'] * 100);
            $refunded = min($amount, $remaining);
            $remaining -= $refunded;
            $data['refunded_amount'] = $refunded / 100;
            $status = $refunded === $amount ? 'Refunded' : ($refunded > 0 ? 'Partially Refunded' : 'Successful');
            if ($status !== $data['status'] || $data['refunded_amount'] !== ($before['refunded_amount'] ?? 0)) {
                $data['status_history'][] = ['from' => $data['status'], 'to' => $status, 'at' => now()->toISOString(), 'actor' => 'Refund reconciliation'];
                $data['status'] = $status;
                $payment->data = $data;
                $payment->version++;
                $payment->save();
                $this->audit('Refund reconciled', 'payments', $before, $payment->row());
            }
        }
    }

    private function audit(string $action, string $resource, array $before, array $after): void
    {
        DB::table('audit_events')->insert(['id' => (string) Str::uuid(), 'actor' => 'Payment system', 'action' => $action, 'resource' => $resource, 'name' => $after['name'] ?? $after['reference'], 'before' => json_encode($before), 'after' => json_encode($after), 'created_at' => now()]);
    }
}
