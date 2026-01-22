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

    public function addItem(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'front_preview' => 'nullable|string',
            'back_preview' => 'nullable|string',
            'customizations' => 'nullable|array',
        ]);

        $userId = Auth::id();
        $sessionId = session()->getId();
        $productId = $request->product_id;

        // Check if item already exists in cart
        $existingItem = CartItem::where(function ($query) use ($userId, $sessionId) {
            $query->where('customer_id', $userId)
                  ->orWhere('session_id', $sessionId);
        })
        ->where('product_id', $productId)
        ->where('status', 'active')
        ->first();

        $product = \App\Models\Product::findOrFail($productId);
        $unitPrice = $product->price ?? 0;
        $quantity = $request->quantity;
        $totalPrice = $unitPrice * $quantity;

        // Prepare metadata
        $metadata = $request->customizations ?? [];
        if ($request->front_preview) {
            $metadata['front_preview'] = $request->front_preview;
        }
        if ($request->back_preview) {
            $metadata['back_preview'] = $request->back_preview;
        }

        if ($existingItem) {
            // Update existing item
            $existingItem->quantity += $quantity;
            $existingItem->total_price = $existingItem->unit_price * $existingItem->quantity;

            // Merge metadata if adding more customizations
            if (!empty($metadata)) {
                $existingMetadata = $existingItem->metadata ?? [];
                $existingItem->metadata = array_merge($existingMetadata, $metadata);
            }

            $existingItem->save();

            return response()->json([
                'success' => true,
                'message' => 'Item quantity updated in cart',
                'cart_item_id' => $existingItem->id
            ]);
        } else {
            // Create new cart item
            $cartItem = CartItem::create([
                'cart_id' => null, // Will be set if using cart system
                'session_id' => $userId ? null : $sessionId,
                'customer_id' => $userId,
                'product_type' => 'product',
                'product_id' => $productId,
                'quantity' => $quantity,
                'paper_type_id' => null,
                'paper_price' => 0,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'status' => 'active',
                'metadata' => $metadata,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Item added to cart',
                'cart_item_id' => $cartItem->id
            ]);
        }
    }

    public function clearCart(): JsonResponse
    {
        $userId = Auth::id();
        $sessionId = session()->getId();

        CartItem::where(function ($query) use ($userId, $sessionId) {
            $query->where('customer_id', $userId)
                  ->orWhere('session_id', $sessionId);
        })->delete();

        return response()->json(['success' => true, 'message' => 'Cart cleared']);
    }

    public function getCartCount(): JsonResponse
    {
        $userId = Auth::id();
        $sessionId = session()->getId();

        $count = CartItem::where(function ($query) use ($userId, $sessionId) {
            $query->where('customer_id', $userId)
                  ->orWhere('session_id', $sessionId);
        })
        ->where('status', 'active')
        ->sum('quantity');

        return response()->json(['count' => $count]);
    }