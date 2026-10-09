<?php

namespace Database\Seeders;

use App\Models\CommerceRecord as Record;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class RealProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = json_decode(file_get_contents(database_path('seeders/real-catalog.json')), true, 512, JSON_THROW_ON_ERROR);
        Validator::make($catalog, [
            'brands' => ['required', 'array'],
            'categories' => ['required', 'array'],
            'products' => ['required', 'array'],
            'products.*.sku' => ['required', 'string', 'distinct'],
            'products.*.slug' => ['required', 'string', 'distinct'],
            'products.*.price' => ['required', 'numeric', 'gt:0'],
            'products.*.sale_price' => ['nullable', 'numeric', 'min:0'],
            'products.*.stock' => ['required', 'integer', 'min:0'],
        ])->validate();

        DB::transaction(function () use ($catalog): void {
            Record::firstOrCreate(['resource' => 'settings'], ['data' => ['store_name' => 'Leekav']]);
            $brands = [];
            foreach ($catalog['brands'] as $data) {
                $record = Record::where('resource', 'brands')->where('data->name', $data['name'])->first() ?? new Record(['resource' => 'brands']);
                $record->data = array_merge($record->data ?? [], $data, ['status' => 'Active']);
                $record->save();
                $brands[$data['slug']] = $record->id;
            }
            $categories = [];
            foreach ($catalog['categories'] as $data) {
                $parent = $data['parent_slug'];
                if ($parent && ! isset($categories[$parent])) {
                    throw new RuntimeException('Unknown parent category: '.$parent);
                }
                unset($data['parent_slug']);
                $record = Record::where('resource', 'categories')->where('data->slug', $data['slug'])->first() ?? new Record(['resource' => 'categories']);
                $record->data = array_merge($record->data ?? [], $data, ['parent_id' => $parent ? $categories[$parent] : null, 'status' => 'Active']);
                $record->save();
                $categories[$data['slug']] = $record->id;
            }

            foreach (Record::where('resource', 'products')->get() as $record) {
                $data = $record->data;
                if (($data['demo'] ?? false) || ($data['is_test'] ?? false)
                    || preg_match('/^(?:test|demo|sample)\b/i', $data['name'] ?? '')
                    || str_contains(strtolower($data['description'] ?? ''), 'demo catalog description')
                    || in_array($data['sku'] ?? '', ['MD-WM-008', 'KA-K4-001', 'LG-TV-043', 'MD-RF-210', 'LG-MW-025', 'KA-WD3-001', 'MD-AC-012', 'LG-WM-009', 'MD-CK-004', 'KA-SC2-001', 'LG-TV-055', 'MD-KT-017'], true)) {
                    $record->delete();
                }
            }

            foreach ($catalog['products'] as $data) {
                if (! isset($brands[$data['brand_slug']])) {
                    throw new RuntimeException('Unknown product brand.');
                }
                $data['brand_id'] = $brands[$data['brand_slug']];
                $data['direct_category_ids'] = array_map(function (string $slug) use ($categories): string {
                    return $categories[$slug] ?? throw new RuntimeException('Unknown product category: '.$slug);
                }, $data['category_slugs']);
                if ($data['sale_price'] !== null && $data['sale_price'] >= $data['price']) {
                    throw new RuntimeException('Invalid discount for '.$data['sku']);
                }
                unset($data['brand_slug'], $data['category_slugs']);
                $record = Record::where('resource', 'products')->where('data->sku', $data['sku'])->first() ?? new Record(['resource' => 'products']);
                if ($record->exists) {
                    $data['stock'] = $record->data['stock'] ?? 0;
                    $data['reserved'] = $record->data['reserved'] ?? 0;
                }
                $record->data = array_merge($record->data ?? [], $data);
                $record->save();
            }
        });
        $this->command?->info('Real LG, Midea and Kärcher catalog imported. New products use the configured initial stock; sourced prices and discounts are reference prices.');
    }
}
