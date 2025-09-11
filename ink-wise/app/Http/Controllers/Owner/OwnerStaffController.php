<?php

namespace App\Http\Controllers\Owner;

use App\Models\Staff;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OwnerStaffController extends Controller
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

        return back()->with('success', 'Staff Approved Successfully.');
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

    public function search(Request $request)
    {
        // Get the search query from the request
        $query = $request->input('search');
        
        // Search the staff table for matching staff (you can customize this as needed)
        $staff = Staff::where('first_name', 'like', "%$query%")
                      ->orWhere('last_name', 'like', "%$query%")
                      ->get();

        // Return a view with the results
        return view('owner.staff.staff-list', compact('staff'));

    }

}
