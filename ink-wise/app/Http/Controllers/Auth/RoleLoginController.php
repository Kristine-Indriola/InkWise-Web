<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route as RouteFacade;

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

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            if (!in_array($user->role, ['admin', 'owner', 'staff'])) {
                Auth::logout();
                return back()->withErrors([
                    'login_error' => '❌ Your account is not authorized to log in.'
                ])->withInput();
            }

            if ($user->status === 'inactive' || $user->status !== 'active') {
                Auth::logout();
                return back()->withErrors([
                    'login_error' => '🚫 Your account is not active. Please contact the administrator.'
                ])->withInput();
            }

            $staffProfile = $user->staff;

            if ($user->role === 'staff' && $staffProfile && $staffProfile->status === 'pending') {
                Auth::logout();
                return back()->withErrors([
                    'login_error' => '⏳ Your account is still pending approval by the owner.'
                ])->withInput();
            }

            $greetingName = $staffProfile->first_name ?? $user->name ?? 'there';

            $redirectRoute = match ($user->role) {
                'admin' => 'admin.dashboard',
                'owner' => 'owner.home',
                'staff' => $staffProfile ? 'staff.dashboard' : null,
                default => null,
            };

            if ($redirectRoute && RouteFacade::has($redirectRoute)) {
                $greeting = $user->role === 'owner' ? 'Owner' : $greetingName;
                return redirect()->intended(route($redirectRoute))
                    ->with('success', '👋 Welcome back, ' . $greeting . '!');
            }

            return redirect()->intended(route('dashboard'))
                ->with('success', '👋 Welcome back, ' . $greetingName . '!');
        }

        return back()->withErrors([
            'login_error' => '❌ Invalid email or password.'
        ])->withInput();
    }


    public function logout(Request $request)
    {
        Auth::logout();
        return redirect('/login')
            ->with('success', '✅ You have been logged out successfully.');
    }
}
