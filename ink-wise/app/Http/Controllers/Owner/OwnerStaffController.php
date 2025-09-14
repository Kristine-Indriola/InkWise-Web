<?php

namespace App\Http\Controllers\Owner;

use App\Models\Staff;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OwnerStaffController extends Controller
{
    protected $staffLimit = 3; // Maximum approved staff allowed

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

    public function approveStaff(Request $request, $staff_id)
    {
        $staff = Staff::with('user')->findOrFail($staff_id);

        if ($staff->status !== 'pending') {
            return back()->with('error', 'Staff account is not pending.');
        }

        $approvedCount = Staff::where('status', 'approved')->count();

        // If the limit is reached and confirmation is NOT given
        if ($approvedCount >= $this->staffLimit && !$request->input('confirm')) {
            return back()->with([
                'warning' => "The approved staff limit of {$this->staffLimit} has been reached. Please confirm to approve anyway.",
                'pendingStaffId' => $staff->staff_id
            ]);
        }

        // Approve the staff
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

    public function search(Request $request)
    {
        $query = $request->input('search');

        $staff = Staff::where('first_name', 'like', "%$query%")
                      ->orWhere('last_name', 'like', "%$query%")
                      ->orWhereHas('user', function($q) use ($query) {
                          $q->where('email', 'like', "%$query%");
                      })
                      ->get();

        $approvedStaff = $staff->where('status', 'approved');
        $pendingStaff  = $staff->where('status', 'pending');

        return view('owner.staff.index', compact('approvedStaff', 'pendingStaff'));
    }
}
