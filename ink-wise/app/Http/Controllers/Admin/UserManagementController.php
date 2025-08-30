<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    // Show form to create user
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
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|min:6|confirmed',
            'role'         => 'required|in:owner,staff',
        ]);

        User::create([
            'first_name'  => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name'   => $request->last_name,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'role'        => $request->role,
            'status'      => 'active', 
        ]);

       return redirect()->route('admin.users.index')->with('success', 'User account created successfully!');
    }

    // List all owners & staff
    public function index()
{
    $users = User::all(); // or paginate
    return view('admin.users.index', compact('users'));
}


    // Show edit form
    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.edit', compact('user'));
    }

    // Update user details
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'first_name'   => 'required|string|max:50',
            'middle_name'  => 'nullable|string|max:50',
            'last_name'    => 'required|string|max:50',
            'email'        => 'required|email|unique:users,email,'.$id,
            'role'         => 'required|in:owner,staff',
            'status'       => 'required|in:active,inactive',
        ]);

        $user->update([
            'first_name'  => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name'   => $request->last_name,
            'email'       => $request->email,
            'role'        => $request->role,
            'status'      => $request->status,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully!');
    }

    // Delete user
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully!');
    }
}
