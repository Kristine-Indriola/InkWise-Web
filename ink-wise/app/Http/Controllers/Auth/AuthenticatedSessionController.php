<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        // Redirect users based on their role
        switch ($user->role) {
            case 'admin':
                return redirect()->route('admin.dashboard');
            case 'owner':
                $rawName = $user->name ?? '';
                $displayName = trim($rawName) !== '' ? trim($rawName) : $user->email;

                return redirect()
                    ->route('owner.owner-home')
                    ->with('success', "Welcome back, {$displayName}!");
            case 'staff':
                return redirect()->route('staff.dashboard');
            default:
                return redirect()->route('customer.dashboard');
        }
        
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
