<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StaffOrderController extends Controller
{
    public function index()
    {
    // Return empty collection so the staff order list shows "No orders found"
    $orders = collect([]);

        return view('staff.order_list', compact('orders'));
    }

    public function show($id)
    {
        // Sample order details
        $sampleOrders = [
            1001 => (object) [
                'id' => 1001,
                'customer' => (object) ['name' => 'Sarah Johnson'],
                'items_count' => 50,
                'total' => 2500.00,
                'status' => 'pending',
                'created_at' => now()->subDays(2),
                'items' => 'Wedding Invitations - Premium Package',
            ],
            1002 => (object) [
                'id' => 1002,
                'customer' => (object) ['name' => 'Mike Chen'],
                'items_count' => 25,
                'total' => 1250.00,
                'status' => 'completed',
                'created_at' => now()->subDays(5),
                'items' => 'Birthday Party Giveaways',
            ],
            1003 => (object) [
                'id' => 1003,
                'customer' => (object) ['name' => 'Emily Davis'],
                'items_count' => 100,
                'total' => 5000.00,
                'status' => 'pending',
                'created_at' => now()->subDays(1),
                'items' => 'Corporate Event Invitations',
            ],
            1004 => (object) [
                'id' => 1004,
                'customer' => (object) ['name' => 'Robert Wilson'],
                'items_count' => 30,
                'total' => 1500.00,
                'status' => 'completed',
                'created_at' => now()->subDays(7),
                'items' => 'Holiday Giveaways',
            ],
            1005 => (object) [
                'id' => 1005,
                'customer' => (object) ['name' => 'Lisa Brown'],
                'items_count' => 75,
                'total' => 3750.00,
                'status' => 'cancelled',
                'created_at' => now()->subDays(3),
                'items' => 'Wedding Invitations - Deluxe Package',
            ],
            1006 => (object) [
                'id' => 1006,
                'customer' => (object) ['name' => 'David Miller'],
                'items_count' => 40,
                'total' => 2000.00,
                'status' => 'pending',
                'created_at' => now()->subHours(12),
                'items' => 'Birthday Party Giveaways',
            ],
            1007 => (object) [
                'id' => 1007,
                'customer' => (object) ['name' => 'Jennifer Garcia'],
                'items_count' => 60,
                'total' => 3000.00,
                'status' => 'completed',
                'created_at' => now()->subDays(4),
                'items' => 'Corporate Event Invitations',
            ],
            1008 => (object) [
                'id' => 1008,
                'customer' => (object) ['name' => 'Thomas Anderson'],
                'items_count' => 20,
                'total' => 1000.00,
                'status' => 'cancelled',
                'created_at' => now()->subDays(6),
                'items' => 'Holiday Giveaways',
            ],
        ];

        $order = $sampleOrders[$id] ?? $sampleOrders[1001];
        return view('staff.order.show', compact('order'));
    }

    public function update(Request $request, $id)
    {
        // Simulate order status update
                return redirect()->route('staff.order_list.index')->with('success', 'Order status updated successfully.');
    }
}