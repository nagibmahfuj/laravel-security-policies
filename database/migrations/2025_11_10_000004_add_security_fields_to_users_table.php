<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
	public function up(): void
	{
		$lastMfaCol      = config('security-policies.user_columns.last_mfa_at', 'last_mfa_at');
		$pwdChangedCol   = config('security-policies.user_columns.password_changed_at', 'password_changed_at');
		$lastActivityCol = config('security-policies.user_columns.last_activity_at', 'last_active_at');

		Schema::table('users', function (Blueprint $table) use ($lastMfaCol, $pwdChangedCol, $lastActivityCol) {
			if (!Schema::hasColumn('users', $lastMfaCol)) {
				$table->timestamp($lastMfaCol)->nullable()->after('remember_token');
			}
			if (!Schema::hasColumn('users', $pwdChangedCol)) {
				// place after last MFA column when possible
				$afterCol = Schema::hasColumn('users', $lastMfaCol) ? $lastMfaCol : 'remember_token';
				$table->timestamp($pwdChangedCol)->nullable()->after($afterCol);
			}
			if (!Schema::hasColumn('users', $lastActivityCol)) {
				$afterCol2 = Schema::hasColumn('users', $pwdChangedCol) ? $pwdChangedCol : (Schema::hasColumn('users', $lastMfaCol) ? $lastMfaCol : 'remember_token');
				$table->timestamp($lastActivityCol)->nullable()->after($afterCol2);
			}
		});
	}

	public function down(): void
	{
		$lastMfaCol      = config('security-policies.user_columns.last_mfa_at', 'last_mfa_at');
		$pwdChangedCol   = config('security-policies.user_columns.password_changed_at', 'password_changed_at');
		$lastActivityCol = config('security-policies.user_columns.last_activity_at', 'last_active_at');

		Schema::table('users', function (Blueprint $table) use ($lastMfaCol, $pwdChangedCol, $lastActivityCol) {
			$drops = [];
			if (Schema::hasColumn('users', $lastMfaCol)) {
				$drops[] = $lastMfaCol;
			}
			if (Schema::hasColumn('users', $pwdChangedCol)) {
				$drops[] = $pwdChangedCol;
			}
			if (Schema::hasColumn('users', $lastActivityCol)) {
				$drops[] = $lastActivityCol;
			}
			if (!empty($drops)) {
				$table->dropColumn($drops);
			}
		});
	}
};
