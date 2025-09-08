<?php

namespace App\Http\Controllers\Owner;

use App\Models\Staff;
use App\Http\Controllers\Controller;

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

        $staff->update(['status' => 'approved']);

        if ($staff->user) {
            $staff->user->update(['status' => 'active']);
        }

        return redirect()->route('owner.staff.index', ['pending' => 'open'])->with('success', 'Staff Approved Successfully!');
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
}
