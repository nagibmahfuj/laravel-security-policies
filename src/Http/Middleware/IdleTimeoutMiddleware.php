<?php

namespace NagibMahfuj\LaravelSecurityPolicies\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class IdleTimeoutMiddleware
{
	public function handle(Request $request, Closure $next)
	{
		$timeout = (int) config('security-policies.session.idle_timeout_minutes', 30);
		if ($timeout > 0 && Auth::check()) {
			$store = (string) config('security-policies.session.last_activity_store', 'session');
			$nowTs = time();

			if ($store === 'database') {
				$user   = Auth::user();
				$col    = config('security-policies.user_columns.last_activity_at', 'last_active_at');
				$lastAt = $user->{$col} ?? null;
				$lastTs = $lastAt ? Carbon::parse($lastAt)->timestamp : $nowTs;

				if (($nowTs - $lastTs) > ($timeout * 60)) {
					Auth::logout();
					$request->session()->invalidate();
					$request->session()->regenerateToken();
					return redirect()->route(config('security-policies.session.redirect_on_idle_to', 'login'))
						->with('error', 'You have been logged out due to inactivity.');
				}

				// Throttle DB writes to at most once per minute
				if (($nowTs - $lastTs) >= 60) {
					$user->forceFill([$col => Carbon::now()])->save();
				}
			} else {
				$last = (int) $request->session()->get('last_activity_ts', $nowTs);
				if (($nowTs - $last) > ($timeout * 60)) {
					Auth::logout();
					$request->session()->invalidate();
					$request->session()->regenerateToken();
					return redirect()->route(config('security-policies.session.redirect_on_idle_to', 'login'))
						->with('error', 'You have been logged out due to inactivity.');
				}
				$request->session()->put('last_activity_ts', $nowTs);
			}
		}
		return $next($request);
	}
}
