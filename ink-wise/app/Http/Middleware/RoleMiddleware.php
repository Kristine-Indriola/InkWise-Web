<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next, ...$roles)
    {
        $expectedRoles = $roles ?: ['customer'];

        if (!Auth::check()) {
            if (in_array('customer', $expectedRoles, true)) {
                return redirect()->route('dashboard', ['modal' => 'login'])
                    ->with('show_login_modal', true)
                    ->withErrors(['error' => '❌ Please log in to access this page.']);
            }

            return redirect('/login');
        }

        $userRole = Auth::user()->role;
        if (!in_array($userRole, $expectedRoles, true)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if (in_array('customer', $expectedRoles, true)) {
                return redirect()->route('dashboard', ['modal' => 'login'])
                    ->with('show_login_modal', true)
                    ->withErrors(['error' => '❌ You must be logged in as a customer to access this page.']);
            }

            return redirect('/unauthorized');
        }

        return $next($request);
    }
}
