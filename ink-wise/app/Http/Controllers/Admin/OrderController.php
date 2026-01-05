<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
        $statusFlow = ['draft', 'pending', 'pending_awaiting_materials', 'processing', 'in_production', 'confirmed', 'completed'];
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
        $newStatus = $validated['status'];

        // Check inventory availability before allowing completion
        if (in_array($newStatus, ['confirmed', 'completed'])) {
            $inventoryCheck = $this->checkInventoryAvailability($order);

            if (!$inventoryCheck['available']) {
                // Automatically set status to pending awaiting materials
                $newStatus = 'pending_awaiting_materials';
                $validated['status'] = $newStatus;

                // Send notification to admin/owner
                $this->notifyAdminAboutMaterialShortage($order, $inventoryCheck['shortages']);

                // Add internal note about material shortage
                $validated['internal_note'] = ($validated['internal_note'] ?? '') .
                    "\n\n[SYSTEM] Order automatically marked as 'Pending – Awaiting Materials' due to insufficient inventory: " .
                    implode(', ', array_map(fn($s) => "{$s['material']} ({$s['required']} needed, {$s['available']} available)", $inventoryCheck['shortages']));
            }
        }

        $order->status = $newStatus;

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
                $userName = $user->name ?? $user->email ?? 'Admin';
            }
            
            \App\Models\OrderActivity::create([
                'order_id' => $order->id,
                'activity_type' => 'status_updated',
                'old_value' => $oldStatus,
                'new_value' => $validated['status'],
                'description' => 'Order status changed from "' . ($statusOptions[$oldStatus] ?? ucfirst(str_replace('_', ' ', $oldStatus))) . '" to "' . ($statusOptions[$validated['status']] ?? ucfirst(str_replace('_', ' ', $validated['status']))) . '"',
                'user_id' => $user ? $user->user_id : null,
                'user_name' => $userName,
                'user_role' => 'Admin',
            ]);
        }

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
                ->route('admin.orders.payment.edit', $order)
                ->with('success', 'Payment status updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update payment status', [
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

    /**
     * Remove the specified order from storage.
     */
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
                $userName = $user->name ?? $user->email ?? 'Admin';
            }
            
            \App\Models\OrderActivity::create([
                'order_id' => $order->id,
                'activity_type' => 'order_archived',
                'old_value' => 'active',
                'new_value' => 'archived',
                'description' => 'Order archived',
                'user_id' => $user ? $user->user_id : null,
                'user_name' => $userName,
                'user_role' => 'Admin',
            ]);
            
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to archive order'], 500);
        }
    }

    protected function statusOptions(): array
    {
        return [
            'draft' => 'New Order',
            'pending' => 'Order Received',
            'pending_awaiting_materials' => 'Pending – Awaiting Materials',
            'processing' => 'Processing',
            'in_production' => 'In Progress',
            'confirmed' => 'Ready for Pickup',
            'completed' => 'Completed',
        ];
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
    /**
     * Check if all materials required for the order are available in inventory
     */
    protected function checkInventoryAvailability(Order $order): array
    {
        $order->loadMissing([
            'items.product.materials.material.inventory',
            'items.paperStockSelection.paperStock.material.inventory',
            'items.addons.productAddon.material.inventory'
        ]);

        $shortages = [];
        $allAvailable = true;

        foreach ($order->items as $item) {
            $quantity = $item->quantity ?? 1;

            // Check product materials
            if ($item->product && $item->product->materials) {
                foreach ($item->product->materials as $productMaterial) {
                    $material = $productMaterial->material;
                    if ($material) {
                        $required = ($productMaterial->qty ?? 0) * $quantity;
                        $available = $material->inventory?->stock_level ?? 0;

                        if ($required > $available) {
                            $shortages[] = [
                                'material' => $material->material_name,
                                'required' => $required,
                                'available' => $available,
                                'shortage' => $required - $available
                            ];
                            $allAvailable = false;
                        }
                    }
                }
            }

            // Check paper stock material
            if ($item->paperStockSelection && $item->paperStockSelection->paperStock) {
                $paperStock = $item->paperStockSelection->paperStock;
                if ($paperStock->material) {
                    $material = $paperStock->material;
                    $required = $quantity; // Assuming 1:1 ratio for paper stock
                    $available = $material->inventory?->stock_level ?? 0;

                    if ($required > $available) {
                        $shortages[] = [
                            'material' => $material->material_name,
                            'required' => $required,
                            'available' => $available,
                            'shortage' => $required - $available
                        ];
                        $allAvailable = false;
                    }
                }
            }

            // Check addon materials
            if ($item->addons) {
                foreach ($item->addons as $addon) {
                    if ($addon->productAddon && $addon->productAddon->material) {
                        $material = $addon->productAddon->material;
                        $addonQuantity = $addon->quantity ?? 1;
                        $required = $addonQuantity * $quantity;
                        $available = $material->inventory?->stock_level ?? 0;

                        if ($required > $available) {
                            $shortages[] = [
                                'material' => $material->material_name,
                                'required' => $required,
                                'available' => $available,
                                'shortage' => $required - $available
                            ];
                            $allAvailable = false;
                        }
                    }
                }
            }
        }

        return [
            'available' => $allAvailable,
            'shortages' => $shortages
        ];
    }

    /**
     * Send notification to admin/owner about material shortage
     */
    protected function notifyAdminAboutMaterialShortage(Order $order, array $shortages): void
    {
        $shortageDetails = collect($shortages)->map(function ($shortage) {
            return "{$shortage['material']}: {$shortage['shortage']} units short (need {$shortage['required']}, have {$shortage['available']})";
        })->implode("\n");

        $message = "Order #{$order->order_number} has been automatically marked as 'Pending – Awaiting Materials' due to insufficient inventory:\n\n{$shortageDetails}\n\nPlease restock materials before proceeding with this order.";

        // Send notification to all admin and owner users
        $adminUsers = \App\Models\User::whereIn('role', ['admin', 'owner'])->get();

        foreach ($adminUsers as $user) {
            $user->notify(new \App\Notifications\MaterialShortageAlert(
                $order->id,
                $order->order_number,
                $shortages,
                $message
            ));
        }

        // Also log this as an order activity
        \App\Models\OrderActivity::create([
            'order_id' => $order->id,
            'activity_type' => 'material_shortage',
            'description' => 'Order automatically marked as pending due to material shortage: ' . $shortageDetails,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name ?? 'System',
            'user_role' => 'Admin',
        ]);
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
