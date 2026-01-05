@extends('layouts.customerprofile')

@section('title', 'Ready for Pickup')

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
       <div class="flex border-b text-base font-semibold mb-4">

    <a href="{{ route('customer.my_purchase.topay') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">To Pay</a>
    <a href="{{ route('customer.my_purchase.inproduction') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">In Production</a>
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
        $toshipOrders = $ordersCollection->filter(function ($order) use ($normalizeMetadata) {
            $status = data_get($order, 'status', 'pending');
            if ($status !== 'confirmed') {
                return false;
            }

            $metadata = $normalizeMetadata(data_get($order, 'metadata', []));

            $trackingNumber = $metadata['tracking_number'] ?? data_get($order, 'tracking_number');
            return empty($trackingNumber);
        })->values();
    @endphp

    <div class="space-y-4">
        @forelse($toshipOrders as $order)
            @php
                // Build a lightweight summary object compatible with ordersummary.js expectations.
                $summary = [
                    'orderId' => $order->id ?? null,
                    'productId' => $order->items->first()->product_id ?? null,
                    'productName' => $order->items->first()->product_name ?? 'Custom invitation',
                    'quantity' => (int) ($order->items->first()->quantity ?? 10),
                    'previewImage' => $order->items->first()->product->image ?? asset('images/placeholder.png'),
                    'previewImages' => [$order->items->first()->product->image ?? asset('images/placeholder.png')],
                    'totalAmount' => $order->total_amount ?? 0,
                    'originalTotal' => $order->total_amount ?? null,
                    'editUrl' => (function(){ try { return route('order.finalstep'); } catch (\Throwable $e) { return url('/order/finalstep'); } })()
                ];
                $statusKey = data_get($order, 'status', 'pending');
                $statusLabel = $statusOptions[$statusKey] ?? ucfirst(str_replace('_', ' ', $statusKey));
                $metadata = $normalizeMetadata(data_get($order, 'metadata', []));
                $trackingNumber = $metadata['tracking_number'] ?? null;
                $statusNote = $metadata['status_note'] ?? null;
                $flowIndex = array_search($statusKey, $statusFlow, true);
                $nextStatusKey = $flowIndex !== false && $flowIndex < count($statusFlow) - 1 ? $statusFlow[$flowIndex + 1] : null;
                $nextStatusLabel = $nextStatusKey ? ($statusOptions[$nextStatusKey] ?? ucfirst(str_replace('_', ' ', $nextStatusKey))) : null;
            @endphp

            <div class="bg-white border rounded-xl mb-4 shadow-sm">
                <div class="flex items-center justify-between px-4 py-3 border-b">
                    <div></div>
                    <div class="flex items-center gap-2">
                        <span class="text-blue-600 flex items-center text-xs">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            {{ $statusLabel }}
                        </span>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row items-start md:items-center px-4 py-4 gap-4">
                    <img src="{{ $order->items->first()->product->image ?? asset('customerimages/image/weddinginvite.png') }}" alt="Invitation Design" class="w-24 h-24 object-cover rounded-lg border">
                    <div class="flex-1">
                        <div class="font-semibold text-lg text-[#a6b7ff]">{{ $order->items->first()->product_name }}</div>
                        <div class="text-sm text-gray-500">Theme: {{ $order->items->first()->product->occasion ?? 'N/A' }}</div>
                        <div class="text-sm text-gray-500">Quantity: {{ $order->items->first()->quantity }} pcs</div>
                        <div class="text-sm text-gray-500">Paper: {{ $order->items->first()->paperStockSelection->paper_stock_name ?? 'Standard' }}</div>
                        @php
                            $formatAddonPrice = function ($value) {
                                if ($value === null || $value === '') {
                                    return null;
                                }

                                if (is_numeric($value)) {
                                    return (float) $value;
                                }

                                if (is_string($value)) {
                                    $numeric = preg_replace('/[^0-9.\-]/', '', $value);
                                    return $numeric === '' ? null : (float) $numeric;
                                }

                                return null;
                            };

                            $formatAddonLabel = function ($addon) use ($formatAddonPrice) {
                                $label = null;
                                $price = null;

                                if (is_array($addon)) {
                                    $label = $addon['name'] ?? $addon['label'] ?? $addon['title'] ?? $addon['value'] ?? null;
                                    $price = $formatAddonPrice($addon['price'] ?? $addon['amount'] ?? $addon['total'] ?? null);
                                } elseif (is_object($addon)) {
                                    $label = $addon->name ?? $addon->label ?? $addon->title ?? $addon->addon_name ?? null;
                                    $price = $formatAddonPrice($addon->price ?? $addon->amount ?? $addon->total ?? $addon->addon_price ?? null);
                                } else {
                                    $label = trim((string) $addon);
                                }

                                if (!$label) {
                                    try {
                                        return json_encode($addon, JSON_UNESCAPED_UNICODE);
                                    } catch (\Throwable $e) {
                                        return 'Add-on';
                                    }
                                }

                                if ($price !== null) {
                                    if ($price > 0.009) {
                                        return sprintf('%s — ₱%s', $label, number_format($price, 2));
                                    }

                                    return sprintf('%s — Included', $label);
                                }

                                return $label;
                            };

                            $collectAddons = function ($items) use ($formatAddonLabel) {
                                if ($items instanceof \Illuminate\Support\Collection) {
                                    $items = $items->all();
                                }

                                if (!is_array($items)) {
                                    return '';
                                }

                                $labels = array_filter(array_map($formatAddonLabel, $items));
                                return $labels ? implode(', ', $labels) : '';
                            };

                            $addonsDisplay = 'NONE';

                            if ($order->items->first() && $order->items->first()->addons) {
                                $addons = $order->items->first()->addons;
                                if ($addons instanceof \Illuminate\Support\Collection || is_array($addons)) {
                                    $addonsDisplay = $collectAddons($addons);
                                } else {
                                    $decoded = json_decode($addons, true);
                                    if (is_array($decoded)) {
                                        $addonsDisplay = $collectAddons($decoded);
                                    } else {
                                        $addonsDisplay = $formatAddonLabel($addons);
                                    }
                                }

                                if ($addonsDisplay === '') {
                                    $addonsDisplay = 'NONE';
                                }
                            }
                        @endphp
                        <div class="text-sm text-gray-500">Add-ons: {{ $addonsDisplay }}</div>
                        <div class="text-sm text-gray-500">Status: <span class="font-semibold text-[#a6b7ff]">{{ $statusLabel }}</span></div>
                        <div class="text-sm text-gray-500">Next step: {{ $nextStatusLabel ?? 'All steps complete' }}</div>
                        @if($trackingNumber)
                            <div class="text-sm text-gray-500">Tracking: {{ $trackingNumber }}</div>
                        @endif
                        @if($statusNote)
                            <div class="text-xs text-gray-400 mt-1">Note: {{ $statusNote }}</div>
                        @endif
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold text-gray-700">₱{{ number_format($order->total_amount, 2) }}</div>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row items-center justify-between px-4 py-3 bg-[#f7f8fa] rounded-b-xl">
                    <div class="text-sm text-gray-500 mb-2 md:mb-0">
                        Order Total: <span class="text-[#a6b7ff] font-bold text-lg">₱{{ number_format($order->total_amount, 2) }}</span>
                    </div>
                    <div class="flex gap-2">
                        @php
                            // Check if order has remaining balance to pay (assuming 50% deposit system)
                            $hasRemainingBalance = false;
                            $metadata = $normalizeMetadata(data_get($order, 'metadata', []));
                            $payments = collect($metadata['payments'] ?? []);
                            $paidAmount = $payments->filter(fn($payment) => ($payment['status'] ?? null) === 'paid')->sum(fn($payment) => (float)($payment['amount'] ?? 0));
                            $remainingBalance = max(($order->total_amount ?? 0) - $paidAmount, 0);
                            $hasRemainingBalance = $remainingBalance > 0.01; // More than 1 cent remaining
                        @endphp

                        @if($hasRemainingBalance)
                        <a href="{{ route('customer.pay.remaining.balance', ['order' => data_get($order, 'id')]) }}"
                           class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded font-semibold transition-colors duration-200">
                            Pay Remaining Balance (₱{{ number_format($remainingBalance, 2) }})
                        </a>
                        @endif

                        <button class="bg-[#a6b7ff] hover:bg-[#bce6ff] text-white px-6 py-2 rounded font-semibold">Track Order</button>
                        <button class="border border-[#a6b7ff] text-[#a6b7ff] px-5 py-2 rounded font-semibold bg-white hover:bg-[#d3b7ff]">Contact Shop</button>
                        <button class="border border-[#a6b7ff] text-[#a6b7ff] px-5 py-2 rounded font-semibold bg-white hover:bg-[#d3b7ff]">View Shop Rating</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-sm text-gray-500">No orders are awaiting shipment.</div>
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
        const blurSelector = '.js-purchase-tab, .space-y-4 button, .bg-white .flex.gap-2 button';
        const controls = Array.from(document.querySelectorAll(blurSelector));
        controls.forEach(ctrl => {
            ctrl.addEventListener('mouseup', blurAfterInteraction);
            ctrl.addEventListener('touchend', blurAfterInteraction);
            ctrl.addEventListener('click', blurAfterInteraction);
        });

        const tabs = controls.filter(ctrl => ctrl.classList.contains('js-purchase-tab'));
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
