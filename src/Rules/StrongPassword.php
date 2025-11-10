<?php

namespace NagibMahfuj\LaravelSecurityPolicies\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $min = (int) config('security-policies.password.min_length', 12);
        $digits = (int) config('security-policies.password.min_digits', 1);
        $symbols = (int) config('security-policies.password.min_symbols', 1);
        $lower = (int) config('security-policies.password.min_lowercase', 1);
        $upper = (int) config('security-policies.password.min_uppercase', 1);

        $password = (string) $value;
        if (strlen($password) < $min) {
            $fail("The :attribute must be at least {$min} characters.");
            return;
        }
        if ($digits > 0 && preg_match_all('/[0-9]/', $password) < $digits) {
            $fail("The :attribute must contain at least {$digits} digit(s).");
            return;
        }
        if ($symbols > 0 && preg_match_all('/[^a-zA-Z0-9]/', $password) < $symbols) {
            $fail("The :attribute must contain at least {$symbols} symbol(s).");
            return;
        }
        if ($lower > 0 && preg_match_all('/[a-z]/', $password) < $lower) {
            $fail("The :attribute must contain at least {$lower} lowercase letter(s).");
            return;
        }
        if ($upper > 0 && preg_match_all('/[A-Z]/', $password) < $upper) {
            $fail("The :attribute must contain at least {$upper} uppercase letter(s).");
            return;
        }
    }
}
