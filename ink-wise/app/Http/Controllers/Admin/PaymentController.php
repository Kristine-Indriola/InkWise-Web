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

        // Get filter parameter
        $filter = $request->query('filter');
        $validFilters = ['paid', 'pending', 'failed', 'partial'];

        // Calculate statistics from all orders
        $statistics = Order::query()
            ->selectRaw('
                COUNT(*) as total_orders,
                SUM(CASE WHEN payment_status = "paid" THEN 1 ELSE 0 END) as paid_orders,
                SUM(CASE WHEN payment_status = "pending" THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN payment_status = "failed" THEN 1 ELSE 0 END) as failed_orders,
                SUM(total_amount) as total_amount,
                SUM(CASE WHEN payment_status = "paid" THEN total_amount ELSE 0 END) as paid_amount,
                SUM(CASE WHEN payment_status = "pending" THEN total_amount ELSE 0 END) as pending_amount
            ')
            ->first();

        // Count partial payments (orders with payments but balance > 0)
        $partialPayments = Order::query()
            ->whereHas('payments', function($query) {
                $query->where('status', 'paid');
            })
            ->get()
            ->filter(function($order) {
                return $order->balanceDue() > 0;
            })
            ->count();

        // Build query with optional filter
        $query = Order::query()
            ->select(['id', 'order_number', 'customer_id', 'customer_order_id', 'total_amount', 'order_date', 'status', 'payment_status', 'created_at'])
            ->with(['customer', 'customerOrder', 'payments']);

        // Apply filter if valid
        if ($filter && in_array($filter, $validFilters)) {
            if ($filter === 'partial') {
                // Filter for partial payments
                $query->whereHas('payments', function($q) {
                    $q->where('status', 'paid');
                });
            } else {
                $query->where('payment_status', $filter);
            }
        }

        // If filtering for partial payments, get all matching orders first
        if ($filter === 'partial') {
            $allOrders = $query->get()->filter(function($order) {
                return $order->balanceDue() > 0;
            });
            
            // Manually paginate the filtered results
            $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
            $orders = new \Illuminate\Pagination\LengthAwarePaginator(
                $allOrders->forPage($currentPage, $perPage),
                $allOrders->count(),
                $perPage,
                $currentPage,
                ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
            );
        } else {
            $orders = $query
                ->latest('order_date')
                ->latest()
                ->paginate($perPage)
                ->withQueryString();
        }

        return view('admin.payments.index', compact('orders', 'statistics', 'filter', 'partialPayments'));
    }
}