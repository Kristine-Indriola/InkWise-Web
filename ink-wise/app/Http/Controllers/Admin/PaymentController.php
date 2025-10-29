<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $allowed = [10, 20, 25, 50, 100];
        $default = 20;
        $perPage = (int) $request->query('per_page', $default);
        if (!in_array($perPage, $allowed, true)) {
            $perPage = $default;
        }

        $orders = Order::query()
            ->select(['id', 'order_number', 'customer_id', 'customer_order_id', 'total_amount', 'order_date', 'status', 'payment_status', 'created_at'])
            ->with(['customer', 'customerOrder'])
            ->latest('order_date')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.payments.index', compact('orders'));
    }
}