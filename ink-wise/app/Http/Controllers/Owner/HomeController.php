<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;

class HomeController extends Controller
{
    public function index()
    {
        $newOrdersCount = Order::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $pendingOrdersCount = Order::query()
            ->where('status', 'pending')
            ->count();

        return view('owner.owner-home', [
            'newOrdersCount'     => $newOrdersCount,
            'pendingOrdersCount' => $pendingOrdersCount,
        ]);
    }
}
