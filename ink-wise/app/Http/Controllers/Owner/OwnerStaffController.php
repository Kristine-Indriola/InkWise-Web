<?php

namespace App\Http\Controllers\Owner;

use App\Models\Staff;
use Illuminate\Http\Request;
use App\Mail\AccountApproved;
use App\Models\UserVerification;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Mail;
use App\Notifications\StaffApprovedNotification;

class OwnerStaffController extends Controller
{
    protected $staffLimit = 3; // ✅ Maximum approved staff allowed

    public function index()
    {
        return view('owner.owner-home');
    }

    // ✅ Single page for approved + pending staff
    public function staffIndex()
    {
        $approvedStaff = Staff::with('user')->where('status', 'approved')->get();
        $pendingStaff  = Staff::with('user')->where('status', 'pending')->get();

        return view('owner.staff.index', compact('approvedStaff', 'pendingStaff'));
    }

    // ✅ Approve staff with confirmation + limit check

public function approveStaff(Request $request, $staff_id)
{
    $staff = Staff::with('user')->findOrFail($staff_id);

    if ($staff->status !== 'pending') {
        return back()->with('error', '❌ This staff account is not pending.');
    }

    // ✅ Check if staff's email is verified
    $verification = UserVerification::where('user_id', $staff->user->id)
                                    ->whereNotNull('verified_at')
                                    ->exists();

    if (!$verification) {
        return back()->with('error', '❌ This staff cannot be approved because their email is not yet verified.');
    }

    $approvedCount = Staff::where('status', 'approved')->count();

    // ⚠️ Prevent approving if limit is reached (unless owner confirms)
    if ($approvedCount >= $this->staffLimit && !$request->boolean('confirm')) {
        return back()->with([
            'warning' => "⚠️ The approved staff limit of {$this->staffLimit} has been reached. Confirm to approve anyway.",
            'pendingStaffId' => $staff->staff_id
        ]);
    }

    // ✅ Approve staff + activate linked user
    $staff->update(['status' => 'approved']);
    $staff->user?->update(['status' => 'active']);

    // ✅ Send email only if user exists and has email
    if ($staff->user && $staff->user->email) {
        Mail::to($staff->user->email)->send(new AccountApproved($staff->user));
    }

    $admin = User::where('role', 'admin')->first(); // adjust to your admin role setup
    if ($admin) {
        $admin->notify(new StaffApprovedNotification($staff));
    }

    return back()->with('success', '✅ Staff approved successfully and notified by email.');
}


    // ✅ Reject staff
    public function rejectStaff($staff_id)
    {
        $staff = Staff::with('user')->findOrFail($staff_id);

        if ($staff->status !== 'pending') {
            return back()->with('error', '❌ This staff account is not pending.');
        }

        $staff->update(['status' => 'rejected']);
        $staff->user?->update(['status' => 'inactive']);

        return back()->with('error', '❌ Staff rejected.');
    }

    // ✅ Search staff by name or email
    public function search(Request $request)
    {
        $query = $request->input('search');

        $staff = Staff::with('user')
            ->where('first_name', 'like', "%$query%")
            ->orWhere('last_name', 'like', "%$query%")
            ->orWhereHas('user', function ($q) use ($query) {
                $q->where('email', 'like', "%$query%");
            })
            ->get();

        // ✅ Separate approved & pending staff
        $approvedStaff = $staff->where('status', 'approved');
        $pendingStaff  = $staff->where('status', 'pending');

        return view('owner.staff.index', compact('approvedStaff', 'pendingStaff'));
    }
}
