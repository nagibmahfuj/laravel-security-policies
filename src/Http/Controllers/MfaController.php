<?php

namespace NagibMahfuj\LaravelSecurityPolicies\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Notification;
use NagibMahfuj\LaravelSecurityPolicies\Models\MfaChallenge;
use NagibMahfuj\LaravelSecurityPolicies\Models\TrustedDevice;
use NagibMahfuj\LaravelSecurityPolicies\Support\MfaEvaluator;
use NagibMahfuj\LaravelSecurityPolicies\Notifications\EmailOtpNotification;

class MfaController extends Controller
{
	public function showVerify(Request $request)
	{
		$user        = Auth::user();
		if (!MfaEvaluator::needsMfa($request, $user)) {
			return MfaEvaluator::redirectWhenNotNeeded($request);
		}
		$challenge   = $this->issueChallengeIfNeeded($user);
		return view('security-policies::mfa.verify', [
			'email' => $user?->email,
		]);
	}

	public function verify(Request $request)
	{
		$user = Auth::user();
		if (!MfaEvaluator::needsMfa($request, $user)) {
			return MfaEvaluator::redirectWhenNotNeeded($request);
		}
		$request->validate([
			'code'            => ['required', 'string'],
			'remember_device' => ['nullable', 'boolean'],
		]);

		$user = Auth::user();
		$code = trim((string) $request->input('code'));

		$challenge = MfaChallenge::where('user_id', $user->getAuthIdentifier())
			->whereNull('consumed_at')
			->latest('id')
			->first();

		if (!$challenge) {
			return back()->withErrors(['code' => 'No active challenge. Please resend code.']);
		}

		$maxAttempts = (int) config('security-policies.mfa.max_attempts', 5);
		if ($challenge->attempts >= $maxAttempts) {
			return back()->withErrors(['code' => 'Too many attempts. Please resend a new code.']);
		}

		if ($challenge->isExpired()) {
			return back()->withErrors(['code' => 'Code expired. Please resend.']);
		}

		$challenge->attempts += 1;
		$challenge->save();

		if (hash_equals($challenge->code, $code)) {
			$challenge->consumed_at = Carbon::now();
			$challenge->save();

			// mark user as recently MFA'd using configurable column
			$lastMfaCol = config('security-policies.user_columns.last_mfa_at', 'last_mfa_at');
			$user->forceFill([$lastMfaCol => Carbon::now()])->save();

			// remember device if requested
			if ($request->boolean('remember_device')) {
				$cookieName  = config('security-policies.mfa.remember_device_cookie', 'mfa_trusted_device');
				$days        = (int) config('security-policies.mfa.device_remember_days', 60);
				$fingerprint = MfaEvaluator::generateDeviceFingerprint($request);
				TrustedDevice::updateOrCreate(
					[
						'user_id'            => $user->getAuthIdentifier(),
						'device_fingerprint' => $fingerprint,
					],
					[
						'user_agent'   => (string) $request->userAgent(),
						'ip_address'   => (string) $request->ip(),
						'verified_at'  => Carbon::now()->addDays($days),
						'last_seen_at' => Carbon::now(),
					]
				);
				Cookie::queue(cookie($cookieName, $fingerprint, $days * 24 * 60));
			}

			// mark current session as MFA-passed to avoid immediate re-prompt
			$request->session()->put('mfa_passed', true);

			// Track device session after successful MFA
			MfaEvaluator::trackDeviceSession($request, $user);

			return redirect()->intended('/');
		}

		return back()->withErrors(['code' => 'Invalid code.']);
	}

	public function resend(Request $request)
	{
		$user = Auth::user();
		if (!MfaEvaluator::needsMfa($request, $user)) {
			return MfaEvaluator::redirectWhenNotNeeded($request);
		}
		$this->issueChallenge($user, true);
		return back()->with('status', 'A new verification code has been sent.');
	}

	protected function issueChallengeIfNeeded($user): ?MfaChallenge
	{
		$existing = MfaChallenge::where('user_id', $user->getAuthIdentifier())
			->whereNull('consumed_at')
			->latest('id')
			->first();
		if ($existing && !$existing->isExpired()) {
			return $existing;
		}
		return $this->issueChallenge($user, false);
	}

	protected function issueChallenge($user, bool $forceNew = false): MfaChallenge
	{
		$otpLen = (int) config('security-policies.mfa.otp_length', 6);
		$ttlMin = (int) config('security-policies.mfa.otp_ttl_minutes', 10);

		if ($forceNew) {
			MfaChallenge::where('user_id', $user->getAuthIdentifier())
				->whereNull('consumed_at')
				->update(['consumed_at' => Carbon::now()]);
		}

		$code      = str_pad((string) random_int(0, pow(10, $otpLen) - 1), $otpLen, '0', STR_PAD_LEFT);
		$challenge = MfaChallenge::create([
			'user_id'    => $user->getAuthIdentifier(),
			'code'       => $code,
			'expires_at' => Carbon::now()->addMinutes($ttlMin),
			'attempts'   => 0,
		]);

		Notification::send($user, new EmailOtpNotification($code));
		return $challenge;
	}
}
