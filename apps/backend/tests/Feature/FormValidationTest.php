<?php

namespace Tests\Feature;

use App\Models\CommerceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FormValidationTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): void
    {
        $user = User::create(['name' => 'Admin', 'email' => 'validation@example.com', 'password' => Hash::make('TestPassword!2026'), 'role' => 'Admin', 'status' => 'Active']);
        $token = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'TestPassword!2026'])->assertOk()->json('token');
        $this->withToken($token);
    }

    public function test_invalid_settings_return_field_errors_without_mutating_the_record(): void
    {
        $this->signIn();
        $settings = CommerceRecord::create(['resource' => 'settings', 'data' => ['store_name' => 'Store']]);
        $this->patchJson('/api/v1/settings', ['version' => 1, 'store_name' => 'Store', 'facebook' => 'javascript:alert(1)', 'ga4_id' => 'invalid', 'meta_pixel_id' => 'abc', 'mail_transport' => 'SMTP', 'smtp_port' => 70000])->assertUnprocessable()->assertJsonValidationErrors(['facebook', 'ga4_id', 'meta_pixel_id', 'smtp_host', 'smtp_port']);
        $this->assertSame(1, $settings->fresh()->version);
    }

    public function test_catalog_forms_validate_enums_boolean_values_and_link_schemes(): void
    {
        $this->signIn();
        $this->postJson('/api/v1/banners', ['name' => 'Promo', 'placement' => 'Unknown', 'link' => 'javascript:alert(1)'])->assertUnprocessable()->assertJsonValidationErrors(['placement', 'link']);
        $this->postJson('/api/v1/attributes', ['name' => 'Size', 'code' => 'size', 'input_type' => 'Text', 'filterable' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors('filterable');
        $this->postJson('/api/v1/emails', ['name' => 'Email', 'event' => 'Unknown', 'subject' => 'Hello', 'body' => 'Body'])->assertUnprocessable()->assertJsonValidationErrors('event');
        $this->assertSame(0, CommerceRecord::count());
    }

    public function test_customer_consent_updates_persist_audit_without_server_errors(): void
    {
        $this->signIn();
        $customer = CommerceRecord::create(['resource' => 'customers', 'data' => ['name' => 'Buyer', 'email' => 'buyer@example.com', 'account_type' => 'Individual', 'status' => 'Active', 'marketing_consent' => false]]);
        $payload = array_merge($customer->row(), ['marketing_consent' => true]);
        $this->patchJson('/api/v1/customers/'.$customer->id, $payload)->assertOk();
        $this->assertTrue($customer->fresh()->data['marketing_consent']);
        $this->assertCount(1, $customer->fresh()->data['consent_history']);
    }

    public function test_product_images_are_validated_persisted_preserved_and_removable(): void
    {
        $this->signIn();
        $payload = ['name' => 'Appliance', 'sku' => 'GALLERY-001', 'slug' => 'gallery-appliance', 'type' => 'Simple', 'price' => 1000, 'stock' => 5, 'status' => 'Inactive'];
        $this->postJson('/api/v1/products', $payload + ['image' => ['one', 'two'], 'gallery_images' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors(['image', 'gallery_images']);
        $this->postJson('/api/v1/products', $payload + ['gallery_images' => ['javascript:alert(1)']])->assertUnprocessable()->assertJsonValidationErrors('gallery_images.0');
        $images = ['/api/v1/media/front.webp', '/assets/products/back.webp', 'https://example.com/detail.webp'];
        $product = $this->postJson('/api/v1/products', $payload + ['image' => 'https://example.com/main.jpg', 'gallery_images' => $images])->assertOk()->assertJsonPath('gallery_images', $images)->json();
        $this->assertSame($images, CommerceRecord::findOrFail($product['id'])->data['gallery_images']);
        unset($product['gallery_images']);
        $updated = $this->patchJson('/api/v1/products/'.$product['id'], $product)->assertOk()->assertJsonPath('gallery_images', $images)->assertJsonPath('image', 'https://example.com/main.jpg')->json();
        $this->patchJson('/api/v1/products/'.$product['id'], array_merge($updated, ['image' => '', 'gallery_images' => []]))->assertOk()->assertJsonPath('gallery_images', [])->assertJsonPath('image', null);
    }

    public function test_products_only_accept_active_and_inactive_statuses(): void
    {
        $this->signIn();
        $payload = ['name' => 'Status appliance', 'sku' => 'STATUS-001', 'slug' => 'status-appliance', 'type' => 'Simple', 'price' => 1000, 'stock' => 5];
        foreach (['Draft', 'Published'] as $status) {
            $this->postJson('/api/v1/products', $payload + ['status' => $status])->assertUnprocessable()->assertJsonValidationErrors('status');
        }
        $product = $this->postJson('/api/v1/products', $payload + ['status' => 'Inactive'])->assertOk()->assertJsonPath('status', 'Inactive')->json();
        $active = $this->patchJson('/api/v1/products/'.$product['id'], array_merge($product, ['status' => 'Active']))->assertOk()->assertJsonPath('status', 'Active')->json();
        $this->patchJson('/api/v1/products/'.$product['id'], array_merge($active, ['status' => 'Draft']))->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertSame('Active', CommerceRecord::findOrFail($product['id'])->data['status']);
    }
}
