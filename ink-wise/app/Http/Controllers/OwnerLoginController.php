<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OwnerLoginController extends Controller
{
    // ⛔ No __construct() here

    public function showLoginForm()
    {
        return view('owner.owner-login');
    }

    public function login(Request $request)
{

    $credentials = $request->validate([
        'email'    => ['required','email'],
        'password' => ['required'],
    ]);

    if (Auth::guard(name: 'owner')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return view('owner.owner-home');
    }

    return back()
        ->withErrors(['email' => 'Invalid credentials provided.'])
        ->onlyInput('email');
}


    public function logout(Request $request)
    {
        Auth::guard('owner')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('owner.login');
    }
}