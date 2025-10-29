<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Order;
use App\Notifications\OrderStatusUpdated;

class OrderController extends Controller
{
    public function editStatus(Order $order)
    {
        // Prevent status management for completed orders
        if ($order->status === 'completed') {
            return redirect()->route('admin.ordersummary.index', $order)
                ->with('error', 'Cannot modify status of completed orders.');
        }

        $order->loadMissing('rating');

        $statusOptions = $this->statusOptions();
        $statusFlow = ['pending', 'processing', 'in_production', 'confirmed', 'to_receive', 'completed'];
        $metadata = $this->normalizeMetadata($order->metadata);

        return view('admin.orders.manage-status', [
            'order' => $order,
            'statusOptions' => $statusOptions,
            'statusFlow' => $statusFlow,
            'metadata' => $metadata,
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        // Prevent status updates for completed orders
        if ($order->status === 'completed') {
            return redirect()->back()->with('error', 'Cannot update status of completed orders.');
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

        // Send notification to customer if status changed
        if ($oldStatus !== $validated['status']) {
            $statusOptions = $this->statusOptions();
            $statusLabel = $statusOptions[$validated['status']] ?? ucfirst(str_replace('_', ' ', $validated['status']));

            // Get the customer user
            $customerUser = $order->user;
            if ($customerUser) {
                $customerUser->notify(new OrderStatusUpdated(
                    $order->id,
                    $order->order_number,
                    $oldStatus,
                    $validated['status'],
                    $statusLabel
                ));
            }
        }

        return redirect()
            ->route('admin.orders.status.edit', $order)
            ->with('success', 'Order status updated successfully.');
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

        $oldPaymentStatus = $order->payment_status;
        $order->payment_status = $validated['payment_status'];

        $metadata = $this->normalizeMetadata($order->metadata);

        if (array_key_exists('payment_note', $validated)) {
            $metadata['payment_note'] = $validated['payment_note'] ?: null;
        }

        $order->metadata = array_filter($metadata, function ($value) {
            return $value !== null && $value !== '';
        });

        $order->save();

        return redirect()
            ->route('admin.orders.payment.edit', $order)
            ->with('success', 'Payment status updated successfully.');
    }

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

    protected function paymentStatusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'paid' => 'Paid',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
        ];
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
}
