<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Customer;
use App\Models\Order;

class AdminCustomerController extends Controller
{
    public function index()
    {
        // Load customers + their related customer profile
        $customers = User::where('role', 'customer')
            ->with('customer') // eager load customer table
            ->get();

        return view('admin.customers.index', compact('customers'));
    }

    public function show($id)
    {
        // Get the user
        $user = User::where('user_id', $id)
            ->where('role', 'customer')
            ->with(['customer', 'address'])
            ->firstOrFail();

        // Get customer details if exists
        $customer = $user->customer;

        // Get customer orders with related data
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
        $pendingOrders = $orders->whereIn('status', ['pending', 'processing', 'confirmed', 'in_production'])->count();
        $cancelledOrders = $orders->where('status', 'cancelled')->count();
        $totalSpent = $orders->where('status', 'completed')->sum('total_amount');
        
        // Get ratings
        $ratings = $orders->filter(function($order) {
            return $order->rating !== null;
        })->map(function($order) {
            return $order->rating;
        });

        $averageRating = $ratings->count() > 0 ? $ratings->avg('rating') : 0;

        // Order status breakdown
        $statusBreakdown = $orders->groupBy('status')->map->count();

        return view('admin.customers.show', compact(
            'user',
            'customer',
            'orders',
            'totalOrders',
            'completedOrders',
            'pendingOrders',
            'cancelledOrders',
            'totalSpent',
            'averageRating',
            'ratings',
            'statusBreakdown'
        ));
    }
}
