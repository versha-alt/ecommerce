<?php

namespace Tests\Feature;

use App\Http\Middleware\AdminSession;
use App\Models\CommerceRecord as Record;
use App\Models\User;
use App\Services\Commerce;
use App\Services\CustomerPayments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class CommerceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // A test-only checkout adapter exercises the shared service without reopening admin creation.
        Route::post('/test/customer-checkout', function (Request $request) {
            return app(Commerce::class)->createOrder($request->all(), $request->header('Idempotency-Key', ''), $request->user());
        })->middleware(AdminSession::class);
    }

    private function admin(string $role = 'Admin'): string
    {
        User::create(['name' => 'Test Admin', 'email' => 'test@example.com', 'password' => Hash::make('StrongTest!2026'), 'role' => $role, 'status' => 'Active']);

        return $this->postJson('/api/v1/auth/login', ['email' => 'test@example.com', 'password' => 'StrongTest!2026'])->assertOk()->json('token');
    }

    private function record(string $resource, array $data): Record
    {
        if ($resource === 'products') {
            $data['slug'] ??= Str::slug($data['name']);
        }

        return Record::create(['resource' => $resource, 'data' => $data + ['status' => 'Active']]);
    }

    private function fixtures(): array
    {
        $this->record('settings', ['store_name' => 'Test store']);
        $customer = $this->record('customers', ['name' => 'Customer', 'email' => 'customer@example.com', 'phone' => '', 'status' => 'Active']);
        $zone = $this->record('delivery-zones', ['name' => 'Test zone', 'charge' => 100, 'free_threshold' => 1000]);
        $this->record('taxes', ['name' => 'Test VAT', 'rate' => 10, 'inclusive' => false]);
        $product = $this->record('products', ['name' => 'Washer', 'sku' => 'TEST-1', 'type' => 'Simple', 'price' => 500, 'sale_price' => null, 'stock' => 5, 'reserved' => 0, 'category_ids' => [], 'brand_id' => null, 'low_stock_threshold' => 2]);

        return [$customer, $zone, $product];
    }

    public function test_workspace_requires_authentication(): void
    {
        $this->getJson('/api/v1/workspace')->assertUnauthorized();
    }

    public function test_sales_role_cannot_manage_users_or_settings(): void
    {
        $token = $this->admin('Sales');
        $this->withToken($token)->postJson('/api/v1/users', [])->assertForbidden();
        $this->withToken($token)->patchJson('/api/v1/settings', [])->assertForbidden();
        $workspace = $this->withToken($token)->getJson('/api/v1/workspace')->assertOk()->json();
        $this->assertArrayNotHasKey('users', $workspace['records']);
        $this->assertArrayNotHasKey('taxes', $workspace['records']);
    }

    public function test_categories_reject_cycles_and_fourth_level(): void
    {
        $token = $this->admin();
        $a = $this->withToken($token)->postJson('/api/v1/categories', ['name' => 'A', 'slug' => 'a'])->assertOk()->json();
        $b = $this->postJson('/api/v1/categories', ['name' => 'B', 'slug' => 'b', 'parent_id' => $a['id']])->assertOk()->json();
        $c = $this->postJson('/api/v1/categories', ['name' => 'C', 'slug' => 'c', 'parent_id' => $b['id']])->assertOk()->json();
        $this->postJson('/api/v1/categories', ['name' => 'D', 'slug' => 'd', 'parent_id' => $c['id']])->assertUnprocessable();
        $this->patchJson('/api/v1/categories/'.$a['id'], ['name' => 'A', 'slug' => 'a', 'parent_id' => $c['id'], 'version' => $a['version']])->assertUnprocessable();
    }

    public function test_order_totals_and_idempotency_reserve_stock_once(): void
    {
        $token = $this->admin();
        [$customer,$zone,$product] = $this->fixtures();
        $body = ['customer_id' => $customer->id, 'delivery_zone_id' => $zone->id, 'delivery_address' => 'Test address', 'lines' => [['product_id' => $product->id, 'quantity' => 2]]];
        $order = $this->withToken($token)->withHeader('Idempotency-Key', 'one')->postJson('/test/customer-checkout', $body)->assertOk()->json();
        $this->assertEquals(1100, $order['total']);
        $this->assertEquals(0, $order['shipping_total']);
        $this->assertEquals(100, $order['tax_total']);
        $this->postJson('/test/customer-checkout', $body)->assertOk()->assertJsonPath('id', $order['id']);
        $this->assertEquals(2, $product->fresh()->data['reserved']);
        $body['lines'][0]['quantity'] = 3;
        $this->postJson('/test/customer-checkout', $body)->assertConflict();
    }

    public function test_failed_order_rolls_back_all_reservations(): void
    {
        $token = $this->admin();
        [$customer,$zone,$product] = $this->fixtures();
        $other = $this->record('products', ['name' => 'Unavailable', 'sku' => 'TEST-2', 'type' => 'Simple', 'price' => 100, 'sale_price' => null, 'stock' => 0, 'reserved' => 0, 'category_ids' => []]);
        $this->withToken($token)->withHeader('Idempotency-Key', 'rollback')->postJson('/test/customer-checkout', ['customer_id' => $customer->id, 'delivery_zone_id' => $zone->id, 'delivery_address' => 'Test', 'lines' => [['product_id' => $product->id, 'quantity' => 1], ['product_id' => $other->id, 'quantity' => 1]]])->assertUnprocessable();
        $this->assertEquals(0, $product->fresh()->data['reserved']);
        $this->assertEquals(0, Record::where('resource', 'orders')->count());
    }

    public function test_inventory_cannot_overwrite_reservations_and_stale_versions(): void
    {
        $token = $this->admin();
        [$c,$z,$p] = $this->fixtures();
        $p->data = array_merge($p->data, ['reserved' => 3]);
        $p->save();
        $this->withToken($token)->postJson('/api/v1/inventory/adjust', ['product_id' => $p->id, 'stock' => 1, 'version' => 1])->assertUnprocessable();
        $this->postJson('/api/v1/inventory/adjust', ['product_id' => $p->id, 'stock' => 7, 'version' => 1])->assertOk();
        $this->postJson('/api/v1/inventory/adjust', ['product_id' => $p->id, 'stock' => 7, 'version' => 1])->assertConflict();
        $this->assertEquals(7, $p->fresh()->data['stock']);
    }

    public function test_payment_and_delivery_lifecycle_preserves_snapshot(): void
    {
        $token = $this->admin();
        [$customer,$zone,$product] = $this->fixtures();
        $o = $this->withToken($token)->withHeader('Idempotency-Key', 'create')->postJson('/test/customer-checkout', ['customer_id' => $customer->id, 'delivery_zone_id' => $zone->id, 'delivery_address' => 'Test address', 'lines' => [['product_id' => $product->id, 'quantity' => 1]]])->assertOk()->json();
        $this->withHeader('Idempotency-Key', 'bad-dispatch')->postJson('/api/v1/order-actions/'.$o['id'], ['action' => 'dispatch', 'version' => $o['version'], 'evidence' => 'Tracking'])->assertUnprocessable();
        foreach (['confirm', 'pay', 'dispatch', 'deliver'] as $action) {
            if ($action === 'pay') {
                $method = new Record(['id' => (string) Str::uuid(), 'data' => ['name' => 'M-Pesa', 'provider' => 'M-Pesa', 'status' => 'Active']]);
                $payment = app(CustomerPayments::class)->begin(Record::find($o['id']), $method);
                app(CustomerPayments::class)->event(['event_id' => 'test-payment', 'payment_id' => $payment->id, 'method' => 'M-Pesa', 'reference' => 'TEST-PAYMENT', 'amount' => $o['total'], 'currency' => 'KES', 'status' => 'Successful']);
                $o = Record::find($o['id'])->row();

                continue;
            }
            $o = $this->withHeader('Idempotency-Key', $action)->postJson('/api/v1/order-actions/'.$o['id'], ['action' => $action, 'version' => $o['version'], 'evidence' => 'Verified-'.$action])->assertOk()->json();
        }
        $this->assertEquals('Delivered', $o['status']);
        $this->assertEquals(4, $product->fresh()->data['stock']);
        $this->assertEquals(0, $product->fresh()->data['reserved']);
        $product->data = array_merge($product->data, ['name' => 'Changed name', 'price' => 999]);
        $product->save();
        $this->assertEquals('Washer', Record::find($o['id'])->data['lines'][0]['name']);
        $this->assertEquals(500, Record::find($o['id'])->data['lines'][0]['unit_price']);
    }

    public function test_settings_secrets_are_encrypted_and_never_returned(): void
    {
        $token = $this->admin();
        $settings = $this->record('settings', ['store_name' => 'Test store']);
        $this->withToken($token)->patchJson('/api/v1/settings', ['store_name' => 'Test', 'version' => 1, 'smtp_password' => 'secret-value'])->assertOk()->assertJsonPath('smtp_password', '');
        $stored = $settings->fresh()->data['smtp_password'];
        $this->assertNotEquals('secret-value', $stored);
        $this->assertEquals('secret-value', Crypt::decryptString($stored));
        $audit = DB::table('audit_events')->first();
        $this->assertStringNotContainsString('secret-value', $audit->after);
    }

    public function test_last_administrator_cannot_be_disabled(): void
    {
        $token = $this->admin();
        $u = User::first();
        $this->withToken($token)->patchJson('/api/v1/users/'.$u->id, ['name' => $u->name, 'email' => $u->email, 'role' => 'Sales', 'status' => 'Active', 'version' => 1])->assertUnprocessable();
    }

    public function test_refund_requires_received_item_and_cannot_be_duplicated(): void
    {
        $token = $this->admin();
        $order = $this->record('orders', ['name' => 'Customer', 'reference' => 'ORD-TEST', 'status' => 'Delivered', 'payment_status' => 'Paid', 'total' => 500]);
        $return = $this->record('returns', ['name' => 'Return', 'order_id' => $order->id, 'order_reference' => 'ORD-TEST', 'reference' => 'RET-TEST', 'reason' => 'Damaged', 'refund_amount' => 500, 'status' => 'Requested']);
        $this->withToken($token)->postJson('/api/v1/returns/'.$return->id.'/refund', ['version' => 1, 'evidence' => 'REFUND-VERIFIED'])->assertUnprocessable();
        $return->data = array_merge($return->data, ['status' => 'Received']);
        $return->save();
        $result = $this->postJson('/api/v1/returns/'.$return->id.'/refund', ['version' => 1, 'evidence' => 'REFUND-VERIFIED'])->assertOk()->json();
        $this->assertEquals('Refunded', $result['refund_status']);
        $this->assertEquals('Refunded', $order->fresh()->data['payment_status']);
        $this->postJson('/api/v1/returns/'.$return->id.'/refund', ['version' => $result['version'], 'evidence' => 'REFUND-VERIFIED'])->assertUnprocessable();
    }

    public function test_uploads_are_resized_and_converted_to_webp(): void
    {
        $token = $this->admin();
        Storage::fake('local');
        $response = $this->withToken($token)->postJson('/api/v1/uploads', ['file' => UploadedFile::fake()->image('product.png', 2000, 1000)])->assertOk();
        $name = basename($response->json('url'));
        $this->assertStringEndsWith('.webp', $name);
        $path = Storage::disk('local')->path('uploads/'.$name);
        $dimensions = getimagesize($path);
        $this->assertEquals(1600, $dimensions[0]);
        $this->assertEquals(800, $dimensions[1]);
    }

    public function test_workspace_does_not_expose_raw_settings_records(): void
    {
        $token = $this->admin();
        $this->record('settings', ['store_name' => 'Test', 'smtp_password' => 'stored-encrypted-secret']);
        $data = $this->withToken($token)->getJson('/api/v1/workspace')->assertOk()->json();
        $this->assertArrayNotHasKey('settings', $data['records']);
        $this->assertEquals('', $data['settings']['smtp_password']);
    }

    public function test_record_update_cannot_bypass_retirement_controls(): void
    {
        $token = $this->admin();
        $category = $this->record('categories', ['name' => 'A', 'slug' => 'a', 'parent_id' => null]);
        $this->withToken($token)->patchJson('/api/v1/categories/'.$category->id, ['name' => 'A', 'slug' => 'a', 'version' => 1, 'status' => 'Retired'])->assertUnprocessable();
    }
}
