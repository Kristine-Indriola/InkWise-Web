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

        // Calculate statistics from all orders with payments
        $allOrders = Order::with('payments')->get();
        
        $statistics = (object) [
            'total_orders' => $allOrders->count(),
            'paid_orders' => $allOrders->filter(fn($o) => $o->balanceDue() <= 0.01 && $o->totalPaid() > 0)->count(),
            'pending_orders' => $allOrders->filter(fn($o) => $o->totalPaid() == 0)->count(),
            'failed_orders' => $allOrders->where('payment_status', 'failed')->count(),
            'total_amount' => $allOrders->sum('total_amount'),
            'paid_amount' => $allOrders->filter(fn($o) => $o->balanceDue() <= 0.01)->sum('total_amount'),
            'pending_amount' => $allOrders->filter(fn($o) => $o->totalPaid() == 0)->sum('total_amount'),
        ];

        // Count partial payments (orders with payments but balance > 0)
        $partialPayments = $allOrders->filter(fn($o) => $o->totalPaid() > 0 && $o->balanceDue() > 0.01)->count();

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