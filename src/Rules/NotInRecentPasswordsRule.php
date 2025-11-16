<?php

namespace NagibMahfuj\LaravelSecurityPolicies\Rules;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Validation\Rule;
use NagibMahfuj\LaravelSecurityPolicies\Models\PasswordHistory;

class NotInRecentPasswordsRule implements Rule
{
	protected int $historyCount;
	protected ?string $message = null;

	public function __construct(?int $historyCount = null)
	{
		$this->historyCount = $historyCount
			?? (int) config('security-policies.password.history', 5);
	}

	public function passes($attribute, $value): bool
	{
		$user = Auth::user();

		// If no user context, skip validation
		if (!$user) {
			return true;
		}

		$password = (string) $value;

		$recent = PasswordHistory::where('user_id', $user->getAuthIdentifier())
			->latest('id')
			->limit($this->historyCount)
			->get();

		foreach ($recent as $entry) {
			if (Hash::check($password, $entry->password_hash)) {
				$this->message =
					'The :attribute was recently used. Please choose a new password.';
				return false;
			}
		}

		return true;
	}

	public function message(): string
	{
		return $this->message
			?? 'The :attribute has been used recently.';
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
