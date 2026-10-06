<?php

namespace Tests\Feature;

use App\Models\CommerceRecord as Record;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InventoryImportTest extends TestCase
{
    use RefreshDatabase;

    private function stock(string $role = 'Admin'): Record
    {
        User::create(['name' => 'Stock admin', 'email' => 'stock@example.com', 'password' => Hash::make('TestPass!2026'), 'role' => $role, 'status' => 'Active']);
        $token = $this->postJson('/api/v1/auth/login', ['email' => 'stock@example.com', 'password' => 'TestPass!2026'])->assertOk()->json('token');
        $this->withToken($token);
        Record::create(['resource' => 'settings', 'data' => ['store_name' => 'Test']]);

        return Record::create(['resource' => 'products', 'data' => ['name' => 'Kettle', 'sku' => 'STOCK-1', 'slug' => 'stock-kettle', 'type' => 'Simple', 'status' => 'Active', 'stock' => 5, 'reserved' => 2, 'price' => 100]]);
    }

    private function upload(string $csv, bool $commit = false, array $versions = [])
    {
        return $this->postJson('/api/v1/inventory/import', ['file' => UploadedFile::fake()->createWithContent('inventory.csv', $csv), 'commit' => $commit, 'versions' => json_encode($versions)]);
    }

    public function test_preview_and_idempotent_import_replace_stock(): void
    {
        $p = $this->stock();
        $csv = "sku,stock\nSTOCK-1,8\n";
        $this->upload($csv)->assertOk()->assertJsonPath('rows.0.current_stock', 5)->assertJsonPath('imported', 0);
        $this->assertEquals(5, $p->fresh()->data['stock']);
        $this->withHeader('Idempotency-Key', 'stock-import');
        $versions = [$p->id => $p->version];
        $this->upload($csv, true, $versions)->assertOk()->assertJsonPath('imported', 1);
        $this->upload($csv, true, $versions)->assertOk()->assertJsonPath('imported', 1);
        $this->assertEquals(8, $p->fresh()->data['stock']);
        $this->assertEquals(100, $p->fresh()->data['price']);
        $this->assertEquals(1, DB::table('audit_events')->count());
    }

    public function test_invalid_import_is_atomic(): void
    {
        $p = $this->stock();
        $this->withHeader('Idempotency-Key', 'invalid');
        $this->upload("sku,stock\nSTOCK-1,8\nUNKNOWN,2\nSTOCK-1,1\n", true, [$p->id => $p->version])->assertUnprocessable()->assertJsonPath('invalid', 2);
        $this->assertEquals(5, $p->fresh()->data['stock']);
        $this->assertEquals(0, DB::table('audit_events')->count());
        $this->upload("sku,stock\nSTOCK-1,1\n")->assertOk()->assertJsonPath('can_import', false);
        $this->upload("sku,stock\nSTOCK-1,-1\n")->assertOk()->assertJsonPath('can_import', false);
    }

    public function test_stale_preview_and_missing_key_are_rejected(): void
    {
        $p = $this->stock();
        $version = $p->version;
        $csv = "sku,stock\nSTOCK-1,9\n";
        $this->upload($csv, true, [$p->id => $version])->assertUnprocessable();
        $this->postJson('/api/v1/inventory/adjust', ['product_id' => $p->id, 'stock' => 7, 'version' => $version])->assertOk();
        $this->withHeader('Idempotency-Key', 'stale');
        $this->upload($csv, true, [$p->id => $version])->assertUnprocessable()->assertJsonPath('can_import', false);
        $this->assertEquals(7, $p->fresh()->data['stock']);
    }

    public function test_sales_cannot_import(): void
    {
        $this->stock('Sales');
        $this->upload("sku,stock\nSTOCK-1,8\n")->assertForbidden();
    }
}
