<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function index()
    {
         $materials = Material::with('inventory')->get();
    return view('admin.dashboard', compact('materials'));
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

    // Update admin info (users + staff + address)
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
            'password'       => 'nullable|min:6|confirmed', // expects password_confirmation
            // Address validation
            'street'         => 'nullable|string|max:255',
            'barangay'       => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:100',
            'province'       => 'nullable|string|max:100',
            'postal_code'    => 'nullable|string|max:20',
            'country'        => 'nullable|string|max:100',
        ]);

        // ✅ Update users table (email + optional password)
        $updateData = ['email' => $request->email];
        if (!empty($request->password)) {
            $updateData['password'] = Hash::make($request->password);
        }
        $admin->update($updateData);

        // ✅ Update staff table
        if ($admin->staff) {
            $admin->staff->update([
                'first_name'     => $request->first_name,
                'middle_name'    => $request->middle_name,
                'last_name'      => $request->last_name,
                'contact_number' => $request->contact_number,
            ]);
        }

        // ✅ Update or create address table
        if ($admin->address) {
            $admin->address->update([
                'street'      => $request->street,
                'barangay'    => $request->barangay,
                'city'        => $request->city,
                'province'    => $request->province,
                'postal_code' => $request->postal_code,
                'country'     => $request->country,
            ]);
        } else {
            $admin->address()->create([
                'street'      => $request->street,
                'barangay'    => $request->barangay,
                'city'        => $request->city,
                'province'    => $request->province,
                'postal_code' => $request->postal_code,
                'country'     => $request->country,
            ]);
        }

        

        return redirect()->route('admin.profile.show')
                         ->with('success', 'Profile updated successfully.');
    }

    public function notifications()
{
    $notifications = auth()->user()->notifications; 
    return view('admin.notifications.index', compact('notifications'));
}

    
}
