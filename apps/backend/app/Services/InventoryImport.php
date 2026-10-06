<?php

namespace App\Services;

use App\Models\CommerceRecord as Record;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class InventoryImport
{
    public function __construct(private Commerce $commerce, private CsvImportReader $reader) {}

    public function run(UploadedFile $file, bool $commit, string $key, User $actor, array $versions = []): array
    {
        if ($commit && $key === '') {
            $this->commerce->fail('An idempotency key is required for import.');
        }
        $rows = $this->reader->read($file, ['sku', 'stock'], ['sku', 'stock'], 'inventory');
        $operation = function () use ($rows, $commit, $actor, $versions): array {
            if ($commit) {
                Record::where('resource', 'settings')->lockForUpdate()->first();
            }
            $query = Record::where('resource', 'products');
            if ($commit) {
                $query->lockForUpdate();
            }
            $products = $query->get();
            $results = [];
            $updates = [];
            $seen = [];
            foreach ($rows as $row) {
                $data = $row['data'];
                $errors = [];
                $product = null;
                if (! $data) {
                    $errors[] = 'Column count does not match the header.';
                } else {
                    $sku = mb_strtolower($data['sku']);
                    if ($sku === '' || isset($seen[$sku])) {
                        $errors[] = 'SKU is required and must occur only once in the file.';
                    }
                    $seen[$sku] = true;
                    $matches = $products->filter(fn ($record) => mb_strtolower($record->data['sku'] ?? '') === $sku);
                    if ($matches->count() !== 1) {
                        $errors[] = 'SKU must match exactly one existing product.';
                    } else {
                        $product = $matches->first();
                        if (($product->data['type'] ?? '') !== 'Simple' || ($product->data['status'] ?? '') === 'Retired') {
                            $errors[] = 'Only non-retired simple products can hold stock.';
                        }
                        if ($commit && ($versions[$product->id] ?? null) !== $product->version) {
                            $errors[] = 'Product changed since preview. Preview the file again.';
                        }
                    }
                    if (! preg_match('/^(0|[1-9][0-9]*)$/', $data['stock']) || (float) $data['stock'] > 100000000) {
                        $errors[] = 'Stock must be a whole number between 0 and 100000000.';
                    } elseif ($product && (int) $data['stock'] < ($product->data['reserved'] ?? 0)) {
                        $errors[] = 'Stock cannot be lower than reserved quantities.';
                    }
                }
                $results[] = ['row' => $row['row'], 'sku' => $data['sku'] ?? '', 'name' => $product?->data['name'] ?? '', 'product_id' => $product?->id, 'version' => $product?->version, 'current_stock' => $product?->data['stock'] ?? null, 'stock' => $data['stock'] ?? '', 'errors' => $errors];
                if (! $errors) {
                    $updates[] = ['product_id' => $product->id, 'stock' => (int) $data['stock'], 'version' => $product->version];
                }
            }
            $valid = count($updates);
            $canImport = $valid === count($rows);
            if ($commit && $canImport) {
                foreach ($updates as $update) {
                    $this->commerce->inventory($update, $actor);
                }
            }

            return ['rows' => $results, 'total' => count($rows), 'valid' => $valid, 'invalid' => count($rows) - $valid, 'can_import' => $canImport, 'imported' => $commit && $canImport ? $valid : 0];
        };

        return $commit ? $this->commerce->idempotent('inventory-import:'.$key, ['file' => hash_file('sha256', $file->getRealPath()), 'versions' => $versions], $operation) : DB::transaction($operation);
    }
}
