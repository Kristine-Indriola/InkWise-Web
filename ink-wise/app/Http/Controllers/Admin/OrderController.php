<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;

class OrderController extends Controller
{
    /**
     * Remove the specified order from storage.
     */
    public function destroy(Request $request, Order $order)
    {
        try {
            $order->delete();
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to delete order'], 500);
        }
    }
}
