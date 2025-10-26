<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Order;

class OrderController extends Controller
{
    public function editStatus(Order $order)
    {
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
            ->route('admin.orders.status.edit', $order)
            ->with('success', 'Order status updated successfully.');
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
