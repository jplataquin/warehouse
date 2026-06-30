<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->needs_password_change) {
            // Check if the current request is already for changing password or logout
            if (! $request->routeIs('password.change') &&
                ! $request->routeIs('password.change.update') &&
                ! $request->routeIs('logout')) {
                return redirect()->route('password.change')
                    ->with('error', 'You must change your password before proceeding.');
            }
        }

        return $next($request);
    }
}
