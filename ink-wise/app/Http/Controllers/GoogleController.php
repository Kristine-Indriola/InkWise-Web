<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GoogleController extends Controller
{
    /**
     * Redirect user to Google login page
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle callback from Google
     */
    public function callback()
    {
        try {
            // Get Google user details
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Find user by google_id OR email
            $user = User::where('google_id', $googleUser->id)
                        ->orWhere('email', $googleUser->email)
                        ->first();

            // If no user exists, create one
            if (!$user) {
                $user = User::create([
                    'name'      => $googleUser->name,
                    'email'     => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'password'  => bcrypt(Str::random(16)), // secure random password
                ]);
            }

            // Log in the user
            Auth::login($user);

            // Redirect to dashboard
            return redirect()->route('dashboard')
                             ->with('success', 'Welcome back, ' . $user->name . '!');

        } catch (\Exception $e) {
            return redirect()->route('login')
                             ->with('error', 'Google login failed. Please try again.');
        }
    }
}
