<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Support\Admin\OrderSummaryPresenter;

class StaffOrderController extends Controller
{
    public function index(Request $request)
    {

    // Return empty collection so the staff order list shows "No orders found"
    $orders = collect([]);

        $statusFilter = $request->query('status');

        $ordersQuery = Order::query()
            ->select(['id', 'order_number', 'customer_order_id', 'customer_id', 'total_amount', 'order_date', 'status', 'payment_status', 'created_at'])
            ->where('archived', false)
            ->with(['customer'])
            ->latest('order_date')
            ->latest();

        if ($statusFilter && $statusFilter !== 'all') {
            $ordersQuery->where('status', $statusFilter);
        }

        // Calculate status counts from all orders (not just paginated)
        $allOrdersQuery = Order::query()->select('status')->where('archived', false);
        if ($statusFilter && $statusFilter !== 'all') {
            $allOrdersQuery->where('status', $statusFilter);
        }
        $statusCounts = $allOrdersQuery->get()->groupBy('status')->map->count();

        // Staff can see all orders, not just orders assigned to them
        $orders = $ordersQuery->paginate(10)->through(function (Order $order) {
            $order->display_customer_name = $this->formatCustomerName($order->effective_customer);
            $order->display_items_count = $this->calculateItemsCount($order);
            $order->display_total_amount = (float) ($order->total_amount ?? 0);
            return $order;
        });

        return view('staff.order_list', [
            'orders' => $orders,
            'statusFilter' => $statusFilter,
            'statusOptions' => $this->statusOptions(),
            'statusCounts' => $statusCounts,
        ]);
    }

    public function show(Order $order)
    {
        $order->loadMissing(['customer', 'items.product', 'payments', 'activities']);

        $presented = OrderSummaryPresenter::make($order);
        $metadata = $this->normalizeMetadata($order->metadata);

        if ($presented) {
            $presented['metadata'] = $metadata;
        }

        return view('staff.order.show', [
            'order' => $presented,
            'statusOptions' => $this->statusOptions(),
            'statusFlow' => $this->statusFlow(),
            'metadata' => $metadata,
        ]);
    }

    public function summary($id)
    {
        $order = Order::find($id);

        if (!$order) {
            abort(404, 'Order not found');
        }

        // Load relationships as needed (lazy loading like admin)
        $order->loadMissing(['customerOrder.customer', 'items.product', 'payments', 'rating', 'activities']);

        $presented = OrderSummaryPresenter::make($order);
        $metadata = $this->normalizeMetadata($order->metadata);

        if ($presented) {
            $presented['metadata'] = $metadata;
        }

        return view('staff.order.show', [
            'order' => $presented,
            'statusOptions' => $this->statusOptions(),
            'statusFlow' => $this->statusFlow(),
            'metadata' => $metadata,
        ]);
    }

    public function editStatus($id)
    {
        $order = Order::find($id);

        if (!$order) {
            abort(404, 'Order not found');
        }

        // Prevent status management for completed orders
        if ($order->status === 'completed') {
            return redirect()->route('staff.orders.summary', $order)
                ->with('error', 'Cannot modify status of completed orders.');
        }

        // Load relationships as needed (lazy loading like admin)
        $order->loadMissing(['customerOrder.customer', 'items.product', 'payments', 'rating']);

        $statusOptions = $this->statusOptions();
        $statusFlow = $this->statusFlow();
        $metadata = $this->normalizeMetadata($order->metadata);

        return view('staff.orders.manage-status', [
            'order' => $order,
            'statusOptions' => $statusOptions,
            'statusFlow' => $statusFlow,
            'metadata' => $metadata,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            abort(404, 'Order not found');
        }

        // Prevent status updates for cancelled orders
        if ($order->status === 'cancelled') {
            return redirect()->back()->with('error', 'Cannot update status of cancelled orders. Cancelled orders are locked.');
        }

        $allowedStatuses = array_keys($this->statusOptions());

        $validated = $request->validate([
            'status' => ['required', Rule::in($allowedStatuses)],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'internal_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $oldStatus = $order->status;
        $order->status = $validated['status'];

        $metadata = $this->normalizeMetadata($order->metadata);

        if (array_key_exists('tracking_number', $validated)) {
            $metadata['tracking_number'] = $validated['tracking_number'] ?: null;
        }

        if (array_key_exists('internal_note', $validated)) {
            $metadata['status_note'] = $validated['internal_note'] ?: null;
        }

        $order->metadata = array_filter($metadata, function ($value) {
            return $value !== null && $value !== '';
        });

        $order->save();

        // Log the activity
        if ($oldStatus !== $validated['status']) {
            $statusOptions = $this->statusOptions();
            $user = Auth::user();
            $userName = 'System';
            if ($user) {
                $userName = $user->name ?? $user->email ?? 'Staff Member';
            }
            
            \App\Models\OrderActivity::create([
                'order_id' => $order->id,
                'activity_type' => 'status_updated',
                'old_value' => $oldStatus,
                'new_value' => $validated['status'],
                'description' => 'Order status changed from "' . ($statusOptions[$oldStatus] ?? ucfirst(str_replace('_', ' ', $oldStatus))) . '" to "' . ($statusOptions[$validated['status']] ?? ucfirst(str_replace('_', ' ', $validated['status']))) . '"',
                'user_id' => $user ? $user->user_id : null,
                'user_name' => $userName,
                'user_role' => 'Staff',
            ]);

            // Send notification to customer
            $statusLabel = $statusOptions[$validated['status']] ?? ucfirst(str_replace('_', ' ', $validated['status']));

            // Get the customer user
            $customerUser = $order->user;
            if ($customerUser) {
                $customerUser->notify(new \App\Notifications\OrderStatusUpdated(
                    $order->id,
                    $order->order_number,
                    $oldStatus,
                    $validated['status'],
                    $statusLabel
                ));
            }
        }

        return redirect()
            ->route('staff.orders.status.edit', $order->id)
            ->with('success', 'Order status updated successfully.');
    }

    public function archive(Request $request, Order $order)
    {
        // Only allow archiving cancelled or completed orders
        if (!in_array(strtolower($order->status), ['cancelled', 'completed'])) {
            return response()->json(['error' => 'Only cancelled or completed orders can be archived'], 400);
        }

        try {
            $order->update(['archived' => true]);
            
            // Log the archive activity
            $user = Auth::user();
            $userName = 'System';
            if ($user) {
                $userName = $user->name ?? $user->email ?? 'Staff';
            }
            
            \App\Models\OrderActivity::create([
                'order_id' => $order->id,
                'activity_type' => 'order_archived',
                'old_value' => 'active',
                'new_value' => 'archived',
                'description' => 'Order archived',
                'user_id' => $user ? $user->user_id : null,
                'user_name' => $userName,
                'user_role' => 'Staff',
            ]);
            
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to archive order'], 500);
        }
    }

    public function archived(Request $request)
    {
        $statusFilter = $request->query('status');

        $ordersQuery = Order::query()
            ->select(['id', 'order_number', 'customer_order_id', 'customer_id', 'total_amount', 'order_date', 'status', 'payment_status', 'created_at'])
            ->where('archived', true)
            ->with(['customer', 'activities' => function ($query) {
                $query->latest()->limit(1); // Get the most recent activity
            }])
            ->latest('order_date')
            ->latest();

        if ($statusFilter && $statusFilter !== 'all') {
            $ordersQuery->where('status', $statusFilter);
        }

        // Calculate status counts from archived orders
        $allOrdersQuery = Order::query()->select('status')->where('archived', true);
        if ($statusFilter && $statusFilter !== 'all') {
            $allOrdersQuery->where('status', $statusFilter);
        }
        $statusCounts = $allOrdersQuery->get()->groupBy('status')->map->count();

        $orders = $ordersQuery->paginate(10)->through(function (Order $order) {
            $order->display_customer_name = $this->formatCustomerName($order->effective_customer);
            $order->display_items_count = $this->calculateItemsCount($order);
            $order->display_total_amount = (float) ($order->total_amount ?? 0);
            return $order;
        });

        return view('staff.order_list', [
            'orders' => $orders,
            'statusFilter' => $statusFilter,
            'statusOptions' => $this->statusOptions(),
            'statusCounts' => $statusCounts,
            'isArchived' => true,
        ]);
    }

    public function update(Request $request, Order $order)
    {
        $allowedStatuses = array_keys($this->statusOptions());

        $validated = $request->validate([
            'status' => ['required', Rule::in($allowedStatuses)],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'internal_note' => ['nullable', 'string', 'max:1000'],
        ]);


        $order->status = $validated['status'];

        $metadata = $this->normalizeMetadata($order->metadata);

        if (array_key_exists('tracking_number', $validated)) {
            $metadata['tracking_number'] = $validated['tracking_number'] ?: null;
        }

        if (array_key_exists('internal_note', $validated)) {
            $metadata['status_note'] = $validated['internal_note'] ?: null;
        }

        $order->metadata = array_filter($metadata, function ($value) {
            return $value !== null && $value !== '';
        });

        $order->save();

        return redirect()
            ->route('staff.order_list.show', $order)
            ->with('success', 'Order status updated successfully.');
    }

    protected function paymentStatusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'paid' => 'Paid',
            'partial' => 'Partial',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
        ];
    }

    public function editPayment(Order $order)
    {
        $order->loadMissing(['payments', 'rating']);

        $paymentStatusOptions = $this->paymentStatusOptions();
        $metadata = $this->normalizeMetadata($order->metadata);

        return view('admin.orders.manage-payment', [
            'order' => $order,
            'paymentStatusOptions' => $paymentStatusOptions,
            'metadata' => $metadata,
        ]);
    }

    public function updatePayment(Request $request, Order $order)
    {
        $allowedPaymentStatuses = array_keys($this->paymentStatusOptions());

        $validated = $request->validate([
            'payment_status' => ['required', Rule::in($allowedPaymentStatuses)],
            'payment_note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $oldPaymentStatus = $order->payment_status;
            $order->payment_status = $validated['payment_status'];

            $metadata = $this->normalizeMetadata($order->metadata);

            if (array_key_exists('payment_note', $validated)) {
                $metadata['payment_note'] = $validated['payment_note'] ?: null;
            }

            $order->metadata = array_filter($metadata, function ($value) {
                return $value !== null && $value !== '';
            });

            $saved = $order->save();

            if (!$saved) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Failed to update payment status. Please try again.');
            }

            return redirect()
                ->route('staff.orders.payment.edit', $order->id)
                ->with('success', 'Payment status updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to update payment status', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'An error occurred while updating payment status: ' . $e->getMessage());
        }
    }

    protected function statusOptions(): array
    {
        return [
            'draft' => 'New Order',
            'pending' => 'Order Received',
            'processing' => 'Processing',
            'in_production' => 'In Progress',
            'confirmed' => 'Ready for Pickup',
            'completed' => 'Completed',
        ];
    }

    protected function statusFlow(): array
    {
        return ['draft', 'pending', 'processing', 'in_production', 'confirmed', 'completed'];
    }

    protected function normalizeMetadata($metadata): array
    {
        if (is_array($metadata)) {
            return $metadata;
        }

        if ($metadata instanceof \JsonSerializable) {
            return (array) $metadata;
        }

        if (is_string($metadata) && $metadata !== '') {
            $decoded = json_decode($metadata, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    protected function formatCustomerName($customer): string
    {
        if (!$customer) {
            return 'Guest customer';
        }

        // Handle CustomerOrder objects
        if ($customer instanceof \App\Models\CustomerOrder) {
            return $customer->name ?? 'Guest customer';
        }

        // Handle Customer objects
        if ($customer instanceof \App\Models\Customer) {
            if (!empty($customer->name)) {
                return $customer->name;
            }

            $parts = array_filter([
                $customer->first_name ?? null,
                $customer->middle_name ?? null,
                $customer->last_name ?? null,
            ]);

            if (!empty($parts)) {
                return implode(' ', $parts);
            }

            return $customer->email ?? 'Guest customer';
        }

        // Fallback for other object types
        return $customer->name ?? $customer->email ?? 'Guest customer';
    }

    protected function calculateItemsCount(Order $order): int
    {
        if ($order->relationLoaded('items') && $order->items) {
            $quantity = (int) $order->items->sum('quantity');
            return $quantity > 0 ? $quantity : $order->items->count();
        }

        return (int) ($order->items_count ?? 0);
    }
}
