<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffProfileController extends Controller
{
    public function edit()
    {
        $user = \App\Models\User::with('staff')->find(Auth::id()); // current logged-in staff with staff relationship
        return view('staff.profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = \App\Models\User::with('staff')->find(Auth::id());

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255', //|unique:users,email,' . $user->user_id . ',user_id',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'profile_pic' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->save();

        // Update staff table with additional fields
        $staff = $user->staff;
        if ($staff) {
            $staff->address = $request->address;

            if ($request->hasFile('profile_pic')) {
                $path = $request->file('profile_pic')->store('staff_profiles', 'public');
                $staff->profile_pic = $path;
            }

            $staff->save();
        }

        return redirect()->route('staff.profile.edit')->with('success', 'Profile updated!');
    }
}
