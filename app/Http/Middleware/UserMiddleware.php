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
        // If user is logged in and is an admin, redirect them to admin dashboard
        if (auth()->check() && auth()->user()->role === 'admin') {
            return redirect('/admin'); // name from your admin.php
        }

        // Otherwise, allow request to continue
        return $next($request);
    }
}
