<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Staff;
use App\Models\Address; // ✅ add this
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
            'street'         => 'nullable|string|max:100',
            'city'           => 'nullable|string|max:50',
            'province'       => 'nullable|string|max:50',
        ]);

        // Create user (inactive by default)
        $user = User::create([
            'email'    => $request->email,
            'password' => Hash::make('defaultpassword'), // Admin can set default password
            'role'     => 'staff',
            'status'   => 'inactive', // inactive until owner approves
        ]);

        // Create staff record (pending by default)
        $staff = Staff::create([
            'user_id'        => $user->user_id,
            'first_name'     => $request->first_name,
            'middle_name'    => $request->middle_name,
            'last_name'      => $request->last_name,
            'contact_number' => $request->contact_number,
            'role'           => 'staff',
            'status'         => 'pending',
        ]);

        // ✅ Create address if provided
        if ($request->street || $request->city || $request->province) {
            Address::create([
                'staff_id' => $staff->staff_id,
                'street'   => $request->street,
                'city'     => $request->city,
                'province' => $request->province,
            ]);
        }

        return redirect()->route('admin.users.index')
                         ->with('success', 'Staff account created and pending approval.');
    }

    // List all staff accounts with status
    public function index(Request $request)
{
    $search = $request->input('search');

    $users = User::with(['staff.address'])
        ->where('role', '!=', 'customer')
        ->when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                // Search in users table
                $q->where('email', 'LIKE', "%{$search}%")
                  ->orWhere('user_id', 'LIKE', "%{$search}%");

                // Search in staff table
                $q->orWhereHas('staff', function ($staffQuery) use ($search) {
                    $staffQuery->where('staff_id', 'LIKE', "%{$search}%")
                        ->orWhere('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('middle_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhere('contact_number', 'LIKE', "%{$search}%")
                        ->orWhere('status', 'LIKE', "%{$search}%");
                });

                // Search in address table
                $q->orWhereHas('staff.address', function ($addressQuery) use ($search) {
                    $addressQuery->where('street', 'LIKE', "%{$search}%")
                        ->orWhere('city', 'LIKE', "%{$search}%")
                        ->orWhere('province', 'LIKE', "%{$search}%");
                });
            });
        })
        ->get();

    return view('admin.users.index', compact('users', 'search'));
}


    

    // Show edit form
    public function edit($user_id)
    {
        $user = User::with('staff.address')->findOrFail($user_id);
        return view('admin.users.edit', compact('user'));
    }

    // Update staff account
    public function update(Request $request, $user_id)
    {
        $user = User::with('staff.address')->findOrFail($user_id);

        $request->validate([
            'first_name'     => 'required|string|max:50',
            'middle_name'    => 'nullable|string|max:50',
            'last_name'      => 'required|string|max:50',
            'contact_number' => 'required|string|max:50',
            'email'          => 'required|email|unique:users,email,' . $user_id . ',user_id',
            'street'         => 'nullable|string|max:100',
            'city'           => 'nullable|string|max:50',
            'province'       => 'nullable|string|max:50',
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

            // ✅ Update or create address
            if ($user->staff->address) {
                $user->staff->address->update([
                    'street'   => $request->street,
                    'city'     => $request->city,
                    'province' => $request->province,
                ]);
            } else {
                if ($request->street || $request->city || $request->province) {
                    Address::create([
                        'staff_id' => $user->staff->staff_id,
                        'street'   => $request->street,
                        'city'     => $request->city,
                        'province' => $request->province,
                    ]);
                }
            }
        }

        return redirect()->route('admin.users.index')
                         ->with('success', 'Staff details updated successfully.');
    }

    // Delete staff account
    public function destroy($user_id)
    {
        $user = User::findOrFail($user_id);
        $user->delete(); // cascade deletes staff + address if FK setup
        return redirect()->route('admin.users.index')
                         ->with('success', 'Staff account deleted successfully.');
    }

    public function show($id)
{
    $user = User::with('staff.address')->findOrFail($id);

    return response()->json([
        'first_name' => $user->staff->first_name,
        'middle_name' => $user->staff->middle_name,
        'last_name' => $user->staff->last_name,
        'email' => $user->email,
        'contact_number' => $user->staff->contact_number,
        'address' => $user->staff->address 
            ? "{$user->staff->address->street}, {$user->staff->address->city}, {$user->staff->address->province}" 
            : null,
        'role' => $user->role,
        'status' => $user->staff->status ?? $user->status,
    ]);
}

}
