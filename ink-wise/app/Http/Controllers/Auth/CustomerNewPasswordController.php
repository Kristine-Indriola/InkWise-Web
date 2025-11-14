<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class CustomerNewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request)
    {
        return view('auth.customer.reset-password');
    }

    /**
     * Handle an incoming new password request.
     */
    public function store(Request $request)
    {
        // Check if this is a code verification request (only code submitted)
        if ($request->has('code') && !$request->has('password')) {
            $request->validate([
                'email' => 'required|email',
                'code' => 'required|string|size:6',
            ]);

            $email = $request->email;
            $code = $request->code;

            // Find the user
            $user = User::where('email', $email)->first();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'No account found with this email address.']);
            }

            // Find active verification token for this email
            $verification = UserVerification::where('email', $email)
                ->active()
                ->first();

            if (!$verification) {
                return response()->json(['success' => false, 'message' => 'Invalid or expired reset code. Please request a new one.']);
            }

            // Verify the code
            if (!Hash::check($code, $verification->token)) {
                // Increment attempts
                $verification->increment('attempts');

                // If too many attempts, consume the token
                if ($verification->attempts >= 5) {
                    $verification->consume();
                    return response()->json(['success' => false, 'message' => 'Too many failed attempts. Please request a new reset code.']);
                }

                return response()->json(['success' => false, 'message' => 'Invalid reset code.']);
            }

            // Code is valid, store it in session and return success
            session(['verified_code' => $code, 'verified_email' => $email]);
            return response()->json(['success' => true]);
        }

        // This is a password reset request (code already verified)
        $request->validate([
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $email = $request->email;
        $verifiedCode = session('verified_code');
        $verifiedEmail = session('verified_email');

        // Verify session data
        if (!$verifiedCode || $verifiedEmail !== $email) {
            return back()->withInput($request->only('email'))
                ->withErrors(['code' => 'Code verification expired. Please verify your code again.']);
        }

        // Find the user
        $user = User::where('email', $email)->first();
        if (!$user) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'No account found with this email address.']);
        }

        // Find active verification token for this email
        $verification = UserVerification::where('email', $email)
            ->active()
            ->first();

        if (!$verification) {
            return back()->withInput($request->only('email'))
                ->withErrors(['code' => 'Invalid or expired reset code. Please request a new one.']);
        }

        // Verify the stored code
        if (!Hash::check($verifiedCode, $verification->token)) {
            return back()->withInput($request->only('email'))
                ->withErrors(['code' => 'Code verification failed. Please try again.']);
        }

        // Code is valid, reset the password
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Consume the verification token
        $verification->consume();

        // Clear session data
        session()->forget(['verified_code', 'verified_email']);

        return redirect()->route('dashboard')->with('status', 'Password reset successfully. You can now sign in with your new password.');
    }
}