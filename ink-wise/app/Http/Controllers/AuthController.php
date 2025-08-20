<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // ------------------------
    // Show Register Form
    // ------------------------
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    // ------------------------
    // Handle Registration
    // ------------------------
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    // ------------------------
    // Show Login Form
    // ------------------------
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // ------------------------
    // Handle Login
    // ------------------------
    public function login(Request $request)
{
    $credentials = $request->only('email', 'password');

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        return redirect()->route('dashboard'); // ✅ redirect to dashboard
    }

    return back()->withErrors([
        'email' => 'Invalid login credentials.',
    ]);
}


    // ------------------------
    // Handle Logout
    // ------------------------
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('dashboard');
    }
}
