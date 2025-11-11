<?php

namespace NagibMahfuj\LaravelSecurityPolicies\Listeners;

use Illuminate\Support\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use NagibMahfuj\LaravelSecurityPolicies\Models\PasswordHistory;

class OnPasswordReset
{
	public function handle(PasswordReset $event): void
	{
		$user = $event->user;
		if (!$user) {
			return;
		}

		// Store password history
		if (isset($user->password) && $user->password) {
			PasswordHistory::create([
				'user_id'       => $user->getAuthIdentifier(),
				'password_hash' => $user->password,
			]);
		}

		// Update timestamp for expiry enforcement
		$changedCol = config('security-policies.user_columns.password_changed_at', 'password_changed_at');
		$user->forceFill([$changedCol => Carbon::now()])->save();
	}
}
