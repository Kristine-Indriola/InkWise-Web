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
        $ownerCount = $this->roleLimitCount('owner');
        $adminCount = $this->roleLimitCount('admin');
        $staffCount = $this->roleLimitCount('staff');

        return view('admin.users.create', compact('ownerCount', 'adminCount', 'staffCount'));
    }

    // Store new staff account
    // Store new staff account
public function store(Request $request)
{
    $request->validate([
        'role' => 'required|string|in:owner,admin,staff',
        'first_name' => 'required|string|max:50',
        'middle_name' => 'nullable|string|max:50',
        'last_name' => 'required|string|max:50',
        'contact_number' => 'required|string|max:50',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:8',
        'street' => 'nullable|string|max:100',
        'barangay' => 'nullable|string|max:100',
        'city' => 'nullable|string|max:50',
        'postal_code' => 'nullable|string|max:20',
        'province' => 'nullable|string|max:50',
        'country' => 'nullable|string|max:50',
    ]);

    // Check for staff limit warning
    $staffWarning = null;
    if ($request->role === 'staff' && $this->roleLimitCount('staff') >= 3) {
        $staffWarning = "⚠️ Staff account limit has been reached. You are creating an extra staff account.";
    }

    // Create User
    $user = User::create([
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'role' => $request->role,
        'status' => 'inactive',
    ]);

    // Create Staff
    Staff::create([
        'user_id' => $user->user_id,
        'first_name' => $request->first_name,
        'middle_name' => $request->middle_name,
        'last_name' => $request->last_name,
        'contact_number' => $request->contact_number,
        'role' => $request->role,
        'status' => 'pending',
    ]);

    // Create Address if provided
    if($request->street || $request->city || $request->province) {
        Address::create([
            'user_id' => $user->user_id,
            'street' => $request->street,
            'barangay' => $request->barangay,
            'city' => $request->city,
            'province' => $request->province,
            'postal_code' => $request->postal_code,
            'country' => $request->country ?? 'Philippines',
        ]);
    }

    // Redirect with success + optional warning
    $message = ucfirst($request->role) . ' account created and pending approval.';
    if ($staffWarning) {
        return redirect()->route('admin.users.index')
            ->with('success', $message)
            ->with('warning', $staffWarning);
    }

    return redirect()->route('admin.users.index')
        ->with('success', $message);
}


    // Other methods (index, edit, update, destroy, show) remain mostly unchanged

    public function edit($user_id)
     { $user = User::with(['staff','address'])
        ->findOrFail($user_id); $ownerCount = $this->roleLimitCount('owner');
         $adminCount = $this->roleLimitCount('admin'); 
         $staffCount = $this->roleLimitCount('staff');
          return view('admin.users.edit', compact('user', 'ownerCount', 'adminCount', 'staffCount'));
        
    }

    public function index(Request $request) 
    { $search = $request->input('search'); 
        $users = User::with(['staff','address']) 
        ->where('role', '!=', 'customer') 
        ->when($search, fn($query, $search) => $query->where('email', 'LIKE', "%{$search}%") 
        ->orWhere('role', 'LIKE', "%{$search}%") 
        ->orWhereHas('staff', fn($q) => $q->where('staff_id', 'LIKE', "%{$search}%") 
        ->orWhere('first_name', 'LIKE', "%{$search}%") 
        ->orWhere('last_name', 'LIKE', "%{$search}%") 
        ->orWhere('role', 'LIKE', "%{$search}%") ) ) ->get(); return view('admin.users.index', compact('users', 'search')); }

    // Only remove strict role limit check from update
    public function update(Request $request, $id)
    {
        $user = User::with('staff', 'address')->findOrFail($id);

        if ($user->status === 'inactive' && $request->status === 'active') {
            return redirect()->back()
                ->withInput()
                ->with('error', '🚫 Archived accounts cannot be reactivated.');
        }

       $request->validate([
    'current_password' => 'required|string',
    'role'             => 'required|in:staff,owner,admin',
    'first_name'       => 'required|string|max:255',
    'middle_name'      => 'nullable|string|max:255',
    'last_name'        => 'required|string|max:255',
    'contact_number'   => 'required|string|max:20',
    'email'            => 'required|email|unique:users,email,' . $user->user_id . ',user_id',
    'status'           => 'required|in:active,inactive',
    'street'           => 'nullable|string|max:255',
    'barangay'         => 'nullable|string|max:255',
    'city'             => 'nullable|string|max:255',
    'province'         => 'nullable|string|max:255',
    'postal_code'      => 'nullable|string|max:20',
    'country'          => 'nullable|string|max:255',
    'password'         => 'nullable|string|min:8|confirmed',
]);

    if (!Hash::check($request->current_password, $user->password)) {
    return redirect()->back()
                     ->withInput()
                     ->with('error', '🚫 Current password is incorrect.');
}

        // Update User
       $user->update([
    'role'     => $request->role,
    'email'    => $request->email,
    'status'   => $request->status,
    'password' => $request->password ? Hash::make($request->password) : $user->password,
]);

        // Update Staff
        if ($user->staff) {
            $user->staff->update([
                'first_name'     => $request->first_name,
                'middle_name'    => $request->middle_name,
                'last_name'      => $request->last_name,
                'contact_number' => $request->contact_number,
                'role'           => $request->role,
            ]);
        }

        // Update or create Address
        if ($user->address) {
            $user->address->update([
                'street'      => $request->street,
                'barangay'    => $request->barangay,
                'city'        => $request->city,
                'province'    => $request->province,
                'postal_code' => $request->postal_code,
                'country'     => $request->country,
            ]);
        } else {
            $user->address()->create([
                'street'      => $request->street,
                'barangay'    => $request->barangay,
                'city'        => $request->city,
                'province'    => $request->province,
                'postal_code' => $request->postal_code,
                'country'     => $request->country,
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', '✅ User updated successfully.');
    }

    // Delete / archive staff account remains unchanged
    public function destroy($user_id)
    {
        $user = User::with('staff')->findOrFail($user_id);

        if ($user->role === 'owner' || $user->role === 'admin') {
            return redirect()->back()->with('error', "🚫 {$user->role} account cannot be archived.");
        }

        if ($user->role === 'staff') {
            $user->update(['status' => 'inactive']);
            if ($user->staff) {
                $user->staff->update(['status' => 'archived']);
            }
            return redirect()->route('admin.users.index')
                             ->with('success', '📦 Staff account archived successfully.');
        }

        return redirect()->back()->with('error', '⚠️ Unknown role, archiving not allowed.');
    }

    // Show staff details
    public function show($id)
    {
        $user = User::with(['staff','address'])->findOrFail($id);
        return view('admin.users.show', compact('user'));
    }

    // Helper: check if role limit is reached (excluding archived)
    private function roleLimitReached($role, $limit)
    {
        return User::where('role', $role)
            ->where('status', 'active')
            ->whereHas('staff', fn($q) => $q->notArchived())
            ->count() >= $limit;
    }

    // Helper: count active users per role (excluding archived)
    private function roleLimitCount($role)
    {
        return User::where('role', $role)
            ->where('status', 'active')
            ->whereHas('staff', fn($q) => $q->where('status', '!=', 'archived'))
            ->count();
    }
}
