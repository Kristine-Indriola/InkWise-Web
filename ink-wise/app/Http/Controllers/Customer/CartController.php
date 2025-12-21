<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $sessionId = session()->getId();

        // Get cart items for user or session
        $cartItems = CartItem::where(function ($query) use ($userId, $sessionId) {
            $query->where('customer_id', $userId)
                  ->orWhere('session_id', $sessionId);
        })->with('product')->get();

        $totalAmount = $cartItems->sum('total_price');

        return view('customer.cart', [
            'cartItems' => $cartItems,
            'totalAmount' => $totalAmount,
        ]);
    }

    public function updateItem(Request $request, CartItem $cartItem): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        // Ensure the item belongs to the current user/session
        $userId = Auth::id();
        $sessionId = session()->getId();

        if ($cartItem->customer_id !== $userId && ($cartItem->session_id !== $sessionId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $cartItem->quantity = $request->quantity;
        $cartItem->total_price = $cartItem->unit_price * $request->quantity;
        $cartItem->save();

        return response()->json(['success' => true]);
    }

    public function removeItem(CartItem $cartItem): JsonResponse
    {
        // Ensure the item belongs to the current user/session
        $userId = Auth::id();
        $sessionId = session()->getId();

        if ($cartItem->customer_id !== $userId && ($cartItem->session_id !== $sessionId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $cartItem->delete();

        return response()->json(['success' => true]);
    }
}