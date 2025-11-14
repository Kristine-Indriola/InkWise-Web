<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class StaffAssignedController extends Controller
{
    public function index(Request $request)
    {
    $search = trim((string) $request->input('search'));

        $ordersQuery = Order::query()
            ->with(['customer', 'items.product'])
            ->latest('order_date');

        if ($search !== '') {
            $ordersQuery->where(function ($query) use ($search) {
                $query->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('items', function ($itemQuery) use ($search) {
                        $itemQuery->where('product_name', 'like', "%{$search}%")
                            ->orWhereHas('product', function ($productQuery) use ($search) {
                                $productQuery->where('name', 'like', "%{$search}%")
                                    ->orWhere('product_name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        if ($user = Auth::user()) {
            $key = $user->user_id ?? $user->getKey();
            $ordersQuery->where('user_id', $key);
        }

        $orders = $ordersQuery->paginate(10)->withQueryString();

        return view('staff.assigned_orders', [
            'orders' => $orders,
            'searchValue' => $search,
        ]);
    }

    public function confirm(Order $order)
    {
        $order->update(['status' => 'confirmed']);

        return back()->with('success', 'Order #' . $order->order_number . ' confirmed successfully.');
    }

    public function updateStatus(Request $request, Order $order)
    {
        // Prevent status updates for cancelled orders
        if ($order->status === 'cancelled') {
            return back()->with('error', 'Cannot update status of cancelled orders. Cancelled orders are locked.');
        }

        $validated = $request->validate([
            'status' => 'required|string|in:pending,in_production,confirmed,to_receive,completed,cancelled',
        ]);

        $order->update(['status' => $validated['status']]);

        $statusLabel = Str::title(str_replace('_', ' ', $validated['status']));

        return back()->with('success', 'Order #' . $order->order_number . ' status updated to ' . $statusLabel . '.');
    }
}
