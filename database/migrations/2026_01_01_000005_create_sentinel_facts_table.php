<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::connection(config('sentinel.storage.connection'))->create('sentinel_facts', function(Blueprint $t){
  $t->ulid('id')->primary(); $t->string('type',120)->index(); $t->string('tenant_key',120)->default('__global__'); $t->string('subject_type',120); $t->string('subject_id',120); $t->string('idempotency_key',191); $t->json('payload')->nullable(); $t->timestamp('occurred_at'); $t->timestamp('recorded_at'); $t->timestamps();
  $t->unique(['type','tenant_key','idempotency_key'], 'sentinel_fact_idempotency_unique'); $t->index(['type','tenant_key','subject_type','subject_id'], 'sentinel_fact_subject_idx');
 }); }
 public function down(): void { Schema::connection(config('sentinel.storage.connection'))->dropIfExists('sentinel_facts'); }
};
