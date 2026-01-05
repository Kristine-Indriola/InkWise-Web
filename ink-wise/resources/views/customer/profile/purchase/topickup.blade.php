@extends('layouts.customerprofile')

@section('title', 'Ready for Pickup')

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex border-b text-base font-semibold mb-4">
        <a href="{{ route('customer.my_purchase.topay') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">To Pay</a>
        <a href="{{ route('customer.my_purchase.inproduction') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">In Production</a>
        <a href="{{ route('customer.my_purchase.topickup') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">Ready for Pickup</a>
        <a href="{{ route('customer.my_purchase.completed') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">Completed</a>
    </div>

    @php
        $statusOptions = [
            'pending' => 'Order Received',
            'processing' => 'Processing',
            'in_production' => 'In Production',
            'confirmed' => 'Ready for Pickup',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
        $statusFlow = ['draft', 'pending', 'processing', 'in_production', 'confirmed', 'completed'];
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
        $formatDate = function ($value, $format = 'M d, Y') {
            try {
                if ($value instanceof \Illuminate\Support\Carbon) {
                    return $value->format($format);
                }
                if ($value) {
                    return \Illuminate\Support\Carbon::parse($value)->format($format);
                }
            } catch (\Throwable $e) {
                return null;
            }
            return null;
        };
        $ordersSource = $orders ?? optional(auth()->user())->customer->orders ?? [];
        $ordersCollection = collect($ordersSource);
        $readyOrders = $ordersCollection->filter(function ($order) {
            $status = data_get($order, 'status', 'pending');
            return $status === 'confirmed';
        })->values();
    @endphp

    <div class="space-y-4">
        @forelse($readyOrders as $order)
            @php
                $productName = data_get($order, 'items.0.product_name', 'Custom invitation');
                $image = data_get($order, 'items.0.product.image', asset('customerimages/image/weddinginvite.png'));
                $quantity = (int) data_get($order, 'items.0.quantity', 0);
                $paperStock = data_get($order, 'items.0.paperStockSelection.paper_stock_name', 'Standard');
                $metadata = $normalizeMetadata(data_get($order, 'metadata', []));
                $statusKey = data_get($order, 'status', 'pending');
                $statusLabel = $statusOptions[$statusKey] ?? ucfirst(str_replace('_', ' ', $statusKey));
                $statusNote = $metadata['status_note'] ?? null;
                $totalAmount = data_get($order, 'total_amount', 0);
                $orderNumber = data_get($order, 'order_number', data_get($order, 'id'));
                $confirmedDate = $formatDate(data_get($order, 'updated_at'));
                $payments = collect($metadata['payments'] ?? []);
                $paidAmount = $payments->filter(fn($payment) => ($payment['status'] ?? null) === 'paid')->sum(fn($payment) => (float)($payment['amount'] ?? 0));
                $remainingBalance = max(($totalAmount ?? 0) - $paidAmount, 0);
                $hasRemainingBalance = $remainingBalance > 0.01;
            @endphp

            <div class="bg-white border rounded-xl mb-4 shadow-sm">
                <div class="flex items-center justify-between px-4 py-3 border-b bg-green-50">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-green-700 font-semibold">Your order is ready for pickup!</span>
                    </div>
                    <span class="text-sm text-gray-600">Order #{{ $orderNumber }}</span>
                </div>
                
                <div class="flex flex-col md:flex-row items-start md:items-center px-4 py-4 gap-4">
                    <img src="{{ $image }}" alt="{{ $productName }}" class="w-24 h-24 object-cover rounded-lg border">
                    <div class="flex-1">
                        <div class="font-semibold text-lg text-[#a6b7ff]">{{ $productName }}</div>
                        <div class="text-sm text-gray-500">Quantity: {{ $quantity }} pcs</div>
                        <div class="text-sm text-gray-500">Paper: {{ $paperStock }}</div>
                        <div class="text-sm text-gray-500">Status: <span class="font-semibold text-green-600">{{ $statusLabel }}</span></div>
                        <div class="text-sm text-gray-500">Confirmed: {{ $confirmedDate ?? 'Recently' }}</div>
                        @if($statusNote)
                            <div class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded text-sm text-blue-800">
                                <strong>📌 Note:</strong> {{ $statusNote }}
                            </div>
                        @endif
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold text-gray-700">₱{{ number_format($totalAmount, 2) }}</div>
                    </div>
                </div>

                <div class="px-4 py-3 bg-yellow-50 border-t border-yellow-200">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <div class="flex-1">
                            <div class="font-semibold text-yellow-800">Pickup Location</div>
                            <div class="text-sm text-yellow-700">Please visit our store to collect your order.</div>
                            <div class="text-sm text-yellow-700 mt-1">Store Hours: Monday - Saturday, 9:00 AM - 6:00 PM</div>
                        </div>
                    </div>
                </div>
                
                <div class="flex flex-col md:flex-row items-center justify-between px-4 py-3 bg-[#f7f8fa] rounded-b-xl">
                    <div class="text-sm text-gray-500 mb-2 md:mb-0">
                        Order Total: <span class="text-[#a6b7ff] font-bold text-lg">₱{{ number_format($totalAmount, 2) }}</span>
                    </div>
                    <div class="flex flex-col gap-3 md:flex-row md:items-center">
                        <div class="flex gap-2">
                            <a href="{{ route('customer.orders.details', ['order' => data_get($order, 'id')]) }}" class="bg-[#a6b7ff] hover:bg-[#8b95e5] text-white px-6 py-2 rounded font-semibold transition-colors">View Details</a>
                            <button class="border border-[#a6b7ff] text-[#a6b7ff] px-5 py-2 rounded font-semibold bg-white hover:bg-[#f0f4ff] transition-colors">Contact Shop</button>
                            @if($hasRemainingBalance)
                                <a href="{{ route('customer.pay.remaining.balance', ['order' => data_get($order, 'id')]) }}" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded font-semibold transition-colors">Pay Remaining Balance</a>
                            @endif
                        </div>
                        <form action="{{ route('customer.orders.confirm_received', ['order' => data_get($order, 'id')]) }}" method="POST" class="md:ml-4">
                            @csrf
                            <button type="submit" class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white rounded font-semibold transition-colors" onclick="return confirm('Mark this order as completed?');">Order Picked Up</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-12">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <div class="text-gray-500 text-lg">No orders ready for pickup yet.</div>
                <div class="text-gray-400 text-sm mt-2">Orders that are packed and ready will appear here.</div>
            </div>
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
@endsection
