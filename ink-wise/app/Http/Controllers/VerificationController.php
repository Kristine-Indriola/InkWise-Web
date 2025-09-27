<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserVerification;

class VerificationController extends Controller
{
    public function verify($token)
{
    $record = UserVerification::where('token', $token)->first();

    if (!$record) {
        return redirect('/login')->with('error', 'Invalid or expired verification link.');
    }

    if ($record->verified_at) {
        return redirect('/login')->with('info', 'Your email is already verified.');
    }

    $record->verified_at = now();
    $record->save();

    $user = $record->user;
    // You can add "is_verified" field in users table if you want to track at user-level too

    return redirect('/login')->with('success', 'Your email has been verified! Waiting for owner approval.');
}
}
