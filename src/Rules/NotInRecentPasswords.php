<?php

namespace NagibMahfuj\LaravelSecurityPolicies\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use NagibMahfuj\LaravelSecurityPolicies\Models\PasswordHistory;

class NotInRecentPasswords implements ValidationRule
{
    public function __construct(protected ?int $historyCount = null)
    {
        $this->historyCount = $historyCount ?? (int) config('security-policies.password.history', 5);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = Auth::user();
        if (!$user) {
            return; // cannot check without user context
        }
        $password = (string) $value;
        $recent = PasswordHistory::where('user_id', $user->getAuthIdentifier())
            ->latest('id')
            ->limit($this->historyCount)
            ->get();
        foreach ($recent as $entry) {
            if (Hash::check($password, $entry->password_hash)) {
                $fail("The :attribute was recently used. Please choose a new password.");
                return;
            }
        }
    }
}
