<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ensure user is logged in and is admin
        if (auth()->check() && auth()->user()->role === 'admin') {
            return $next($request);
        }

        // Otherwise abort with forbidden page
        abort(403, 'Unauthorized access.');
    }
}
