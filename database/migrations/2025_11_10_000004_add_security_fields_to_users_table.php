<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_mfa_at')->nullable()->after('remember_token');
            $table->timestamp('password_changed_at')->nullable()->after('last_mfa_at');
        });
    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_mfa_at','password_changed_at']);
        });
    }
};
