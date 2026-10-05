<?php

namespace Tests\Feature;

use App\Models\CommerceRecord as Record;
use App\Models\User;
use App\Services\CustomerPayments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PaymentHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function login(): void
    {
        User::create(['name' => 'Admin', 'email' => 'payments@example.com', 'password' => Hash::make('PaymentsTest!2026'), 'role' => 'Admin', 'status' => 'Active']);
        $this->withToken($this->postJson('/api/v1/auth/login', ['email' => 'payments@example.com', 'password' => 'PaymentsTest!2026'])->json('token'));
    }

    private function fixtures(): array
    {
        Record::create(['resource' => 'settings', 'data' => ['store_name' => 'Test']]);
        $order = Record::create(['resource' => 'orders', 'data' => ['reference' => 'ORD-PAY', 'name' => 'Buyer', 'customer_name' => 'Buyer', 'customer_id' => 'buyer', 'customer_email' => 'buyer@example.com', 'total' => 500, 'status' => 'Confirmed', 'payment_status' => 'Unpaid', 'currency' => 'KES']]);
        $method = Record::create(['resource' => 'payment-methods', 'data' => ['name' => 'PayPal', 'provider' => 'PayPal', 'status' => 'Active']]);
        $payment = app(CustomerPayments::class)->begin($order, $method);

        return [$order, $payment];
    }

    private function event(Record $payment, array $changes = []): array
    {
        return $changes + ['event_id' => 'event-one', 'payment_id' => $payment->id, 'method' => 'PayPal', 'reference' => 'CUSTOMER-TXN-1', 'amount' => 500, 'currency' => 'KES', 'status' => 'Successful'];
    }

    private function sendEvent(array $event, bool $signed = true): TestResponse
    {
        config(['commerce.payment_events_secret' => str_repeat('a', 40)]);
        $body = json_encode($event);
        $timestamp = (string) time();

        return $this->call('POST', '/api/v1/payment-events', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_X_PAYMENT_TIMESTAMP' => $timestamp, 'HTTP_X_PAYMENT_SIGNATURE' => $signed ? hash_hmac('sha256', $timestamp.'.'.$body, str_repeat('a', 40)) : 'invalid'], $body);
    }

    public function test_admin_cannot_create_or_fake_payments_but_can_manage_notes(): void
    {
        $this->login();
        [$order,$payment] = $this->fixtures();
        $this->postJson('/api/v1/payments', [])->assertStatus(405);
        $this->postJson('/api/v1/order-actions/'.$order->id, ['action' => 'pay', 'version' => 1, 'evidence' => 'fake'])->assertStatus(405);
        $this->patchJson('/api/v1/payments/'.$payment->id, ['version' => 1, 'status' => 'Successful'])->assertUnprocessable();
        $this->patchJson('/api/v1/payments/'.$payment->id, ['version' => 1, 'notes' => 'Followed up with customer'])->assertOk();
        $this->assertEquals('Pending', $payment->fresh()->data['status']);
    }

    public function test_verified_customer_completion_updates_existing_attempt_and_order_idempotently(): void
    {
        [$order,$payment] = $this->fixtures();
        $event = $this->event($payment);
        $this->sendEvent($event)->assertOk()->assertJsonPath('status', 'Successful');
        $this->sendEvent($event)->assertOk();
        $this->assertEquals(1, Record::where('resource', 'payments')->count());
        $this->assertEquals('Paid', $order->fresh()->data['payment_status']);
        $this->assertEquals('Buyer', $payment->fresh()->data['customer_name']);
        $this->assertCount(2, $payment->fresh()->data['status_history']);
        $this->sendEvent($this->event($payment, ['event_id' => 'late-failure', 'status' => 'Failed']))->assertOk()->assertJsonPath('status', 'Successful');
    }

    public function test_unsigned_or_mismatched_payment_events_cannot_mark_orders_paid(): void
    {
        [$order,$payment] = $this->fixtures();
        $this->sendEvent($this->event($payment), false)->assertUnauthorized();
        $this->sendEvent($this->event($payment, ['amount' => 1]))->assertUnprocessable();
        $this->assertEquals('Unpaid', $order->fresh()->data['payment_status']);
        $this->sendEvent($this->event($payment, ['status' => 'Failed']))->assertOk()->assertJsonPath('status', 'Failed');
        $this->assertEquals('Unpaid', $order->fresh()->data['payment_status']);
    }

    public function test_refunds_update_payment_history_and_preserve_original_amount(): void
    {
        [$order,$payment] = $this->fixtures();
        app(CustomerPayments::class)->event($this->event($payment));
        app(CustomerPayments::class)->synchronizeRefunds($order, 100);
        $this->assertEquals('Partially Refunded', $payment->fresh()->data['status']);
        $this->assertEquals(100, $payment->fresh()->data['refunded_amount']);
        app(CustomerPayments::class)->synchronizeRefunds($order, 500);
        $this->assertEquals('Refunded', $payment->fresh()->data['status']);
        $this->assertEquals(500, $payment->fresh()->data['amount']);
        $this->login();
        $this->getJson('/api/v1/workspace')->assertOk()->assertJsonPath('records.payments.0.status','Refunded');
    }
}
