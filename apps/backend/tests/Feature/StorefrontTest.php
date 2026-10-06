<?php

namespace Tests\Feature;

use App\Models\CommerceRecord as Record;
use App\Models\User;
use App\Services\Commerce;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        Record::create(['resource' => 'settings', 'data' => ['store_name' => 'Test']]);
        Record::create(['resource' => 'taxes', 'data' => ['name' => 'VAT', 'rate' => 10, 'inclusive' => false, 'status' => 'Active']]);
        $product = Record::create(['resource' => 'products', 'data' => ['name' => 'Washer', 'slug' => 'washer', 'sku' => 'W1', 'type' => 'Simple', 'price' => 500, 'stock' => 5, 'reserved' => 0, 'status' => 'Active', 'category_ids' => []]]);
        $zone = Record::create(['resource' => 'delivery-zones', 'data' => ['name' => 'Nairobi', 'country_code' => 'KE', 'county_codes' => ['047'], 'charge' => 100, 'free_threshold' => 1000, 'status' => 'Active']]);
        $method = Record::create(['resource' => 'payment-methods', 'data' => ['name' => 'Cash on delivery', 'category' => 'Manual', 'provider' => 'COD', 'status' => 'Active']]);

        return [$product, $zone, $method];
    }

    private function body(array $fixtures, string $email = 'guest@example.com'): array
    {
        return ['name' => 'Customer', 'email' => $email, 'phone' => '0700000000', 'address' => 'Test road', 'county_code' => '047', 'delivery_zone_id' => $fixtures[1]->id, 'payment_method_id' => $fixtures[2]->id, 'lines' => [['product_id' => $fixtures[0]->id, 'quantity' => 2]]];
    }

    private function member(string $email = 'member@example.com'): string
    {
        return $this->postJson('/api/v1/store/register', ['name' => 'Member', 'email' => $email, 'password' => 'CustomerTest!2026', 'password_confirmation' => 'CustomerTest!2026'])->assertOk()->json('token');
    }

    public function test_registration_requires_matching_password_confirmation(): void
    {
        $input = ['name' => 'Customer', 'email' => 'confirmation@example.com', 'password' => 'CustomerTest!2026'];
        $this->postJson('/api/v1/store/register', $input)->assertUnprocessable()->assertJsonValidationErrors('password_confirmation');
        $this->postJson('/api/v1/store/register', $input + ['password_confirmation' => 'DifferentTest!2026'])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertSame(0, Record::where('resource', 'customers')->count());
        $this->assertDatabaseCount('customer_sessions', 0);
        $response = $this->postJson('/api/v1/store/register', $input + ['password_confirmation' => 'CustomerTest!2026'])->assertOk();
        $this->assertArrayNotHasKey('password_confirmation', Record::where('resource', 'customers')->firstOrFail()->data);
        $response->assertJsonMissingPath('customer.password_confirmation');
    }

    public function test_registration_sessions_and_admin_edits_preserve_private_credentials(): void
    {
        $this->fixture();
        $token = $this->member();
        $customer = Record::where('resource', 'customers')->firstOrFail();
        $this->assertNotSame('CustomerTest!2026', $customer->data['password_hash']);
        $this->assertArrayNotHasKey('password_hash', $customer->row());
        $this->assertDatabaseMissing('customer_sessions', ['token_hash' => $token]);
        $this->postJson('/api/v1/store/register', ['name' => 'Duplicate', 'email' => 'MEMBER@example.com', 'password' => 'CustomerTest!2026', 'password_confirmation' => 'CustomerTest!2026'])->assertUnprocessable();
        app(Commerce::class)->save('customers', $customer->row() + ['password_hash' => 'injected'], new User(['name' => 'Admin', 'email' => 'admin@example.com', 'role' => 'Admin']), $customer->id);
        $this->postJson('/api/v1/store/login', ['email' => 'member@example.com', 'password' => 'CustomerTest!2026', 'password_confirmation' => 'CustomerTest!2026'])->assertOk();
        $this->withToken($token)->getJson('/api/v1/store/account')->assertOk()->assertJsonMissingPath('customer.password_hash');
        $this->postJson('/api/v1/store/logout')->assertOk();
        $this->getJson('/api/v1/store/account')->assertUnauthorized();
    }

    public function test_quote_rolls_back_and_guest_checkout_is_idempotent_and_order_scoped(): void
    {
        $f = $this->fixture();
        $body = $this->body($f);
        $this->postJson('/api/v1/store/quote', $body)->assertOk()->assertJsonPath('total', 1100);
        $this->assertSame(0, Record::where('resource', 'orders')->count());
        $this->assertSame(0, Record::where('resource', 'customers')->count());
        $this->assertSame(0, $f[0]->fresh()->data['reserved']);
        $r = $this->withHeader('Idempotency-Key', 'guest-order')->postJson('/api/v1/store/checkout', $body)->assertOk()->json();
        $this->postJson('/api/v1/store/checkout', $body)->assertOk()->assertJsonPath('order.id', $r['order']['id']);
        $this->assertSame(1, Record::where('resource', 'orders')->count());
        $this->assertSame(1, Record::where('resource', 'payments')->count());
        $this->assertSame(2, $f[0]->fresh()->data['reserved']);
        $this->withToken($r['token'])->getJson('/api/v1/store/orders/'.$r['order']['id'])->assertOk();
        $this->getJson('/api/v1/store/account')->assertUnauthorized();
        $other = Record::create(['resource' => 'orders', 'data' => ['customer_id' => $r['order']['customer_id']]]);
        $this->getJson('/api/v1/store/orders/'.$other->id)->assertNotFound();
    }

    public function test_mail_failure_does_not_block_checkout_or_duplicate_confirmation(): void
    {
        $f = $this->fixture();
        Mail::shouldReceive('build')->once()->andThrow(new \RuntimeException('Connection refused'));
        $body = $this->body($f);
        $this->postJson('/api/v1/store/quote', $body)->assertOk();
        $this->assertDatabaseCount('email_deliveries', 0);
        $first = $this->withHeader('Idempotency-Key', 'mail-failure')->postJson('/api/v1/store/checkout', $body)->assertOk()->assertJsonPath('email_delivery.status', 'Failed')->json();
        $this->postJson('/api/v1/store/checkout', $body)->assertOk()->assertJsonPath('order.id', $first['order']['id']);
        $this->assertDatabaseCount('email_deliveries', 1);
        $this->assertSame(1, Record::where('resource', 'orders')->count());
    }

    public function test_checkout_rejects_wrong_county_unconnected_gateway_and_guest_impersonation(): void
    {
        $f = $this->fixture();
        $body = $this->body($f);
        $this->withHeader('Idempotency-Key', 'invalid')->postJson('/api/v1/store/checkout', array_merge($body, ['county_code' => '001']))->assertUnprocessable();
        $f[2]->data = array_merge($f[2]->data, ['category' => 'Online', 'provider' => 'PayPal']);
        $f[2]->save();
        $this->postJson('/api/v1/store/checkout', $body)->assertUnprocessable();
        $f[2]->data = array_merge($f[2]->data, ['category' => 'Manual', 'provider' => 'COD']);
        $f[2]->save();
        $this->member('guest@example.com');
        $this->postJson('/api/v1/store/checkout', $body)->assertUnprocessable();
        $this->assertSame(0, Record::where('resource', 'orders')->count());
    }

    public function test_member_order_isolation_cancellation_and_review_moderation(): void
    {
        $f = $this->fixture();
        $token = $this->member();
        $order = $this->withToken($token)->withHeader('Idempotency-Key', 'member-order')->postJson('/api/v1/store/checkout', $this->body($f, 'member@example.com'))->assertOk()->json('order');
        $this->getJson('/api/v1/store/account')->assertOk()->assertJsonCount(1, 'orders');
        $this->withHeader('Idempotency-Key', 'cancel')->postJson('/api/v1/store/orders/'.$order['id'].'/cancel', ['version' => $order['version'], 'evidence' => 'Changed mind'])->assertOk()->assertJsonPath('order.status', 'Cancelled');
        $this->assertSame(0, $f[0]->fresh()->data['reserved']);
        $this->postJson('/api/v1/store/reviews', ['submission_id' => (string) Str::uuid(), 'product_id' => $f[0]->id, 'rating' => 4, 'body' => 'Very useful'])->assertOk();
        $this->assertSame('Pending', Record::where('resource', 'reviews')->firstOrFail()->data['status']);
        $this->getJson('/api/v1/products/'.$f[0]->id.'/reviews')->assertOk()->assertJsonCount(0, 'data');
        $other = $this->member('other@example.com');
        $this->withToken($other)->getJson('/api/v1/store/orders/'.$order['id'])->assertNotFound();
    }

    public function test_enquiries_and_newsletter_validate_and_persist(): void
    {
        $this->postJson('/api/v1/store/enquiries', [])->assertUnprocessable();
        $this->postJson('/api/v1/store/enquiries', ['name' => 'Visitor', 'email' => 'visitor@example.com', 'subject' => 'Delivery', 'message' => 'Can you deliver here?'])->assertOk();
        $this->assertSame('Open', Record::where('resource', 'enquiries')->firstOrFail()->data['status']);
        $this->postJson('/api/v1/store/newsletter', ['email' => 'bad'])->assertUnprocessable();
        $this->postJson('/api/v1/store/newsletter', ['email' => 'visitor@example.com'])->assertOk();
        $this->postJson('/api/v1/store/newsletter', ['email' => 'visitor@example.com'])->assertOk();
        $this->assertSame(1, Record::where('resource', 'newsletter')->count());
    }

    public function test_public_catalog_hides_internal_notes_and_gateway_credentials(): void
    {
        $fixtures = $this->fixture();
        $fixtures[2]->data = array_merge($fixtures[2]->data, ['credentials' => 'private-secret', 'notes' => 'Internal']);
        $fixtures[2]->save();
        $response = $this->getJson('/api/v1/store/catalog')->assertOk();
        $response->assertJsonMissingPath('payment_methods.0.credentials')->assertJsonMissingPath('payment_methods.0.notes');
        $this->assertSame(47, count($response->json('locations.countries.KE.counties')));
    }

    public function test_return_requests_are_owned_idempotent_and_visible_without_internal_notes(): void
    {
        $this->fixture();
        $token = $this->member();
        $customer = Record::where('resource', 'customers')->firstOrFail();
        $order = Record::create(['resource' => 'orders', 'data' => ['customer_id' => $customer->id, 'status' => 'Delivered', 'payment_status' => 'Paid', 'total' => 1000, 'refunded_amount' => 0, 'reference' => 'ORD-TEST', 'name' => 'Member']]);
        $body = ['order_id' => $order->id, 'reason' => 'Damaged item', 'refund_amount' => 100];
        $this->withToken($token)->withHeader('Idempotency-Key', 'return-one')->postJson('/api/v1/store/returns', $body)->assertOk()->assertJsonPath('return.status', 'Requested');
        $this->postJson('/api/v1/store/returns', $body)->assertOk();
        $this->assertSame(1, Record::where('resource', 'returns')->count());
        $this->getJson('/api/v1/store/account')->assertOk()->assertJsonCount(1, 'returns')->assertJsonMissingPath('returns.0.notes');
        $other = $this->member('other@example.com');
        $this->withToken($other)->withHeader('Idempotency-Key', 'other-return')->postJson('/api/v1/store/returns', $body)->assertNotFound();
    }
}
