<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SimpleAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $username = config('app.auth_username');
        $password = config('app.auth_password');

        // Skip if not configured
        if (empty($username) || empty($password)) {
            return $next($request);
        }

        // Check session auth
        if (session('auth_logged_in') === true) {
            return $next($request);
        }

        // Skip auth for webhook routes
        if ($request->is('webhook/*')) {
            return $next($request);
        }

        // Skip auth for login routes
        if ($request->is('login') || $request->is('login/*')) {
            return $next($request);
        }

        // Redirect to login
        return redirect()->route('login');
    }
}