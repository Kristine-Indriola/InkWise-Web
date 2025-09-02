<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    // Show create user form
    public function create()
    {
        return view('admin.users.create');
    }

    // Store new user
    public function store(Request $request)
    {
        $request->validate([
            'first_name'   => 'required|string|max:50',
            'middle_name'  => 'nullable|string|max:50',
            'last_name'    => 'required|string|max:50',
            'contact_number' => 'required|string|max:50',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|min:6|confirmed',
            'role'         => 'required|in:owner,staff',
        ]);

        // Create user
        $user = User::create([
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
            'status'   => 'active',
        ]);

        // Create staff record
        Staff::create([
            'user_id'      => $user->user_id,
            'role'         => $request->role,
            'first_name'   => $request->first_name,
            'middle_name'  => $request->middle_name,
            'last_name'    => $request->last_name,
            'contact_number' => $request->contact_number,
        ]);

        return redirect()->route('admin.users.index')
                         ->with('success', 'User account created successfully!');
    }

    // List all users
    public function index()
    {
        $users = User::with('staff')->get(); 
        return view('admin.users.index', compact('users'));
    }

    // Show edit form
    public function edit($user_id)
    {
        $user = User::with('staff')->findOrFail($user_id);
        return view('admin.users.edit', compact('user'));
    }

    // Update user
    public function update(Request $request, $user_id)
    {
        $user = User::with('staff')->findOrFail($user_id);

        $request->validate([
            'first_name'     => 'required|string|max:50',
            'middle_name'    => 'nullable|string|max:50',
            'last_name'      => 'required|string|max:50',
            'contact_number' => 'required|string|max:50',
            'email'          => 'required|email|unique:users,email,' . $user_id . ',user_id',
            'role'           => 'required|in:owner,staff',
            'status'         => 'required|in:active,inactive',
        ]);

        // Update user
        $user->update([
            'email'  => $request->email,
            'role'   => $request->role,
            'status' => $request->status,
        ]);

        // Update staff
        if ($user->staff) {
            $user->staff->update([
                'role'           => $request->role,
                'first_name'     => $request->first_name,
                'middle_name'    => $request->middle_name,
                'last_name'      => $request->last_name,
                'contact_number' => $request->contact_number,
            ]);
        }

        return redirect()->route('admin.users.index')
                         ->with('success', 'User updated successfully!');
    }

    // Delete user
    public function destroy($user_id)
    {
        $user = User::findOrFail($user_id);
        $user->delete(); // cascade deletes staff
        return redirect()->route('admin.users.index')
                         ->with('success', 'User deleted successfully!');
    }
}
