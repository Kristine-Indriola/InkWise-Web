<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    // Remove this line if you have it
    // protected $redirectTo = '/admin/dashboard';

    protected function authenticated(Request $request, $user)
{
    if ($user->role === 'admin') {
        return redirect()->route('admin.dashboard');
    } elseif ($user->role === 'owner') {
        return redirect()->route('owner.owner-home');
    } else {
        return redirect()->route('customer.dashboard');
    }
}

}
