<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffOrderController extends Controller
{
    public function index(Request $request)
    {
        $statusFilter = $request->query('status');

        $ordersQuery = Order::query()
            ->with(['customer', 'items'])
            ->latest('order_date')
            ->latest();

        if ($statusFilter && $statusFilter !== 'all') {
            $ordersQuery->where('status', $statusFilter);
        }

        if ($user = Auth::user()) {
            $staffKey = $user->user_id ?? $user->getKey();
            $ordersQuery->where('user_id', $staffKey);
        }

        $orders = $ordersQuery->get()->each(function (Order $order) {
            $order->display_customer_name = $this->formatCustomerName($order->customer);
            $order->display_items_count = $this->calculateItemsCount($order);
            $order->display_total_amount = (float) ($order->total_amount ?? 0);
        });

        return view('staff.order_list', [
            'orders' => $orders,
            'statusFilter' => $statusFilter,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function show(Order $order)
    {
        $order->loadMissing(['customer', 'items.product', 'payments']);

        return view('staff.order.show', [
            'order' => $order,
            'statusOptions' => $this->statusOptions(),
            'statusFlow' => $this->statusFlow(),
            'metadata' => $this->normalizeMetadata($order->metadata),
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

    protected function statusOptions(): array
    {
        return [
            'pending' => 'Order Received',
            'in_production' => 'In Progress',
            'confirmed' => 'To Ship',
            'to_receive' => 'To Receive',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    }

    protected function statusFlow(): array
    {
        return ['pending', 'in_production', 'confirmed', 'to_receive', 'completed'];
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

    protected function formatCustomerName(?Customer $customer): string
    {
        if (!$customer) {
            return 'Guest customer';
        }

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

    protected function calculateItemsCount(Order $order): int
    {
        if ($order->relationLoaded('items') && $order->items) {
            $quantity = (int) $order->items->sum('quantity');
            return $quantity > 0 ? $quantity : $order->items->count();
        }

        return (int) ($order->items_count ?? 0);
    }
}
