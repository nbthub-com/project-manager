<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $role = auth()->user()->role;

        $redirects = [
            'admin'  => '/admin',
            'client' => '/client',
        ];

        if (isset($redirects[$role])) {
            $dashboard = $redirects[$role];

            // Prevent infinite redirect if already on dashboard
            if (!$request->is(ltrim($dashboard, '/').'*')) {
                return redirect($dashboard);
            }
        }

        return $next($request);
    }
}
