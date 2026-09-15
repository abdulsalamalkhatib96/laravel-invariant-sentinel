<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection(config('sentinel.storage.connection'))->create('sentinel_states', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->string('invariant_key', 120); $t->unsignedInteger('invariant_version');
            $t->string('tenant_key', 120)->default('__global__'); $t->string('subject_type', 120); $t->string('subject_id', 120);
            $t->string('status', 32)->index(); $t->unsignedInteger('consecutive_failures')->default(0); $t->unsignedInteger('consecutive_passes')->default(0);
            $t->timestamp('first_failed_at')->nullable(); $t->timestamp('last_failed_at')->nullable(); $t->timestamp('last_evaluated_at')->nullable(); $t->timestamp('next_evaluation_at')->nullable();
            $t->ulid('active_incident_id')->nullable()->index(); $t->json('meta')->nullable(); $t->timestamps();
            $t->unique(['invariant_key','tenant_key','subject_type','subject_id'], 'sentinel_states_subject_unique');
        });
    }
    public function down(): void { Schema::connection(config('sentinel.storage.connection'))->dropIfExists('sentinel_states'); }
};
