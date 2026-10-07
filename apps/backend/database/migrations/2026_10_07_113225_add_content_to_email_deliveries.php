<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_deliveries', function (Blueprint $table): void {
            $table->string('subject')->nullable();
            $table->longText('body')->nullable();
            $table->unsignedInteger('attempts')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('email_deliveries', function (Blueprint $table): void {
            $table->dropColumn(['subject', 'body', 'attempts']);
        });
    }
};
