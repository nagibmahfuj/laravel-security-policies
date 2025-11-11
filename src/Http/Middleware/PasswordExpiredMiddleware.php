<?php

namespace NagibMahfuj\LaravelSecurityPolicies\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use NagibMahfuj\LaravelSecurityPolicies\Models\PasswordHistory;

class PasswordExpiredMiddleware
{
	public function handle(Request $request, Closure $next)
	{
		if (!Auth::check()) {
			return $next($request);
		}

		$user       = Auth::user();
		$expireDays = (int) config('security-policies.password.expire_days', 90);
		$redirect   = config('security-policies.password.redirect_on_expired_to', 'password.request');
		$requireHistory = (bool) config('security-policies.password.require_history', false);

		if ($expireDays > 0) {
			$changedCol = config('security-policies.user_columns.password_changed_at', 'password_changed_at');
			$changedAt  = $user->{$changedCol} ?? null;
			if (!$changedAt || Carbon::parse($changedAt)->lt(Carbon::now()->subDays($expireDays))) {
				if ($request->route()?->getName() !== $redirect) {
					return redirect()->route($redirect);
				}
			}
		}

		if ($requireHistory) {
			$hasHistory = PasswordHistory::where('user_id', $user->getAuthIdentifier())->exists();
			if (!$hasHistory && $request->route()?->getName() !== $redirect) {
				return redirect()->route($redirect);
			}
		}

		return $next($request);
	}
}
