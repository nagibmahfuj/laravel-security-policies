<?php

namespace NagibMahfuj\LaravelSecurityPolicies\Listeners;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use NagibMahfuj\LaravelSecurityPolicies\Models\PasswordHistory;

class OnPasswordReset
{
    public function handle(PasswordReset $event): void
    {
        $user = $event->user;
        if (! $user) {
            return;
        }

        // Store password history
        if (isset($user->password) && $user->password) {
            PasswordHistory::create([
                'user_id' => $user->getAuthIdentifier(),
                'password_hash' => $user->password,
            ]);
        }

        // Update timestamp for expiry enforcement
        $user->forceFill(['password_changed_at' => Carbon::now()])->save();
    }
}
