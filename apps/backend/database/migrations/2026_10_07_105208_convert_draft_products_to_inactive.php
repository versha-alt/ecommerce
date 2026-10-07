<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('commerce_records')->where('resource', 'products')->orderBy('id')->chunk(100, function ($products): void {
            foreach ($products as $product) {
                $data = json_decode($product->data, true);
                if (($data['status'] ?? '') === 'Draft') {
                    $data['status'] = 'Inactive';
                    DB::table('commerce_records')->where('id', $product->id)->update(['data' => json_encode($data), 'version' => $product->version + 1, 'updated_at' => now()]);
                }
            }
        });
        Cache::forget('commerce.records.products');
    }

    public function down(): void
    {
        // Existing inactive products cannot be distinguished from former drafts.
    }
};
