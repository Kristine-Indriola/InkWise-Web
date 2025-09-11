<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class StaffAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('Staff.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || $user->role !== 'staff') {
            return back()->withErrors([
                'email' => 'No staff account found with this email.',
            ]);
        }

        if ($user->status !== 'approved') {
            return back()->withErrors([
                'email' => 'Your account is not approved yet.',
            ]);
        }

        if (Auth::attempt($credentials)) {
            return redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'email' => 'Invalid credentials.',
        ]);
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/login');
    }
}

