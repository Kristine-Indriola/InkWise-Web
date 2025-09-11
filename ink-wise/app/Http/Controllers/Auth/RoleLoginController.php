<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login'); // your single login blade
    }

    public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($credentials)) {
        $user = Auth::user();

         // ✅ Allow only specific roles
        if (!in_array($user->role, ['admin', 'owner', 'staff'])) {
            Auth::logout();
            return back()->withErrors([
                'login_error' => '❌ Your account is not authorized to log in.'
            ])->withInput();
        }

        // 🔴 Check account status
        if ($user->staff->status === 'pending') {
            Auth::logout();
            return back()->withErrors([
                'login_error' => '⏳ Your account is still pending approval by the owner.'
            ])->withInput();
        }
        
        
        if ($user->status === 'inactive') {
            Auth::logout();
            return back()->withErrors([
                'login_error' => '🚫 Your account has been deactivated. Please contact the administrator.'
            ])->withInput();
        }

        if ($user->status !== 'active') {
            Auth::logout();
            return back()->withErrors([
                'login_error' => '❌ Your account is not valid. Please contact support.'
            ])->withInput();
        }

        // 🟢 Redirect based on role
        switch ($user->role) {
            case 'admin':
                return redirect()->route('admin.dashboard')
                     ->with('success', '👋 Welcome back, ' . $user->staff->first_name . '!');
            case 'owner':
                return redirect()->route('owner.home')
                    ->with('success', '👋 Welcome back, ' . $user->staff->first_name . '!');
            case 'staff':
                return redirect()->route('staff.dashboard')
                    ->with('success', '👋 Welcome back, ' . $user->staff->first_name . '!');
            default:
                Auth::logout();
                return redirect()->route('login')->withErrors([
                    'login_error' => '❌ Unauthorized role.'
                ]);
        }
    }

    // 🔴 Invalid login
    return back()->withErrors([
        'login_error' => '❌ Invalid email or password.'
    ])->withInput();
}


    public function logout(Request $request)
    {
        Auth::logout();
        return redirect()->route('login')
            ->with('success', '✅ You have been logged out successfully.');
    }
}
