<?php

namespace App\Http\Controllers;

use App\Models\CommerceRecord as Record;
use App\Models\User;
use App\Services\BrandImageResolver;
use App\Services\Commerce;
use App\Services\StoreEmails;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StorefrontController extends Controller
{
    public function catalog(): array
    {
        $commerce = app(Commerce::class);
        $catalogRecords = Record::whereIn('resource', ['products', 'brands', 'categories', 'coupons', 'banners', 'homepage-sections', 'articles', 'pages', 'delivery-zones', 'payment-methods', 'orders', 'reviews'])->latest()->get()->groupBy('resource');
        $rows = fn (string $resource): array => ($catalogRecords->get($resource) ?? collect())->map(fn (Record $record): array => $record->row())->all();
        $active = fn (string $resource): array => array_values(array_filter($rows($resource), fn ($row) => ($row['status'] ?? '') === 'Active'));
        $settings = array_intersect_key($commerce->settings(), array_flip(['deals_tagline', 'announcement', 'footer_tagline', 'site_title', 'site_description', 'store_name', 'email', 'phone', 'whatsapp', 'address', 'logo', 'favicon', 'facebook', 'instagram', 'ga4_id', 'meta_pixel_id']));
        $products = array_map(fn ($row) => array_intersect_key($row, array_flip(['id', 'name', 'sku', 'slug', 'type', 'brand_id', 'category_ids', 'price', 'sale_price', 'stock', 'reserved', 'featured', 'deal', 'new_arrival', 'image', 'gallery_images', 'description', 'specifications', 'warranty', 'manual', 'seo_title', 'seo_description', 'created_at', 'updated_at'])), $active('products'));

        $sold = [];
        foreach ($rows('orders') as $order) {
            if (($order['status'] ?? '') === 'Delivered') {
                foreach ($order['lines'] ?? [] as $line) {
                    $sold[$line['product_id']] = ($sold[$line['product_id']] ?? 0) + $line['quantity'];
                }
            }
        }
        // Only moderated, published reviews contribute to public product ratings.
        $ratings = [];
        foreach ($rows('reviews') as $review) {
            if (($review['status'] ?? '') !== 'Approved' || ! isset($review['product_id'], $review['rating'])) {
                continue;
            }
            $rating = (int) $review['rating'];
            if ($rating >= 1 && $rating <= 5) {
                $ratings[$review['product_id']][] = $rating;
            }
        }
        foreach ($products as &$product) {
            $product['sold_count'] = $sold[$product['id']] ?? 0;
            $productRatings = $ratings[$product['id']] ?? [];
            $product['review_count'] = count($productRatings);
            $product['rating_average'] = $productRatings ? round(array_sum($productRatings) / count($productRatings), 1) : null;
        }
        unset($product);
        $expose = fn (string $resource, array $fields): array => array_map(fn (array $row): array => array_intersect_key($row, array_flip($fields)), $active($resource));

        $banners = $expose('banners', ['id', 'name', 'placement', 'eyebrow', 'headline', 'description', 'image', 'link', 'cta_label', 'sort_order']);
        $homepageSections = $expose('homepage-sections', ['id', 'name', 'key', 'eyebrow', 'heading', 'body', 'image', 'link', 'link_label', 'sort_order']);
        $articles = $expose('articles', ['id', 'name', 'slug', 'category', 'excerpt', 'body', 'image', 'image_alt', 'minutes', 'sort_order', 'created_at', 'updated_at']);
        $coupons = array_values(array_filter($active('coupons'), function (array $coupon): bool {
            return ($coupon['eligibility'] ?? 'All') === 'All'
                && (empty($coupon['starts_at']) || now()->gte($coupon['starts_at']))
                && (empty($coupon['ends_at']) || now()->lte(Carbon::parse($coupon['ends_at'])->endOfDay()))
                && (empty($coupon['usage_limit']) || ($coupon['usage'] ?? 0) < $coupon['usage_limit']);
        }));
        $coupons = array_map(fn (array $coupon): array => array_intersect_key($coupon, array_flip(['id', 'name', 'code', 'description', 'discount_kind', 'discount_type', 'value', 'minimum_type', 'minimum_amount', 'minimum_quantity', 'starts_at', 'ends_at', 'once_per_customer'])), $coupons);
        $pages = array_map(fn ($row) => array_intersect_key($row, array_flip(['name', 'slug', 'body', 'seo_title', 'seo_description', 'updated_at'])), $active('pages'));
        $warnings = [];
        if (! collect($banners)->contains('placement', 'Hero slider')) {
            $warnings[] = 'No active homepage hero slides are configured.';
        }
        foreach (['categories', 'featured-products', 'deals', 'deals-page', 'editorial', 'new-arrivals', 'brands', 'journal'] as $requiredSection) {
            if (! collect($homepageSections)->contains('key', $requiredSection)) {
                $warnings[] = 'Missing active homepage section: '.$requiredSection.'.';
            }
        }
        foreach (['about', 'faq', 'privacy', 'shipping-returns', 'terms-conditions', 'contact'] as $requiredPage) {
            if (! collect($pages)->contains('slug', $requiredPage)) {
                $warnings[] = 'Missing active CMS page: '.$requiredPage.'.';
            }
        }

        return ['products' => $products, 'brands' => $expose('brands', ['id', 'name', 'description', 'image', 'banner']), 'categories' => $expose('categories', ['id', 'name', 'slug', 'parent_id', 'description', 'image', 'nav_label', 'promo_image', 'promo_label', 'promo_headline', 'promo_description', 'sort_order']), 'coupons' => $coupons, 'banners' => $banners, 'homepage_sections' => $homepageSections, 'articles' => $articles, 'pages' => $pages, 'delivery_zones' => $expose('delivery-zones', ['id', 'name', 'country_code', 'county_codes', 'county_names', 'charge', 'free_threshold']), 'locations' => config('shipping'), 'payment_methods' => array_map(fn ($row) => array_intersect_key($row, array_flip(['id', 'name', 'category', 'provider', 'instructions'])), $active('payment-methods')), 'settings' => $settings, 'integration_warnings' => $warnings];
    }

    public function brandHero(string $id, BrandImageResolver $resolver): array
    {
        $brand = app(Commerce::class)->active('brands', $id);

        return ['hero' => $resolver->ensure($brand)];
    }

    private function customer(Request $request, bool $member = true): Record
    {
        $session = DB::table('customer_sessions')->where('token_hash', hash('sha256', $request->bearerToken() ?? ''))->where('expires_at', '>', now())->first();
        abort_unless($session && (! $member || ! $session->order_id), 401, 'Please sign in to your customer account.');
        $request->attributes->set('customer_session', $session);

        return app(Commerce::class)->active('customers', $session->customer_id);
    }

    private function session(Record $customer, ?string $orderId = null): string
    {
        $token = Str::random(64);
        DB::table('customer_sessions')->insert(['token_hash' => hash('sha256', $token), 'customer_id' => $customer->id, 'order_id' => $orderId, 'expires_at' => now()->addDays($orderId ? 1 : 7), 'created_at' => now()]);

        return $token;
    }

    private function profileData(Record $customer): array
    {
        return array_intersect_key($customer->row(), array_flip(['id', 'name', 'email', 'phone', 'addresses', 'wishlist', 'account_type', 'marketing_consent']));
    }

    private function orderData(array $order): array
    {
        $public = array_intersect_key($order, array_flip(['id', 'version', 'created_at', 'reference', 'customer_name', 'customer_id', 'delivery_address', 'tracking_reference', 'lines', 'subtotal', 'discount', 'tax_total', 'tax_inclusive', 'shipping_total', 'total', 'status', 'payment_status', 'fulfilment_status', 'status_history']));
        $public['status_history'] = array_map(fn (array $entry): array => array_intersect_key($entry, array_flip(['to', 'at'])), $public['status_history'] ?? []);

        return $public;
    }

    public function register(Request $request): array
    {
        $data = $request->validate(['name' => 'required|string|max:180', 'email' => 'required|email|max:180', 'password' => 'required|string|min:12|max:72|confirmed', 'password_confirmation' => 'required|string|max:72', 'phone' => 'nullable|string|max:50']);

        return DB::transaction(function () use ($data): array {
            Record::where('resource', 'settings')->lockForUpdate()->first();
            $email = strtolower($data['email']);
            if (Record::where('resource', 'customers')->where('data->email', $email)->exists()) {
                throw ValidationException::withMessages(['email' => 'This email is already registered. Please sign in.']);
            }
            $customer = Record::create(['resource' => 'customers', 'data' => ['name' => $data['name'], 'email' => $email, 'phone' => $data['phone'] ?? '', 'password_hash' => Hash::make($data['password']), 'status' => 'Active', 'account_type' => 'Individual', 'marketing_consent' => false, 'addresses' => [], 'wishlist' => []]]);

            return ['token' => $this->session($customer), 'customer' => $this->profileData($customer)];
        });
    }

    public function login(Request $request): array
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string|max:72']);
        $customer = Record::where('resource', 'customers')->where('data->email', strtolower($data['email']))->first();
        abort_unless($customer && ($customer->data['status'] ?? '') === 'Active' && ! empty($customer->data['password_hash']) && Hash::check($data['password'], $customer->data['password_hash']), 401, 'Email or password is incorrect.');

        return ['token' => $this->session($customer), 'customer' => $this->profileData($customer)];
    }

    public function logout(Request $request): array
    {
        DB::table('customer_sessions')->where('token_hash', hash('sha256', $request->bearerToken() ?? ''))->delete();

        return ['ok' => true];
    }

    public function account(Request $request): array
    {
        $customer = $this->customer($request);

        return ['customer' => $this->profileData($customer), 'orders' => Record::where('resource', 'orders')->where('data->customer_id', $customer->id)->latest()->get()->map(fn ($row) => $this->orderData($row->row()))->all(), 'returns' => Record::where('resource', 'returns')->where('data->customer_id', $customer->id)->latest()->get()->map(fn ($row) => array_intersect_key($row->row(), array_flip(['id', 'order_id', 'reason', 'refund_amount', 'refund_status', 'status', 'created_at'])))->all()];
    }

    public function profile(Request $request): array
    {
        $customer = $this->customer($request);
        $input = $request->validate(['name' => 'required|string|max:180', 'phone' => 'nullable|string|max:50', 'marketing_consent' => 'sometimes|boolean', 'wishlist' => 'nullable|array|max:100', 'wishlist.*' => 'uuid|distinct', 'addresses' => 'nullable|array|max:10', 'addresses.*.town' => 'nullable|string|max:180', 'addresses.*.phone' => 'nullable|string|max:50', 'addresses.*.is_default' => 'sometimes|boolean', 'addresses.*.address' => 'required|string|max:1000', 'addresses.*.county_code' => ['required', Rule::in(array_keys(config('shipping.countries.KE.counties')))], 'current_password' => 'required_with:password|nullable|string|max:72', 'password' => 'nullable|string|min:12|max:72']);

        return DB::transaction(function () use ($input, $customer): array {
            $customer = app(Commerce::class)->find('customers', $customer->id, true);
            $data = $customer->data;
            if (! empty($input['password'])) {
                abort_unless(Hash::check($input['current_password'], $data['password_hash']), 422, 'Current password is incorrect.');
                $data['password_hash'] = Hash::make($input['password']);
            }
            $customer->data = array_merge($data, array_intersect_key($input, array_flip(['name', 'phone', 'wishlist', 'addresses', 'marketing_consent'])));
            $customer->version++;
            $customer->save();

            return ['customer' => $this->profileData($customer)];
        });
    }

    public function checkout(Request $request): array
    {
        return $this->place($request, false);
    }

    public function quote(Request $request): array
    {
        DB::beginTransaction();
        try {
            $result = $this->place($request, true);

            return array_intersect_key($result['order'], array_flip(['subtotal', 'discount', 'tax_total', 'tax_inclusive', 'shipping_total', 'total']));
        } finally {
            DB::rollBack();
        }
    }

    private function place(Request $request, bool $preview): array
    {
        $input = $request->validate(['name' => 'required|string|max:180', 'email' => 'required|email|max:180', 'phone' => 'required|string|max:50', 'address' => 'required|string|max:1000', 'county_code' => ['required', Rule::in(array_keys(config('shipping.countries.KE.counties')))], 'delivery_zone_id' => 'required|uuid', 'payment_method_id' => 'required|uuid', 'coupon_code' => 'nullable|string|max:50', 'lines' => 'required|array|min:1|max:100', 'lines.*.product_id' => 'required|uuid|distinct', 'lines.*.quantity' => 'required|integer|min:1|max:10000']);
        $customer = $request->bearerToken() ? $this->customer($request, false) : null;
        if ($request->attributes->get('customer_session')?->order_id) {
            $customer = null;
        }
        $key = $preview ? (string) Str::uuid() : $request->header('Idempotency-Key', '');
        abort_unless($key && strlen($key) <= 100, 422, 'A valid checkout idempotency key is required.');
        $result = app(Commerce::class)->idempotent('store-checkout:'.$key, $input + ['account_id' => $customer?->id], function () use ($input, $customer, $key): array {
            $commerce = app(Commerce::class);
            $zone = $commerce->active('delivery-zones', $input['delivery_zone_id']);
            abort_unless(($zone->data['country_code'] ?? '') === 'KE' && in_array($input['county_code'], $zone->data['county_codes'] ?? [], true), 422, 'Choose a delivery zone that serves your county.');
            $method = $commerce->active('payment-methods', $input['payment_method_id']);
            abort_unless(($method->data['category'] ?? '') === 'Manual', 422, 'This online payment gateway is not connected yet. Please choose an available manual method.');
            if (! $customer) {
                $email = strtolower($input['email']);
                $customer = Record::where('resource', 'customers')->where('data->email', $email)->first();
                abort_if($customer && ! empty($customer->data['password_hash']), 422, 'This email has an account. Please sign in to continue.');
                abort_if($customer && ($customer->data['status'] ?? '') !== 'Active', 422, 'This customer account is disabled.');
                $customer ??= Record::create(['resource' => 'customers', 'data' => ['name' => $input['name'], 'email' => $email, 'phone' => $input['phone'], 'account_type' => 'Individual', 'status' => 'Active', 'address' => $input['address'], 'county' => config('shipping.countries.KE.counties.'.$input['county_code']), 'marketing_consent' => false]]);
            }
            $actor = new User(['name' => $input['name'], 'email' => $input['email'], 'role' => 'Sales']);
            $order = $commerce->createOrder(['customer_id' => $customer->id, 'delivery_zone_id' => $zone->id, 'payment_method_id' => $method->id, 'delivery_address' => $input['address'].', '.config('shipping.countries.KE.counties.'.$input['county_code']).', Kenya', 'coupon_code' => $input['coupon_code'] ?? '', 'lines' => $input['lines']], $key, $actor);

            return ['order' => $this->orderData($order)];
        });
        if (! $preview) {
            $result['email_delivery'] = app(StoreEmails::class)->confirmation($result['order']);
        }
        if (! $preview && ! $customer) {
            $result['token'] = $this->session(Record::findOrFail($result['order']['customer_id']), $result['order']['id']);
        }

        return $result;
    }

    public function order(Request $request, string $id): array
    {
        $customer = $this->customer($request, false);
        $order = app(Commerce::class)->find('orders', $id);
        $session = $request->attributes->get('customer_session');
        abort_unless(($order->data['customer_id'] ?? '') === $customer->id && (! $session->order_id || $session->order_id === $id), 404);

        return ['order' => $this->orderData($order->row())];
    }

    public function cancel(Request $request, string $id): array
    {
        $customer = $this->customer($request);
        $order = app(Commerce::class)->find('orders', $id);
        abort_unless(($order->data['customer_id'] ?? '') === $customer->id, 404);
        abort_unless($request->header('Idempotency-Key') && strlen($request->header('Idempotency-Key')) <= 100, 422, 'A valid cancellation idempotency key is required.');
        $input = $request->validate(['version' => 'required|integer', 'evidence' => 'required|string|max:1000']);
        $actor = new User(['name' => $customer->data['name'], 'email' => $customer->data['email'], 'role' => 'Sales']);

        return ['order' => $this->orderData(app(Commerce::class)->orderAction($id, $input + ['action' => 'cancel'], $request->header('Idempotency-Key', ''), $actor))];
    }

    public function reviewEligibility(Request $request, string $product): array
    {
        $customer = $this->customer($request);
        app(Commerce::class)->active('products', $product);
        $eligible = (bool) app(ProductReviewController::class)->eligibility($customer->id, $product);
        $review = Record::where('resource', 'reviews')->where('data->customer_id', $customer->id)->where('data->product_id', $product)->first();

        return ['eligible' => $eligible, 'review' => $review ? array_intersect_key($review->row(), array_flip(['id', 'version', 'rating', 'title', 'body', 'photos', 'status'])) : null];
    }

    public function review(Request $request): array
    {
        $customer = $this->customer($request);
        $input = $request->validate(['submission_id' => 'required|uuid', 'product_id' => 'required|uuid', 'rating' => 'required|integer|min:1|max:5', 'title' => 'nullable|string|max:180', 'body' => 'required|string|max:5000', 'photos' => 'sometimes|array|max:3', 'photos.*' => 'required|string|max:3000000', 'version' => 'nullable|integer|min:1']);

        return app(ProductReviewController::class)->create($input + ['customer_id' => $customer->id], app(Commerce::class));
    }

    public function returns(Request $request): array
    {
        $customer = $this->customer($request);
        $input = $request->validate(['order_id' => 'required|uuid', 'reason' => 'required|string|max:2000', 'refund_amount' => 'required|numeric|min:0.01']);
        abort_unless($request->header('Idempotency-Key') && strlen($request->header('Idempotency-Key')) <= 100, 422, 'A valid return idempotency key is required.');

        return app(Commerce::class)->idempotent('store-return:'.$request->header('Idempotency-Key', ''), $input + ['customer_id' => $customer->id], function () use ($input, $customer): array {
            $order = app(Commerce::class)->find('orders', $input['order_id'], true);
            abort_unless(($order->data['customer_id'] ?? '') === $customer->id, 404);
            $data = app(Commerce::class)->validate('returns', $input + ['status' => 'Requested']);
            $data['customer_id'] = $customer->id;
            $data['refund_status'] = 'Pending';
            $data['status_history'] = [['from' => null, 'to' => 'Requested', 'actor' => $customer->data['email'], 'at' => now()->toISOString()]];

            return ['return' => Record::create(['resource' => 'returns', 'data' => $data])->row()];
        });
    }

    public function enquiry(Request $request): array
    {
        $input = $request->validate(['name' => 'required|string|max:180', 'email' => 'required|email|max:180', 'subject' => 'required|string|max:200', 'message' => 'required|string|max:10000', 'product_id' => 'nullable|uuid']);
        $record = Record::create(['resource' => 'enquiries', 'data' => $input + ['status' => 'Open']]);

        return ['id' => $record->id, 'message' => 'Your enquiry has been received.'];
    }

    public function newsletter(Request $request): array
    {
        $input = $request->validate(['email' => 'required|email|max:180']);
        $subscriber = Record::firstOrCreate(['resource' => 'newsletter', 'data->email' => strtolower($input['email'])], ['data' => ['email' => strtolower($input['email']), 'status' => 'Subscribed', 'consent_at' => now()->toISOString()]]);

        $delivery = app(StoreEmails::class)->newsletter($subscriber);

        return ['message' => $delivery['status'] === 'Failed' ? 'You are subscribed, but we could not send the welcome email. Please try again later.' : 'Thanks for subscribing.', 'email_delivery' => $delivery];
    }
}
