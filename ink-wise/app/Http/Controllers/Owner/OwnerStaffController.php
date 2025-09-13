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
        $query = $request->input('search');

        // If no query, show default staffIndex
        if (empty($query)) {
            return $this->staffIndex();
        }

        // Split query into terms (for multi-word names)
        $terms = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);

        // Search in approved staff
        $approvedStaff = Staff::with('user')
            ->where('status', 'approved')
            ->where(function ($q) use ($terms, $query) {
                foreach ($terms as $term) {
                    $q->where(function ($q2) use ($term) {
                        $q2->where('first_name', 'like', "%{$term}%")
                        ->orWhere('middle_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%");
                    });
                }
                // Also search email in users table
                $q->orWhereHas('user', function ($q3) use ($query) {
                    $q3->where('email', 'like', "%{$query}%");
                });
            })
            ->get();

        // Search in pending staff
        $pendingStaff = Staff::with('user')
            ->where('status', 'pending')
            ->where(function ($q) use ($terms, $query) {
                foreach ($terms as $term) {
                    $q->where(function ($q2) use ($term) {
                        $q2->where('first_name', 'like', "%{$term}%")
                        ->orWhere('middle_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%");
                    });
                }
                // Also search email in users table
                $q->orWhereHas('user', function ($q3) use ($query) {
                    $q3->where('email', 'like', "%{$query}%");
                });
            })
            ->get();

        return view('owner.staff.index', compact('approvedStaff', 'pendingStaff'))
            ->with('search', $query);
    }



}