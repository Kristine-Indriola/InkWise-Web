<?php

namespace App\Http\Controllers\Owner;

use App\Models\Staff;
use Illuminate\Http\Request;
use App\Mail\AccountApproved;
use App\Models\UserVerification;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Notifications\StaffApprovedNotification;
use Illuminate\Support\Facades\Hash; // ✅ ADD THIS

class OwnerController extends Controller
{
    // Owner home
    public function index()
    {
        // Approved staff
        $approvedStaff = Staff::with('user')
            ->where('status', 'approved')
            ->get();

        // Pending staff
        $pendingStaff = Staff::with('user')
            ->where('status', 'pending')
            ->get();

            
        return view('owner.staff.index', compact('approvedStaff', 'pendingStaff'));
    }

    // Show all staff (approved + pending)
    public function staffIndex()
    {
        $approvedStaff = Staff::with('user')->where('status', 'approved')->get();
        $pendingStaff  = Staff::with('user')->where('status', 'pending')->get();

        return view('owner.staff.index', compact('approvedStaff', 'pendingStaff'));
    }

    // Approve staff
    public function approveStaff($staff_id)
{
    $staff = Staff::with('user')->findOrFail($staff_id);

    if ($staff->status !== 'pending') {
        return back()->with('error', 'Staff account is not pending.');
    }

    // ✅ Check if this user has a verified record
    $verification = UserVerification::where('user_id', $staff->user->id)->first();

    if (!$staff->user->verification || is_null($staff->user->verification->verified_at)) {
    return back()->with('error', '❌ Cannot approve. Email not verified.');
}


    // Approve staff
    $staff->update(['status' => 'approved']);
    $staff->user->update(['status' => 'active']);

    // Send email
    if ($staff->user && $staff->user->email) {
        Mail::to($staff->user->email)->send(new AccountApproved($staff->user));
    }

     $admin = User::where('role', 'admin')->first(); // adjust to your admin role setup
    if ($admin) {
        $admin->notify(new StaffApprovedNotification($staff));
    }

    return redirect()->route('owner.staff.index', ['pending' => 'open'])
                     ->with('success', '✅ Staff Approved Successfully!');
}


    // Reject staff
    public function rejectStaff($staff_id)
    {
        $staff = Staff::with('user')->findOrFail($staff_id);

        if ($staff->status !== 'pending') {
            return back()->with('error', 'Staff account is not pending.');
        }

        $staff->update(['status' => 'rejected']);

        if ($staff->user) {
            $staff->user->update(['status' => 'inactive']);
        }

        return back()->with('error', 'Staff rejected.');
    }

    public function show()
    {
        $owner = Auth::user(); // current logged-in owner
        return view('owner.profile.show', compact('owner'));
    }

    public function edit()
    {
        $owner = Auth::user();
        return view('owner.profile.edit', compact('owner'));
    }

    public function update(Request $request)
    {
        $owner = Auth::user();

        // ✅ Validation
        $request->validate([
            'email'          => 'required|email|unique:users,email,' . $owner->user_id . ',user_id',
            'first_name'     => 'required|string|max:100',
            'middle_name'    => 'nullable|string|max:100',
            'last_name'      => 'required|string|max:100',
            'contact_number' => 'required|string|max:20',
            'password'       => 'nullable|min:6|confirmed',
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
            $updateData['password'] = Hash::make($request->password); // ✅ Fix
        }
        $owner->update($updateData);

        // ✅ Update staff table
        if ($owner->staff) {
            $owner->staff->update([
                'first_name'     => $request->first_name,
                'middle_name'    => $request->middle_name,
                'last_name'      => $request->last_name,
                'contact_number' => $request->contact_number,
            ]);
        }

        // ✅ Update or create address table
        if ($owner->address) {
            $owner->address->update([
                'street'      => $request->street,
                'barangay'    => $request->barangay,
                'city'        => $request->city,
                'province'    => $request->province,
                'postal_code' => $request->postal_code,
                'country'     => $request->country,
            ]);
        } else {
            $owner->address()->create([
                'street'      => $request->street,
                'barangay'    => $request->barangay,
                'city'        => $request->city,
                'province'    => $request->province,
                'postal_code' => $request->postal_code,
                'country'     => $request->country,
            ]);
        }

        return redirect()->route('owner.profile.show')
                         ->with('success', 'Profile updated successfully.');
    }
}