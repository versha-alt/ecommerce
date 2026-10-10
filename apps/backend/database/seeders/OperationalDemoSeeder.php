<?php

namespace Database\Seeders;

use App\Models\CommerceRecord as Record;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class OperationalDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $products = Record::where('resource', 'products')->where('data->status', 'Active')->get()
                ->filter(fn (Record $product): bool => ($product->data['type'] ?? '') === 'Simple')
                ->take(8)->values();

            if ($products->count() < 4) {
                throw new RuntimeException('Seed the real product catalog before operational demo data.');
            }

            $customers = [
                ['10000000-0000-4000-8000-000000000001', 'Wanjiku Kamau', 'wanjiku.kamau@example.test', '+254 712 345 678', 'Westlands, Nairobi', 'Nairobi'],
                ['10000000-0000-4000-8000-000000000002', 'Brian Otieno', 'brian.otieno@example.test', '+254 723 456 789', 'Milimani, Kisumu', 'Kisumu'],
                ['10000000-0000-4000-8000-000000000003', 'Amina Hassan', 'amina.hassan@example.test', '+254 734 567 890', 'Nyali, Mombasa', 'Mombasa'],
                ['10000000-0000-4000-8000-000000000004', 'David Mwangi', 'david.mwangi@example.test', '+254 745 678 901', 'Section 58, Nakuru', 'Nakuru'],
                ['10000000-0000-4000-8000-000000000005', 'Faith Chebet', 'faith.chebet@example.test', '+254 756 789 012', 'Elgon View, Eldoret', 'Uasin Gishu'],
                ['10000000-0000-4000-8000-000000000006', 'James Njoroge', 'james.njoroge@example.test', '+254 767 890 123', 'Thika Road, Kiambu', 'Kiambu'],
            ];

            foreach ($customers as [$id, $name, $email, $phone, $address, $county]) {
                $this->record($id, 'customers', [
                    'name' => $name, 'email' => $email, 'phone' => $phone, 'address' => $address,
                    'county' => $county, 'account_type' => 'Individual', 'status' => 'Active',
                    'marketing_consent' => true, 'password_hash' => Hash::make('CustomerDemo!2026'),
                    'addresses' => [['address' => $address, 'town' => explode(',', $address)[0], 'county_code' => $this->countyCode($county), 'phone' => $phone, 'is_default' => true]],
                    'wishlist' => [], 'demo' => true,
                ], Carbon::now()->subDays(75 - array_search($id, array_column($customers, 0)) * 8));
            }

            $orders = [
                ['20000000-0000-4000-8000-000000000001', 'ORD-LKV-1048', 0, 'Pending', 'Pending', 'Reserved', 0, 1, null],
                ['20000000-0000-4000-8000-000000000002', 'ORD-LKV-1047', 1, 'Confirmed', 'Paid', 'Reserved', 1, 1, null],
                ['20000000-0000-4000-8000-000000000003', 'ORD-LKV-1046', 2, 'Dispatched', 'Paid', 'Dispatched', 2, 2, 'LKV-KE-582104'],
                ['20000000-0000-4000-8000-000000000004', 'ORD-LKV-1045', 3, 'Delivered', 'Paid', 'Delivered', 3, 1, 'LKV-KE-581992'],
                ['20000000-0000-4000-8000-000000000005', 'ORD-LKV-1044', 4, 'Cancelled', 'Failed', 'Unfulfilled', 4, 1, null],
                ['20000000-0000-4000-8000-000000000006', 'ORD-LKV-1043', 5, 'Delivered', 'Refunded', 'Delivered', 5, 1, 'LKV-KE-580731'],
                ['20000000-0000-4000-8000-000000000007', 'ORD-LKV-1042', 0, 'Delivered', 'Partially refunded', 'Delivered', 6, 2, 'LKV-KE-579884'],
                ['20000000-0000-4000-8000-000000000008', 'ORD-LKV-1041', 1, 'Delivered', 'Paid', 'Delivered', 7, 1, 'LKV-KE-578445'],
            ];

            foreach ($orders as $index => [$id, $reference, $customerIndex, $status, $paymentStatus, $fulfilmentStatus, $productIndex, $quantity, $tracking]) {
                $customer = Record::findOrFail($customers[$customerIndex][0]);
                $product = $products[$productIndex % $products->count()];
                $createdAt = Carbon::now()->subDays([0, 1, 2, 5, 8, 13, 21, 34][$index])->subHours($index + 2);
                $unitPrice = (float) ($product->data['sale_price'] ?? $product->data['price']);
                $subtotal = $unitPrice * $quantity;
                $shipping = $subtotal >= 50000 ? 0 : 650;
                $total = $subtotal + $shipping;
                $history = [['from' => null, 'to' => 'Pending', 'actor' => $customer->id, 'at' => $createdAt->toISOString(), 'note' => 'Order placed on the storefront']];
                foreach (array_slice(['Confirmed', 'Dispatched', 'Delivered'], 0, array_search($status, ['Pending', 'Confirmed', 'Dispatched', 'Delivered']) ?: 0) as $step => $transition) {
                    $history[] = ['from' => $step === 0 ? 'Pending' : ['Confirmed', 'Dispatched'][$step - 1], 'to' => $transition, 'actor' => 'operations@leekav.com', 'at' => $createdAt->copy()->addHours(($step + 1) * 6)->toISOString(), 'note' => 'Demo lifecycle update'];
                }
                if ($status === 'Cancelled') {
                    $history[] = ['from' => 'Pending', 'to' => 'Cancelled', 'actor' => $customer->id, 'at' => $createdAt->copy()->addHours(2)->toISOString(), 'note' => 'Customer requested cancellation'];
                }
                $paymentHistory = [['from' => 'Unpaid', 'to' => $paymentStatus, 'actor' => 'Verified payment event', 'at' => $createdAt->copy()->addMinutes(8)->toISOString()]];

                $this->record($id, 'orders', [
                    'reference' => $reference, 'name' => $customer->data['name'], 'customer_id' => $customer->id,
                    'customer_name' => $customer->data['name'], 'customer_email' => $customer->data['email'], 'customer_phone' => $customer->data['phone'],
                    'delivery_address' => $customer->data['address'].', Kenya', 'currency' => 'KES',
                    'lines' => [['product_id' => $product->id, 'name' => $product->data['name'], 'sku' => $product->data['sku'], 'quantity' => $quantity, 'unit_price' => $unitPrice, 'total' => $subtotal, 'brand_name' => '', 'category_name' => '', 'category_ids' => $product->data['category_ids'] ?? [], 'warranty' => $product->data['warranty'] ?? '']],
                    'subtotal' => $subtotal, 'discount' => 0, 'tax_total' => round($subtotal - ($subtotal / 1.16), 2), 'tax_inclusive' => true,
                    'shipping_total' => $shipping, 'shipping_discount' => 0, 'total' => $total, 'coupon_code' => '',
                    'status' => $status, 'payment_status' => $paymentStatus, 'fulfilment_status' => $fulfilmentStatus,
                    'tracking_reference' => $tracking, 'status_history' => $history, 'payment_status_history' => $paymentHistory,
                    'notes' => 'Realistic local demonstration record.', 'source' => 'Storefront', 'demo' => true,
                ], $createdAt);

                $paymentId = sprintf('30000000-0000-4000-8000-%012d', $index + 1);
                $transactionStatus = match ($paymentStatus) {
                    'Paid' => 'Successful', 'Refunded' => 'Refunded', 'Partially refunded' => 'Partially Refunded', default => $paymentStatus,
                };
                $refundedAmount = $paymentStatus === 'Refunded' ? $total : ($paymentStatus === 'Partially refunded' ? 2500 : 0);
                $this->record($paymentId, 'payments', [
                    'name' => 'PAY-'.$reference, 'payment_id' => 'PAY-'.$reference, 'order_id' => $id, 'order_reference' => $reference,
                    'customer_id' => $customer->id, 'customer_name' => $customer->data['name'], 'customer_email' => $customer->data['email'],
                    'method' => $index % 3 === 0 ? 'COD' : 'M-Pesa', 'method_name' => $index % 3 === 0 ? 'Cash on delivery' : 'M-Pesa',
                    'currency' => 'KES', 'amount' => $total, 'status' => $transactionStatus,
                    'reference' => in_array($transactionStatus, ['Successful', 'Refunded', 'Partially Refunded'], true) ? 'QK'.str_pad((string) (820410 + $index), 8, '0', STR_PAD_LEFT) : null,
                    'refunded_amount' => $refundedAmount, 'source' => 'Customer checkout', 'processed_at' => $createdAt->copy()->addMinutes(8)->toISOString(),
                    'status_history' => [['from' => null, 'to' => $transactionStatus, 'actor' => 'Verified payment event', 'at' => $createdAt->copy()->addMinutes(8)->toISOString()]], 'demo' => true,
                ], $createdAt);
            }

            $this->record('40000000-0000-4000-8000-000000000001', 'returns', [
                'name' => 'ORD-LKV-1043', 'reference' => 'RET-LKV-301', 'order_id' => $orders[5][0], 'order_reference' => 'ORD-LKV-1043',
                'customer_id' => $customers[5][0], 'customer_name' => $customers[5][1],
                'reason' => 'Appliance arrived with visible transit damage.', 'refund_amount' => Record::findOrFail($orders[5][0])->data['total'],
                'status' => 'Received', 'refund_status' => 'Refunded', 'refund_reference' => 'RF-MPESA-1043',
                'refund_recorded_at' => Carbon::now()->subDays(10)->toISOString(), 'evidence' => 'Provider refund confirmation RF-MPESA-1043',
                'status_history' => [['from' => 'Requested', 'to' => 'Received', 'actor' => 'returns@leekav.com', 'at' => Carbon::now()->subDays(11)->toISOString()]], 'demo' => true,
            ], Carbon::now()->subDays(12));

            $reserved = [];
            foreach (Record::where('resource', 'orders')->get() as $order) {
                if (in_array($order->data['status'] ?? '', ['Pending', 'Confirmed'], true)) {
                    foreach ($order->data['lines'] ?? [] as $line) {
                        $reserved[$line['product_id']] = ($reserved[$line['product_id']] ?? 0) + (int) $line['quantity'];
                    }
                }
            }
            foreach (Record::where('resource', 'products')->get() as $product) {
                $data = $product->data;
                $data['reserved'] = $reserved[$product->id] ?? 0;
                $product->data = $data;
                $product->save();
            }
        });

        $this->command?->info('Realistic customers, orders, payments, refunds and stock reservations seeded. Demo customer password: CustomerDemo!2026');
    }

    private function record(string $id, string $resource, array $data, Carbon $createdAt): void
    {
        $record = Record::find($id) ?? new Record(['id' => $id]);
        $record->resource = $resource;
        $record->data = $data;
        $record->created_at = $createdAt;
        $record->updated_at = $createdAt;
        $record->save();
    }

    private function countyCode(string $county): string
    {
        return ['Nairobi' => '047', 'Kisumu' => '042', 'Mombasa' => '001', 'Nakuru' => '032', 'Uasin Gishu' => '027', 'Kiambu' => '022'][$county];
    }
}
