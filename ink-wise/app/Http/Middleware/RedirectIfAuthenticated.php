<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  ...$guards
     * @return mixed
     */

    public function handle(Request $request, Closure $next, ...$guards)
    {
        if ($request->routeIs('password.reset', 'password.store')) {
            return $next($request);
        }

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::user();

                // Redirect based on role
                switch ($user->role) {
                    case 'admin':
                        return redirect()->route('admin.dashboard');
                    case 'owner':
                        return redirect()->route('owner.owner-home');
                    case 'staff':
                        return redirect()->route('staff.dashboard');
                    case 'customer':
                        return redirect()->route('customer.dashboard');
                    default:
                        return redirect()->route('categories'); // fallback
                }
            }
        }

        return $next($request);
    }

}
