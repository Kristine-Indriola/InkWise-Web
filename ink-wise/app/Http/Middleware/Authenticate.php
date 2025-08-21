<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        if (!$request->expectsJson()) {
            if ($request->routeIs('owner.*')) {
                return route('owner.login');   // unauthenticated owner → owner login
            }
            return route('owner.login');             // keep if you also have regular web login
        }
        return null;
    }
}
