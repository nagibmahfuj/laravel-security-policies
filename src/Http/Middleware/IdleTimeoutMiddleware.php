<?php

namespace NagibMahfuj\LaravelSecurityPolicies\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IdleTimeoutMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $timeout = (int) config('security-policies.session.idle_timeout_minutes', 30);
        if ($timeout > 0 && Auth::check()) {
            $last = (int) $request->session()->get('last_activity_ts', time());
            if ((time() - $last) > ($timeout * 60)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route(config('security-policies.session.redirect_on_idle_to', 'login'));
            }
            $request->session()->put('last_activity_ts', time());
        }
        return $next($request);
    }
}
