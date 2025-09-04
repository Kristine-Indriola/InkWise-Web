<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function index()
    {
        return view('admin.dashboard');
    }

    // Show profile info
    public function show()
    {
        $admin = Auth::user(); // current logged-in admin
        return view('admin.profile.show', compact('admin'));
    }

    // Show edit form
    public function edit()
    {
        $admin = Auth::user();
        return view('admin.profile.edit', compact('admin'));
    }

    // Update admin info (users + staff)
    public function update(Request $request)
    {
        $admin = Auth::user();

        // ✅ Validation
        $request->validate([
            'email'          => 'required|email|unique:users,email,' . $admin->user_id . ',user_id',
            'first_name'     => 'required|string|max:100',
            'middle_name'    => 'nullable|string|max:100',
            'last_name'      => 'required|string|max:100',
            'contact_number' => 'required|string|max:20',
            'password'       => 'nullable|min:6|confirmed', // ✅ new password rules
        ]);

        // ✅ Update users table (email + optional password)
        $updateData = ['email' => $request->email];

        if (!empty($request->password)) {
            $updateData['password'] = Hash::make($request->password);
        }

        $admin->update($updateData);

        // ✅ Update staff table (profile info)
        if ($admin->staff) {
            $admin->staff->update([
                'first_name'     => $request->first_name,
                'middle_name'    => $request->middle_name,
                'last_name'      => $request->last_name,
                'contact_number' => $request->contact_number,
            ]);
        }

        return redirect()->route('admin.profile.show')
                         ->with('success', 'Profile updated successfully.');
    }
}
