<?php

namespace App\Http\Controllers\Staff;

use App\Models\User;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StaffCustomerController extends Controller
{
    public function index()
    {
        // get all customers (adjust if you have a different role system)
        $customers = User::where('role', 'customer')
            ->with(['customer', 'address'])
            ->get(); 

        return view('staff.customer_profile', compact('customers'));
    }

    public function show($id)
    {
        // Load the user and eager-load related customer + address (like admin)
        $user = User::where('user_id', $id)
            ->where('role', 'customer')
            ->with(['customer', 'address'])
            ->firstOrFail();

        // Get customer details from the related model
        $customer = $user->customer;

        // Get customer orders with related data (include product details for items)
        $orders = Order::where('user_id', $id)
            ->orWhereHas('customer', function($query) use ($customer) {
                if ($customer) {
                    $query->where('customer_id', $customer->customer_id);
                }
            })
            ->with(['items.product', 'payments', 'rating'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate statistics
        $totalOrders = $orders->count();
        $completedOrders = $orders->where('status', 'completed')->count();
        $totalSpent = $orders->where('status', 'completed')->sum('total_amount');
        
        // Get ratings
        $ratings = $orders->filter(function($order) {
            return $order->rating !== null;
        })->map(function($order) {
            return $order->rating;
        });

        $averageRating = $ratings->count() > 0 ? $ratings->avg('rating') : 0;

        return view('staff.customer_detail', compact(
            'user',
            'customer',
            'orders',
            'totalOrders',
            'completedOrders',
            'totalSpent',
            'averageRating',
            'ratings'
        ));
    }
}
