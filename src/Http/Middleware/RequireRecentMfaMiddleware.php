<?php

namespace NagibMahfuj\LaravelSecurityPolicies\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Carbon;
use NagibMahfuj\LaravelSecurityPolicies\Models\TrustedDevice;

class RequireRecentMfaMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('security-policies.mfa.enabled', true)) {
            return $next($request);
        }

        if (!Auth::check()) {
            return $next($request);
        }

        $path = trim($request->path(), '/');
        if (preg_match('#^mfa/verify#', $path)) {
            return $next($request);
        }

        $user = Auth::user();

        $cookieName = config('security-policies.mfa.remember_device_cookie', 'mfa_trusted_device');
        $rememberDays = (int) config('security-policies.mfa.device_remember_days', 60);
        $fingerprint = Cookie::get($cookieName);
        if ($fingerprint) {
            $trusted = TrustedDevice::where('user_id', $user->getAuthIdentifier())
                ->where('device_fingerprint', $fingerprint)
                ->first();
            if ($trusted && (!$trusted->verified_at || $trusted->verified_at->isPast() === false)) {
                $trusted->last_seen_at = Carbon::now();
                $trusted->save();
                return $next($request);
            }
        }

        $graceDays = (int) config('security-policies.mfa.grace_days_after_login', 30);
        $lastMfaAt = $user->last_mfa_at ?? null;
        $needsMfa = !$lastMfaAt || Carbon::parse($lastMfaAt)->lt(Carbon::now()->subDays($graceDays));

        if ($needsMfa) {
            return redirect()->route('security.mfa.verify');
        }

        return $next($request);
    }
}
