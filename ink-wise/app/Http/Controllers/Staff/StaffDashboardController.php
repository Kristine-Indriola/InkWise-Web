<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Message;
use App\Models\OrderRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffDashboardController extends Controller
{
    /**
     * Display the staff dashboard with statistics.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $staff = Auth::user();
        $staffId = $staff->user_id;

        // Total Orders (all orders in the system)
        $totalOrders = Order::where(function ($query) {
                $query->where('payment_status', '!=', 'pending')
                      ->orWhereNull('payment_status');
            })
            ->where(function ($query) {
                $query->whereNull('archived')
                      ->orWhere('archived', false);
            })
            ->count();

        // Assigned Orders - Orders that are in active statuses (not completed or cancelled)
        // These represent orders that need attention from the staff
        $assignedOrders = Order::whereNotIn('status', ['completed', 'cancelled'])
            ->where(function ($query) {
                $query->where('payment_status', '!=', 'pending')
                      ->orWhereNull('payment_status');
            })
            ->where(function ($query) {
                $query->whereNull('archived')
                      ->orWhere('archived', false);
            })
            ->count();

        // Total Customers
        $customers = Customer::count();

        // Unread Messages for staff
        // Messages sent to this staff member that haven't been seen yet
        $unreadMessages = Message::where('receiver_type', 'App\Models\User')
            ->where('receiver_id', $staffId)
            ->whereNull('seen_at')
            ->count();

        // Recent Reviews
        $recentReviews = OrderRating::with(['customer', 'order', 'staffReplyBy'])
            ->latest('submitted_at')
            ->take(5)
            ->get();

        return view('staff.dashboard', compact(
            'totalOrders',
            'assignedOrders',
            'customers',
            'unreadMessages',
            'recentReviews'
        ));
    }
}
