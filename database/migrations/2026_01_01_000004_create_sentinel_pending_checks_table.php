<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::connection(config('sentinel.storage.connection'))->create('sentinel_pending_checks', function(Blueprint $t){
  $t->ulid('id')->primary(); $t->string('invariant_key',120); $t->string('tenant_key',120)->default('__global__'); $t->string('subject_type',120); $t->string('subject_id',120);
  $t->string('status',32)->default('pending')->index(); $t->timestamp('not_before')->index(); $t->timestamp('first_triggered_at'); $t->timestamp('last_triggered_at'); $t->timestamp('claimed_at')->nullable();
  $t->unsignedInteger('trigger_count')->default(1); $t->unsignedInteger('attempts')->default(0); $t->string('last_trigger',32)->nullable(); $t->text('last_error')->nullable(); $t->timestamps();
  $t->unique(['invariant_key','tenant_key','subject_type','subject_id'], 'sentinel_pending_subject_unique');
 }); }
 public function down(): void { Schema::connection(config('sentinel.storage.connection'))->dropIfExists('sentinel_pending_checks'); }
};
