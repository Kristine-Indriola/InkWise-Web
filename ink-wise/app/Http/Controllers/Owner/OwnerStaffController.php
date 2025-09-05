<?php

namespace App\Http\Controllers\Owner;

use App\Models\Staff;
use App\Http\Controllers\Controller;

class OwnerController extends Controller
{
    public function index()
    {
        return view('owner.owner-home');
    }

    // Single page for approved + pending staff
    public function staffIndex()
    {
        $approvedStaff = Staff::with('user')->where('status', 'approved')->get();
        $pendingStaff  = Staff::with('user')->where('status', 'pending')->get();

        return view('owner.staff.index', compact('approvedStaff', 'pendingStaff'));
    }

    public function approveStaff($staff_id)
    {
        $staff = Staff::with('user')->findOrFail($staff_id);

        if ($staff->status !== 'pending') {
            return back()->with('error', 'Staff account is not pending.');
        }

        $staff->update(['status' => 'approved']);
        $staff->user?->update(['status' => 'active']);

        return back()->with('success', 'Staff approved successfully.');
    }

    public function rejectStaff($staff_id)
    {
        $staff = Staff::with('user')->findOrFail($staff_id);

        if ($staff->status !== 'pending') {
            return back()->with('error', 'Staff account is not pending.');
        }

        $staff->update(['status' => 'rejected']);
        $staff->user?->update(['status' => 'inactive']);

        return back()->with('error', 'Staff rejected.');
    }
}
