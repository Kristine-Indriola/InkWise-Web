<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required_without:first_name', 'nullable', 'string', 'max:255'],
            'first_name' => ['required_without:name', 'nullable', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required_without:name', 'nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:255'],
            'birthdate' => ['nullable', 'date'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $displayName = trim((string) ($validated['name'] ?? ''));

        $firstName = trim((string) ($validated['first_name'] ?? ''));
        $lastName = trim((string) ($validated['last_name'] ?? ''));

        if ($displayName && (!$firstName || !$lastName)) {
            $parts = preg_split('/\s+/', $displayName, -1, PREG_SPLIT_NO_EMPTY);
            $firstName = $firstName ?: ($parts[0] ?? $displayName);
            $lastName = $lastName ?: (count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : $firstName);
        }

        $firstName = $firstName ?: ($displayName ?: 'Customer');
        $lastName = $lastName ?: ($displayName ?: $firstName);
        $displayName = $displayName !== '' ? $displayName : trim($firstName . ' ' . $lastName);

        // Step 1: Create User
        $user = User::create([
            'name' => $displayName,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'customer',  // force role
            'status' => 'active',
        ]);

        // Step 2: Create Customer profile linked to User
        Customer::create([
            'user_id' => $user->user_id,
            'first_name' => $firstName,
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $lastName,
            'contact_number' => $validated['contact_number'] ?? null,
            'date_of_birth' => $validated['birthdate'] ?? null,
        ]);

        event(new Registered($user));

        // Auto-login
        Auth::login($user);

        return redirect()->route('dashboard'); // 👈 adjust route if needed
    }
}
