<?php

namespace App\Http\Controllers;

use App\Models\User;

class VerificationController extends Controller
{
    public function verify($token)
    {
        $user = User::where('email_verification_token', $token)->first();

        if (!$user) {
            return redirect('/login')->with('error', 'Invalid verification link.');
        }

        if ($user->email_verified_at) {
            return redirect('/login')->with('info', 'Your email is already verified.');
        }

        $user->email_verified_at = now();
        $user->email_verification_token = null;
        $user->save();

        return redirect('/login')->with('success', 'Your email has been verified! Waiting for owner approval.');
    }
}
