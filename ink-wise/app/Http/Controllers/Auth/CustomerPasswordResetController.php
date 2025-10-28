<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\CustomerPasswordResetCode;
use App\Models\User;
use App\Models\UserVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class CustomerPasswordResetController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create()
    {
        return view('auth.customer.forgot-password');
    }

    /**
     * Handle an incoming password reset code request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->input('email');

        // Check if user exists
        if (!User::where('email', $email)->exists()) {
            // Don't reveal if email exists or not for security
            return back()->with('status', 'If an account with that email exists, we have sent a password reset code.');
        }

        $recentToken = UserVerification::where('email', $email)
            ->whereNull('consumed_at')
            ->where('created_at', '>', now()->subMinute())
            ->first();

        if ($recentToken) {
            return back()->withErrors(['email' => 'A reset code was just sent. Please wait before requesting a new one.']);
        }

        // Clean up old tokens for this email
        UserVerification::where('email', $email)
            ->whereNull('consumed_at')
            ->delete();

        $code = (string) random_int(100000, 999999);

        UserVerification::create([
            'email' => $email,
            'token' => Hash::make($code),
            'expires_at' => now()->addMinutes(15),
        ]);

        Log::info('Dispatching customer password reset code email.', [
            'email' => $email,
        ]);

        try {
            Mail::to($email)->send(new CustomerPasswordResetCode($code));
        } catch (\Throwable $exception) {
            Log::error('Failed to send customer password reset code email.', [
                'email' => $email,
                'exception' => $exception->getMessage(),
            ]);

            return back()->withErrors(['email' => 'Failed to send reset code. Please try again.']);
        }

        return back()->with('status', 'If an account with that email exists, we have sent a password reset code.');
    }
}