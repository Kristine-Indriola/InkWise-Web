@extends('layouts.customerprofile')

@section('title', 'To Pay')

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex border-b text-base font-semibold mb-4">

        <a href="{{ route('customer.my_purchase.topay') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">To Pay</a>
        <a href="{{ route('customer.my_purchase.inproduction') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">In Production</a>
        <a href="{{ route('customer.my_purchase.topickup') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">Ready for Pickup</a>
        <a href="{{ route('customer.my_purchase.completed') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">Completed</a>
        <a href="{{ route('customer.my_purchase.cancelled') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">Cancelled</a>
        <a href="{{ route('customer.my_purchase.return_refund') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">Return/Refund</a>
    </div>

    @php
        $ordersSource = $orders ?? optional(auth()->user())->customer->orders ?? [];
        $ordersList = collect($ordersSource)->filter(function ($order) {
            $status = data_get($order, 'status', 'pending');
            return $status === 'pending';
        })->values();
        $placeholderImage = asset('images/placeholder.png');
        $statusOptions = [
            'pending' => 'Order Received',
            'processing' => 'Processing',
            'in_production' => 'In Progress',
            'confirmed' => 'Ready for Pickup',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
        $statusFlow = ['pending', 'processing', 'in_production', 'confirmed', 'completed'];
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
    @endphp

    <div class="space-y-4 js-to-pay-list">
        @forelse($ordersList as $order)
            @php
                $orderId = data_get($order, 'id');
                $productId = data_get($order, 'product_id');
                $productName = data_get($order, 'product_name', 'Custom invitation');
                $previewImage = data_get($order, 'image', asset('customerimages/image/weddinginvite.png'));
                $resolvedPreviewImage = $previewImage ?: $placeholderImage;
                $theme = data_get($order, 'theme', 'N/A');
                $quantity = (int) data_get($order, 'quantity', 0);
                $paper = data_get($order, 'paper', 'Standard');
                $addonsRaw = data_get($order, 'addons', []);
                $totalAmount = (float) data_get($order, 'total_amount', data_get($order, 'total', 0));
                $originalTotal = data_get($order, 'original_total');
                $statusKey = data_get($order, 'status', 'pending');
                $statusLabel = $statusOptions[$statusKey] ?? ucfirst(str_replace('_', ' ', $statusKey));
                $metadata = $normalizeMetadata(data_get($order, 'metadata', []));
                $trackingNumber = $metadata['tracking_number'] ?? null;
                $statusNote = $metadata['status_note'] ?? null;
                $flowIndex = array_search($statusKey, $statusFlow, true);
                $nextStatusKey = $flowIndex !== false && $flowIndex < count($statusFlow) - 1 ? $statusFlow[$flowIndex + 1] : null;
                $nextStatusLabel = $nextStatusKey ? ($statusOptions[$nextStatusKey] ?? ucfirst(str_replace('_', ' ', $nextStatusKey))) : null;

                $summary = [
                    'orderId' => $orderId,
                    'productId' => $productId,
                    'productName' => $productName,
                    'quantity' => $quantity ?: 10,
                    'previewImage' => $resolvedPreviewImage,
                    'previewImages' => [$resolvedPreviewImage],
                    'totalAmount' => $totalAmount,
                    'originalTotal' => $originalTotal,
                    'theme' => $theme,
                    'paper' => $paper,
                    'addons' => $addonsRaw,
                    'status' => $statusKey,
                    'statusLabel' => $statusLabel,
                    'trackingNumber' => $trackingNumber,
                    'nextStatusLabel' => $nextStatusLabel,
                    'statusNote' => $statusNote,
                    'editUrl' => (function(){ try { return route('order.finalstep'); } catch (\Throwable $e) { return url('/order/finalstep'); } })()
                ];

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

                if (!empty($addonsRaw)) {
                    if ($addonsRaw instanceof \Illuminate\Support\Collection || is_array($addonsRaw)) {
                        $addonsDisplay = $collectAddons($addonsRaw);
                    } else {
                        $decoded = json_decode($addonsRaw, true);
                        if (is_array($decoded)) {
                            $addonsDisplay = $collectAddons($decoded);
                        } else {
                            $addonsDisplay = $formatAddonLabel($addonsRaw);
                        }
                    }

                    if ($addonsDisplay === '') {
                        $addonsDisplay = 'NONE';
                    }
                }
            @endphp

            <div class="bg-white border rounded-xl mb-4 shadow-sm" data-summary-order-id="{{ $orderId ?? '' }}">
                <div class="flex items-center justify-between px-4 py-3 border-b">
                    <div></div>
                    <div class="flex items-center gap-2">
                        <span class="text-yellow-600 flex items-center text-xs">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z M21 12.5C21 18 17.5 22 12 22S3 18 3 12.5 6.5 3 12 3s9 4.5 9 9.5z"/></svg>
                            {{ $statusLabel }}
                        </span>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row items-start md:items-center px-4 py-4 gap-4">
                    <img src="{{ $resolvedPreviewImage }}" alt="Invitation Design" class="w-24 h-24 object-cover rounded-lg border">
                    <div class="flex-1">
                        <div class="font-semibold text-lg text-[#a6b7ff]">{{ $productName }}</div>
                        <div class="text-sm text-gray-500">Theme: {{ $theme }}</div>
                        <div class="text-sm text-gray-500">Quantity: {{ $quantity ?: '—' }} pcs</div>
                        <div class="text-sm text-gray-500">Paper: {{ $paper }}</div>
                        <div class="text-sm text-gray-500">Add-ons: {{ $addonsDisplay }}</div>
                        <div class="text-sm text-gray-500">Status: <span class="font-semibold text-[#a6b7ff]">{{ $statusLabel }}</span></div>
                        <div class="text-sm text-red-500">Pay within: <span class="js-payment-timer" data-order-id="{{ $orderId }}" data-deadline="{{ now()->addHours(24)->toISOString() }}">24:00:00</span></div>
                        @if($trackingNumber)
                            <div class="text-sm text-gray-500">Tracking: {{ $trackingNumber }}</div>
                        @endif
                        @if($statusNote)
                            <div class="text-xs text-gray-400 mt-1">Note: {{ $statusNote }}</div>
                        @endif
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold text-gray-700">₱{{ number_format($totalAmount, 2) }}</div>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row items-center justify-between px-4 py-3 bg-[#f7f8fa] rounded-b-xl">
                    <div class="text-sm text-gray-500 mb-2 md:mb-0">
                        Order Total: <span class="text-[#a6b7ff] font-bold text-lg">₱{{ number_format($totalAmount, 2) }}</span>
                    </div>
                    <div class="flex gap-2">
                        <button class="bg-[#a6b7ff] hover:bg-[#bce6ff] text-white px-4 py-2 rounded font-semibold js-to-pay-checkout" type="button" data-summary='@json($summary)' data-mode="half">Pay Deposit (₱{{ number_format($totalAmount / 2, 2) }})</button>
                        <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded font-semibold js-to-pay-checkout-full" type="button" data-summary='@json($summary)' data-mode="full">Pay in Full (₱{{ number_format($totalAmount, 2) }})</button>
                        <button type="button" class="border border-gray-300 text-gray-700 px-5 py-2 rounded font-semibold js-to-pay-cancel" data-order-id="{{ $orderId ?? '' }}">Cancel</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-sm text-gray-500">No orders awaiting payment.</div>
        @endforelse
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkoutUrl = @json(route('customer.checkout'));
    const clearSummaryUrl = @json(route('order.summary.clear'));
    const buttons = Array.from(document.querySelectorAll('.js-to-pay-checkout'));
    if (!buttons.length) {
        console.log('No checkout buttons found');
        return;
    }

    console.log('Found', buttons.length, 'checkout buttons');

    buttons.forEach((btn, index) => {
        btn.addEventListener('click', (event) => {
            event.preventDefault(); // Prevent any default behavior

            try {
                const raw = btn.getAttribute('data-summary');
                const summary = raw ? JSON.parse(raw) : null;
                const mode = btn.getAttribute('data-mode') || 'half'; // Default to half for backward compatibility

                if (summary) {
                    // Add payment mode to summary
                    summary.paymentMode = mode;
                    window.sessionStorage.setItem('inkwise-finalstep', JSON.stringify(summary));
                }
            } catch (err) {
                console.warn('failed to save order summary to sessionStorage', err);
            }

            window.location.href = checkoutUrl;
        });
    });

    // Handle full payment buttons
    const fullButtons = Array.from(document.querySelectorAll('.js-to-pay-checkout-full'));
    fullButtons.forEach((btn, index) => {
        btn.addEventListener('click', (event) => {
            event.preventDefault(); // Prevent any default behavior

            try {
                const raw = btn.getAttribute('data-summary');
                const summary = raw ? JSON.parse(raw) : null;

                if (summary) {
                    // Force full payment mode
                    summary.paymentMode = 'full';
                    window.sessionStorage.setItem('inkwise-finalstep', JSON.stringify(summary));
                }
            } catch (err) {
                console.warn('failed to save order summary to sessionStorage', err);
            }

            window.location.href = checkoutUrl;
        });
    });    const cancels = Array.from(document.querySelectorAll('.js-to-pay-cancel'));
    cancels.forEach(btn => {
        btn.addEventListener('click', async () => {
            const orderId = btn.getAttribute('data-order-id');
            if (!orderId) {
                console.warn('No order ID found for cancel button');
                return;
            }

            if (!confirm('Cancel this order? If you have already paid, a refund will be processed to your account.')) {
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
                    const card = btn.closest('.bg-white.border.rounded-xl') || btn.closest('[data-summary-order-id]');
                    if (card) {
                        card.remove();
                    }

                    // Clear sessionStorage if it matches this order
                    const stored = window.sessionStorage.getItem('inkwise-finalstep');
                    if (stored) {
                        try {
                            const summary = JSON.parse(stored);
                            if (summary && summary.orderId == orderId) {
                                window.sessionStorage.removeItem('inkwise-finalstep');
                                // Clear server summary
                                await fetch(clearSummaryUrl, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                                    }
                                });
                            }
                        } catch (sessionError) {
                            console.warn('failed to clear sessionStorage', sessionError);
                        }
                    }

                    // Show success message
                    const message = result.message || result.status || 'Order cancelled successfully.';
                    alert(message);
                } else {
                    const errorData = await response.json().catch(() => ({}));
                    alert(errorData.message || 'Failed to cancel order. Please try again.');
                }
            } catch (error) {
                console.error('Cancel order error:', error);
                alert('Failed to cancel order. Please check your connection and try again.');
            }
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.classList.contains('js-client-cancel')) {
            return;
        }

        const card = event.target.closest('.bg-white.border.rounded-xl') || event.target.closest('[data-summary-order-id]');
        if (card) {
            card.remove();
        }
        window.sessionStorage.removeItem('inkwise-finalstep');
    });

    document.addEventListener('click', (event) => {
        if (!event.target.classList.contains('js-client-checkout')) {
            return;
        }

        window.location.href = checkoutUrl;
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const placeholderImage = @json($placeholderImage);

    function renderAddons(addons) {
        if (!addons) {
            return 'NONE';
        }

        if (typeof addons === 'string') {
            try {
                const parsed = JSON.parse(addons);
                if (Array.isArray(parsed)) {
                    addons = parsed;
                } else {
                    return addons;
                }
            } catch (e) {
                return addons;
            }
        }

        const parsePrice = (value) => {
            if (value === null || value === undefined || value === '') {
                return null;
            }
            if (typeof value === 'number') {
                return Number.isFinite(value) ? value : null;
            }
            const numeric = Number.parseFloat(String(value).replace(/[^0-9.\-]/g, ''));
            return Number.isFinite(numeric) ? numeric : null;
        };

        const formatAddon = (item) => {
            if (!item) {
                return '';
            }

            if (typeof item === 'string') {
                return item;
            }

            const label = item.name || item.label || item.title || item.addon_name || item.value || '';
            const price = parsePrice(item.price ?? item.amount ?? item.total ?? item.addon_price);

            if (!label) {
                try {
                    return JSON.stringify(item);
                } catch (e) {
                    return String(item);
                }
            }

            if (price !== null) {
                if (price > 0.009) {
                    return `${label} — ₱${price.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                }

                return `${label} — Included`;
            }

            return label;
        };

        if (Array.isArray(addons)) {
            return addons.map(formatAddon).filter(Boolean).join(', ');
        }

        if (typeof addons === 'object') {
            return formatAddon(addons);
        }

        return String(addons);
    }
});
</script>

<script>
// Normalize nav hover/active behavior
(function () {
    const activeClasses = ['border-b-2', 'border-[#a6b7ff]', 'text-[#a6b7ff]'];
    const inactiveTextClass = 'text-gray-500';

    function blurAfterInteraction(event) {
        try {
            const control = event.currentTarget;
            setTimeout(() => {
                if (document.activeElement === control) {
                    control.blur();
                }
            }, 50);
        } catch (error) {
            // ignore
        }
    }

    function normalizePath(url) {
        try {
            return new URL(url, window.location.origin).pathname.replace(/\/+$/, '') || '/';
        } catch (error) {
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
        const blurSelector = '.js-purchase-tab, .js-to-pay-list button, .bg-white .flex.gap-2 button, .js-to-pay-checkout';
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

<script>
// Payment Timer Countdown
(function () {
    const clearSummaryUrl = @json(route('order.summary.clear'));

    function formatTime(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    async function cancelOrder(card, orderId) {
        try {
            // Remove the card from DOM
            card.remove();

            // Clear session storage if it matches
            const stored = window.sessionStorage.getItem('inkwise-finalstep');
            if (stored) {
                const summary = JSON.parse(stored);
                if (summary && summary.orderId == orderId) {
                    window.sessionStorage.removeItem('inkwise-finalstep');
                    // Clear server summary
                    await fetch(clearSummaryUrl, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        }
                    });
                }
            }
        } catch (err) {
            console.warn('failed to auto-cancel expired order', err);
        }
    }

    function updateTimers() {
        const timers = document.querySelectorAll('.js-payment-timer');
        timers.forEach(timer => {
            const deadline = new Date(timer.getAttribute('data-deadline'));
            const now = new Date();
            const remaining = Math.max(0, Math.floor((deadline - now) / 1000));

            timer.textContent = formatTime(remaining);

            if (remaining === 0) {
                // Payment expired - auto cancel the order
                timer.textContent = 'EXPIRED';
                timer.classList.add('text-gray-500');
                timer.classList.remove('text-red-500');

                // Auto cancel the order
                const card = timer.closest('.bg-white.border.rounded-xl');
                if (card) {
                    const orderId = timer.getAttribute('data-order-id');
                    cancelOrder(card, orderId);
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateTimers();
        setInterval(updateTimers, 1000);
    });
})();
</script>

@endsection
