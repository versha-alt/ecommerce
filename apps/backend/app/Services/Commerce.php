<?php

namespace App\Services;

use App\Models\CommerceRecord as Record;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class Commerce
{
    public const RESOURCES = ['products', 'brands', 'categories', 'attributes', 'customers', 'delivery-zones', 'taxes', 'coupons', 'banners', 'pages', 'orders', 'payments', 'payment-methods', 'returns', 'reviews', 'enquiries', 'emails', 'settings'];

    public const PERMISSIONS = [
        'Admin' => ['dashboard', 'products', 'brands', 'categories', 'attributes', 'inventory', 'customers', 'delivery-zones', 'taxes', 'coupons', 'banners', 'pages', 'users', 'orders', 'payments', 'payment-methods', 'returns', 'reviews', 'enquiries', 'emails', 'settings', 'reports', 'activity'],
        'Sales' => ['dashboard', 'products', 'brands', 'categories', 'customers', 'orders', 'payments', 'returns', 'enquiries', 'reports'],
        'Store Manager' => ['dashboard', 'products', 'brands', 'categories', 'attributes', 'inventory', 'customers', 'orders', 'reports'],
    ];

    public function permissions(User $u): array
    {
        return self::PERMISSIONS[$u->role] ?? [];
    }

    public function authorize(User $u, string $resource, bool $write = false): void
    {
        abort_unless(in_array($resource, $this->permissions($u)), 403, 'Your role does not have access to this module.');
        if ($write && $u->role !== 'Admin') {
            abort_unless(in_array($resource, $u->role === 'Sales' ? ['orders', 'customers', 'returns', 'enquiries'] : ['orders', 'inventory', 'products']), 403, 'Your role cannot change these records.');
        }
    }

    public function rows(string $resource, bool $fresh = false): array
    {
        $load = fn () => Record::where('resource', $resource)->latest()->get()->map(fn ($r) => $r->row())->all();

        return $fresh || DB::transactionLevel() > 0 ? $load() : Cache::remember('commerce.records.'.$resource, 30, $load);
    }

    public function find(string $resource, string $id, bool $lock = false): Record
    {
        $q = Record::where('resource', $resource)->where('id', $id);

        return ($lock ? $q->lockForUpdate() : $q)->firstOrFail();
    }

    public function active(string $resource, string $id): Record
    {
        $r = $this->find($resource, $id, true);
        if (($r->data['status'] ?? 'Active') !== 'Active') {
            $this->fail('The selected '.str_replace('-', ' ', $resource).' record is not active.');
        }

        return $r;
    }

    public function fail(string $message): never
    {
        throw ValidationException::withMessages(['record' => $message]);
    }

    public function audit(User $actor, string $action, string $resource, ?array $before, ?array $after): void
    {
        DB::table('audit_events')->insert(['id' => (string) Str::uuid(), 'actor' => $actor->email, 'action' => $action, 'resource' => $resource, 'name' => $after['name'] ?? $after['reference'] ?? $before['name'] ?? $resource, 'before' => $before ? json_encode($this->redact($before)) : null, 'after' => $after ? json_encode($this->redact($after)) : null, 'created_at' => now()]);
    }

    public function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (preg_match('/password|secret|passkey|consumer_key|credentials/', $key)) {
                $data[$key] = '[redacted]';
            }
        }

        return $data;
    }

    public function validate(string $resource, array $data, ?Record $old = null): array
    {
        $base = ['name' => 'required|string|max:180', 'status' => 'nullable|string|max:30', 'description' => 'nullable|string|max:20000', 'notes' => 'nullable|string|max:20000', 'image' => 'nullable|string|max:2000', 'banner' => 'nullable|string|max:2000', 'seo_title' => 'nullable|string|max:250', 'seo_description' => 'nullable|string|max:2000', 'specifications' => 'nullable|string|max:20000', 'warranty' => 'nullable|string|max:2000', 'website' => 'nullable|url|max:2000'];
        $rules = match ($resource) {
            'products' => ['gallery_images' => 'nullable|array', 'gallery_images.*' => ['required', 'string', 'max:2000', 'distinct', 'regex:~^(https?://|/api/v1/media/)~'], 'slug' => ['required', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('commerce_records', 'product_slug')->ignore($old?->id)], 'sku' => 'required|string|max:80', 'type' => 'required|in:Simple,Variable', 'price' => 'required|numeric|min:0|max:100000000', 'sale_price' => 'nullable|numeric|min:0|max:100000000', 'stock' => 'required|integer|min:0|max:1000000', 'low_stock_threshold' => 'nullable|integer|min:0', 'brand_id' => 'nullable|uuid', 'category_ids' => 'nullable|array', 'category_ids.*' => 'uuid', 'status' => 'required|in:Active,Inactive', 'image' => 'nullable|string|max:2000', 'manual' => 'nullable|string|max:2000'],
            'categories' => ['slug' => 'required|string|max:180|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'parent_id' => 'nullable|uuid'],
            'attributes' => ['filterable' => 'nullable|boolean', 'required' => 'nullable|boolean', 'unit' => 'nullable|string|max:80', 'options.*' => 'string|max:180', 'code' => 'required|string|max:80|regex:/^[a-z][a-z0-9_]*$/', 'input_type' => 'required|in:Text,Number,Single choice,Multiple choice,Yes/No', 'options' => 'nullable|array'],
            'customers' => ['marketing_consent' => 'nullable|boolean', 'company' => 'nullable|string|max:180', 'county' => 'nullable|string|max:180', 'email' => 'required|email|max:180', 'phone' => 'nullable|string|max:50', 'account_type' => 'required|in:Individual,Business', 'status' => 'required|in:Active,Inactive,Retired', 'address' => 'nullable|string|max:2000'],
            'delivery-zones' => ['country_code' => ['required', Rule::in(array_keys(config('shipping.countries')))], 'county_codes' => 'required|array|min:1|max:47', 'county_codes.*' => ['required', 'string', 'distinct', Rule::in(array_keys(config('shipping.countries.'.($data['country_code'] ?? 'KE').'.counties', [])))], 'charge' => 'required|numeric|min:0', 'free_threshold' => 'nullable|numeric|min:0', 'status' => 'required|in:Active,Inactive,Retired'],
            'taxes' => ['rate' => 'required|numeric|min:0|max:100', 'inclusive' => 'boolean'],
            'coupons' => [],
            'banners' => ['placement' => 'required|in:Hero slider,Promotional banner,Featured brand,Featured product', 'headline' => 'nullable|string|max:250', 'link' => ['nullable', 'string', 'max:2000', 'regex:~^(https?://|/(?!/)|\#)~']],
            'pages' => ['slug' => 'required|string|max:180|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'body' => 'required|string|max:50000'],
            'emails' => ['event' => 'required|in:Order confirmation,Order status update,Admin new order,Payment successful,Password reset', 'subject' => 'required|string|max:250', 'body' => 'required|string|max:20000'],
            'enquiries' => ['email' => 'required|email', 'subject' => 'required|string|max:200', 'message' => 'required|string|max:10000', 'status' => 'required|in:Open,In progress,Resolved'],
            'payments' => ['name' => 'nullable', 'order_id' => 'required|uuid', 'reference' => 'required|string|max:120', 'method' => 'required|in:M-Pesa,Card,PayPal,Razorpay,COD,Bank transfer,Other online,Other manual,Pesapal,Flutterwave,DPO', 'amount' => 'required|numeric|min:0.01', 'status' => 'required|in:Pending,Failed'],
            'returns' => ['name' => 'nullable', 'order_id' => 'required|uuid', 'reason' => 'required|string|max:2000', 'refund_amount' => 'required|numeric|min:0.01', 'status' => 'required|in:Requested,Approved,Received,Rejected'],
            default => []
        };
        $validator = Validator::make($data, array_merge($base, $rules));
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        $protected = ['reserved', 'usage', 'refund_status', 'refund_reference', 'refund_recorded_at', 'evidence', 'consent_history'];
        $clean = array_diff_key($data, array_flip($protected));
        unset($clean['id'],$clean['version'],$clean['created_at'],$clean['updated_at'],$clean['_key']);
        foreach ($clean as $k => $v) {
            if ($v === '') {
                $clean[$k] = null;
            }
        }
        $clean['status'] ??= 'Active';
        if (! in_array($resource, ['products', 'customers', 'payments', 'returns', 'enquiries']) && ! in_array($clean['status'], ['Active', 'Inactive', 'Retired'])) {
            $this->fail('Invalid status.');
        }
        foreach (['price', 'sale_price', 'charge', 'free_threshold', 'value', 'min_order', 'amount', 'refund_amount'] as $k) {
            if (isset($clean[$k])) {
                if (abs($clean[$k] * 100 - round($clean[$k] * 100)) > 0.00001) {
                    $this->fail('Money amounts support a maximum of two decimal places.');
                }$clean[$k] = (float) $clean[$k];
            }
        }
        if ($resource === 'delivery-zones') {
            $counties = config('shipping.countries.'.$clean['country_code'].'.counties');
            $clean['country_name'] = config('shipping.countries.'.$clean['country_code'].'.name');
            $clean['county_codes'] = array_values($clean['county_codes']);
            $clean['county_names'] = array_map(fn (string $code): string => $counties[$code], $clean['county_codes']);
            $clean['towns'] = $clean['county_names'];
            unset($clean['legacy_unmapped_locations']);
        }
        if ($resource === 'products') {
            $clean['gallery_images'] = array_values($clean['gallery_images'] ?? $old?->data['gallery_images'] ?? []);
            foreach ($this->rows('products') as $p) {
                if ($p['id'] !== $old?->id && strcasecmp($p['sku'], $clean['sku']) === 0) {
                    throw ValidationException::withMessages(['sku' => 'This SKU already exists.']);
                }
            }
            if (! empty($clean['brand_id'])) {
                $this->active('brands', $clean['brand_id']);
            }
            $clean['category_ids'] ??= [];
            $clean['direct_category_ids'] = $clean['category_ids'];
            foreach ($clean['category_ids'] as $id) {
                $this->active('categories', $id);
            }
            $clean['reserved'] = $old?->data['reserved'] ?? 0;
            $clean['stock'] = (int) $clean['stock'];
            $clean['low_stock_threshold'] = (int) ($clean['low_stock_threshold'] ?? 5);
            if ($old && $clean['stock'] !== $old->data['stock']) {
                $this->fail('Use Inventory to adjust existing product stock with a reason.');
            }
            if (isset($clean['sale_price']) && $clean['sale_price'] > $clean['price']) {
                throw ValidationException::withMessages(['sale_price' => 'Sale price must be lower than or equal to regular price.']);
            }
            if ($clean['type'] === 'Variable') {
                $clean['stock'] = 0;
                $clean['reserved'] = 0;
            }
        }
        if ($resource === 'categories') {
            foreach ($this->rows('categories') as $r) {
                if ($r['id'] !== $old?->id && $r['slug'] === $clean['slug']) {
                    $this->fail('This URL slug is already used.');
                }
            }
            $clean['parent_id'] ??= null;
            $all = $this->rows('categories');
            $id = $old?->id ?? 'new';
            $all = array_values(array_filter($all, fn ($r) => $r['id'] !== $id));
            $all[] = array_merge($clean, ['id' => $id]);
            $byId = array_column($all, null, 'id');
            foreach ($all as $r) {
                $seen = [];
                $depth = 0;
                while ($r) {
                    if (in_array($r['id'], $seen)) {
                        $this->fail('Categories cannot contain cycles.');
                    }$seen[] = $r['id'];
                    if (++$depth > 3) {
                        $this->fail('Categories support a maximum of three levels.');
                    }$parent = $r['parent_id'] ?? null;
                    if (! $parent) {
                        break;
                    }if (! isset($byId[$parent]) || $byId[$parent]['status'] === 'Retired') {
                        $this->fail('Choose an existing parent category.');
                    }$r = $byId[$parent];
                }
            }
            $clean['redirects'] = $old?->data['redirects'] ?? [];
            if ($old && $old->data['slug'] !== $clean['slug']) {
                $clean['redirects'][] = $old->data['slug'];
            }
        }
        if ($resource === 'attributes') {
            if ($old && $old->data['code'] !== $clean['code']) {
                $this->fail('Permanent attribute codes cannot be changed.');
            }
            foreach ($this->rows($resource) as $r) {
                if ($r['id'] !== $old?->id && $r['code'] === $clean['code']) {
                    $this->fail('Attribute code already exists.');
                }
            }
            if (in_array($clean['input_type'], ['Single choice', 'Multiple choice']) && empty($clean['options'])) {
                $this->fail('Choice attributes need options.');
            }
        }
        if ($resource === 'customers') {
            $clean['email'] = strtolower($clean['email']);
            foreach ($this->rows($resource) as $r) {
                if ($r['id'] !== $old?->id && $r['email'] === $clean['email']) {
                    $this->fail('A customer with this email exists. Review the existing record.');
                }
            }
            $clean['consent_history'] = $old?->data['consent_history'] ?? [];
            if (! $old || ($old->data['marketing_consent'] ?? false) !== ($clean['marketing_consent'] ?? false)) {
                $clean['consent_history'][] = ['channel' => 'Email', 'consent' => $clean['marketing_consent'] ?? false, 'source' => 'Storefront', 'at' => now()->toISOString()];
            }
        }
        if ($resource === 'taxes' && $clean['status'] === 'Active') {
            foreach ($this->rows('taxes') as $r) {
                if ($r['id'] !== $old?->id && $r['status'] === 'Active') {
                    $this->fail('Only one active VAT rule is supported. Deactivate the existing rule first.');
                }
            }
        }
        if ($resource === 'coupons') {
            $clean = array_merge($clean, app(Discounts::class)->validate($clean, $old));
        }
        if ($resource === 'payment-methods') {
            $clean = array_intersect_key($clean, array_flip(['name', 'status', 'description']));
            $clean = array_merge($clean, app(PaymentMethods::class)->validate($data, $old));
        }
        if ($resource === 'returns' && $old && ($old->data['refund_status'] ?? '') === 'Refunded') {
            $this->fail('Refunded requests cannot be edited.');
        }
        if (in_array($resource, ['payments', 'returns'])) {
            $order = $this->find('orders', $clean['order_id'], true)->row();
            $clean['order_reference'] = $order['reference'];
            $clean['name'] = $order['reference'];
            if ($resource === 'payments') {
                foreach ($this->rows('payments') as $p) {
                    if ($p['id'] !== $old?->id && $p['reference'] === $clean['reference']) {
                        $this->fail('Transaction reference already exists.');
                    }
                }
                if ($order['status'] === 'Cancelled' || $order['payment_status'] === 'Paid') {
                    $this->fail('This order is not eligible for a new payment attempt.');
                }
            } else {
                $clean['reference'] = $old?->data['reference'] ?? 'RET-'.strtoupper(Str::random(7));
                if ($order['status'] !== 'Delivered' || ! in_array($order['payment_status'], ['Paid', 'Partially refunded'])) {
                    $this->fail('Returns require a delivered, paid order.');
                }
                $requested = Record::where('resource', 'returns')->where('id', '!=', $old?->id ?? '')->get()->filter(fn ($r) => $r->data['order_id'] === $clean['order_id'] && $r->data['status'] !== 'Rejected')->sum(fn ($r) => $r->data['refund_amount']);
                if ($requested + $clean['refund_amount'] > $order['total']) {
                    $this->fail('Requested refunds exceed the original order value.');
                }
            }
        }

        return $clean;
    }

    public function save(string $resource, array $input, User $actor, ?string $id = null): array
    {
        return DB::transaction(function () use ($resource, $input, $actor, $id) {
            Record::where('resource', 'settings')->lockForUpdate()->first();
            abort_unless(in_array($resource, self::RESOURCES) && ! in_array($resource, ['orders', 'settings']), 404);
            abort_if(! $id && in_array($resource, ['customers', 'returns', 'payments', 'reviews']), 405, 'Customers, return requests, payments and reviews originate in the storefront.');
            $old = $id ? $this->find($resource, $id, true) : null;
            if ($old && ($input['version'] ?? null) !== $old->version) {
                abort(409, 'This record changed. Reload before saving.');
            }
            $before = $old?->row();
            if (($input['status'] ?? '') === 'Retired' || ($old?->data['status'] ?? '') === 'Retired') {
                $this->fail('Use the retirement action; retired records cannot be edited.');
            }
            if ($resource === 'reviews') {
                Validator::make($input, ['status' => 'required|in:Pending,Approved,Rejected', 'notes' => 'nullable|string|max:20000'])->validate();
                foreach ($input as $field => $value) {
                    if (! in_array($field, ['id', 'version', 'created_at', 'updated_at', 'status', 'notes']) && $value !== ($old->row()[$field] ?? null)) {
                        $this->fail('Customer review details cannot be changed.');
                    }
                }
                if ($input['status'] === 'Pending' && $old->data['status'] !== 'Pending') {
                    $this->fail('Choose Approved or Rejected for a moderated review.');
                }
                $data = $old->data;
                if ($input['status'] !== $data['status']) {
                    $data['status_history'][] = ['from' => $data['status'], 'to' => $input['status'], 'actor' => $actor->email, 'at' => now()->toISOString(), 'note' => $input['notes'] ?? ''];
                }
                $data['status'] = $input['status'];
                $data['notes'] = $input['notes'] ?? $data['notes'] ?? '';
                $old->data = $data;
                $old->version++;
                $old->save();
                $this->audit($actor, 'Review moderated', 'reviews', $before, $old->row());

                return $old->row();
            }
            if ($resource === 'payments') {
                foreach ($input as $field => $value) {
                    if (! in_array($field, ['id', 'version', 'created_at', 'updated_at', 'notes']) && $value !== ($old->row()[$field] ?? null)) {
                        $this->fail('Transaction details and financial status are managed by the payment system.');
                    }
                }
                Validator::make($input, ['notes' => 'nullable|string|max:20000'])->validate();
                $old->data = array_merge($old->data, ['notes' => $input['notes'] ?? $old->data['notes'] ?? '']);
                $old->version++;
                $old->save();
                $this->audit($actor, 'Transaction notes updated', 'payments', $before, $old->row());

                return $old->row();
            }
            if ($resource === 'returns' && $old) {
                foreach (['order_id', 'reason', 'refund_amount'] as $field) {
                    if (isset($input[$field]) && (string) $input[$field] !== (string) ($old->data[$field] ?? '')) {
                        $this->fail('Customer request details cannot be changed.');
                    }
                }
                $next = $input['status'] ?? $old->data['status'];
                $transitions = ['Requested' => ['Requested', 'Approved', 'Rejected'], 'Approved' => ['Approved', 'Received', 'Rejected'], 'Received' => ['Received'], 'Rejected' => ['Rejected']];
                if (! in_array($next, $transitions[$old->data['status']] ?? [])) {
                    $this->fail('Invalid return status transition.');
                }
                if ($next !== $old->data['status'] && $actor->role !== 'Admin') {
                    abort(403, 'Return decisions require Admin access.');
                }
                if ($next === 'Rejected' && $next !== $old->data['status'] && empty(trim($input['notes'] ?? ''))) {
                    $this->fail('Add a reason when rejecting a return.');
                }
                $input = array_merge($old->data, array_intersect_key($input, array_flip(['status', 'notes'])));
            }
            unset($input['password_hash']);
            $clean = $this->validate($resource, $input, $old);
            if ($resource === 'customers' && $old) {
                foreach (['password_hash', 'addresses', 'wishlist'] as $field) {
                    if (array_key_exists($field, $old->data)) {
                        $clean[$field] = $old->data[$field];
                    }
                }
            }
            if ($resource === 'returns' && $old) {
                $clean['refund_status'] = $old->data['refund_status'] ?? 'Pending';
                $clean['status_history'] = $old->data['status_history'] ?? [];
                if ($clean['status'] !== $old->data['status']) {
                    $clean['status_history'][] = ['from' => $old->data['status'], 'to' => $clean['status'], 'actor' => $actor->email, 'at' => now()->toISOString(), 'note' => $clean['notes'] ?? ''];
                }
            }
            $record = $old ?? new Record(['resource' => $resource]);
            $record->data = $clean;
            if ($old) {
                $record->version++;
            }try {
                $record->save();
            } catch (QueryException $error) {
                if ($resource === 'products' && str_contains($error->getMessage(), 'product_slug')) {
                    $this->fail('This product URL slug already exists.');
                }throw $error;
            }$this->audit($actor, $old ? 'Updated' : 'Created', $resource, $before, $record->row());

            return $record->row();
        });
    }

    public function retire(string $resource, string $id, int $version, User $actor): array
    {
        abort_if($resource === 'reviews', 405, 'Use Approved or Rejected to moderate reviews.');

        return DB::transaction(function () use ($resource, $id, $version, $actor) {
            Record::where('resource', 'settings')->lockForUpdate()->first();
            $r = $this->find($resource, $id, true);
            abort_if($r->version !== $version, 409, 'Record changed. Reload before retiring.');
            $before = $r->row();
            abort_if(in_array($resource, ['orders', 'settings', 'payments', 'returns']), 422, 'This resource cannot be retired.');
            if ($resource === 'products' && ($r->data['reserved'] ?? 0) > 0) {
                $this->fail('Release stock reservations before retiring this product.');
            }
            if ($resource === 'categories') {
                foreach ($this->rows($resource) as $c) {
                    if (($c['parent_id'] ?? null) === $id && $c['status'] !== 'Retired') {
                        $this->fail('Retire child categories first.');
                    }
                }
            }
            $r->data = array_merge($r->data, ['status' => 'Retired']);
            $r->version++;
            $r->save();
            $this->audit($actor, 'Retired', $resource, $before, $r->row());

            return $r->row();
        });
    }

    public function inventory(array $input, User $actor): array
    {
        Validator::make($input, ['product_id' => 'required|uuid', 'stock' => 'required|integer|min:0|max:100000000', 'version' => 'required|integer'])->validate();

        return DB::transaction(function () use ($input, $actor) {
            Record::where('resource', 'settings')->lockForUpdate()->first();
            $p = $this->find('products', $input['product_id'], true);
            if ($p->version !== $input['version']) {
                abort(409, 'Stock changed. Reload before adjusting.');
            }$before = $p->row();
            $d = $p->data;
            if (($d['status'] ?? '') === 'Retired') {
                $this->fail('Retired products cannot have stock updated.');
            }if ($d['type'] !== 'Simple') {
                $this->fail('Variable parents do not hold physical stock.');
            }$stock = (int) $input['stock'];
            if ($stock < ($d['reserved'] ?? 0)) {
                $this->fail('Stock cannot be lower than reserved quantities.');
            }$d['stock'] = $stock;
            $p->data = $d;
            $p->version++;
            $p->save();
            $this->audit($actor, 'Stock quantity set', 'inventory', $before, $p->row());

            return $p->row();
        });
    }

    public function idempotent(string $key, array $input, callable $operation): array
    {
        if (! $key || strlen($key) > 150) {
            $this->fail('A valid idempotency key is required.');
        }
        $fingerprint = hash('sha256', json_encode($input));

        return DB::transaction(function () use ($key, $fingerprint, $operation) {
            // Serialize order operations by locking the stable settings row before checking the key.
            Record::where('resource', 'settings')->lockForUpdate()->first();
            $previous = DB::table('idempotency_keys')->where('id', $key)->first();
            if ($previous) {
                abort_if($previous->fingerprint !== $fingerprint, 409, 'This idempotency key was already used for different details.');

                return json_decode($previous->response, true);
            }
            $response = $operation();
            DB::table('idempotency_keys')->insert(['id' => $key, 'fingerprint' => $fingerprint, 'response' => json_encode($response), 'created_at' => now(), 'updated_at' => now()]);

            return $response;
        });
    }

    public function createOrder(array $input, string $key, User $actor): array
    {
        Validator::make($input, ['customer_id' => 'required|uuid', 'delivery_zone_id' => 'required|uuid', 'delivery_address' => 'required|string|max:2000', 'lines' => 'required|array|min:1|max:100', 'lines.*.product_id' => 'required|uuid|distinct', 'lines.*.quantity' => 'required|integer|min:1|max:10000', 'payment_method_id' => 'nullable|uuid', 'coupon_code' => 'nullable|string|max:50', 'notes' => 'nullable|string|max:10000'])->validate();

        return $this->idempotent('order:'.$key, $input, function () use ($input, $actor) {
            $customer = $this->active('customers', $input['customer_id'])->row();
            $zone = $this->active('delivery-zones', $input['delivery_zone_id'])->row();
            $tax = Record::where('resource', 'taxes')->get()->first(fn ($r) => ($r->data['status'] ?? '') === 'Active');
            if (! $tax) {
                $this->fail('Configure an active VAT rule in Tax Settings before creating orders.');
            }
            $lines = [];
            $subtotal = 0;
            $productRecords = [];
            foreach ($input['lines'] as $line) {
                $p = $this->active('products', $line['product_id']);
                $d = $p->data;
                if ($d['type'] !== 'Simple') {
                    $this->fail('Select a sellable Simple product. Variable parent purchase is not supported.');
                }if ($line['quantity'] > $d['stock'] - ($d['reserved'] ?? 0)) {
                    $this->fail('Insufficient stock for '.$d['name'].'.');
                }$price = (int) round(($d['sale_price'] ?? $d['price']) * 100);
                $lineTotal = $price * $line['quantity'];
                $subtotal += $lineTotal;
                $brand = ($d['brand_id'] ?? null) ? $this->find('brands', $d['brand_id'])->data['name'] : '';
                $category = ($d['category_ids'][0] ?? null) ? $this->find('categories', $d['category_ids'][0])->data['name'] : '';
                $lines[] = ['product_id' => $p->id, 'name' => $d['name'], 'sku' => $d['sku'], 'quantity' => $line['quantity'], 'unit_price' => $price / 100, 'total' => $lineTotal / 100, 'brand_name' => $brand, 'category_name' => $category, 'category_ids' => $d['category_ids'] ?? [], 'warranty' => $d['warranty'] ?? ''];
                $productRecords[] = $p;
            }
            $discount = 0;
            $shippingDiscount = 0;
            $coupon = null;
            $code = strtoupper(trim($input['coupon_code'] ?? ''));
            $shipping = isset($zone['free_threshold']) && $subtotal >= (int) round($zone['free_threshold'] * 100) ? 0 : (int) round($zone['charge'] * 100);
            if ($code) {
                $coupon = Record::where('resource', 'coupons')->lockForUpdate()->get()->first(fn ($r) => $r->data['code'] === $code);
                if (! $coupon) {
                    $this->fail('Coupon code not found.');
                }$result = app(Discounts::class)->calculate($coupon, $lines, $customer, $shipping);
                $discount = $result['discount'];
                $shippingDiscount = $result['shipping_discount'];
            }
            $net = $subtotal - $discount;
            $rate = (float) $tax->data['rate'];
            $inclusive = (bool) ($tax->data['inclusive'] ?? false);
            $taxAmount = (int) round($inclusive ? $net - $net / (1 + $rate / 100) : $net * $rate / 100);
            $shipping = isset($zone['free_threshold']) && $net >= (int) round($zone['free_threshold'] * 100) ? 0 : (int) round($zone['charge'] * 100);
            $shippingDiscount = min($shippingDiscount, $shipping);
            $shipping -= $shippingDiscount;
            $order = Record::create(['resource' => 'orders', 'data' => ['reference' => 'ORD-'.strtoupper(Str::random(7)), 'name' => $customer['name'], 'customer_id' => $customer['id'], 'customer_name' => $customer['name'], 'customer_email' => $customer['email'], 'customer_phone' => $customer['phone'] ?? '', 'delivery_address' => $input['delivery_address'], 'delivery_zone_id' => $zone['id'], 'delivery_zone_name' => $zone['name'], 'delivery_snapshot' => $zone, 'tax_snapshot' => $tax->row(), 'currency' => 'KES', 'lines' => $lines, 'subtotal' => $subtotal / 100, 'discount' => $discount / 100, 'tax_total' => $taxAmount / 100, 'tax_inclusive' => $inclusive, 'shipping_total' => $shipping / 100, 'shipping_discount' => $shippingDiscount / 100, 'total' => ($net + ($inclusive ? 0 : $taxAmount) + $shipping) / 100, 'coupon_code' => $code, 'coupon_id' => $coupon?->id, 'status' => 'Pending', 'payment_status' => 'Unpaid', 'fulfilment_status' => 'Reserved', 'notes' => $input['notes'] ?? '', 'source' => 'Storefront', 'status_history' => [['from' => null, 'to' => 'Pending', 'at' => now()->toISOString(), 'actor' => $customer['id'], 'note' => 'Order placed']]]]);
            if (! empty($input['payment_method_id'])) {
                app(CustomerPayments::class)->begin($order, $this->active('payment-methods', $input['payment_method_id']));
                $order->refresh();
            }
            foreach ($productRecords as $i => $p) {
                $before = $p->row();
                $p->data = array_merge($p->data, ['reserved' => ($p->data['reserved'] ?? 0) + $lines[$i]['quantity']]);
                $p->version++;
                $p->save();
                $this->audit($actor, 'Stock reserved', 'inventory', $before, $p->row());
            }
            if ($coupon) {
                $coupon->data = array_merge($coupon->data, ['usage' => ($coupon->data['usage'] ?? 0) + 1]);
                $coupon->version++;
                $coupon->save();
            }
            $this->audit($actor, 'Order placed', 'orders', null, $order->row());

            return $order->row();
        });
    }

    public function orderAction(string $id, array $input, string $key, User $actor): array
    {
        Validator::make($input, ['version' => 'required|integer', 'action' => 'required|in:confirm,pay,dispatch,deliver,cancel,note', 'evidence' => 'required|string|max:10000'])->validate();
        abort_if($input['action'] === 'pay', 405, 'Payments are completed by customers; admin payment creation is disabled.');

        return $this->idempotent('action:'.$key, array_merge($input, ['order_id' => $id]), function () use ($id, $input, $actor) {
            $o = $this->find('orders', $id, true);
            if ($o->version !== $input['version']) {
                abort(409, 'This order changed. Reload before continuing.');
            }$before = $o->row();
            $d = $o->data;
            $action = $input['action'];
            if ($action === 'confirm') {
                if ($d['status'] !== 'Pending') {
                    $this->fail('Only pending orders can be confirmed.');
                }$d['status'] = 'Confirmed';
            } elseif ($action === 'dispatch') {
                if ($d['status'] !== 'Confirmed' || $d['payment_status'] !== 'Paid') {
                    $this->fail('Confirm the order and verify payment before dispatch.');
                }
                foreach ($d['lines'] as $l) {
                    $p = $this->find('products', $l['product_id'], true);
                    $pb = $p->row();
                    $pd = $p->data;
                    if ($pd['reserved'] < $l['quantity'] || $pd['stock'] < $l['quantity']) {
                        $this->fail('Stock reservation mismatch.');
                    }$pd['reserved'] -= $l['quantity'];
                    $pd['stock'] -= $l['quantity'];
                    $p->data = $pd;
                    $p->version++;
                    $p->save();
                    $this->audit($actor, 'Stock dispatched', 'inventory', $pb, $p->row());
                }$d['status'] = 'Dispatched';
                $d['fulfilment_status'] = 'Dispatched';
                $d['tracking_reference'] = $input['evidence'];
            } elseif ($action === 'deliver') {
                if ($d['status'] !== 'Dispatched') {
                    $this->fail('Only dispatched orders can be delivered.');
                }$d['status'] = 'Delivered';
                $d['fulfilment_status'] = 'Delivered';
                $d['delivery_evidence'] = $input['evidence'];
            } elseif ($action === 'cancel') {
                if (! in_array($d['status'], ['Pending', 'Confirmed']) || $d['payment_status'] === 'Paid') {
                    $this->fail('Only unpaid, undispatched orders can be cancelled.');
                }foreach ($d['lines'] as $l) {
                    $p = $this->find('products', $l['product_id'], true);
                    $pb = $p->row();
                    $p->data = array_merge($p->data, ['reserved' => $p->data['reserved'] - $l['quantity']]);
                    $p->version++;
                    $p->save();
                    $this->audit($actor, 'Reservation released', 'inventory', $pb, $p->row());
                }$d['status'] = 'Cancelled';
                $d['fulfilment_status'] = 'Unfulfilled';
                $d['cancellation_reason'] = $input['evidence'];
                if ($d['coupon_code']) {
                    $coupon = Record::where('resource', 'coupons')->lockForUpdate()->get()->first(fn ($r) => ! empty($d['coupon_id']) ? $r->id === $d['coupon_id'] : $r->data['code'] === $d['coupon_code']);
                    if ($coupon) {
                        $coupon->data = array_merge($coupon->data, ['usage' => max(0, ($coupon->data['usage'] ?? 1) - 1)]);
                        $coupon->version++;
                        $coupon->save();
                    }
                }
            } else {
                $d['notes'] = trim(($d['notes'] ?? '')."\n".$actor->name.': '.$input['evidence']);
            }
            if ($d['status'] !== $before['status']) {
                $d['status_history'] ??= [];
                $d['status_history'][] = ['from' => $before['status'], 'to' => $d['status'], 'actor' => $actor->email, 'at' => now()->toISOString(), 'note' => $input['evidence']];
            }
            $o->data = $d;
            $o->version++;
            $o->save();
            $this->audit($actor, ucfirst($action), 'orders', $before, $o->row());

            return $o->row();
        });
    }

    public function updateOrderStatuses(string $id, array $input, string $key, User $actor): array
    {
        Validator::make($input, ['version' => 'required|integer|min:1', 'status' => 'nullable|in:Pending,Confirmed,Dispatched,Delivered,Cancelled', 'payment_status' => 'nullable|in:Unpaid,Pending,Incomplete,Failed,Paid,Refunded,Partially refunded', 'evidence' => 'required|string|max:10000'])->validate();

        return $this->idempotent('order-status:'.$key, $input + ['order_id' => $id], function () use ($id, $input, $key, $actor) {
            $order = $this->find('orders', $id, true);
            abort_if($order->version !== $input['version'], 409, 'This order changed. Reload before continuing.');
            if (isset($input['payment_status']) && $input['payment_status'] !== ($order->data['payment_status'] ?? 'Unpaid')) {
                $before = $order->row();
                $data = $order->data;
                $data['payment_status'] = $input['payment_status'];
                $data['payment_status_history'][] = ['from' => $before['payment_status'], 'to' => $input['payment_status'], 'actor' => $actor->email, 'at' => now()->toISOString(), 'note' => $input['evidence'], 'source' => 'Manual admin override'];
                $order->data = $data;
                $order->version++;
                $order->save();
                $this->audit($actor, 'Manual payment status override', 'orders', $before, $order->row());
            }
            if (isset($input['status']) && $input['status'] !== $order->data['status']) {
                $action = ['Confirmed' => 'confirm', 'Dispatched' => 'dispatch', 'Delivered' => 'deliver', 'Cancelled' => 'cancel'][$input['status']] ?? null;
                if (! $action) {
                    $this->fail('An order cannot be moved backwards to Pending.');
                }

                return $this->orderAction($id, ['action' => $action, 'version' => $order->version, 'evidence' => $input['evidence']], 'status-transition:'.$key, $actor);
            }

            return $order->row();
        });
    }

    public function settings(): array
    {
        $r = Record::where('resource', 'settings')->first();
        $d = $r?->row() ?? ['store_name' => 'Leekav'];
        foreach ($d as $k => $v) {
            if (preg_match('/secret|password|passkey|consumer_key/', $k)) {
                $d[$k] = '';
            }
        }

        return $d;
    }

    public function saveSettings(array $input, User $actor): array
    {
        Validator::make($input, [
            'store_name' => 'required|string|max:180', 'version' => 'required|integer|min:1',
            'email' => 'nullable|email|max:180', 'mail_from' => 'nullable|email|max:180',
            'phone' => 'nullable|string|max:50', 'whatsapp' => 'nullable|string|max:50', 'address' => 'nullable|string|max:2000',
            'logo' => 'nullable|string|max:2000', 'favicon' => 'nullable|string|max:2000', 'facebook' => 'nullable|url:http,https|max:2000', 'instagram' => 'nullable|url:http,https|max:2000', 'cdn_url' => 'nullable|url:http,https|max:2000',
            'mail_transport' => 'nullable|in:Log (local preview),SMTP,Amazon SES',
            'smtp_host' => 'required_if:mail_transport,SMTP|nullable|string|max:250',
            'smtp_port' => 'required_if:mail_transport,SMTP|nullable|integer|min:1|max:65535',
            'smtp_username' => 'nullable|string|max:250', 'smtp_password' => 'nullable|string|max:2000',
            'ga4_id' => ['nullable', 'regex:/^G-[A-Z0-9]+$/'], 'meta_pixel_id' => ['nullable', 'regex:/^[0-9]+$/'],
        ])->validate();

        return DB::transaction(function () use ($input, $actor) {
            $r = Record::where('resource', 'settings')->lockForUpdate()->firstOrFail();
            if (($input['version'] ?? null) !== $r->version) {
                abort(409, 'Settings changed. Reload before saving.');
            }$before = $r->row();
            $d = $r->data;
            foreach ($input as $k => $v) {
                if (preg_match('/^(mpesa_|card_)/', $k)) {
                    continue;
                }if (in_array($k, ['id', 'version', 'created_at', 'updated_at'])) {
                    continue;
                }if (preg_match('/secret|password|passkey|consumer_key/', $k)) {
                    if ($v) {
                        $d[$k] = Crypt::encryptString($v);
                    }
                } else {
                    $d[$k] = $v;
                }
            }$allowed = ['store_name', 'email', 'phone', 'whatsapp', 'address', 'logo', 'favicon', 'facebook', 'instagram', 'cdn_url', 'mail_transport', 'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'mail_from', 'ga4_id', 'meta_pixel_id'];
            $d = array_intersect_key($d, array_flip($allowed));
            $r->data = $d;
            $r->version++;
            $r->save();
            $this->audit($actor, 'Settings updated', 'settings', $before, $r->row());

            return $this->settings();
        });
    }

    public function saveUser(array $input, User $actor, ?string $id = null): array
    {
        Validator::make($input, ['name' => 'required|string|max:180', 'email' => 'required|email', 'role' => 'required|in:Admin,Sales,Store Manager', 'status' => 'required|in:Active,Inactive,Retired', 'password' => ($id ? 'nullable' : 'required').'|string|min:12|max:1024'])->validate();

        return DB::transaction(function () use ($input, $actor, $id) {
            $u = $id ? User::lockForUpdate()->findOrFail($id) : new User;
            if ($id && ($input['version'] ?? null) !== $u->version) {
                abort(409, 'User changed. Reload before saving.');
            }if (User::where('email', $input['email'])->where('id', '!=', $id ?? 0)->exists()) {
                throw ValidationException::withMessages(['email' => 'This email is already in use.']);
            }if ($id && $u->role === 'Admin' && ($input['role'] !== 'Admin' || $input['status'] !== 'Active') && User::where('role', 'Admin')->where('status', 'Active')->count() <= 1) {
                $this->fail('Keep at least one active administrator.');
            }$before = $id ? $u->toArray() : null;
            $u->name = $input['name'];
            $u->email = strtolower($input['email']);
            $u->role = $input['role'];
            $u->status = $input['status'];
            if (! empty($input['password'])) {
                $u->password = Hash::make($input['password']);
            }if ($id) {
                $u->version++;
            }$u->save();
            if ($id && (! empty($input['password']) || $input['status'] !== 'Active')) {
                DB::table('admin_tokens')->where('user_id', $id)->delete();
            }$this->audit($actor, $id ? 'User updated' : 'User created', 'users', $before, $u->toArray());

            return $u->toArray();
        });
    }
}
