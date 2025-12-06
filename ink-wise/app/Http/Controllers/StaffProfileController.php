<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user(); // Get the authenticated user directly
        if (!$user) {
            return redirect()->route('login')->withErrors(['error' => 'Please log in to access this page.']);
        }

        $user->load('staff'); // Ensure staff relationship is loaded

        // Debug: Check if staff exists
        if (!$user->staff) {
            // Create empty staff record for the user if it doesn't exist
            $staff = new \App\Models\Staff();
            $staff->user_id = $user->user_id;
            $staff->role = $user->role ?? 'staff';
            $staff->status = 'active';
            $staff->save();

            // Reload the relationship
            $user->load('staff');
        }

        return view('staff.profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->withErrors(['error' => 'Please log in to access this page.']);
        }

        $user->load('staff');

        $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255', //|unique:users,email,' . $user->user_id . ',user_id',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'profile_pic' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB
        ]);

        // Construct full name from components
        $fullName = trim($request->first_name . ' ' . ($request->middle_name ? $request->middle_name . ' ' : '') . $request->last_name);

        $user->name = $fullName;
        $user->email = $request->email;
        $user->save();

        // Update staff table with separate name fields
        $staff = $user->staff;
        if ($staff) {
            $staff->first_name = $request->first_name;
            $staff->middle_name = $request->middle_name;
            $staff->last_name = $request->last_name;
            $staff->contact_number = $request->phone;
            $staff->address = $request->address;

            if ($request->hasFile('profile_pic')) {
                $path = $request->file('profile_pic')->store('staff_profiles', 'public');
                $staff->profile_pic = $path;
            }

            $staff->save();
        } else {
            // Create staff record if it doesn't exist
            $staff = new \App\Models\Staff();
            $staff->user_id = $user->user_id;
            $staff->first_name = $request->first_name;
            $staff->middle_name = $request->middle_name;
            $staff->last_name = $request->last_name;
            $staff->contact_number = $request->phone;
            $staff->address = $request->address;
            $staff->role = $user->role ?? 'staff';
            $staff->status = 'active';

            if ($request->hasFile('profile_pic')) {
                $path = $request->file('profile_pic')->store('staff_profiles', 'public');
                $staff->profile_pic = $path;
            }

            $staff->save();
        }

        return redirect()->route('staff.profile.edit')->with('success', 'Profile updated!');
    }
}
