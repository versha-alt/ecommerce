<?php

namespace Tests\Feature;

use App\Models\CommerceRecord as Record;
use App\Models\User;
use App\Services\CustomerPayments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    use RefreshDatabase;

    private function fixtures(string $role = 'Admin'): array
    {
        $user = User::create(['name' => 'Staff', 'email' => 'statuses@example.com', 'password' => Hash::make('StatusesTest!2026'), 'role' => $role, 'status' => 'Active']);
        $token = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'StatusesTest!2026'])->assertOk()->json('token');
        $this->withToken($token);
        Record::create(['resource' => 'settings', 'data' => ['store_name' => 'Store']]);
        $product = Record::create(['resource' => 'products', 'data' => ['name' => 'Product', 'slug' => 'lifecycle-product', 'stock' => 10, 'reserved' => 2, 'status' => 'Active']]);
        $order = Record::create(['resource' => 'orders', 'data' => ['name' => 'Buyer', 'reference' => 'ORD-STATUS', 'status' => 'Pending', 'payment_status' => 'Pending', 'total' => 500, 'fulfilment_status' => 'Reserved', 'coupon_code' => '', 'lines' => [['product_id' => $product->id, 'quantity' => 2]]]]);

        return [$order, $product];
    }

    private function lifecycle(Record $order, string $stage, bool $signed = true, ?string $eventId = null): mixed
    {
        config(['commerce.order_events_secret' => str_repeat('s', 40)]);
        $input = ['event_id' => $eventId ?? (string) Str::uuid(), 'order_id' => $order->id, 'version' => $order->fresh()->version, 'stage' => $stage, 'evidence' => 'Customer lifecycle event'];
        $body = json_encode($input);
        $timestamp = (string) time();

        return $this->call('POST', '/api/v1/order-events', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_X_ORDER_TIMESTAMP' => $timestamp, 'HTTP_X_ORDER_SIGNATURE' => $signed ? hash_hmac('sha256', $timestamp.'.'.$body, str_repeat('s', 40)) : 'invalid'], $body);
    }

    public function test_signed_lifecycle_uses_shared_inventory_and_status_history(): void
    {
        [$order, $product] = $this->fixtures();
        $this->lifecycle($order, 'Processing', false)->assertUnauthorized();
        $this->lifecycle($order, 'Processing')->assertOk()->assertJsonPath('status', 'Confirmed');
        $this->withHeader('Idempotency-Key', 'manual-paid')->postJson('/api/v1/order-status/'.$order->id, ['version' => $order->fresh()->version, 'payment_status' => 'Paid', 'evidence' => 'Verified offline receipt'])->assertOk();
        $this->lifecycle($order, 'Shipping')->assertOk()->assertJsonPath('status', 'Dispatched');
        $this->assertSame(8, $product->fresh()->data['stock']);
        $this->assertSame(0, $product->fresh()->data['reserved']);
        $this->lifecycle($order, 'Delivered')->assertOk()->assertJsonPath('status', 'Delivered');
        $this->assertCount(3, $order->fresh()->data['status_history']);
        $this->assertCount(1, $order->fresh()->data['payment_status_history']);
    }

    public function test_manual_overrides_require_admin_reason_and_fresh_version_and_roll_back_invalid_transitions(): void
    {
        [$order] = $this->fixtures();
        $this->withHeader('Idempotency-Key', 'invalid-transition')->postJson('/api/v1/order-status/'.$order->id, ['version' => 1, 'status' => 'Delivered', 'payment_status' => 'Paid', 'evidence' => 'Invalid skip'])->assertUnprocessable();
        $this->assertSame('Pending', $order->fresh()->data['payment_status']);
        $payload = ['version' => 1, 'status' => 'Confirmed', 'payment_status' => 'Paid', 'evidence' => 'Checked receipt'];
        $this->withHeader('Idempotency-Key', 'valid-update')->postJson('/api/v1/order-status/'.$order->id, $payload)->assertOk();
        $this->postJson('/api/v1/order-status/'.$order->id, $payload)->assertOk();
        $this->assertCount(1, $order->fresh()->data['payment_status_history']);
        $this->withHeader('Idempotency-Key', 'stale-update')->postJson('/api/v1/order-status/'.$order->id, $payload)->assertConflict();
        $this->withHeader('Idempotency-Key', 'empty-reason')->postJson('/api/v1/order-status/'.$order->id, ['version' => $order->fresh()->version, 'payment_status' => 'Failed', 'evidence' => ''])->assertUnprocessable()->assertJsonValidationErrors('evidence');
    }

    public function test_sales_staff_cannot_override_payment_status(): void
    {
        [$order] = $this->fixtures('Sales');
        $this->withHeader('Idempotency-Key', 'sales-override')->postJson('/api/v1/order-status/'.$order->id, ['version' => 1, 'payment_status' => 'Paid', 'evidence' => 'Override'])->assertForbidden();
    }

    public function test_payment_events_update_incomplete_failed_and_paid_states_without_late_downgrades(): void
    {
        [$order] = $this->fixtures();
        $method = Record::create(['resource' => 'payment-methods', 'data' => ['name' => 'PayPal', 'provider' => 'PayPal', 'status' => 'Active']]);
        $payment = app(CustomerPayments::class)->begin($order, $method);
        $base = ['payment_id' => $payment->id, 'method' => 'PayPal', 'amount' => 500, 'currency' => 'KES'];
        app(CustomerPayments::class)->event($base + ['event_id' => 'incomplete', 'status' => 'Incomplete']);
        $this->assertSame('Incomplete', $order->fresh()->data['payment_status']);
        app(CustomerPayments::class)->event($base + ['event_id' => 'failed', 'status' => 'Failed']);
        $this->assertSame('Failed', $order->fresh()->data['payment_status']);
        app(CustomerPayments::class)->event($base + ['event_id' => 'paid', 'status' => 'Successful', 'reference' => 'VERIFIED-TXN']);
        app(CustomerPayments::class)->event($base + ['event_id' => 'late-failed', 'status' => 'Failed', 'reference' => 'VERIFIED-TXN']);
        $this->assertSame('Paid', $order->fresh()->data['payment_status']);
    }

    public function test_delayed_failure_from_old_attempt_does_not_replace_new_attempt_pending_status(): void
    {
        [$order] = $this->fixtures();
        $method = Record::create(['resource' => 'payment-methods', 'data' => ['name' => 'PayPal', 'provider' => 'PayPal', 'status' => 'Active']]);
        $first = app(CustomerPayments::class)->begin($order, $method);
        $second = app(CustomerPayments::class)->begin($order->fresh(), $method);
        app(CustomerPayments::class)->event(['event_id' => 'old-failure', 'payment_id' => $first->id, 'method' => 'PayPal', 'amount' => 500, 'currency' => 'KES', 'status' => 'Failed']);
        $this->assertSame('Pending', $order->fresh()->data['payment_status']);
        $this->assertSame($second->id, $order->fresh()->data['active_payment_id']);
    }
}
