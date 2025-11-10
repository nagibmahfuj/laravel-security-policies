<?php

namespace NagibMahfuj\LaravelSecurityPolicies\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class PasswordExpiredMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $expireDays = (int) config('security-policies.password.expire_days', 90);
        $redirect = config('security-policies.password.redirect_on_expired_to', 'password.request');

        if ($expireDays > 0) {
            $changedAt = $user->password_changed_at ?? null;
            if (!$changedAt || Carbon::parse($changedAt)->lt(Carbon::now()->subDays($expireDays))) {
                if ($request->route()?->getName() !== $redirect) {
                    return redirect()->route($redirect);
                }
            }
        }

        return $next($request);
    }
}
