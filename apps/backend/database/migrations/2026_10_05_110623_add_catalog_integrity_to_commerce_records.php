<?php

use App\Models\CommerceRecord;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $seen = [];
        $updates = [];
        foreach (DB::table('commerce_records')->where('resource', 'products')->get() as $row) {
            $data = json_decode($row->data, true);
            $slug = strtolower(trim($data['slug'] ?? ''));
            if (! $slug) {
                $slug = (Str::slug($data['name'] ?? 'product') ?: 'product').'-'.substr($row->id, 0, 8);
            }
            if (isset($seen[$slug])) {
                throw new RuntimeException('Duplicate existing product slug: '.$slug.'. Resolve it before migrating.');
            }
            $seen[$slug] = true;
            $data['slug'] = $slug;
            $data['direct_category_ids'] = $data['category_ids'] ?? [];
            $updates[$row->id] = $data;
        }
        foreach ($updates as $id => $data) {
            DB::table('commerce_records')->where('id', $id)->update(['data' => json_encode($data)]);
        }
        $expression = DB::getDriverName() === 'sqlite'
            ? "CASE WHEN resource = 'products' THEN lower(json_extract(data, '$.slug')) ELSE NULL END"
            : "CASE WHEN resource = 'products' THEN LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, '$.slug'))) ELSE NULL END";
        Schema::table('commerce_records', function (Blueprint $table) use ($expression) {
            $table->string('product_slug', 180)->nullable()->storedAs($expression);
            $table->unique('product_slug');
        });
        Schema::create('product_categories', function (Blueprint $table) {
            $table->foreignUuid('product_id')->constrained('commerce_records')->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('commerce_records')->restrictOnDelete();
            $table->boolean('is_direct')->default(false);
            $table->primary(['product_id', 'category_id']);
            $table->index('category_id');
        });
        foreach (CommerceRecord::where('resource', 'products')->get() as $product) {
            $product->save();
        }
        Cache::forget('commerce.records.products');
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
        Schema::table('commerce_records', function (Blueprint $table) {
            $table->dropUnique('commerce_records_product_slug_unique');
            $table->dropColumn('product_slug');
        });
    }
};
