from pathlib import Path
p=Path('apps/backend/app/Services/ProductImport.php');s=p.read_text();start=s.index('    private function parse(');end=s.index('    private function prepare(',start);old=s[start:end];reader=old.replace('private function parse(UploadedFile $file): array','public function read(UploadedFile $file, array $columns, array $required, string $label): array').replace('self::COLUMNS','$columns').replace("array_diff(['name', 'sku', 'slug', 'price'], $headers)", 'array_diff($required, $headers)').replace("$this->commerce->fail('Required CSV columns: name, sku, slug and price.');","$this->commerce->fail('Required CSV columns: '.implode(', ', $required).'.');").replace("'Import up to 500 products per CSV file.'","'Import up to 500 rows per CSV file.'").replace("'The CSV contains no product rows.'","'The CSV contains no '.$label.' rows.'")
Path('apps/backend/app/Services/CsvImportReader.php').write_text('''<?php
namespace App\\Services;
use Illuminate\\Http\\UploadedFile;
class CsvImportReader
{
    public function __construct(private Commerce $commerce) {}
'''+reader+'}\n')
s=s[:start]+'''    private function parse(UploadedFile $file): array
    {
        return app(CsvImportReader::class)->read($file, self::COLUMNS, ['name', 'sku', 'slug', 'price'], 'product');
    }

'''+s[end:];p.write_text(s)
p=Path('apps/backend/app/Services/Commerce.php');s=p.read_text();s=s.replace("'quantity' => 'required|integer|not_in:0', 'reason' => 'required|string|max:500'", "'stock' => 'required|integer|min:0|max:100000000'");s=s.replace("$stock = $d['stock'] + $input['quantity'];", "$stock = (int) $input['stock'];");s=s.replace("$this->audit($actor, 'Stock adjustment: '.$input['reason'], 'inventory', $before, $p->row());", "$this->audit($actor, 'Stock quantity set', 'inventory', $before, $p->row());");s=s.replace("if ($d['type'] !== 'Simple') {", "if (($d['status'] ?? '') === 'Retired') {\n                $this->fail('Retired products cannot have stock updated.');\n            }if ($d['type'] !== 'Simple') {",1);p.write_text(s)
p=Path('apps/backend/app/Http/Controllers/AdminController.php');s=p.read_text().replace('use App\\Services\\ProductImport;', 'use App\\Services\\ProductImport;\nuse App\\Services\\InventoryImport;');idx=s.index('    public function upload(');s=s[:idx]+'''    public function importInventory(Request $request): JsonResponse
    {
        $this->commerce->authorize($request->user(), 'inventory', true);
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048', 'commit' => 'required|boolean', 'versions' => 'nullable|json']);
        abort_unless(strtolower($request->file('file')->getClientOriginalExtension()) === 'csv', 422, 'Select a .csv file.');
        $versions = json_decode($request->input('versions', '{}'), true);
        abort_unless(is_array($versions), 422, 'Preview the file before importing.');
        $report = app(InventoryImport::class)->run($request->file('file'), $request->boolean('commit'), $request->header('Idempotency-Key', ''), $request->user(), $versions);
        return response()->json($report, $request->boolean('commit') && !$report['can_import'] ? 422 : 200);
    }

'''+s[idx:];p.write_text(s)
p=Path('apps/backend/routes/web.php');s=p.read_text().replace("Route::post('inventory/adjust',", "Route::post('inventory/import', [AdminController::class, 'importInventory']);\n        Route::post('inventory/adjust',");p.write_text(s)
p=Path('apps/backend/tests/Feature/CommerceTest.php');s=p.read_text().replace("'quantity' => -4, 'reason' => 'Test'", "'stock' => 1").replace("'quantity' => 2, 'reason' => 'Receipt'", "'stock' => 7");p.write_text(s)
