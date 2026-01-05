<?php

namespace App\Http\Controllers\Owner;

use App\Models\Staff;
use Illuminate\Http\Request;
use App\Mail\AccountApproved;
use App\Models\UserVerification;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Notifications\StaffApprovedNotification;
use Illuminate\Support\Facades\Hash; // ✅ ADD THIS
use Illuminate\Support\Facades\Notification;

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

    $admins = User::query()
        ->where('role', 'admin')
        ->whereNotNull('email')
        ->get();

    if ($admins->isNotEmpty()) {
        Notification::send($admins, new StaffApprovedNotification($staff));
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
        /** @var \App\Models\User $owner */
        $owner = Auth::user()->load(['staff', 'address']);

        $this->attachOwnerStaffRecord($owner);

        return view('owner.profile.show', compact('owner'));
    }

    public function edit()
    {
        /** @var \App\Models\User $owner */
        $owner = Auth::user()->load(['staff', 'address']);

        $this->attachOwnerStaffRecord($owner);

        return view('owner.profile.edit', compact('owner'));
    }

    public function update(Request $request)
    {
        /** @var \App\Models\User $owner */
        $owner = Auth::user()->load(['staff', 'address']);

        $this->attachOwnerStaffRecord($owner, $request);

        // ✅ Validation (do not validate or update email from this form)
        $request->validate([
            'first_name'     => 'required|string|max:100',
            'middle_name'    => 'nullable|string|max:100',
            'last_name'      => 'required|string|max:100',
            'contact_number' => 'required|string|max:20',
            'password'       => 'nullable|min:6|confirmed',
            'profile_pic'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'address'        => 'nullable|string|max:255',
        ]);

        // ✅ Update users table (only optional password). Do NOT change email here.
        $updateData = [];
        if (!empty($request->password)) {
            $updateData['password'] = Hash::make($request->password);
        }
        if (!empty($updateData)) {
            $owner->update($updateData);
        }

        // ✅ Update staff table (only editable owner fields — do NOT change linkage or role)
        if ($owner->staff) {
            $staffUpdate = [
                'first_name'     => $request->first_name,
                'middle_name'    => $request->middle_name,
                'last_name'      => $request->last_name,
                'contact_number' => $request->contact_number,
                'address'        => $request->address,
            ];

            if ($request->hasFile('profile_pic')) {
                $path = $request->file('profile_pic')->store('owner_profiles', 'public');
                $staffUpdate['profile_pic'] = $path;
            }

            $owner->staff->update($staffUpdate);
        }

        // ✅ Update or create address table
        if ($owner->address) {
            $owner->address->update([
                'street'      => $request->address,
                'barangay'    => null,
                'city'        => null,
                'province'    => null,
                'postal_code' => null,
                'country'     => 'Philippines',
            ]);
        } else {
            $owner->address()->create([
                'street'      => $request->address,
                'barangay'    => null,
                'city'        => null,
                'province'    => null,
                'postal_code' => null,
                'country'     => 'Philippines',
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('owner.profile.show')
                         ->with('success', 'Profile updated successfully.');
    }

    /**
     * Always bind the owner to the dedicated staff record (ID 8879) for profile data.
     */
    private function attachOwnerStaffRecord(User $owner, ?Request $request = null): void
    {
        $ownerStaffId = 8879;

        $ownerStaff = Staff::find($ownerStaffId);
        if ($ownerStaff) {
            // Never change the user_id/role linkage for this dedicated staff row.
            // Simply expose it to the owner profile views.
            $owner->setRelation('staff', $ownerStaff);
            return;
        }

        if (!$owner->staff) {
            $ownerStaff = $owner->staff()->create([
                'first_name'     => $request?->first_name ?: 'Owner',
                'last_name'      => $request?->last_name ?: 'Account',
                'role'           => 'owner',
                'contact_number' => $request?->contact_number ?: '0917-000-0000',
            ]);
            $owner->setRelation('staff', $ownerStaff);
        }
    }
}