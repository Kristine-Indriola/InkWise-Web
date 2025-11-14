@extends('layouts.customerprofile')

@section('title', 'In Production')

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
       <div class="flex border-b text-base font-semibold mb-4">
    <a href="{{ route('customer.my_purchase.topay') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">To Pay</a>
    <a href="{{ route('customer.my_purchase.inproduction') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">In Production</a>
    <a href="{{ route('customer.my_purchase.toship') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">To Ship</a>
    <a href="{{ route('customer.my_purchase.toreceive') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">To Receive</a>
    <a href="{{ route('customer.my_purchase.completed') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">Completed</a>
    <a href="{{ route('customer.my_purchase.cancelled') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">Cancelled</a>
    <a href="{{ route('customer.my_purchase.return_refund') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">Return/Refund</a>
    </div>

    @php
        $statusOptions = [
            'pending' => 'Order Received',
            'in_production' => 'In Production',
            'confirmed' => 'To Ship',
            'to_receive' => 'To Receive',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
        $statusFlow = ['pending', 'in_production', 'confirmed', 'to_receive', 'completed'];
        $normalizeMetadata = function ($metadata) {
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
        };
        $ordersSource = $orders ?? optional(auth()->user())->customer->orders ?? [];
        $inProductionOrders = collect($ordersSource)->filter(function ($order) {
            $status = data_get($order, 'status', 'pending');
            return $status === 'in_production';
        })->values();
    @endphp

    <div class="space-y-4">
        @forelse($inProductionOrders as $order)
            @php
                $items = data_get($order, 'items');
                if ($items instanceof \Illuminate\Support\Collection) {
                    $primaryItem = $items->first();
                } elseif (is_array($items)) {
                    $primaryItem = collect($items)->first();
                } else {
                    $primaryItem = null;
                }

                $productName = data_get($primaryItem, 'product_name', data_get($order, 'product_name', 'Custom invitation'));
                $image = data_get($primaryItem, 'product.image', asset('customerimages/image/weddinginvite.png'));
                $quantity = (int) data_get($primaryItem, 'quantity', data_get($order, 'quantity', 0));
                $paperStock = data_get($primaryItem, 'paperStockSelection.paper_stock_name', 'Standard');
                $metadata = $normalizeMetadata(data_get($order, 'metadata', []));
                $statusKey = data_get($order, 'status', 'pending');
                $statusLabel = $statusOptions[$statusKey] ?? ucfirst(str_replace('_', ' ', $statusKey));
                $flowIndex = array_search($statusKey, $statusFlow, true);
                $nextStatusKey = $flowIndex !== false && $flowIndex < count($statusFlow) - 1 ? $statusFlow[$flowIndex + 1] : null;
                $nextStatusLabel = $nextStatusKey ? ($statusOptions[$nextStatusKey] ?? ucfirst(str_replace('_', ' ', $nextStatusKey))) : null;
                $statusNote = $metadata['status_note'] ?? null;
                $totalAmount = data_get($order, 'total_amount', 0);
                $orderNumber = data_get($order, 'order_number', data_get($order, 'id'));
                $trackingNumber = $metadata['tracking_number'] ?? null;
            @endphp

            <div class="bg-white border rounded-xl p-4 shadow-sm flex flex-col gap-4 md:flex-row md:items-center">
                <img src="{{ $image }}" alt="{{ $productName }}" class="w-24 h-24 object-cover rounded-lg border">
                <div class="flex-1 space-y-1">
                    <div class="font-semibold text-lg text-[#a6b7ff]">{{ $productName }}</div>
                    <div class="text-sm text-gray-500">Order: {{ $orderNumber }}</div>
                    <div class="text-sm text-gray-500">Quantity: {{ $quantity ?: '—' }} pcs</div>
                    <div class="text-sm text-gray-500">Paper: {{ $paperStock }}</div>
                    <div class="text-sm text-gray-500">Status: <span class="font-semibold text-[#a6b7ff]">{{ $statusLabel }}</span></div>
                    <div class="text-sm text-gray-500">Next step: {{ $nextStatusLabel ?? 'All steps complete' }}</div>
                    @if($trackingNumber)
                        <div class="text-sm text-gray-500">Tracking: {{ $trackingNumber }}</div>
                    @endif
                    @if($statusNote)
                        <div class="text-xs text-gray-400">Note: {{ $statusNote }}</div>
                    @endif
                </div>
                <div class="flex flex-col items-end gap-2">
                    <div class="text-gray-700 font-bold">₱{{ number_format($totalAmount, 2) }}</div>
                    <div class="flex gap-2">
                        @php
                            // Check if order has remaining balance to pay
                            $hasRemainingBalance = false;
                            $metadata = $normalizeMetadata(data_get($order, 'metadata', []));
                            $payments = collect($metadata['payments'] ?? []);
                            $paidAmount = $payments->filter(fn($payment) => ($payment['status'] ?? null) === 'paid')->sum(fn($payment) => (float)($payment['amount'] ?? 0));
                            $remainingBalance = max(($totalAmount ?? 0) - $paidAmount, 0);
                            $hasRemainingBalance = $remainingBalance > 0.01; // More than 1 cent remaining
                        @endphp

                        @if($hasRemainingBalance)
                        <a href="{{ route('customer.pay.remaining.balance', ['order' => data_get($order, 'id')]) }}"
                           class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded font-semibold transition-colors duration-200">
                            Pay Remaining (₱{{ number_format($remainingBalance, 2) }})
                        </a>
                        @endif

                        <button class="px-4 py-2 bg-[#a6b7ff] text-white rounded font-semibold">View Design Proof</button>
                        <button class="px-4 py-2 border border-[#a6b7ff] text-[#a6b7ff] rounded font-semibold">Message Inkwise</button>
                        <button type="button" class="px-4 py-2 border border-red-500 text-red-500 hover:bg-red-50 rounded font-semibold js-cancel-production-order" data-order-id="{{ data_get($order, 'id') }}">Cancel Order</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-sm text-gray-500">No orders are currently in production.</div>
        @endforelse
    </div>
</div>

<script>
// Normalize nav hover/active behavior
(function () {
    const activeClasses = ['border-b-2', 'border-[#a6b7ff]', 'text-[#a6b7ff]'];
    const inactiveTextClass = 'text-gray-500';

    function blurAfterInteraction(ev) {
        try {
            const control = ev.currentTarget;
            setTimeout(() => {
                if (document.activeElement === control) {
                    control.blur();
                }
            }, 50);
        } catch (e) {
            // ignore
        }
    }

    function normalizePath(url) {
        try {
            return new URL(url, window.location.origin).pathname.replace(/\/+$/, '') || '/';
        } catch (e) {
            return url;
        }
    }

    function setActiveTab(tabs, target) {
        tabs.forEach(tab => {
            tab.classList.remove(...activeClasses);
            if (!tab.classList.contains(inactiveTextClass)) {
                tab.classList.add(inactiveTextClass);
            }
        });
        if (target) {
            target.classList.remove(inactiveTextClass);
            target.classList.add(...activeClasses);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const blurSelector = '.js-purchase-tab';
        const controls = Array.from(document.querySelectorAll(blurSelector));
        controls.forEach(ctrl => {
            ctrl.addEventListener('mouseup', blurAfterInteraction);
            ctrl.addEventListener('touchend', blurAfterInteraction);
            ctrl.addEventListener('click', blurAfterInteraction);
        });

        const tabs = controls;
        if (!tabs.length) {
            return;
        }

        const currentPath = normalizePath(window.location.href);
        let activeTab = null;

        tabs.forEach(tab => {
            if (tab.tagName === 'A') {
                const tabPath = normalizePath(tab.getAttribute('href'));
                if (tabPath === currentPath) {
                    activeTab = tab;
                }
            } else if (!activeTab && tab.dataset.route === 'all') {
                activeTab = tab;
            }
        });

        if (!activeTab) {
            activeTab = tabs.find(tab => tab.dataset.route === 'all') || tabs[0];
        }

        setActiveTab(tabs, activeTab);

        tabs.forEach(tab => {
            tab.addEventListener('click', function () {
                setActiveTab(tabs, tab);
            });
        });
    });
})();
</script>

<script>
// Handle cancel production order
document.addEventListener('DOMContentLoaded', function () {
    const cancelButtons = document.querySelectorAll('.js-cancel-production-order');
    
    cancelButtons.forEach(btn => {
        btn.addEventListener('click', async function () {
            const orderId = this.getAttribute('data-order-id');
            
            if (!orderId) {
                console.warn('No order ID found for cancel button');
                return;
            }

            // Show warning for orders in production
            const confirmed = confirm(
                'This order is already in production. Cancelling may result in a restocking fee or partial refund.\n\n' +
                'Are you sure you want to cancel this order? For immediate assistance, please contact InkWise support.'
            );

            if (!confirmed) {
                return;
            }

            try {
                const response = await fetch(`/customer/orders/${orderId}/cancel`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Accept': 'application/json',
                    },
                });

                if (response.ok) {
                    const result = await response.json().catch(() => ({}));
                    
                    // Successfully cancelled - remove from UI
                    const card = btn.closest('.bg-white.border.rounded-xl');
                    if (card) {
                        card.remove();
                    }

                    // Show success message
                    const message = result.message || result.status || 'Order cancellation request submitted. Our team will review and process your refund.';
                    alert(message);

                    // Reload page if no more orders
                    const remainingOrders = document.querySelectorAll('.bg-white.border.rounded-xl').length;
                    if (remainingOrders === 0) {
                        location.reload();
                    }
                } else {
                    const errorData = await response.json().catch(() => ({}));
                    if (response.status === 403 || errorData.message?.includes('no longer be cancelled')) {
                        alert('This order cannot be cancelled at this stage. Please contact InkWise support for assistance.');
                    } else {
                        alert(errorData.message || 'Failed to cancel order. Please contact InkWise support.');
                    }
                }
            } catch (error) {
                console.error('Cancel order error:', error);
                alert('Failed to cancel order. Please check your connection and try again, or contact InkWise support.');
            }
        });
    });
});
</script>
@endsection
