<?php

use App\Models\CommerceRecord;
use App\Services\CustomerPayments;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $orders = DB::table('commerce_records')->where('resource', 'orders')->get()->keyBy('id');
        foreach (DB::table('commerce_records')->where('resource', 'payments')->get() as $row) {
            $data = json_decode($row->data, true);
            $order = isset($orders[$data['order_id'] ?? '']) ? json_decode($orders[$data['order_id']]->data, true) : [];
            $data['payment_id'] = 'PAY-'.$row->id;
            $data['name'] = $data['payment_id'];
            $data['customer_id'] = $order['customer_id'] ?? null;
            $data['customer_name'] = $order['customer_name'] ?? $order['name'] ?? 'Unknown customer';
            $data['customer_email'] = $order['customer_email'] ?? '';
            $data['currency'] ??= 'KES';
            $data['refunded_amount'] ??= 0;
            $data['source'] ??= 'Legacy transaction';
            $data['status'] = $data['status'] === 'Paid' ? 'Successful' : $data['status'];
            $data['status_history'] ??= [['from' => null, 'to' => $data['status'], 'at' => $row->created_at, 'actor' => 'Legacy transaction']];
            DB::table('commerce_records')->where('id', $row->id)->update(['data' => json_encode($data), 'version' => $row->version + 1]);
        }
        $expression = DB::getDriverName() === 'sqlite' ? "CASE WHEN resource = 'payments' AND json_extract(data, '$.reference') IS NOT NULL AND json_extract(data, '$.reference') <> '' THEN json_extract(data, '$.method') || ':' || json_extract(data, '$.reference') ELSE NULL END" : "CASE WHEN resource = 'payments' AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.reference')) IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.reference')) NOT IN ('', 'null') THEN CONCAT(JSON_UNQUOTE(JSON_EXTRACT(data, '$.method')), ':', JSON_UNQUOTE(JSON_EXTRACT(data, '$.reference'))) ELSE NULL END";
        Schema::table('commerce_records', function (Blueprint $table) use ($expression) {
            $table->string('transaction_reference', 210)->nullable()->storedAs($expression);
            $table->unique('transaction_reference');
        });
        foreach (CommerceRecord::where('resource', 'orders')->get()->filter(fn ($order) => in_array($order->data['payment_status'] ?? '', ['Refunded', 'Partially refunded'])) as $order) {
            $total = CommerceRecord::where('resource', 'returns')->get()->filter(fn ($return) => ($return->data['order_id'] ?? '') === $order->id && ($return->data['refund_status'] ?? '') === 'Refunded')->sum(fn ($return) => $return->data['refund_amount']);
            app(CustomerPayments::class)->synchronizeRefunds($order, (float) $total);
        }
        Cache::forget('commerce.records.payments');
    }

    public function down(): void
    {
        Schema::table('commerce_records', function (Blueprint $table) {
            $table->dropUnique('commerce_records_transaction_reference_unique');
            $table->dropColumn('transaction_reference');
        });
    }
};
