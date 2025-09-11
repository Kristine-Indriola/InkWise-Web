<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Staff;
use App\Models\Address;
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
            'role'           => 'required|string|in:owner,admin,staff',
            'first_name'     => 'required|string|max:50',
            'middle_name'    => 'nullable|string|max:50',
            'last_name'      => 'required|string|max:50',
            'contact_number' => 'required|string|max:50',
            'email'          => 'required|email|unique:users,email',
            'street'         => 'nullable|string|max:100',
            'barangay'       => 'nullable|string|max:100',
            'city'           => 'nullable|string|max:50',
            'postal_code'    => 'nullable|string|max:20',
            'province'       => 'nullable|string|max:50',
            'country'        => 'nullable|string|max:50',
        ]);

        // Create user (inactive by default)
        $user = User::create([
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
            'status'   => 'inactive',
        ]);

        // Create staff record (pending by default)
        Staff::create([
            'user_id'        => $user->user_id,
            'first_name'     => $request->first_name,
            'middle_name'    => $request->middle_name,
            'last_name'      => $request->last_name,
            'contact_number' => $request->contact_number,
            'role'           => $request->role,
            'status'         => 'pending',
        ]);

        // ✅ Create address if provided
        if ($request->street || $request->city || $request->province) {
            Address::create([
                'user_id'     => $user->user_id,
                'street'      => $request->street,
                'barangay'    => $request->barangay,
                'city'        => $request->city,
                'province'    => $request->province,
                'postal_code' => $request->postal_code,
                'country'     => $request->country ?? 'Philippines',
            ]);
        }

        return redirect()->route('admin.users.index')
                         ->with('success', 'Staff account created and pending approval.');
    }

    // List all staff accounts with status
    public function index(Request $request)
    {
        $search = $request->input('search');

        $users = User::with(['staff','address'])
            ->where('role', '!=', 'customer')
            ->when($search, function ($query, $search) {
                $query->where('email', 'LIKE', "%{$search}%")
                      ->orWhere('role', 'LIKE', "%{$search}%") // ✅ search by role
                      ->orWhereHas('staff', function ($q) use ($search) {
                          $q->where('staff_id', 'LIKE', "%{$search}%")
                            ->orWhere('first_name', 'LIKE', "%{$search}%")
                            ->orWhere('last_name', 'LIKE', "%{$search}%")
                            ->orWhere('role', 'LIKE', "%{$search}%"); // ✅ staff role search
                      });
            })
            ->get();

        return view('admin.users.index', compact('users', 'search'));
    }

    // Show edit form
    public function edit($user_id)
    {
        $user = User::with(['staff','address'])->findOrFail($user_id);
        return view('admin.users.edit', compact('user'));
    }

    // Update staff account
    public function update(Request $request, $id)
{
    $user = User::findOrFail($id);

    $request->validate([
        'role' => 'required|in:staff,manager,owner',
        'first_name' => 'required|string|max:255',
        'middle_name' => 'nullable|string|max:255',
        'last_name' => 'required|string|max:255',
        'contact_number' => 'required|string|max:20',
        'email' => 'required|email|unique:users,email,' . $user->user_id . ',user_id',
        'status' => 'required|in:active,inactive',
        'street' => 'nullable|string|max:255',
        'barangay' => 'nullable|string|max:255',
        'city' => 'nullable|string|max:255',
        'province' => 'nullable|string|max:255',
        'postal_code' => 'nullable|string|max:20',
        'country' => 'nullable|string|max:255',
        'password' => 'nullable|string|min:8', // ✅ add this to validation
    ]);

    // ✅ Update user basic info (except password)
    $user->update([
        'role' => $request->role,
        'email' => $request->email,
        'status' => $request->status,
    ]);

    // ✅ Update password only if filled
    if ($request->filled('password')) {
        $user->update([
            'password' => Hash::make($request->password),
        ]);
    }

    // ✅ Update staff details
    $user->staff->update([
        'first_name' => $request->first_name,
        'middle_name' => $request->middle_name,
        'last_name' => $request->last_name,
        'contact_number' => $request->contact_number,
    ]);

    // ✅ Update or create address
    if ($user->address) {
        $user->address->update([
            'street' => $request->street,
            'barangay' => $request->barangay,
            'city' => $request->city,
            'province' => $request->province,
            'postal_code' => $request->postal_code,
            'country' => $request->country,
        ]);
    } else {
        $user->address()->create([
            'street' => $request->street,
            'barangay' => $request->barangay,
            'city' => $request->city,
            'province' => $request->province,
            'postal_code' => $request->postal_code,
            'country' => $request->country,
        ]);
    }

    return redirect()->route('admin.users.index')
        ->with('success', 'User updated successfully.');
}


    // Delete staff account
    public function destroy($user_id)
    {
        $user = User::findOrFail($user_id);
        $user->delete(); // cascade deletes staff + address if FK setup
        return redirect()->route('admin.users.index')
                         ->with('success', 'Staff account deleted successfully.');
    }

    // Show staff details
    public function show($id)
    {
        $user = User::with(['staff','address'])->findOrFail($id);
        return view('admin.users.show', compact('user'));
    }
}
