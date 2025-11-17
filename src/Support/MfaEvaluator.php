<?php

namespace NagibMahfuj\LaravelSecurityPolicies\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
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
		// If the request is an Inertia visit, respond with 409 + X-Inertia-Location
		if ($request->header('X-Inertia')) {
			return response('', 409)->header('X-Inertia-Location', url($target));
		}
		return redirect()->intended($target);
	}

	public static function enforceDeviceSessionControl(Request $request, $user)
	{
		$deviceControl = config('security-policies.mfa.device_session_control', 'multiple');

		if ($deviceControl !== 'single' || !Auth::check()) {
			return null;
		}

		$action             = config('security-policies.mfa.single_device_action', 'logout_previous');
		$currentFingerprint = static::generateDeviceFingerprint($request);

		// Get all trusted devices for this user
		$trustedDevices = TrustedDevice::where('user_id', $user->getAuthIdentifier())
			->whereNotNull('verified_at')
			->where('verified_at', '>=', Carbon::now()->subDays(config('security-policies.mfa.device_remember_days', 60)))
			->get();

		// Check if current device is already trusted
		$currentDeviceTrusted = $trustedDevices->where('device_fingerprint', $currentFingerprint)->first();

		if (!$currentDeviceTrusted && $trustedDevices->isNotEmpty()) {
			if ($action === 'prevent_new') {
				Auth::logout();
				$request->session()->invalidate();
				$request->session()->regenerateToken();

				return redirect()->route(config('security-policies.session.redirect_on_idle_to', 'login'))
					->with('error', 'You are already logged in on another device. Single device access is enabled.');
			} else {
				// Logout previous devices
				foreach ($trustedDevices as $device) {
					$device->update(['verified_at' => null]); // Invalidate previous devices
				}
			}
		}

		return null;
	}

	public static function generateDeviceFingerprint(Request $request): string
	{
		$data = [
			(string) $request->user()->getAuthIdentifier(),
			(string) $request->userAgent(),
			(string) self::getClientIpAddress(),
			(string) config('app.key'),
		];

		return hash('sha256', implode('|', $data));
	}

	public static function trackDeviceSession(Request $request, $user)
	{
		$deviceControl = config('security-policies.mfa.device_session_control', 'multiple');

		if ($deviceControl !== 'single' || !Auth::check()) {
			return;
		}

		$fingerprint = static::generateDeviceFingerprint($request);

		// Update last seen for current device
		$trustedDevice = TrustedDevice::where('user_id', $user->getAuthIdentifier())
			->where('device_fingerprint', $fingerprint)
			->first();

		if ($trustedDevice) {
			$trustedDevice->update([
				'last_seen_at' => Carbon::now(),
				'user_agent'   => $request->userAgent(),
				'ip_address'   => (string) self::getClientIpAddress(),
			]);
		}
	}

	public static function getClientIpAddress()
	{
		foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'] as $key) {
			if (array_key_exists($key, $_SERVER) === true) {
				foreach (explode(',', $_SERVER[$key]) as $ip) {
					$ip = trim($ip); // just to be safe
					if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
						return $ip;
					}
				}
			}
		}
		return request()->ip(); // it will return the server IP if the client IP is not found using this method.
	}
}
