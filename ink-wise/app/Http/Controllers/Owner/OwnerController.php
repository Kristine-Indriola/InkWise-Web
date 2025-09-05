<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;

class OwnerController extends Controller
{
    public function index()
    {
        return view('owner.owner-home'); // create a blade file for owner
    }

      // Show pending staff accounts
    public function pendingStaff()
    {
        $pendingStaff = Staff::with('user')
            ->where('status', 'pending')
            ->get();

        return view('owner.staff.pending', compact('pendingStaff'));
    }

    // Approve staff
    public function approveStaff($id)
    {
        $staff = Staff::findOrFail($id);
        $staff->status = 'approved';
        $staff->save();

        // Also activate the user account
        $staff->user->status = 'active';
        $staff->user->save();

        return back()->with('success', 'Staff approved successfully.');
    }

    // Reject staff
    public function rejectStaff($id)
    {
        $staff = Staff::findOrFail($id);

        // Option 1: Delete the user + staff record
        $staff->user->delete();

        // Option 2 (alternative): just mark as rejected
        // $staff->status = 'rejected';
        // $staff->save();

        return back()->with('error', 'Staff account rejected.');
    }
}


