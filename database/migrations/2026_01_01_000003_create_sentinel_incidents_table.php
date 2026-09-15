<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::connection(config('sentinel.storage.connection'))->create('sentinel_incidents', function(Blueprint $t){
  $t->ulid('id')->primary(); $t->string('invariant_key',120)->index(); $t->unsignedInteger('invariant_version'); $t->string('tenant_key',120)->default('__global__');
  $t->string('subject_type',120); $t->string('subject_id',120); $t->string('severity',32)->index(); $t->json('rule_keys'); $t->string('fingerprint',64)->index(); $t->string('status',32)->index();
  $t->timestamp('opened_at'); $t->timestamp('acknowledged_at')->nullable(); $t->timestamp('resolved_at')->nullable(); $t->string('acknowledged_by')->nullable(); $t->string('resolution_reason')->nullable();
  $t->ulid('first_observation_id')->nullable(); $t->ulid('latest_observation_id')->nullable(); $t->unsignedInteger('occurrence_count')->default(1); $t->json('meta')->nullable(); $t->timestamps();
  $t->index(['invariant_key','tenant_key','subject_type','subject_id'], 'sentinel_incident_subject_idx');
 }); }
 public function down(): void { Schema::connection(config('sentinel.storage.connection'))->dropIfExists('sentinel_incidents'); }
};
