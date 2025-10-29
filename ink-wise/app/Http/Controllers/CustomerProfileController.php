<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderRating;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\PasswordChangeAttempt;
use App\Mail\PasswordChangeVerification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CustomerProfileController extends Controller
{
    // --- Profile Methods ---
    public function index()
    {
        $user = Auth::user();
        $customer = $user->customer;
        return view('customer.profile.index', compact('customer'));
    }

    public function edit()
    {
        $user = Auth::user();
        $customer = $user->customer;
        $address = $user->address;

        return view('customer.profile.update', compact('customer', 'address'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $customer = $user->customer;

        $validated = $request->validate([
            'first_name'  => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name'   => 'required|string|max:255',
            'email'       => 'required|email|max:255',
            'contact_number'       => 'nullable|string|max:255',
            'date_of_birth'   => 'nullable|date',
            'gender'      => 'nullable|in:male,female,other',
            'photo'       => 'nullable|image|max:2048',
            'remove_photo'=> 'nullable|boolean',
        ]);

        // Update user email
        $user->update(['email' => $request->email]);

        // Handle photo removal
        if ($request->boolean('remove_photo')) {
            if ($customer->photo && Storage::disk('public')->exists($customer->photo)) {
                Storage::disk('public')->delete($customer->photo);
            }
            $customer->photo = null;
        }

        // Handle new photo upload
        if ($request->hasFile('photo')) {
            $originalPhoto = $customer->photo; // Store original for debugging
            $newPhotoPath = $request->file('photo')->store('avatars', 'public');
            $customer->photo = $newPhotoPath;

            // Debug logging
            Log::info('Photo upload', [
                'user_id' => $user->id,
                'customer_id' => $customer->id,
                'original_photo' => $originalPhoto,
                'new_photo_path' => $newPhotoPath,
                'file_exists' => Storage::disk('public')->exists($newPhotoPath)
            ]);
        }

        // Update other fields
        $customer->update([
            'first_name'     => $request->first_name,
            'middle_name'    => $request->middle_name,
            'last_name'      => $request->last_name,
            'contact_number' => $request->phone,
            'date_of_birth'  => $request->birthdate,
            'gender'         => $request->gender,
            'photo'          => $customer->photo,
        ]);

        return back()->with('status', 'Profile updated successfully!');
    }

    // --- Address Methods ---
    public function addresses()
    {
        $addresses = Address::where('user_id', Auth::id())->get();
        return view('customer.profile.addresses', compact('addresses'));
    }

    public function storeAddress(Request $request)
    {
        // Accept full_name and phone as optional inputs in the form; Address model doesn't have these columns yet.
        $data = $this->validateAddress($request);

        Address::create(array_merge($data, [
            'user_id' => Auth::id(),
            'country' => 'Philippines',
        ]));

        return redirect()->route('customerprofile.addresses')->with('success', 'Address added successfully!');
    }

    public function updateAddress(Request $request, Address $address)
{
    $data = $this->validateAddress($request);

    $address->update(array_merge($data, [
        'country' => 'Philippines', // still force Philippines
    ]));

    return redirect()->route('customerprofile.addresses')->with('success', 'Address updated successfully!');
}

    public function destroyAddress(Address $address)
    {
        $address->delete();
        return redirect()->route('customerprofile.addresses')->with('success', 'Address deleted!');
    }

    // --- Shared Validation ---
    protected function validateAddress(Request $request)
{
    return $request->validate([
        'full_name' => 'nullable|string|max:255',
        'phone' => 'nullable|string|max:255',
        'region'      => 'required|string|max:255',
        'province'    => 'required|string|max:255',
        'city'        => 'required|string|max:255',
        'barangay'    => 'required|string|max:255',
        'postal_code' => 'required|string|max:20',
        'street'      => 'required|string|max:255',
        'label'       => 'required|string|max:50',
    ]);
}

    public function cancelOrder(Request $request, Order $order): RedirectResponse
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('customer.login.form');
        }

        $customer = $user->customer;
        if (!$customer) {
            abort(403);
        }

        $ownsOrder = ((int) $order->customer_id === (int) $customer->customer_id)
            || ((int) $order->user_id === (int) $user->id);

        if (!$ownsOrder) {
            abort(403);
        }

        $cancellableStatuses = ['pending', 'awaiting_payment'];

        if (!in_array($order->status, $cancellableStatuses, true)) {
            return redirect()->back()->with('error', 'Order can no longer be cancelled once it is in progress.');
        }

        $metadata = $order->metadata ?? [];
        $metadata['cancelled_by'] = 'customer';
        $metadata['cancelled_at'] = now()->toIso8601String();

        if ($request->filled('cancel_reason')) {
            $metadata['cancel_reason'] = trim((string) $request->input('cancel_reason'));
        }

        $order->update([
            'status' => 'cancelled',
            'payment_status' => 'cancelled',
            'metadata' => $metadata,
        ]);

        return redirect()->back()->with('status', 'Order cancelled successfully.');
    }

    public function confirmReceived(Request $request, Order $order): RedirectResponse
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('customer.login.form');
        }

        $customer = $user->customer;
        if (!$customer) {
            abort(403);
        }

        $ownsOrder = ((int) $order->customer_id === (int) $customer->customer_id)
            || ((int) $order->user_id === (int) $user->id);

        if (!$ownsOrder) {
            abort(403);
        }

        if ($order->status === 'completed') {
            return redirect()->back()->with('status', 'Thanks! This order is already marked as completed.');
        }

        if (!in_array($order->status, ['to_receive', 'confirmed'], true)) {
            return redirect()->back()->with('error', 'This order cannot be marked as received yet.');
        }

        $metadata = $order->metadata ?? [];
        $metadata['customer_confirmed_received_at'] = now()->toIso8601String();
        $metadata['customer_confirmed_received_by'] = $user->getAuthIdentifier();

        $order->update([
            'status' => 'completed',
            'metadata' => $metadata,
        ]);

        return redirect()->back()->with('status', 'Thank you! The order is now marked as completed.');
    }

    public function rate()
    {
        $user = Auth::user();
        $customer = $user?->customer;

        if (!$customer) {
            return view('customer.profile.purchase.rate', ['orders' => collect()]);
        }

        $orders = Order::query()
            ->with('rating')
            ->where('customer_id', $customer->getKey())
            ->where('status', 'completed')
            ->orderByDesc('updated_at')
            ->get();

        return view('customer.profile.purchase.rate', compact('orders'));
    }

    public function storeRating(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:600',
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:2048',
        ]);

        $user = Auth::user();
        $customer = $user?->customer;

        if (!$customer) {
            return redirect()->back()->withErrors(['order' => 'Unable to locate your customer record.']);
        }

        $order = Order::query()
            ->with('rating')
            ->where('id', $request->order_id)
            ->where('customer_id', $customer->getKey())
            ->where('status', 'completed')
            ->firstOrFail();

        if ($order->rating) {
            return redirect()->back()->withErrors(['order' => 'This order has already been rated.']);
        }

        $photos = [];

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $photos[] = $photo->store('ratings', 'public');
            }
        }

        DB::transaction(function () use ($order, $customer, $user, $request, $photos) {
            $rating = OrderRating::create([
                'order_id' => $order->id,
                'customer_id' => $customer->getKey(),
                'submitted_by' => $user->getAuthIdentifier(),
                'rating' => (int) $request->rating,
                'review' => $request->review,
                'photos' => $photos ?: null,
                'metadata' => null,
                'submitted_at' => now(),
            ]);

            $metadata = $order->metadata ?? [];
            $metadata['rating'] = $rating->rating;
            $metadata['review'] = $rating->review;
            $metadata['rating_photos'] = $photos;
            $metadata['rating_submitted_at'] = optional($rating->submitted_at)->toIso8601String();

            $order->update(['metadata' => $metadata]);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Thank you for your rating!',
            ]);
        }

        return redirect()->back()->with('status', 'Thank you for your rating!');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => [
                'required',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
                'different:current_password'
            ],
        ], [
            'password.min' => 'Password must be at least 8 characters long.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
            'password.different' => 'New password must be different from your current password.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        $user = Auth::user();

        // Check if email verification was completed
        if (!session('password_change_verified')) {
            return back()->withErrors(['verification' => 'Please verify your email before changing password.']);
        }

        // Check if current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Clear verification session
        session()->forget(['password_change_verified', 'verified_attempt_id']);

        return redirect()->route('customerprofile.index')->with('status', 'Password updated successfully!');
    }

    public function showChangePasswordForm()
    {
        return view('customer.profile.password-change-form');
    }

    // Email Verification Methods
    public function showEmailVerification()
    {
        return view('customer.profile.change-password.email-verification');
    }

    public function sendVerificationEmail(Request $request)
    {
        Log::info('sendVerificationEmail method called', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $request->headers->all()
        ]);

        try {
            $user = Auth::user();

            if (!$user) {
                Log::error('No authenticated user found');
                throw new \Exception('User not authenticated');
            }

            Log::info('Sending verification email for user', ['user_id' => $user->user_id, 'email' => $user->email]);

            $customer = $user->customer;
            
            if (!$customer) {
                Log::error('No customer record found for user', ['user_id' => $user->user_id]);
                // Try to find customer by different means
                $customer = \App\Models\Customer::where('user_id', $user->user_id)->first();
                if (!$customer) {
                    // For testing, create a dummy customer object
                    Log::warning('Creating dummy customer for testing');
                    $customer = (object) [
                        'first_name' => 'Test',
                        'last_name' => 'User',
                        'id' => 1
                    ];
                }
            }            // Get client information
            $ip = $request->ip();
            $userAgent = $request->userAgent();

            Log::info('Creating password change attempt', [
                'user_id' => $user->user_id,
                'ip' => $ip,
                'user_agent' => $userAgent
            ]);

            // Create password change attempt
            $attempt = PasswordChangeAttempt::create([
                'user_id' => $user->user_id,
                'token' => Str::random(64),
                'email' => $user->email,
                'attempt_details' => [
                    'ip' => $ip,
                    'device' => $this->getDeviceInfo($userAgent),
                    'location' => $this->getLocationFromIP($ip),
                    'user_agent' => $userAgent,
                ],
                'expires_at' => now()->addMinutes(15), // Token expires in 15 minutes
            ]);

            // Verify the attempt was created
            if (!$attempt) {
                Log::error('Failed to create password change attempt record');
                throw new \Exception('Failed to create password change attempt record');
            }

            Log::info('Password change attempt created', ['attempt_id' => $attempt->id]);

            // Send verification email (use smtp mailer for production)
            Log::info('Sending verification email via smtp mailer');
            try {
                \Illuminate\Support\Facades\Mail::mailer('smtp')->to($user->email)->send(new PasswordChangeVerification($attempt));
                Log::info('Verification email sent successfully via Mailable');
            } catch (\Exception $mailException) {
                Log::error('Mailable sending failed', ['error' => $mailException->getMessage()]);
                throw $mailException;
            }

            return response()->json([
                'success' => true,
                'message' => 'Verification email sent successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send verification email', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification email. Please try again.'
            ], 500);
        }
    }

    public function confirmEmail($token)
    {
        $attempt = PasswordChangeAttempt::where('token', $token)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$attempt) {
            return redirect()->route('customerprofile.change-password')
                ->with('error', 'Invalid or expired verification link.');
        }

        // Mark attempt as used
        $attempt->markAsUsed();

        // Store verification in session for the change password form
        session(['password_change_verified' => true, 'verified_attempt_id' => $attempt->id]);

        return view('customer.profile.change-password.password-change-attempt-approved', compact('attempt'));
    }

    public function showPasswordChangeConfirm()
    {
        // Check if user has verified email
        if (!session('password_change_verified')) {
            return redirect()->route('customerprofile.change-password')
                ->with('error', 'Please verify your email first.');
        }

        $attempt = PasswordChangeAttempt::find(session('verified_attempt_id'));

        return view('customer.profile.change-password.password-change-confirm', compact('attempt'));
    }

    // Helper methods
    private function getDeviceInfo($userAgent)
    {
        // Simple device detection
        if (strpos($userAgent, 'Mobile') !== false) {
            return 'Mobile Device';
        } elseif (strpos($userAgent, 'Tablet') !== false) {
            return 'Tablet';
        } else {
            return 'Desktop Computer';
        }
    }

    private function getLocationFromIP($ip)
    {
        // For demo purposes, return a placeholder
        // In production, you would use a geolocation service
        return 'Cebu City, PH';
    }
}
