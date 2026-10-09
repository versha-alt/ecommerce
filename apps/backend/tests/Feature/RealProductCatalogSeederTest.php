<?php

namespace Tests\Feature;

use App\Models\CommerceRecord as Record;
use Database\Seeders\RealProductCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealProductCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_replaces_test_products_and_exposes_real_brands_categories_and_sourced_deals(): void
    {
        Record::create(['resource' => 'products', 'data' => ['name' => 'Test appliance', 'slug' => 'test-appliance', 'sku' => 'TEST-1', 'category_ids' => [], 'status' => 'Active']]);
        $this->seed(RealProductCatalogSeeder::class);
        $this->assertDatabaseMissing('commerce_records', ['product_slug' => 'test-appliance']);
        $catalog = $this->getJson('/api/v1/store/catalog')->assertOk()->json();
        $manifest = json_decode(file_get_contents(database_path('seeders/real-catalog.json')), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(count($manifest['products']), $catalog['products']);
        $this->assertSame(['Kärcher', 'LG', 'Midea'], collect($catalog['brands'])->pluck('name')->sort()->values()->all());
        $categories = collect($catalog['categories'])->keyBy('id');
        $brands = collect($catalog['brands'])->keyBy('id');
        foreach ($catalog['products'] as $product) {
            $this->assertTrue($brands->has($product['brand_id']));
            $this->assertSame(10, $product['stock']);
            $this->assertSame(0, $product['reserved']);
            $this->assertNotEmpty($product['category_ids']);
            foreach ($product['category_ids'] as $id) {
                $this->assertTrue($categories->has($id));
                if ($categories[$id]['parent_id']) {
                    $this->assertContains($categories[$id]['parent_id'], $product['category_ids']);
                }
                $this->assertDatabaseHas('product_categories', ['product_id' => $product['id'], 'category_id' => $id]);
            }
            if ($product['sale_price'] !== null) {
                $this->assertLessThan($product['price'], $product['sale_price']);
            }
            $this->assertNotEmpty($product['description']);
            $this->assertNotEmpty($product['specifications']);
            foreach ($product['gallery_images'] as $image) {
                $this->assertFileExists(base_path('../storefront/public'.$image));
            }
            $this->assertArrayNotHasKey('catalog_source', $product);
        }
        foreach ($categories->whereNull('parent_id') as $category) {
            $this->assertTrue(collect($catalog['products'])->contains(fn (array $product): bool => in_array($category['id'], $product['category_ids'], true)), $category['name']);
        }
    }

    public function test_reimport_is_idempotent_and_preserves_existing_inventory_and_other_products(): void
    {
        $unrelated = Record::create(['resource' => 'products', 'data' => ['name' => 'Existing real appliance', 'slug' => 'existing-real-appliance', 'sku' => 'EXISTING-1', 'category_ids' => [], 'status' => 'Active']]);
        $this->seed(RealProductCatalogSeeder::class);
        $product = Record::where('resource', 'products')->where('data->sku', 'F4Y2TYG6X')->firstOrFail();
        $product->data = array_merge($product->data, ['stock' => 12, 'reserved' => 2]);
        $product->save();
        $count = Record::count();
        $this->seed(RealProductCatalogSeeder::class);
        $this->assertSame($count, Record::count());
        $this->assertSame(12, $product->fresh()->data['stock']);
        $this->assertSame(2, $product->fresh()->data['reserved']);
        $this->assertNotNull($unrelated->fresh());
    }
}
