<?php

namespace App\Services;

use App\Models\CommerceRecord as Record;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Discounts
{
    public function validate(array $input, ?Record $old): array
    {
        $input['discount_kind'] ??= 'Order';
        $input['discount_type'] ??= 'Percentage';
        $input['value'] ??= 0;
        $input['eligibility'] ??= 'All';
        $input['scope'] ??= 'All';
        $input['minimum_type'] ??= isset($input['min_order']) ? 'Amount' : 'None';
        $input['minimum_amount'] ??= $input['min_order'] ?? 0;
        $input['code'] = strtoupper(trim($input['code'] ?? ''));
        $rules = [
            'code' => ['required', 'regex:/^[A-Z0-9_-]{1,50}$/'],
            'discount_kind' => 'required|in:Product,Order,BuyXGetY,Shipping',
            'discount_type' => 'required|in:Percentage,Fixed,Free',
            'value' => 'required|numeric|min:0|max:100000000',
            'eligibility' => 'required|in:All,Customers,Account type',
            'customer_ids' => 'nullable|array', 'customer_ids.*' => 'uuid',
            'account_type' => 'nullable|in:Individual,Business',
            'scope' => 'required|in:All,Products,Categories',
            'product_ids' => 'nullable|array', 'product_ids.*' => 'uuid',
            'category_ids' => 'nullable|array', 'category_ids.*' => 'uuid',
            'minimum_type' => 'required|in:None,Amount,Quantity',
            'minimum_amount' => 'nullable|numeric|min:0|max:100000000',
            'minimum_quantity' => 'nullable|integer|min:1|max:10000',
            'usage_limit' => 'nullable|integer|min:1',
            'once_per_customer' => 'nullable|boolean',
            'starts_at' => 'nullable|date', 'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'buy_quantity' => 'required_if:discount_kind,BuyXGetY|nullable|integer|min:1|max:10000',
            'get_quantity' => 'required_if:discount_kind,BuyXGetY|nullable|integer|min:1|max:10000',
            'get_scope' => 'required_if:discount_kind,BuyXGetY|nullable|in:All,Products,Categories',
            'get_product_ids' => 'nullable|array', 'get_product_ids.*' => 'uuid',
            'get_category_ids' => 'nullable|array', 'get_category_ids.*' => 'uuid',
            'max_applications' => 'nullable|integer|min:1|max:10000',
            'max_shipping' => 'nullable|numeric|min:0',
        ];
        $data = Validator::make($input, $rules)->validate();
        if (Record::where('resource', 'coupons')->where('id', '!=', $old?->id ?? '')->get()->contains(fn ($r) => strtoupper($r->data['code']) === $data['code'])) {
            $this->fail('This discount code already exists.');
        }
        if ($data['discount_kind'] !== 'Shipping' && ($data['discount_type'] !== 'Free' && $data['value'] <= 0)) {
            $this->fail('Enter a discount greater than zero.');
        }
        if ($data['discount_type'] === 'Percentage' && $data['value'] > 100) {
            $this->fail('Percentage discounts cannot exceed 100%.');
        }
        if ($data['discount_type'] === 'Free' && ! in_array($data['discount_kind'], ['BuyXGetY', 'Shipping'], true)) {
            $this->fail('Free items are available only for Buy X get Y discounts.');
        }
        if (in_array($data['discount_kind'], ['Order', 'Shipping'], true) && $data['scope'] !== 'All') {
            $this->fail('Order and shipping discounts apply to the whole cart. Choose Amount off products for product restrictions.');
        }
        if ($data['eligibility'] === 'Customers') {
            $this->requireSelection($data, 'customer_ids', 'customers');
        }
        if ($data['eligibility'] === 'Account type' && empty($data['account_type'])) {
            $this->fail('Choose an eligible account type.');
        }
        foreach (['' => $data['scope'], 'get_' => $data['get_scope'] ?? 'All'] as $prefix => $scope) {
            if ($scope === 'Products') {
                $this->requireSelection($data, $prefix.'product_ids', 'products');
            }
            if ($scope === 'Categories') {
                $this->requireSelection($data, $prefix.'category_ids', 'categories');
            }
        }
        if ($data['minimum_type'] === 'Amount' && ($data['minimum_amount'] ?? 0) <= 0) {
            $this->fail('Enter the minimum purchase amount.');
        }
        if ($data['minimum_type'] === 'Quantity' && empty($data['minimum_quantity'])) {
            $this->fail('Enter the minimum item quantity.');
        }
        foreach (['value', 'minimum_amount', 'max_shipping'] as $key) {
            if (isset($data[$key]) && abs($data[$key] * 100 - round($data[$key] * 100)) > 0.00001) {
                $this->fail('Amounts support two decimal places.');
            }
        }
        if ($data['discount_kind'] === 'Shipping') {
            $data['discount_type'] = 'Free';
            $data['value'] = 0;
        }

        return $data + ['usage' => $old?->data['usage'] ?? 0];
    }

    private function requireSelection(array $data, string $key, string $resource): void
    {
        $ids = array_unique($data[$key] ?? []);
        if (! $ids || Record::where('resource', $resource)->whereIn('id', $ids)->get()->filter(fn ($r) => ($r->data['status'] ?? '') === 'Active')->count() !== count($ids)) {
            $this->fail('Choose active '.$resource.' for '.$key.'.');
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['discount' => $message]);
    }

    private function matches(array $line, array $discount, string $prefix = ''): bool
    {
        return match ($discount[$prefix.'scope'] ?? 'All') {
            'Products' => in_array($line['product_id'], $discount[$prefix.'product_ids'] ?? [], true),
            'Categories' => (bool) array_intersect($line['category_ids'] ?? [], $discount[$prefix.'category_ids'] ?? []),
            default => true,
        };
    }

    public function calculate(Record $coupon, array $lines, array $customer, int $shipping): array
    {
        $data = $coupon->data;
        $kind = $data['discount_kind'] ?? 'Order';
        $eligible = array_filter($lines, fn ($line) => $this->matches($line, $data));
        $eligibleTotal = array_sum(array_map(fn ($line) => (int) round($line['unit_price'] * 100) * $line['quantity'], $eligible));
        $quantity = array_sum(array_column($eligible, 'quantity'));
        $usedByCustomer = Record::where('resource', 'orders')->get()->contains(fn ($order) => ($order->data['customer_id'] ?? '') === $customer['id'] && ((($order->data['coupon_id'] ?? null) === $coupon->id) || (($order->data['coupon_code'] ?? '') === $data['code'])) && $order->data['status'] !== 'Cancelled');
        if ($data['status'] !== 'Active' || (! empty($data['starts_at']) && now()->lt($data['starts_at'])) || (! empty($data['ends_at']) && now()->gt(Carbon::parse($data['ends_at'])->endOfDay())) || (! empty($data['usage_limit']) && ($data['usage'] ?? 0) >= $data['usage_limit']) || (! empty($data['once_per_customer']) && $usedByCustomer)) {
            $this->fail('This discount is inactive, expired or its usage limit has been reached.');
        }
        if (($data['eligibility'] ?? 'All') === 'Customers' && ! in_array($customer['id'], $data['customer_ids'] ?? [], true)) {
            $this->fail('This customer is not eligible for this discount.');
        }
        if (($data['eligibility'] ?? 'All') === 'Account type' && ($customer['account_type'] ?? 'Individual') !== ($data['account_type'] ?? '')) {
            $this->fail('This account type is not eligible for this discount.');
        }
        $minimumType = $data['minimum_type'] ?? 'Amount';
        if (($minimumType === 'Amount' && $eligibleTotal < (int) round(($data['minimum_amount'] ?? $data['min_order'] ?? 0) * 100)) || ($minimumType === 'Quantity' && $quantity < ($data['minimum_quantity'] ?? 1)) || ! $quantity) {
            $this->fail('The cart does not meet this discount’s purchase requirements.');
        }
        if ($kind === 'Shipping') {
            if (isset($data['max_shipping']) && $shipping > (int) round($data['max_shipping'] * 100)) {
                $this->fail('The delivery charge exceeds this discount’s limit.');
            }

            return ['discount' => 0, 'shipping_discount' => $shipping];
        }
        if ($kind !== 'BuyXGetY') {
            $amount = $data['discount_type'] === 'Percentage' ? (int) round($eligibleTotal * $data['value'] / 100) : (int) round($data['value'] * 100);

            return ['discount' => min($amount, $eligibleTotal), 'shipping_discount' => 0];
        }
        $remaining = array_column($lines, 'quantity', 'product_id');
        $buy = array_values($eligible);
        $get = array_values(array_filter($lines, fn ($line) => $this->matches($line, $data, 'get_')));
        usort($buy, fn ($a, $b) => $b['unit_price'] <=> $a['unit_price']);
        usort($get, fn ($a, $b) => $a['unit_price'] <=> $b['unit_price']);
        $discount = 0;
        for ($application = 0; $application < ($data['max_applications'] ?? 1); $application++) {
            $attempt = $remaining;
            $needed = $data['buy_quantity'];
            foreach ($buy as $line) {
                $taken = min($needed, $attempt[$line['product_id']]);
                $attempt[$line['product_id']] -= $taken;
                $needed -= $taken;
            }
            if ($needed) {
                break;
            }
            $needed = $data['get_quantity'];
            $reward = 0;
            foreach ($get as $line) {
                $taken = min($needed, $attempt[$line['product_id']]);
                $attempt[$line['product_id']] -= $taken;
                $needed -= $taken;
                $price = (int) round($line['unit_price'] * 100);
                $reward += $taken * match ($data['discount_type']) {
                    'Free' => $price, 'Percentage' => (int) round($price * $data['value'] / 100), default => min($price, (int) round($data['value'] * 100))
                };
            }
            if ($needed) {
                break;
            }
            $remaining = $attempt;
            $discount += $reward;
        }
        if (! $discount) {
            $this->fail('Add the required qualifying and reward items to the cart.');
        }

        return ['discount' => $discount, 'shipping_discount' => 0];
    }
}
