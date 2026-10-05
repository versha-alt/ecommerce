<?php

namespace App\Services;

use App\Models\CommerceRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductCatalog
{
    public function expand(array $selected): array
    {
        $categories = CommerceRecord::where('resource', 'categories')->get()->keyBy('id');
        $associated = [];
        foreach (array_unique($selected) as $id) {
            $seen = [];
            while ($id) {
                if (isset($seen[$id]) || ! isset($categories[$id])) {
                    throw ValidationException::withMessages(['category_ids' => 'Select existing categories with a valid hierarchy.']);
                }
                $seen[$id] = true;
                $associated[$id] = $id;
                $id = $categories[$id]->data['parent_id'] ?? null;
            }
        }

        return array_values($associated);
    }

    public function synchronize(CommerceRecord $product): void
    {
        DB::table('product_categories')->where('product_id', $product->id)->delete();
        foreach ($product->data['category_ids'] ?? [] as $id) {
            DB::table('product_categories')->insert(['product_id' => $product->id, 'category_id' => $id, 'is_direct' => in_array($id, $product->data['direct_category_ids'] ?? [], true)]);
        }
    }

    public function rebuild(): void
    {
        foreach (CommerceRecord::where('resource', 'products')->lockForUpdate()->get() as $product) {
            $data = $product->data;
            $expanded = $this->expand($data['direct_category_ids'] ?? $data['category_ids'] ?? []);
            if ($expanded !== ($data['category_ids'] ?? [])) {
                $product->data = array_merge($data, ['category_ids' => $expanded]);
                $product->version++;
                $product->save();
            }
        }
    }
}
