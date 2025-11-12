<?php

namespace NagibMahfuj\LaravelSecurityPolicies\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use NagibMahfuj\LaravelSecurityPolicies\Support\MfaEvaluator;

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

		$user     = Auth::user();
		$needsMfa = MfaEvaluator::needsMfa($request, $user);

		// If the request is for MFA routes by name
		$isMfaRoute = $request->routeIs('security.mfa.verify') || $request->routeIs('security.mfa.verify.post') || $request->routeIs('security.mfa.resend');
		if ($isMfaRoute) {
			// Allow only when MFA is required, otherwise redirect away
			if ($needsMfa) {
				return $next($request);
			}
			return MfaEvaluator::redirectWhenNotNeeded($request);
		}

		// For non-MFA routes: enforce MFA if needed
		if ($needsMfa) {
			if ($request->header('X-Inertia')) {
				return response('', 409)->header('X-Inertia-Location', route('security.mfa.verify'));
			}
			return redirect()->route('security.mfa.verify');
		}

		return $next($request);
	}
}
