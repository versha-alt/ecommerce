<?php

namespace Tests\Feature;

use App\Models\CommerceRecord as Record;
use App\Models\User;
use App\Services\Commerce;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    private function login(string $role = 'Admin'): User
    {
        $user = User::create(['name' => 'Test staff', 'email' => Str::uuid().'@example.com', 'password' => Hash::make('TestPassword!2026'), 'role' => $role, 'status' => 'Active']);
        $token = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'TestPassword!2026'])->assertOk()->json('token');
        $this->withToken($token);

        return $user;
    }

    private function record(string $resource, array $data): Record
    {
        return Record::create(['resource' => $resource, 'data' => $data + ['status' => 'Active']]);
    }

    private function product(array $overrides = []): array
    {
        return $overrides + ['name' => 'Test product', 'slug' => 'test-'.Str::lower(Str::random(8)), 'sku' => Str::random(10), 'type' => 'Simple', 'price' => 500, 'stock' => 20, 'reserved' => 0, 'category_ids' => [], 'status' => 'Active'];
    }

    private function checkout(User $actor, Record $product, string $code, int $quantity = 1, ?Record $customer = null): array
    {
        if (! Record::where('resource', 'settings')->exists()) {
            $this->record('settings', ['store_name' => 'Test']);
        }
        if (! Record::where('resource', 'taxes')->exists()) {
            $this->record('taxes', ['name' => 'VAT', 'rate' => 10, 'inclusive' => false]);
        }
        $customer ??= $this->record('customers', ['name' => 'Buyer', 'email' => 'buyer@example.com', 'account_type' => 'Individual']);
        $zone = $this->record('delivery-zones', ['name' => 'Zone', 'charge' => 100]);

        return app(Commerce::class)->createOrder(['customer_id' => $customer->id, 'delivery_zone_id' => $zone->id, 'delivery_address' => 'Test address', 'lines' => [['product_id' => $product->id, 'quantity' => $quantity]], 'coupon_code' => $code], (string) Str::uuid(), $actor);
    }

    private function discount(array $overrides = []): array
    {
        return $overrides + ['name' => 'Test discount', 'code' => 'TEST', 'discount_kind' => 'Order', 'discount_type' => 'Percentage', 'value' => 10, 'scope' => 'All', 'eligibility' => 'All', 'minimum_type' => 'None', 'status' => 'Active'];
    }

    public function test_admin_cannot_create_customer_order_or_return_but_can_update_customer(): void
    {
        $this->login();
        foreach (['orders', 'customers', 'returns'] as $resource) {
            $this->postJson('/api/v1/'.$resource, [])->assertStatus(405);
        }
        $customer = $this->record('customers', ['name' => 'Buyer', 'email' => 'buyer@example.com', 'account_type' => 'Individual']);
        $this->patchJson('/api/v1/customers/'.$customer->id, $customer->row() + [])->assertOk();
        $this->assertEquals(2, $customer->fresh()->version);
    }

    public function test_product_slug_is_required_and_unique_on_create_and_update(): void
    {
        $this->login();
        $first = $this->postJson('/api/v1/products', $this->product(['slug' => 'unique-slug']))->assertOk()->json();
        $second = $this->postJson('/api/v1/products', $this->product())->assertOk()->json();
        $this->postJson('/api/v1/products', $this->product(['slug' => 'unique-slug']))->assertUnprocessable()->assertJsonValidationErrors('slug');
        $this->patchJson('/api/v1/products/'.$second['id'], array_merge($second, ['slug' => 'unique-slug']))->assertUnprocessable();
        $this->patchJson('/api/v1/products/'.$first['id'], $first)->assertOk();
        $this->postJson('/api/v1/products', $this->product(['slug' => '']))->assertUnprocessable();
    }

    public function test_database_rejects_duplicate_slug_even_when_application_validation_is_bypassed(): void
    {
        $this->record('products', $this->product(['slug' => 'database-unique']));
        $this->expectException(QueryException::class);
        DB::table('commerce_records')->insert(['id' => (string) Str::uuid(), 'resource' => 'products', 'data' => json_encode($this->product(['slug' => 'DATABASE-UNIQUE'])), 'version' => 1]);
    }

    public function test_ancestor_membership_is_persisted_and_rebuilt_when_category_moves(): void
    {
        $this->login();
        $root = $this->record('categories', ['name' => 'Electronics', 'slug' => 'electronics']);
        $middle = $this->record('categories', ['name' => 'Mobiles', 'slug' => 'mobiles', 'parent_id' => $root->id]);
        $child = $this->record('categories', ['name' => 'Smartphones', 'slug' => 'smartphones', 'parent_id' => $middle->id]);
        $other = $this->record('categories', ['name' => 'Other', 'slug' => 'other']);
        $product = $this->postJson('/api/v1/products', $this->product(['category_ids' => [$child->id]]))->assertOk()->json();
        $this->assertEquals([$child->id, $middle->id, $root->id], $product['category_ids']);
        $this->assertEquals([$child->id], $product['direct_category_ids']);
        $this->assertDatabaseHas('product_categories', ['product_id' => $product['id'], 'category_id' => $root->id, 'is_direct' => false]);
        $this->patchJson('/api/v1/categories/'.$middle->id, array_merge($middle->row(), ['parent_id' => $other->id]))->assertOk();
        $stored = Record::find($product['id']);
        $this->assertEquals([$child->id, $middle->id, $other->id], $stored->data['category_ids']);
        $this->assertDatabaseMissing('product_categories', ['product_id' => $stored->id, 'category_id' => $root->id]);
        $this->assertEquals(2, $stored->version);
    }

    public function test_order_status_history_and_workspace_share_persisted_state(): void
    {
        $actor = $this->login();
        $product = $this->record('products', $this->product());
        $order = $this->checkout($actor, $product, '');
        $updated = $this->withHeader('Idempotency-Key', 'confirm-history')->postJson('/api/v1/order-actions/'.$order['id'], ['action' => 'confirm', 'version' => 1, 'evidence' => 'Reviewed'])->assertOk()->json();
        $this->assertEquals('Confirmed', Record::find($order['id'])->data['status']);
        $this->assertEquals('Pending', $updated['status_history'][1]['from']);
        $this->assertEquals($actor->email, $updated['status_history'][1]['actor']);
        $this->getJson('/api/v1/workspace')->assertOk()->assertJsonPath('records.orders.0.status', 'Confirmed');
        $this->withHeader('Idempotency-Key', 'stale-history')->postJson('/api/v1/order-actions/'.$order['id'], ['action' => 'cancel', 'version' => 1, 'evidence' => 'Stale'])->assertConflict();
    }

    public function test_returns_have_immutable_customer_details_and_guarded_decisions(): void
    {
        $this->login();
        $order = $this->record('orders', ['name' => 'Buyer', 'reference' => 'ORD-RETURN', 'status' => 'Delivered', 'payment_status' => 'Paid', 'total' => 500]);
        $request = $this->record('returns', ['name' => 'Return', 'order_id' => $order->id, 'reason' => 'Damaged', 'refund_amount' => 100, 'status' => 'Requested']);
        $this->patchJson('/api/v1/returns/'.$request->id, array_merge($request->row(), ['refund_amount' => 400]))->assertUnprocessable();
        $this->patchJson('/api/v1/returns/'.$request->id, ['version' => 1, 'status' => 'Received'])->assertUnprocessable();
        $this->patchJson('/api/v1/returns/'.$request->id, ['version' => 1, 'status' => 'Rejected'])->assertUnprocessable();
        $approved = $this->patchJson('/api/v1/returns/'.$request->id, ['version' => 1, 'status' => 'Approved', 'notes' => 'Reviewed photos'])->assertOk()->json();
        $this->assertEquals('Approved', $approved['status_history'][0]['to']);
        $this->login('Sales');
        $this->patchJson('/api/v1/returns/'.$request->id, ['version' => 2, 'status' => 'Rejected', 'notes' => 'Decision'])->assertForbidden();
        $this->patchJson('/api/v1/returns/'.$request->id, ['version' => 2, 'status' => 'Approved', 'notes' => 'Staff inspection note'])->assertOk();
        $this->assertEquals(100, $request->fresh()->data['refund_amount']);
    }

    public function test_four_discount_types_calculate_correct_order_totals(): void
    {
        $actor = $this->login();
        $product = $this->record('products', $this->product());
        foreach (['Order' => 100, 'Product' => 100, 'BuyXGetY' => 500, 'Shipping' => 0] as $kind => $expected) {
            $code = strtoupper($kind);
            $discount = $this->discount(['code' => $code, 'discount_kind' => $kind]);
            if ($kind === 'BuyXGetY') {
                $discount += ['buy_quantity' => 1, 'get_quantity' => 1, 'get_scope' => 'Products', 'get_product_ids' => [$product->id]];
                $discount['discount_type'] = 'Free';
                $discount['value'] = 0;
            }
            $this->postJson('/api/v1/coupons', $discount)->assertOk();
            $order = $this->checkout($actor, $product, $code, 2);
            $this->assertEquals($expected, $order['discount']);
            $this->assertEquals($kind === 'Shipping' ? 0 : 100, $order['shipping_total']);
            $this->assertEquals($kind === 'Shipping' ? 100 : 0, $order['shipping_discount']);
        }
    }

    public function test_discount_category_restrictions_include_inherited_categories_and_exclude_other_products(): void
    {
        $actor = $this->login();
        $root = $this->record('categories', ['name' => 'Root', 'slug' => 'root']);
        $child = $this->record('categories', ['name' => 'Child', 'slug' => 'child', 'parent_id' => $root->id]);
        $product = $this->record('products', $this->product(['category_ids' => [$child->id]]));
        $other = $this->record('products', $this->product());
        $this->postJson('/api/v1/coupons', $this->discount(['discount_kind' => 'Product', 'scope' => 'Categories', 'category_ids' => [$root->id], 'minimum_type' => 'Amount', 'minimum_amount' => 500]))->assertOk();
        $order = $this->checkout($actor, $product, 'TEST');
        $this->assertEquals(50, $order['discount']);
        $this->expectException(ValidationException::class);
        $this->checkout($actor, $other, 'TEST');
    }

    public function test_buy_x_get_y_never_uses_the_same_unit_as_both_qualifying_and_reward_item(): void
    {
        $actor = $this->login();
        $product = $this->record('products', $this->product());
        $this->postJson('/api/v1/coupons', $this->discount(['discount_kind' => 'BuyXGetY', 'discount_type' => 'Free', 'value' => 0, 'buy_quantity' => 1, 'get_quantity' => 1, 'get_scope' => 'All']))->assertOk();
        $this->expectException(ValidationException::class);
        $this->checkout($actor, $product, 'TEST', 1);
    }

    public function test_discount_usage_limits_and_customer_eligibility_are_enforced_and_cancellation_releases_usage(): void
    {
        $actor = $this->login();
        $customer = $this->record('customers', ['name' => 'Eligible', 'email' => 'eligible@example.com']);
        $product = $this->record('products', $this->product());
        $this->postJson('/api/v1/coupons', $this->discount(['eligibility' => 'Customers', 'customer_ids' => [$customer->id], 'once_per_customer' => true, 'usage_limit' => 1]))->assertOk();
        $order = $this->checkout($actor, $product, 'TEST', 1, $customer);
        try {
            $this->checkout($actor, $product, 'TEST', 1, $customer);
            $this->fail('Usage limit was bypassed.');
        } catch (ValidationException) {
        }
        app(Commerce::class)->orderAction($order['id'], ['action' => 'cancel', 'version' => 1, 'evidence' => 'Cancelled'], 'cancel-coupon', $actor);
        $this->assertEquals(0, Record::where('resource', 'coupons')->first()->data['usage']);
        $this->checkout($actor, $product, 'TEST', 1, $customer);
        $this->assertEquals(1, $product->fresh()->data['reserved']);
    }

    public function test_expired_inactive_and_minimum_quantity_discounts_are_rejected(): void
    {
        $actor = $this->login();
        $product = $this->record('products', $this->product());
        foreach ([['ends_at' => now()->subDay()->toDateString()], ['status' => 'Inactive'], ['minimum_type' => 'Quantity', 'minimum_quantity' => 3]] as $index => $rule) {
            $code = 'RULE'.$index;
            $this->postJson('/api/v1/coupons', $this->discount($rule + ['code' => $code]))->assertOk();
            try {
                $this->checkout($actor, $product, $code);
                $this->fail('Ineligible discount was accepted.');
            } catch (ValidationException) {
            }
        }
        $this->assertEquals(0, $product->fresh()->data['reserved']);
    }

    public function test_online_credentials_are_encrypted_redacted_and_preserved_when_blank(): void
    {
        $this->login();
        $method = $this->postJson('/api/v1/payment-methods', ['name' => 'PayPal', 'category' => 'Online', 'provider' => 'PayPal', 'environment' => 'Sandbox', 'public_key' => 'public-client', 'credentials' => '{"client_secret":"secret-value"}', 'status' => 'Active'])->assertOk()->assertJsonPath('credentials', '')->json();
        $stored = Record::find($method['id']);
        $this->assertEquals(['client_secret' => 'secret-value'], json_decode(Crypt::decryptString($stored->data['credentials']), true));
        $this->patchJson('/api/v1/payment-methods/'.$method['id'], array_merge($method, ['status' => 'Inactive', 'credentials' => '']))->assertOk();
        $this->assertEquals($stored->data['credentials'], $stored->fresh()->data['credentials']);
        $this->assertEquals('Inactive', $stored->fresh()->data['status']);
        $active = $this->patchJson('/api/v1/payment-methods/'.$method['id'], array_merge($method, ['version' => 2, 'status' => 'Active', 'credentials' => '']))->assertOk()->assertJsonPath('status', 'Active')->json();
        $this->assertEquals('Active', $stored->fresh()->data['status']);
        $this->patchJson('/api/v1/payment-methods/'.$method['id'], array_merge($active, ['status' => 'Inactive']))->assertOk()->assertJsonPath('status', 'Inactive');
        $this->assertEquals($stored->data['credentials'], $stored->fresh()->data['credentials']);
        $this->assertStringNotContainsString('secret-value', $this->getJson('/api/v1/workspace')->assertOk()->getContent());
        $this->assertStringNotContainsString('secret-value', DB::table('audit_events')->get()->toJson());
        $this->patchJson('/api/v1/payment-methods/'.$method['id'], array_merge($method, ['version' => 4, 'configuration' => '{"nested":{"api_secret":"unsafe"}}']))->assertUnprocessable();
        $this->postJson('/api/v1/payment-methods', ['name' => 'Duplicate', 'category' => 'Online', 'provider' => 'PayPal', 'environment' => 'Sandbox', 'status' => 'Inactive'])->assertUnprocessable();
    }

    public function test_manual_methods_can_be_configured_without_gateway_credentials_and_sales_cannot_manage_them(): void
    {
        $this->login();
        $method = $this->postJson('/api/v1/payment-methods', ['name' => 'Cash on delivery', 'category' => 'Manual', 'provider' => 'COD', 'environment' => 'Production', 'instructions' => 'Pay the courier', 'status' => 'Active'])->assertOk()->json();
        $this->assertEquals('', $method['credentials']);
        Cache::put('commerce.records.payment-methods', [array_merge($method, ['status' => 'Inactive'])], 30);
        $this->getJson('/api/v1/workspace')->assertOk()->assertJsonPath('records.payment-methods.0.status', 'Active')->assertHeader('Cache-Control', 'no-store, private');
        $online = $this->postJson('/api/v1/payment-methods', ['name' => 'PayPal', 'category' => 'Online', 'provider' => 'PayPal', 'environment' => 'Sandbox', 'status' => 'Inactive'])->assertOk()->json();
        $manual = $this->patchJson('/api/v1/payment-methods/'.$online['id'], array_merge($online, ['category' => 'Manual', 'provider' => 'Bank transfer', 'environment' => 'Production', 'status' => 'Active']))->assertOk()->assertJsonPath('category', 'Manual')->assertJsonPath('status', 'Active')->json();
        $this->assertEquals('Active', Record::find($online['id'])->data['status']);
        $this->patchJson('/api/v1/payment-methods/'.$online['id'], array_merge($manual, ['status' => 'Inactive']))->assertOk()->assertJsonPath('status', 'Inactive');
        $this->postJson('/api/v1/payment-methods', ['name' => 'Wrong category', 'category' => 'Manual', 'provider' => 'Razorpay', 'environment' => 'Sandbox'])->assertUnprocessable();
        $this->login('Sales');
        $this->patchJson('/api/v1/payment-methods/'.$method['id'], array_merge($method, ['status' => 'Inactive']))->assertForbidden();
        $this->assertArrayNotHasKey('payment-methods', $this->getJson('/api/v1/workspace')->assertOk()->json('records'));
    }

    public function test_fresh_migrations_do_not_prevent_demo_workspace_seeding(): void
    {
        putenv('ADMIN_PASSWORD=TestPassword!2026');
        config(['commerce.demo' => true]);
        $this->seed(DatabaseSeeder::class);
        $this->assertEquals(12, Record::where('resource', 'products')->count());
        $this->assertEquals(25, Record::where('resource', 'orders')->count());
        $this->assertEquals(2, Record::where('resource', 'payment-methods')->count());
        $this->assertNotEmpty(Record::where('resource', 'orders')->first()->data['status_history']);
    }

    public function test_legacy_gateway_settings_migrate_without_losing_encrypted_credentials(): void
    {
        $settings = $this->record('settings', ['store_name' => 'Legacy', 'mpesa_environment' => 'Sandbox', 'mpesa_shortcode' => '123456', 'mpesa_consumer_key' => Crypt::encryptString('key-value'), 'mpesa_consumer_secret' => Crypt::encryptString('secret-value'), 'mpesa_passkey' => Crypt::encryptString('passkey-value')]);
        $migration = require database_path('migrations/2026_10_05_111211_migrate_payment_method_configuration.php');
        $migration->up();
        $method = Record::where('resource', 'payment-methods')->get()->first(fn ($record) => $record->data['provider'] === 'M-Pesa');
        $this->assertEquals(['consumer_key' => 'key-value', 'consumer_secret' => 'secret-value', 'passkey' => 'passkey-value'], json_decode(Crypt::decryptString($method->data['credentials']), true));
        $this->assertEquals('123456', $method->data['public_key']);
        $this->assertEquals('Inactive', $method->data['status']);
        $this->assertEquals('', $method->row()['credentials']);
        $this->assertArrayNotHasKey('mpesa_consumer_secret', $settings->fresh()->data);
    }
}
