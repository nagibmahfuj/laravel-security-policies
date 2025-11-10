<?php

namespace NagibMahfuj\LaravelSecurityPolicies\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PasswordExpiredMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }
}
