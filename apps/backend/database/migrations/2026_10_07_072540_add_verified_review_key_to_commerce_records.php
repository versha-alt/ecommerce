<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commerce_records', function (Blueprint $table) {
            $expression = Schema::getConnection()->getDriverName() === 'sqlite'
                ? "CASE WHEN resource = 'reviews' AND json_extract(data, '$.verified_purchase') = 1 THEN json_extract(data, '$.customer_id') || ':' || json_extract(data, '$.product_id') ELSE NULL END"
                : "CASE WHEN resource = 'reviews' AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.verified_purchase')) = 'true' THEN CONCAT(JSON_UNQUOTE(JSON_EXTRACT(data, '$.customer_id')), ':', JSON_UNQUOTE(JSON_EXTRACT(data, '$.product_id'))) ELSE NULL END";
            $table->string('verified_review_key', 80)->nullable()->storedAs($expression)->unique();
        });
    }

    public function down(): void
    {
        Schema::table('commerce_records', function (Blueprint $table) {
            $table->dropUnique(['verified_review_key']);
            $table->dropColumn('verified_review_key');
        });
    }
};
