<?php

namespace NagibMahfuj\LaravelSecurityPolicies\Rules;

use Illuminate\Contracts\Validation\Rule;

class StrongPasswordRule implements Rule
{
	protected ?string $message = null;

	public function passes($attribute, $value): bool
	{
		$min            = (int) config('security-policies.password.min_length', 12);
		$digits         = (int) config('security-policies.password.min_digits', 1);
		$symbols        = (int) config('security-policies.password.min_symbols', 1);
		$lower          = (int) config('security-policies.password.min_lowercase', 1);
		$upper          = (int) config('security-policies.password.min_uppercase', 1);
		$allowedSymbols = (string) config(
			'security-policies.password.allowed_symbols',
			"!@#$%^&*()_+-={}[]:;'\"<>,.?/\\|~`"
		);

		$password = (string) $value;

		// Length check
		if (strlen($password) < $min) {
			$this->message = "The :attribute must be at least {$min} characters.";
			return false;
		}

		// Digits
		if ($digits > 0 && preg_match_all('/[0-9]/', $password) < $digits) {
			$this->message = "The :attribute must contain at least {$digits} digit(s).";
			return false;
		}

		// Symbols - count only allowed symbols
		$allowedClass       = preg_quote($allowedSymbols, '/');
		$allowedSymbolCount = preg_match_all('/[' . $allowedClass . ']/', $password);

		if ($symbols > 0 && $allowedSymbolCount < $symbols) {
			$this->message =
				"The :attribute must contain at least {$symbols} allowed symbol(s): {$allowedSymbols}.";
			return false;
		}

		// Disallowed symbols
		if (preg_match('/[^a-zA-Z0-9' . $allowedClass . ']/', $password)) {
			$this->message =
				"The :attribute contains symbol(s) not allowed. Allowed symbols: {$allowedSymbols}.";
			return false;
		}

		// Lowercase
		if ($lower > 0 && preg_match_all('/[a-z]/', $password) < $lower) {
			$this->message =
				"The :attribute must contain at least {$lower} lowercase letter(s).";
			return false;
		}

		// Uppercase
		if ($upper > 0 && preg_match_all('/[A-Z]/', $password) < $upper) {
			$this->message =
				"The :attribute must contain at least {$upper} uppercase letter(s).";
			return false;
		}

		return true;
	}

	public function message(): string
	{
		return $this->message ?? 'The :attribute does not meet the password policy requirements.';
	}

	/**
	 * Optional: Laravel 10+ Invokable Rule Support
	 */
	public function __invoke($attribute, $value, $fail)
	{
		if (!$this->passes($attribute, $value)) {
			$fail($this->message());
		}
	}
}
