<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerProfileController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'first_name'  => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name'   => 'required|string|max:255',
            'email'       => 'required|email|max:255',
            'phone'       => 'nullable|string|max:255',
            'birthdate'   => 'nullable|date',
            'gender'      => 'nullable|in:male,female,other',
            'photo'       => 'nullable|image|max:2048',
        ]);

        $user = Auth::user();
        $customer = $user->customer;

        // Update user email
        $user->email = $request->email;
        $user->save();

        // Update customer fields
        $customer->first_name = $request->first_name;
        $customer->middle_name = $request->middle_name;
        $customer->last_name = $request->last_name;
        $customer->contact_number = $request->phone;
        $customer->date_of_birth = $request->birthdate;
        $customer->gender = $request->gender;

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('avatars', 'public');
            $customer->photo = $path;
        }

        $customer->save();

        return back()->with('status', 'Profile updated successfully!');
    }

    public function edit()
    {
        return view('customerprofile.profile');
    }
}


