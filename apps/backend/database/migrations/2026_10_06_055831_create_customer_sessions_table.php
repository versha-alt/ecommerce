<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_sessions', function (Blueprint $table): void {
            $table->string('token_hash', 64)->primary();
            $table->uuid('customer_id');
            $table->uuid('order_id')->nullable();
            $table->dateTime('expires_at')->index();
            $table->dateTime('created_at');
            $table->foreign('customer_id')->references('id')->on('commerce_records')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_sessions');
    }
};
