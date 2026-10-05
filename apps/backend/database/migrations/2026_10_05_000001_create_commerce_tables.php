<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('users', function(Blueprint $t) { $t->string('role')->default('Admin'); $t->string('status')->default('Active'); $t->unsignedInteger('version')->default(1); });
  Schema::create('commerce_records', function(Blueprint $t) { $t->uuid('id')->primary(); $t->string('resource')->index(); $t->json('data'); $t->unsignedInteger('version')->default(1); $t->timestamps(); });
  Schema::create('admin_tokens', function(Blueprint $t) { $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete(); $t->string('token',64)->unique(); $t->timestamp('expires_at'); });
  Schema::create('audit_events', function(Blueprint $t) { $t->uuid('id')->primary(); $t->string('actor'); $t->string('action'); $t->string('resource'); $t->string('name')->nullable(); $t->json('before')->nullable(); $t->json('after')->nullable(); $t->timestamp('created_at'); });
  Schema::create('idempotency_keys', function(Blueprint $t) { $t->string('id')->primary(); $t->string('fingerprint',64); $t->json('response'); $t->timestamps(); });
 }
 public function down(): void { Schema::dropIfExists('idempotency_keys');Schema::dropIfExists('audit_events');Schema::dropIfExists('admin_tokens');Schema::dropIfExists('commerce_records');Schema::table('users',fn(Blueprint $t)=>$t->dropColumn(['role','status','version'])); }
};
