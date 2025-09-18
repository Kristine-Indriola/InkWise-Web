<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = Auth::user();
        $customer = $user->customer;

        $validated = $request->validate([
            'first_name'  => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name'   => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email,' . $user->id,
            'phone'       => 'nullable|string|max:20',
            'birthdate'   => 'nullable|date',
            'gender'      => 'nullable|in:male,female,other',
            'photo'       => 'nullable|image|max:2048',
        ]);

        // Update user email if changed
        $user->email = $validated['email'];
        $user->save();

        // Update customer fields
        $customer->fill($validated);
        if ($request->hasFile('photo')) {
            $customer->photo = $request->file('photo')->store('customer_photos', 'public');
        }
        $customer->save();

        // Refresh session
        Auth::setUser($user->fresh());

        return back()->with('status', 'Profile updated successfully!');
    }

    public function edit()
    {
        return view('customerprofile.profile');
    }
}


