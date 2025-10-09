<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\CustomerEmailVerificationCode;
use App\Models\User;
use App\Models\Customer;
use App\Models\UserVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CustomerAuthController extends Controller
{
    public function showRegister()
    {
        return view('customer.register');
    }

    public function showLogin()
    {
        return view('customer.login');
    }

    public function register(Request $request)
    {
        $request->validate([
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'password'       => 'required|min:6|confirmed',
            'birthdate'      => 'required|date',
            'contact_number' => 'nullable|string|max:20',
            'middle_name'    => 'nullable|string|max:255',
            'verification_code' => 'required|string|size:6',
        ]);

        $verificationRecord = $this->ensureEmailIsVerified($request->email, $request->verification_code);

        // 1. Create the User (for authentication)
        $user = User::create([
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'customer',
        ]);

        // 2. Create the Customer profile
        Customer::create([
            'user_id'        => $user->user_id,
            'first_name'     => $request->first_name,
            'middle_name'    => $request->middle_name,
            'last_name'      => $request->last_name,
            'contact_number' => $request->contact_number,
            'birthdate'      => $request->birthdate,
        ]);

        if ($verificationRecord) {
            $verificationRecord->user_id = $user->user_id;
            $verificationRecord->save();
        }


        Auth::login($user);

        return redirect()->route('customer.dashboard');
    }

    public function sendVerificationCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->input('email');

        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'This email is already registered.',
            ]);
        }

        $recentToken = UserVerification::where('email', $email)
            ->whereNull('consumed_at')
            ->orderByDesc('created_at')
            ->first();

        if ($recentToken && $recentToken->created_at->gt(now()->subMinute())) {
            return response()->json([
                'message' => 'A verification code was just sent. Please wait before requesting a new one.',
            ], 429);
        }

        UserVerification::where('email', $email)
            ->whereNull('consumed_at')
            ->delete();

        $code = (string) random_int(100000, 999999);

        UserVerification::create([
            'email' => $email,
            'token' => Hash::make($code),
            'expires_at' => now()->addMinutes(15),
        ]);

        Log::info('Dispatching customer verification code email.', [
            'email' => $email,
        ]);

        try {
            Mail::to($email)->send(new CustomerEmailVerificationCode($code));
        } catch (\Throwable $exception) {
            Log::error('Failed to send customer verification code email.', [
                'email' => $email,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'We couldn\'t send your verification code right now. Please try again shortly.',
            ], 500);
        }

        Log::info('Customer verification code email sent successfully.', [
            'email' => $email,
        ]);

        return response()->json([
            'message' => 'Verification code sent to your email.',
        ]);
    }

    protected function ensureEmailIsVerified(string $email, string $code): ?UserVerification
    {
        $token = UserVerification::where('email', $email)
            ->whereNull('consumed_at')
            ->orderByDesc('created_at')
            ->first();

        if (!$token) {
            throw ValidationException::withMessages([
                'verification_code' => 'Please request a verification code first.',
            ]);
        }

        if ($token->isExpired()) {
            $token->consume();

            throw ValidationException::withMessages([
                'verification_code' => 'Your verification code has expired. Request a new one.',
            ]);
        }

        $token->increment('attempts');

        if ($token->attempts > 5) {
            $token->consume();

            throw ValidationException::withMessages([
                'verification_code' => 'Too many incorrect attempts. Request a new code.',
            ]);
        }

        if (! $token->checkCode($code)) {
            throw ValidationException::withMessages([
                'verification_code' => 'The verification code is incorrect.',
            ]);
        }

        $token->markVerified();

        return $token;
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            return redirect()->route('customer.dashboard');
        }

        return back()->withErrors(['email' => 'Invalid email or password']);
    }

    public function dashboard()
    {
        return view('customer.dashboard', [
            'customer' => Auth::user()?->customer, // safe access
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('dashboard'); // 👈 check if this route exists
    }

    public function uploadDesign(Request $request)
    {
        $request->validate([
            'design_file' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
        ]);

        if ($request->hasFile('design_file')) {
            $file = $request->file('design_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('customer_uploads', $filename, 'public');

            // You can save to database or session for later use
            // For now, redirect to design edit page with the uploaded image
            return redirect()->route('design.edit')->with('uploaded_image', $path);
        }

        return back()->withErrors(['design_file' => 'Upload failed.']);
    }
}
