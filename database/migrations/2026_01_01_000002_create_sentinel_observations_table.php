<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::connection(config('sentinel.storage.connection'))->create('sentinel_observations', function(Blueprint $t){
  $t->ulid('id')->primary(); $t->ulid('evaluation_id')->index(); $t->string('invariant_key',120)->index(); $t->unsignedInteger('invariant_version');
  $t->string('tenant_key',120)->default('__global__'); $t->string('subject_type',120); $t->string('subject_id',120); $t->string('status',32)->index(); $t->string('trigger',32);
  $t->unsignedInteger('duration_ms')->default(0); $t->json('checks')->nullable(); $t->json('context')->nullable(); $t->timestamp('started_at'); $t->timestamp('finished_at'); $t->timestamps();
  $t->index(['invariant_key','tenant_key','subject_type','subject_id'], 'sentinel_observation_subject_idx');
 }); }
 public function down(): void { Schema::connection(config('sentinel.storage.connection'))->dropIfExists('sentinel_observations'); }
};
