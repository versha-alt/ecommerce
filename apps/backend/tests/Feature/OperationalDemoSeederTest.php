<?php

namespace Tests\Feature;

use App\Models\CommerceRecord as Record;
use Database\Seeders\OperationalDemoSeeder;
use Database\Seeders\RealProductCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_operations_are_idempotent_and_financial_statuses_match_orders(): void
    {
        $this->seed([RealProductCatalogSeeder::class, OperationalDemoSeeder::class]);
        $this->seed(OperationalDemoSeeder::class);

        $this->assertSame(6, Record::where('resource', 'customers')->where('data->demo', true)->count());
        $this->assertSame(8, Record::where('resource', 'orders')->where('data->demo', true)->count());
        $this->assertSame(8, Record::where('resource', 'payments')->where('data->demo', true)->count());

        foreach (Record::where('resource', 'payments')->where('data->demo', true)->get() as $payment) {
            $order = Record::findOrFail($payment->data['order_id']);
            $expected = match ($payment->data['status']) {
                'Successful' => 'Paid',
                'Partially Refunded' => 'Partially refunded',
                default => $payment->data['status'],
            };
            $this->assertSame($expected, $order->data['payment_status']);
        }

        foreach (Record::where('resource', 'products')->get() as $product) {
            $expected = Record::where('resource', 'orders')->get()
                ->filter(fn (Record $order): bool => in_array($order->data['status'], ['Pending', 'Confirmed'], true))
                ->sum(fn (Record $order): int => collect($order->data['lines'])->where('product_id', $product->id)->sum('quantity'));
            $this->assertSame($expected, $product->data['reserved']);
        }
    }
}
