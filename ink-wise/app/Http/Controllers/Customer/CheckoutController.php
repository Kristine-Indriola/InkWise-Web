<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Services\OrderFlowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    private const CHECKOUT_SESSION_KEY = 'checkout_data';
    private const CART_SESSION_KEY = 'cart_items';

    public function __construct(private readonly OrderFlowService $orderFlow)
    {
    }

    /**
     * Display the checkout page with cart items
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $sessionId = session()->getId();

        // Get cart items for authenticated user or session
        $cartItems = $this->getCartItems($userId, $sessionId);

        if ($cartItems->isEmpty()) {
            return redirect()->route('customer.catalog')->with('error', 'Your cart is empty.');
        }

        // Calculate totals
        $subtotal = $cartItems->sum('total_price');
        $taxRate = 0.12; // 12% VAT
        $taxAmount = $subtotal * $taxRate;
        $shippingFee = $this->calculateShippingFee($cartItems);
        $totalAmount = $subtotal + $taxAmount + $shippingFee;

        // Store checkout data in session
        $checkoutData = [
            'items' => $cartItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? 'Unknown Product',
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                    'metadata' => $item->metadata ?? [],
                    'front_preview' => $item->metadata['front_preview'] ?? null,
                    'back_preview' => $item->metadata['back_preview'] ?? null,
                ];
            })->toArray(),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'shipping_fee' => $shippingFee,
            'total_amount' => $totalAmount,
            'created_at' => now()->toISOString(),
        ];

        session()->put(self::CHECKOUT_SESSION_KEY, $checkoutData);

        return view('customer.checkout.index', [
            'cartItems' => $cartItems,
            'checkoutData' => $checkoutData,
            'subtotal' => $subtotal,
            'taxAmount' => $taxAmount,
            'shippingFee' => $shippingFee,
            'totalAmount' => $totalAmount,
        ]);
    }

    /**
     * Process the checkout and create order
     */
    public function process(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => ['required', Rule::in(['gcash', 'cod', 'bank_transfer'])],
            'shipping_address' => 'required_if:shipping_method,delivery|array',
            'shipping_address.full_name' => 'required_if:shipping_method,delivery|string|max:255',
            'shipping_address.phone' => 'required_if:shipping_method,delivery|string|max:20',
            'shipping_address.address_line_1' => 'required_if:shipping_method,delivery|string|max:255',
            'shipping_address.city' => 'required_if:shipping_method,delivery|string|max:100',
            'shipping_address.province' => 'required_if:shipping_method,delivery|string|max:100',
            'shipping_address.postal_code' => 'required_if:shipping_method,delivery|string|max:10',
            'shipping_method' => ['required', Rule::in(['pickup', 'delivery'])],
            'special_instructions' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Validation failed.'
            ], 422);
        }

        $checkoutData = session()->get(self::CHECKOUT_SESSION_KEY);

        if (!$checkoutData) {
            return response()->json([
                'success' => false,
                'message' => 'Checkout session expired. Please try again.'
            ], 400);
        }

        $userId = Auth::id();
        $sessionId = session()->getId();

        // Verify cart items still exist and haven't changed
        $cartItems = $this->getCartItems($userId, $sessionId);
        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Your cart is empty.'
            ], 400);
        }

        // Verify totals match
        $currentSubtotal = $cartItems->sum('total_price');
        if (abs($currentSubtotal - $checkoutData['subtotal']) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Cart contents have changed. Please review your order.'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Create the order
            $order = $this->createOrder($request, $checkoutData, $cartItems);

            // Create order items
            $this->createOrderItems($order, $cartItems);

            // Create payment record
            $payment = $this->createPayment($order, $request);

            // Clear cart
            $this->clearCart($userId, $sessionId);

            // Clear checkout session
            session()->forget(self::CHECKOUT_SESSION_KEY);

            DB::commit();

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_url' => $this->getPaymentUrl($payment),
                'message' => 'Order created successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Checkout process failed: ' . $e->getMessage(), [
                'user_id' => $userId,
                'request_data' => $request->all(),
                'checkout_data' => $checkoutData
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process order. Please try again.'
            ], 500);
        }
    }

    /**
     * Handle payment completion
     */
    public function paymentComplete(Request $request, $orderId)
    {
        $order = Order::where('id', $orderId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Update payment status
        $order->update([
            'payment_status' => 'paid',
            'status' => 'confirmed'
        ]);

        // Update payment record
        Payment::where('order_id', $order->id)->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        // Log activity
        $this->orderFlow->logActivity($order, 'payment_completed', [
            'payment_method' => $order->payment_method,
            'amount' => $order->total_amount
        ]);

        return redirect()->route('customer.order.success', $order->id)
            ->with('success', 'Payment completed successfully! Your order is now being processed.');
    }

    /**
     * Handle payment cancellation
     */
    public function paymentCancel(Request $request, $orderId)
    {
        $order = Order::where('id', $orderId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Update payment status
        $order->update([
            'payment_status' => 'cancelled',
            'status' => 'cancelled'
        ]);

        // Update payment record
        Payment::where('order_id', $order->id)->update([
            'status' => 'cancelled'
        ]);

        return redirect()->route('customer.checkout')
            ->with('error', 'Payment was cancelled. You can try again or choose a different payment method.');
    }

    /**
     * Get cart items for user or session
     */
    private function getCartItems($userId, $sessionId)
    {
        return CartItem::where(function ($query) use ($userId, $sessionId) {
            if ($userId) {
                $query->where('customer_id', $userId);
            } else {
                $query->where('session_id', $sessionId);
            }
        })
        ->where('status', 'active')
        ->with('product')
        ->get();
    }

    /**
     * Calculate shipping fee based on items and method
     */
    private function calculateShippingFee($cartItems)
    {
        // Simple shipping calculation - can be made more complex
        $totalWeight = $cartItems->sum(function ($item) {
            return ($item->product->weight ?? 0) * $item->quantity;
        });

        if ($totalWeight <= 500) { // grams
            return 150.00;
        } elseif ($totalWeight <= 1000) {
            return 250.00;
        } else {
            return 350.00;
        }
    }

    /**
     * Create the order record
     */
    private function createOrder(Request $request, array $checkoutData, $cartItems): Order
    {
        $customerOrder = $this->orderFlow->createCustomerOrder(Auth::user());

        $metadata = [
            'checkout_data' => $checkoutData,
            'shipping_method' => $request->shipping_method,
            'special_instructions' => $request->special_instructions,
        ];

        if ($request->shipping_method === 'delivery') {
            $metadata['shipping_address'] = $request->shipping_address;
        }

        return $customerOrder->orders()->create([
            'customer_id' => $customerOrder->customer_id,
            'user_id' => Auth::id(),
            'order_number' => $this->orderFlow->generateOrderNumber(),
            'status' => 'draft',
            'subtotal_amount' => $checkoutData['subtotal'],
            'tax_amount' => $checkoutData['tax_amount'],
            'shipping_fee' => $checkoutData['shipping_fee'],
            'total_amount' => $checkoutData['total_amount'],
            'shipping_option' => $request->shipping_method,
            'payment_method' => $request->payment_method,
            'payment_status' => 'pending',
            'metadata' => $metadata,
        ]);
    }

    /**
     * Create order items from cart items
     */
    private function createOrderItems(Order $order, $cartItems): void
    {
        foreach ($cartItems as $cartItem) {
            $order->items()->create([
                'product_id' => $cartItem->product_id,
                'product_name' => $cartItem->product->name ?? 'Unknown Product',
                'quantity' => $cartItem->quantity,
                'unit_price' => $cartItem->unit_price,
                'subtotal' => $cartItem->total_price,
                'line_type' => 'invitation',
                'metadata' => $cartItem->metadata ?? [],
            ]);
        }
    }

    /**
     * Create payment record
     */
    private function createPayment(Order $order, Request $request): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'amount' => $order->total_amount,
            'payment_method' => $request->payment_method,
            'status' => 'pending',
            'metadata' => [
                'checkout_request' => $request->all(),
            ],
            'recorded_by' => Auth::id(),
        ]);
    }

    /**
     * Clear cart after successful order
     */
    private function clearCart($userId, $sessionId): void
    {
        CartItem::where(function ($query) use ($userId, $sessionId) {
            if ($userId) {
                $query->where('customer_id', $userId);
            } else {
                $query->where('session_id', $sessionId);
            }
        })->delete();
    }

    /**
     * Get payment URL based on payment method
     */
    private function getPaymentUrl(Payment $payment): ?string
    {
        return match($payment->payment_method) {
            'gcash' => route('customer.checkout.payment.gcash', ['orderId' => $payment->order_id]),
            'cod' => route('customer.checkout.payment.cod', ['orderId' => $payment->order_id]),
            'bank_transfer' => route('customer.checkout.payment.bank', ['orderId' => $payment->order_id]),
            default => null,
        };
    }
}