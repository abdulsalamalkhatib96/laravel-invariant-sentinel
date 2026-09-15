<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::connection(config('sentinel.storage.connection'))->create('sentinel_audit_logs', function(Blueprint $t){
  $t->ulid('id')->primary(); $t->string('action',120)->index(); $t->string('actor')->nullable(); $t->ulid('incident_id')->nullable()->index(); $t->json('before')->nullable(); $t->json('after')->nullable(); $t->json('context')->nullable(); $t->timestamps();
 }); }
 public function down(): void { Schema::connection(config('sentinel.storage.connection'))->dropIfExists('sentinel_audit_logs'); }
};
