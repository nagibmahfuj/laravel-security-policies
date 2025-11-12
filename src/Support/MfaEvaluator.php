<?php

namespace NagibMahfuj\LaravelSecurityPolicies\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use NagibMahfuj\LaravelSecurityPolicies\Models\TrustedDevice;

class MfaEvaluator
{
	public static function needsMfa(Request $request, $user): bool
	{
		if (!$user) {
			return false;
		}

		$sessionPassed = $request->session()->get('mfa_passed') === true;
		$mode          = (string) config('security-policies.mfa.mode', 'trusted_only');

		$cookieName   = config('security-policies.mfa.remember_device_cookie', 'mfa_trusted_device');
		$rememberDays = (int) config('security-policies.mfa.device_remember_days', 60);
		$fingerprint  = Cookie::get($cookieName);

		$trustedOk = false;
		if ($fingerprint) {
			$trusted = TrustedDevice::where('user_id', $user->getAuthIdentifier())
				->where('device_fingerprint', $fingerprint)
				->whereNotNull('verified_at')
				->where('verified_at', '>=', Carbon::now()->subDays($rememberDays))
				->first();
			if ($trusted) {
				$trustedOk = true;
			}
		}

		$withinGrace = false;
		if ($mode === 'grace_or_trusted') {
			$graceDays   = (int) config('security-policies.mfa.grace_days_after_login', 30);
			$lastMfaCol  = config('security-policies.user_columns.last_mfa_at', 'last_mfa_at');
			$lastMfaAt   = $user->{$lastMfaCol} ?? null;
			$withinGrace = $lastMfaAt && Carbon::parse($lastMfaAt)->gte(Carbon::now()->subDays($graceDays));
		}

		return !($sessionPassed || $trustedOk || $withinGrace);
	}

	public static function redirectWhenNotNeeded(Request $request)
	{
		$target = (string) config('security-policies.mfa.redirect_when_not_needed', '/');
		return redirect()->intended($target);
	}
}
