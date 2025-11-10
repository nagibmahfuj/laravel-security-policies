<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('device_fingerprint');
            $table->string('user_agent')->nullable();
            $table->string('ip_hash')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->timestamps();
            $table->index(['user_id','device_fingerprint']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('trusted_devices');
    }
};
