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
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $email = $request->email;
        $code = $request->code;

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

        // Verify the code
        if (!Hash::check($code, $verification->token)) {
            // Increment attempts
            $verification->increment('attempts');

            // If too many attempts, consume the token
            if ($verification->attempts >= 5) {
                $verification->consume();
                return back()->withInput($request->only('email'))
                    ->withErrors(['code' => 'Too many failed attempts. Please request a new reset code.']);
            }

            return back()->withInput($request->only('email'))
                ->withErrors(['code' => 'Invalid reset code.']);
        }

        // Code is valid, reset the password
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Consume the verification token
        $verification->consume();

        return redirect()->route('customer.login.form')->with('status', 'Password reset successfully. You can now sign in with your new password.');
    }
}