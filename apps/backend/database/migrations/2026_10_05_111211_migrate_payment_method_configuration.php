<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $settings = DB::table('commerce_records')->where('resource', 'settings')->first();
        $data = $settings ? json_decode($settings->data, true) : [];
        $methods = $settings ? [['name' => 'Cash on delivery', 'category' => 'Manual', 'provider' => 'COD', 'environment' => 'Production', 'instructions' => 'Pay the courier on delivery.', 'status' => 'Inactive']] : [];
        if (isset($data['mpesa_environment']) || isset($data['mpesa_shortcode'])) {
            $credentials = [];
            foreach (['consumer_key', 'consumer_secret', 'passkey'] as $key) {
                if (! empty($data['mpesa_'.$key])) {
                    $credentials[$key] = Crypt::decryptString($data['mpesa_'.$key]);
                }
            }
            $methods[] = ['name' => 'M-Pesa', 'category' => 'Online', 'provider' => 'M-Pesa', 'environment' => $data['mpesa_environment'] ?? 'Sandbox', 'public_key' => $data['mpesa_shortcode'] ?? '', 'credentials' => $credentials ? Crypt::encryptString(json_encode($credentials)) : '', 'status' => 'Inactive'];
        }
        if (! empty($data['card_gateway']) && $data['card_gateway'] !== 'Not selected') {
            $methods[] = ['name' => $data['card_gateway'], 'category' => 'Online', 'provider' => $data['card_gateway'], 'environment' => 'Sandbox', 'credentials' => ! empty($data['card_secret']) ? Crypt::encryptString(json_encode(['secret' => Crypt::decryptString($data['card_secret'])])) : '', 'status' => 'Inactive'];
        }
        foreach ($methods as $method) {
            DB::table('commerce_records')->insert(['id' => (string) Str::uuid(), 'resource' => 'payment-methods', 'data' => json_encode($method), 'version' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }
        if ($settings) {
            $data = array_filter($data, fn ($key) => ! str_starts_with($key, 'mpesa_') && ! str_starts_with($key, 'card_'), ARRAY_FILTER_USE_KEY);
            DB::table('commerce_records')->where('id', $settings->id)->update(['data' => json_encode($data), 'version' => $settings->version + 1]);
        }
        Cache::forget('commerce.records.settings');
    }

    public function down(): void
    {
        // Keep administrator configuration when rolling back unrelated application code.
    }
};
