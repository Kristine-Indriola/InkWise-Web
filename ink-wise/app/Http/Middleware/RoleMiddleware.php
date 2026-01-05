<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next, $role)
    {
        if (!Auth::check()) {
            // Redirect to appropriate login page based on expected role
            if ($role === 'customer') {
                return redirect()->route('dashboard', ['modal' => 'login'])
                    ->with('show_login_modal', true)
                    ->withErrors(['error' => '❌ Please log in to access this page.']);
            }
            return redirect('/login');
        }

        if (Auth::user()->role !== $role) {
            // Log out the user and redirect to appropriate login
            Auth::logout();
            
            if ($role === 'customer') {
                return redirect()->route('dashboard', ['modal' => 'login'])
                    ->with('show_login_modal', true)
                    ->withErrors(['error' => '❌ You must be logged in as a customer to access this page.']);
            }
            
            return redirect('/unauthorized'); // you can make a custom 403 page
        }

        return $next($request);
    }
}
