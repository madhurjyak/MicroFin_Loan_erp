<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * Accepts variadic roles: middleware('role:admin,manager')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        if (!in_array($request->user()->role, $roles)) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
