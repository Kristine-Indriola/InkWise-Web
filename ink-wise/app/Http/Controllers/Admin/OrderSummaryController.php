<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Admin\OrderSummaryPresenter;
use Illuminate\Http\Request;

class OrderSummaryController extends Controller
{
    public function show(Request $request, Order $order = null)
    {
        $orderModel = $order ?? Order::query()->latest('order_date')->latest()->first();

        if (! $orderModel) {
            return view('admin.ordersummary.index', ['order' => null]);
        }

        // Load activities for timeline
        $orderModel->loadMissing(['activities' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }]);

        $presented = OrderSummaryPresenter::make($orderModel);

        return view('admin.ordersummary.index', [
            'order' => $presented,
        ]);
    }
}
