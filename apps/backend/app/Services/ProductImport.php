<?php

namespace App\Services;

use App\Models\CommerceRecord as Record;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductImport
{
    public const COLUMNS = ['name', 'sku', 'slug', 'type', 'price', 'sale_price', 'stock', 'low_stock_threshold', 'status', 'brand', 'categories', 'brand_id', 'category_ids', 'description', 'specifications', 'warranty', 'image', 'manual', 'seo_title', 'seo_description'];

    public const TEMPLATE_COLUMNS = ['name', 'sku', 'slug', 'type', 'price', 'sale_price', 'stock', 'low_stock_threshold', 'status', 'brand', 'categories', 'description', 'specifications', 'warranty', 'image', 'manual', 'seo_title', 'seo_description'];

    public function __construct(private Commerce $commerce) {}

    private function parse(UploadedFile $file): array
    {
        $contents = file_get_contents($file->getRealPath());
        if (! mb_check_encoding($contents, 'UTF-8') || str_contains($contents, "\0")) {
            $this->commerce->fail('Use a UTF-8 CSV file. Export as CSV UTF-8 from Excel.');
        }
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, preg_replace('/^\xEF\xBB\xBF/', '', $contents));
        rewind($stream);
        try {
            $headers = fgetcsv($stream, 0, ',', '"', '');
            if (! $headers) {
                $this->commerce->fail('The CSV file is empty.');
            }
            $headers = array_map(fn ($header) => strtolower(trim($header)), $headers);
            if (count(array_unique($headers)) !== count($headers) || array_diff($headers, self::COLUMNS)) {
                $this->commerce->fail('CSV headers must be unique supported column names. Download the template for the correct format.');
            }
            if (array_diff(['name', 'sku', 'slug', 'price'], $headers)) {
                $this->commerce->fail('Required CSV columns: name, sku, slug and price.');
            }
            if ((in_array('brand', $headers) && in_array('brand_id', $headers)) || (in_array('categories', $headers) && in_array('category_ids', $headers))) {
                $this->commerce->fail('Use either brand/categories or their ID columns, not both.');
            }
            $rows = [];
            $number = 1;
            while (($values = fgetcsv($stream, 0, ',', '"', '')) !== false) {
                $number++;
                if (count(array_filter($values, fn ($value) => trim($value ?? '') !== '')) === 0) {
                    continue;
                }
                if (count($rows) >= 500) {
                    $this->commerce->fail('Import up to 500 products per CSV file.');
                }
                $rows[] = ['row' => $number, 'data' => count($values) === count($headers) ? array_combine($headers, array_map(fn ($value) => trim($value ?? ''), $values)) : null];
            }
            if (! $rows) {
                $this->commerce->fail('The CSV contains no product rows.');
            }

            return $rows;
        } finally {
            fclose($stream);
        }
    }

    private function prepare(array $input): array
    {
        foreach (['type' => 'Simple', 'stock' => 0, 'low_stock_threshold' => 5, 'status' => 'Draft'] as $key => $default) {
            if (! isset($input[$key]) || $input[$key] === '') {
                $input[$key] = $default;
            }
        }
        if (! in_array($input['status'], ['Draft', 'Active', 'Inactive'], true)) {
            $this->commerce->fail('Imported product status must be Draft, Active or Inactive.');
        }
        foreach (['sale_price', 'brand_id'] as $key) {
            if (($input[$key] ?? '') === '') {
                $input[$key] = null;
            }
        }
        if (! empty($input['brand'])) {
            $brands = Record::where('resource', 'brands')->get()->filter(fn ($brand) => ($brand->data['status'] ?? '') === 'Active' && mb_strtolower($brand->data['name']) === mb_strtolower($input['brand']));
            if ($brands->count() !== 1) {
                $this->commerce->fail('Brand must match exactly one active brand name.');
            }
            $input['brand_id'] = $brands->first()->id;
        }
        $input['category_ids'] = empty($input['category_ids']) ? [] : array_values(array_unique(array_filter(explode('|', $input['category_ids']))));
        if (! empty($input['categories'])) {
            $categories = Record::where('resource', 'categories')->get();
            foreach (array_unique(explode('|', $input['categories'])) as $slug) {
                $matches = $categories->filter(fn ($category) => ($category->data['status'] ?? '') === 'Active' && ($category->data['slug'] ?? '') === trim($slug));
                if ($matches->count() !== 1) {
                    $this->commerce->fail('Category slug "'.trim($slug).'" must match exactly one active category.');
                }
                $input['category_ids'][] = $matches->first()->id;
            }
        }
        unset($input['brand'], $input['categories']);

        return $input;
    }

    private function inspect(array $rows): array
    {
        $results = [];
        $products = [];
        $slugs = [];
        $skus = [];
        foreach ($rows as $row) {
            $input = $row['data'];
            $errors = [];
            if ($input === null) {
                $errors[] = 'Column count does not match the header.';
            } else {
                foreach (['slug' => &$slugs, 'sku' => &$skus] as $field => &$seen) {
                    $key = mb_strtolower($input[$field]);
                    if (isset($seen[$key])) {
                        $errors[] = ucfirst($field).' duplicates CSV row '.$seen[$key].'.';
                    }
                    $seen[$key] ??= $row['row'];
                }
                unset($seen);
                try {
                    $products[$row['row']] = $this->commerce->validate('products', $this->prepare($input));
                } catch (ValidationException $exception) {
                    $errors = array_merge($errors, array_merge(...array_values($exception->errors())));
                } catch (ModelNotFoundException) {
                    $errors[] = 'The selected brand or category does not exist.';
                }
            }
            $results[] = ['row' => $row['row'], 'name' => $input['name'] ?? '', 'sku' => $input['sku'] ?? '', 'slug' => $input['slug'] ?? '', 'price' => $input['price'] ?? '', 'status' => $errors ? 'Invalid' : 'Ready', 'errors' => array_values(array_unique($errors))];
        }
        $invalid = count(array_filter($results, fn ($row) => $row['errors']));

        return ['total' => count($rows), 'valid' => count($rows) - $invalid, 'invalid' => $invalid, 'can_import' => $invalid === 0, 'rows' => $results, 'products' => $products];
    }

    public function run(UploadedFile $file, bool $commit, string $key, User $actor): array
    {
        $rows = $this->parse($file);
        $operation = function () use ($rows, $commit, $actor) {
            $report = $this->inspect($rows);
            $products = $report['products'];
            unset($report['products']);
            if (! $commit || ! $report['can_import']) {
                return $report + ['imported' => 0];
            }
            foreach ($products as $input) {
                $this->commerce->save('products', $input, $actor);
            }
            $this->commerce->audit($actor, 'CSV import: '.count($products).' products', 'products', null, ['name' => 'Product CSV import', 'count' => count($products)]);

            return $report + ['imported' => count($products)];
        };
        if (! $commit) {
            return DB::transaction($operation);
        }

        return $this->commerce->idempotent('product-import:'.$actor->id.':'.$key, ['file_hash' => hash_file('sha256', $file->getRealPath())], $operation);
    }
}
