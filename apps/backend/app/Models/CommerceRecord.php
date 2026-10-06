<?php

namespace App\Models;

use App\Services\ProductCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CommerceRecord extends Model
{
    protected $attributes = ['version' => 1];

    protected $table = 'commerce_records';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['product_slug'];

    protected function casts(): array
    {
        return ['data' => 'array', 'version' => 'integer'];
    }

    public function save(array $options = []): bool
    {
        return DB::transaction(function () use ($options) {
            if (in_array($this->resource, ['products', 'categories'], true)) {
                self::where('resource', 'settings')->lockForUpdate()->first();
            }

            return parent::save($options);
        });
    }

    protected static function booted(): void
    {
        static::creating(function ($record) {
            $record->id ??= (string) Str::uuid();
        });
        static::saving(function ($record) {
            if ($record->resource === 'products') {
                $data = $record->data;
                Validator::make($data, ['slug' => ['required', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('commerce_records', 'product_slug')->ignore($record->id)]])->validate();
                $data['direct_category_ids'] ??= $data['category_ids'] ?? [];
                $data['category_ids'] = app(ProductCatalog::class)->expand($data['direct_category_ids']);
                $record->data = $data;
            }
        });
        static::saved(function ($record) {
            Cache::forget('commerce.records.'.$record->resource);
            if ($record->resource === 'products') {
                app(ProductCatalog::class)->synchronize($record);
            }
            if ($record->resource === 'categories' && $record->wasChanged('data')) {
                app(ProductCatalog::class)->rebuild();
            }
        });
        static::deleted(fn ($record) => Cache::forget('commerce.records.'.$record->resource));
    }

    public function row(): array
    {
        $data = $this->data;
        if ($this->resource === 'customers') {
            unset($data['password_hash']);
        }
        if ($this->resource === 'payment-methods') {
            $data['has_credentials'] = ! empty($data['credentials']);
            $data['credentials'] = '';
        }

        return array_merge($data, ['id' => $this->id, 'version' => $this->version, 'created_at' => $this->created_at?->toISOString(), 'updated_at' => $this->updated_at?->toISOString()]);
    }
}
