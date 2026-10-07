<?php

namespace Tests\Feature;

use App\Models\CommerceRecord as Record;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    private function login(string $role = 'Admin'): void
    {
        User::create(['name' => 'Importer', 'email' => 'importer@example.com', 'password' => Hash::make('ImportTest!2026'), 'role' => $role, 'status' => 'Active']);
        $token = $this->postJson('/api/v1/auth/login', ['email' => 'importer@example.com', 'password' => 'ImportTest!2026'])->assertOk()->json('token');
        $this->withToken($token);
        Record::create(['resource' => 'settings', 'data' => ['store_name' => 'Test store']]);
    }

    private function csv(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('products.csv', $content);
    }

    public function test_preview_checks_rows_without_creating_products_and_import_synchronizes_categories(): void
    {
        $this->login();
        $brand = Record::create(['resource' => 'brands', 'data' => ['name' => 'Midea', 'status' => 'Active']]);
        $root = Record::create(['resource' => 'categories', 'data' => ['name' => 'Appliances', 'slug' => 'appliances', 'status' => 'Active']]);
        $child = Record::create(['resource' => 'categories', 'data' => ['name' => 'Laundry', 'slug' => 'laundry', 'parent_id' => $root->id, 'status' => 'Active']]);
        $csv = "\xEF\xBB\xBFname,sku,slug,price,stock,brand,categories,description\r\n\"Washer, silver\",CSV-1,csv-washer,500,7,Midea,laundry,\"First line\nSecond line\"\r\nKettle,CSV-2,csv-kettle,100,2,,,\r\n";
        $preview = $this->postJson('/api/v1/products/import', ['file' => $this->csv($csv), 'commit' => false])->assertOk()->assertJsonPath('valid', 2)->assertJsonPath('imported', 0);
        $this->assertEquals(0, Record::where('resource', 'products')->count());
        $this->assertEquals(0, DB::table('audit_events')->count());
        $result = $this->withHeader('Idempotency-Key', 'import-one')->postJson('/api/v1/products/import', ['file' => $this->csv($csv), 'commit' => true])->assertOk()->assertJsonPath('imported', 2);
        $product = Record::where('product_slug', 'csv-washer')->first();
        $this->assertEquals('Washer, silver', $product->data['name']);
        $this->assertEquals("First line\nSecond line", $product->data['description']);
        $this->assertEquals($brand->id, $product->data['brand_id']);
        $this->assertEquals([$child->id, $root->id], $product->data['category_ids']);
        $this->assertEquals(7, $product->data['stock']);
        $this->assertEquals('Inactive', $product->data['status']);
        $this->assertDatabaseHas('product_categories', ['product_id' => $product->id, 'category_id' => $root->id]);
        $this->postJson('/api/v1/products/import', ['file' => $this->csv($csv), 'commit' => true])->assertOk()->assertJsonPath('imported', 2);
        $this->assertEquals(2, Record::where('resource', 'products')->count());
        $this->assertEquals(3, DB::table('audit_events')->count());
        $this->postJson('/api/v1/products/import', ['file' => $this->csv(str_replace('Kettle', 'Different', $csv)), 'commit' => true])->assertConflict();
    }

    public function test_invalid_file_reports_errors_per_row_and_imports_nothing(): void
    {
        $this->login();
        $csv = "name,sku,slug,price\nValid,ONE,one,100\nDuplicate,one,one,200\nInvalid,TWO,two,-1\n";
        $this->postJson('/api/v1/products/import', ['file' => $this->csv($csv), 'commit' => false])->assertOk()->assertJsonPath('valid', 1)->assertJsonPath('invalid', 2)->assertJsonPath('can_import', false)->assertJsonPath('rows.1.row', 3);
        $this->withHeader('Idempotency-Key', 'bad-file')->postJson('/api/v1/products/import', ['file' => $this->csv($csv), 'commit' => true])->assertUnprocessable()->assertJsonPath('imported', 0);
        $this->assertEquals(0, Record::where('resource', 'products')->count());
        $this->assertEquals(0, DB::table('audit_events')->count());
    }

    public function test_existing_products_are_never_overwritten_and_commit_revalidates_after_preview(): void
    {
        $this->login();
        $csv = "name,sku,slug,price\nNew,CSV-1,csv-new,100\n";
        $this->postJson('/api/v1/products/import', ['file' => $this->csv($csv), 'commit' => false])->assertOk()->assertJsonPath('can_import', true);
        $existing = Record::create(['resource' => 'products', 'data' => ['name' => 'Existing', 'sku' => 'CSV-1', 'slug' => 'csv-existing', 'type' => 'Simple', 'price' => 999, 'stock' => 10, 'reserved' => 3, 'status' => 'Active']]);
        $this->withHeader('Idempotency-Key', 'stale-preview')->postJson('/api/v1/products/import', ['file' => $this->csv($csv), 'commit' => true])->assertUnprocessable()->assertJsonPath('invalid', 1);
        $this->assertEquals(999, $existing->fresh()->data['price']);
        $this->assertEquals(3, $existing->fresh()->data['reserved']);
        $this->assertEquals(1, Record::where('resource', 'products')->count());
    }

    public function test_unknown_brands_categories_and_duplicate_database_slugs_are_reported(): void
    {
        $this->login();
        Record::create(['resource' => 'products', 'data' => ['name' => 'Existing', 'sku' => 'EXISTING', 'slug' => 'used-slug', 'type' => 'Simple', 'price' => 100, 'stock' => 0, 'status' => 'Active']]);
        $csv = "name,sku,slug,price,brand,categories\nA,A,new-a,100,Unknown,\nB,B,new-b,100,,missing\nC,C,used-slug,100,,\n";
        $result = $this->postJson('/api/v1/products/import', ['file' => $this->csv($csv), 'commit' => false])->assertOk()->assertJsonPath('invalid', 3)->json();
        foreach ($result['rows'] as $row) {
            $this->assertNotEmpty($row['errors']);
        }
    }

    public function test_invalid_headers_malformed_rows_and_non_csv_files_are_rejected(): void
    {
        $this->login();
        foreach (["name,sku,price\nA,A,1\n", "name,sku,slug,price,unknown\nA,A,a,1,x\n", "name,sku,slug,price,sku\nA,A,a,1,A\n", "name,sku,slug,price\n"] as $csv) {
            $this->postJson('/api/v1/products/import', ['file' => $this->csv($csv), 'commit' => false])->assertUnprocessable();
        }
        $this->postJson('/api/v1/products/import', ['file' => $this->csv("name,sku,slug,price\nA,A,a\n"), 'commit' => false])->assertOk()->assertJsonPath('invalid', 1);
        $this->postJson('/api/v1/products/import', ['file' => UploadedFile::fake()->createWithContent('products.txt', "name,sku,slug,price\nA,A,a,1\n"), 'commit' => false])->assertUnprocessable();
        $this->postJson('/api/v1/products/import', ['file' => $this->csv("name,sku,slug,price\n\xFF,A,a,1\n"), 'commit' => false])->assertUnprocessable();
    }

    public function test_import_requires_write_permission_and_store_managers_can_import(): void
    {
        $csv = "name,sku,slug,price\nA,A,a,1\n";
        $this->postJson('/api/v1/products/import', ['file' => $this->csv($csv), 'commit' => false])->assertUnauthorized();
        $this->login('Sales');
        $this->postJson('/api/v1/products/import', ['file' => $this->csv($csv), 'commit' => false])->assertForbidden();
        User::first()->update(['role' => 'Store Manager']);
        $this->withHeader('Idempotency-Key', 'manager-import')->postJson('/api/v1/products/import', ['file' => $this->csv($csv), 'commit' => true])->assertOk()->assertJsonPath('imported', 1);
    }

    public function test_file_and_row_limits_and_commit_key_are_enforced(): void
    {
        $this->login();
        $this->postJson('/api/v1/products/import', ['file' => UploadedFile::fake()->create('large.csv', 2049, 'text/csv'), 'commit' => false])->assertUnprocessable();
        $csv = "name,sku,slug,price\n";
        for ($index = 0; $index < 501; $index++) {
            $csv .= "A,S-$index,a-$index,1\n";
        }
        $this->postJson('/api/v1/products/import', ['file' => $this->csv($csv), 'commit' => false])->assertUnprocessable();
        $this->postJson('/api/v1/products/import', ['file' => $this->csv("name,sku,slug,price\nA,A,a,1\n"), 'commit' => true])->assertUnprocessable();
        $this->assertEquals(0, Record::where('resource', 'products')->count());
    }
}
