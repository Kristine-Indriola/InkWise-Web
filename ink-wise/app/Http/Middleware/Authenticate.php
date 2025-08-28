<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo($request)
{
    if (! $request->expectsJson()) {
        if ($request->is('staff/*')) {
            return route('Staff.dashboard');  // 👈 staff login
        } elseif ($request->is('owner/*')) {
            return route('owner.login');  // 👈 owner login
        }
        return route('login'); // fallback (normal user login)
    }
}
}
