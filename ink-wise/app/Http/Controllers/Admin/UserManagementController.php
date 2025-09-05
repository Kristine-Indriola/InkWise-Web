<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    // Show create staff form
    public function create()
    {
        return view('admin.users.create');
    }

    // Store new staff account
    public function store(Request $request)
    {
        $request->validate([
            'first_name'     => 'required|string|max:50',
            'middle_name'    => 'nullable|string|max:50',
            'last_name'      => 'required|string|max:50',
            'contact_number' => 'required|string|max:50',
            'email'          => 'required|email|unique:users,email',
        ]);

        // Create user (inactive by default)
        $user = User::create([
            'email'    => $request->email,
            'password' => Hash::make('defaultpassword'), // Admin can set default password
            'role'     => 'staff',
            'status'   => 'inactive', // inactive until owner approves
        ]);

        // Create staff record (pending by default)
        Staff::create([
            'user_id'       => $user->user_id,
            'first_name'    => $request->first_name,
            'middle_name'   => $request->middle_name,
            'last_name'     => $request->last_name,
            'contact_number'=> $request->contact_number,
            'role'          => 'staff',
            'status'        => 'pending', // pending by default
        ]);

        return redirect()->route('admin.users.index')
                         ->with('success', 'Staff account created and pending approval.');
    }

    // List all staff accounts with status
    public function index()
    {
        $users = User::with('staff')
             ->where('role', '!=', 'customer') // admin, owner, staff
             ->get();

        return view('admin.users.index', compact('users'));
    }

    // Show edit form
    public function edit($user_id)
    {
        $user = User::with('staff')->findOrFail($user_id);
        return view('admin.users.edit', compact('user'));
    }

    // Update staff account (admin can only update details)
    public function update(Request $request, $user_id)
    {
        $user = User::with('staff')->findOrFail($user_id);

        $request->validate([
            'first_name'     => 'required|string|max:50',
            'middle_name'    => 'nullable|string|max:50',
            'last_name'      => 'required|string|max:50',
            'contact_number' => 'required|string|max:50',
            'email'          => 'required|email|unique:users,email,' . $user_id . ',user_id',
        ]);

        // Update user email only
        $user->update([
            'email' => $request->email,
        ]);

        // Update staff details
        if ($user->staff) {
            $user->staff->update([
                'first_name'     => $request->first_name,
                'middle_name'    => $request->middle_name,
                'last_name'      => $request->last_name,
                'contact_number' => $request->contact_number,
            ]);
        }

        return redirect()->route('admin.users.index')
                         ->with('success', 'Staff details updated successfully.');
    }

    // Delete staff account
    public function destroy($user_id)
    {
        $user = User::findOrFail($user_id);
        $user->delete(); // cascade deletes staff
        return redirect()->route('admin.users.index')
                         ->with('success', 'Staff account deleted successfully.');
    }
}
