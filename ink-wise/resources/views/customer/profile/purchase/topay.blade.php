@extends('layouts.customerprofile')

@section('title', 'To Pay')

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <!-- Tabs (mirror of my_purchase) -->
         <div class="flex border-b text-base font-semibold mb-4">
    <a href="{{ route('customer.my_purchase') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab" data-route="all">All</a>
     <a href="{{ route('customer.my_purchase.topay') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">To Pay</a>
     <a href="{{ route('customer.my_purchase.toship') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">To Ship</a>
     <a href="{{ route('customer.my_purchase.toreceive') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">To Receive</a>
     <a href="{{ route('customer.my_purchase.completed') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">Completed</a>
     <a href="{{ route('customer.my_purchase.cancelled') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">Cancelled</a>
     <a href="{{ route('customer.my_purchase.return_refund') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">Return/Refund</a>
     </div>

    @php
        // Expect $orders to be passed to this view. Do not render placeholder/sample data here.
        $ordersList = $orders ?? [];
    @endphp

    <div class="space-y-4">
        @foreach($ordersList as $order)
            @php
                // Build a lightweight summary object compatible with ordersummary.js expectations.
                $summary = [
                    'orderId' => $order->id ?? null,
                    'productId' => $order->product_id ?? null,
                    'productName' => $order->product_name ?? 'Custom invitation',
                    'quantity' => (int) ($order->quantity ?? 10),
                    'previewImage' => $order->image ?? asset('images/placeholder.png'),
                    'previewImages' => [$order->image ?? asset('images/placeholder.png')],
                    'totalAmount' => $order->total_amount ?? ($order->total ?? 0),
                    'originalTotal' => $order->original_total ?? null,
                    'editUrl' => (function(){ try { return route('order.finalstep'); } catch (\Throwable $e) { return url('/order/finalstep'); } })()
                ];
            @endphp

            <div class="bg-white border rounded-xl mb-4 shadow-sm">
                <div class="flex items-center justify-between px-4 py-3 border-b">
                    <div></div>
                    <div class="flex items-center gap-2">
                        <span class="text-yellow-600 flex items-center text-xs">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z M21 12.5C21 18 17.5 22 12 22S3 18 3 12.5 6.5 3 12 3s9 4.5 9 9.5z"/></svg>
                            Pending payment
                        </span>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row items-start md:items-center px-4 py-4 gap-4">
                    <img src="{{ $order->image ?? asset('customerimages/image/weddinginvite.png') }}" alt="Invitation Design" class="w-24 h-24 object-cover rounded-lg border">
                    <div class="flex-1">
                        <div class="font-semibold text-lg text-[#a6b7ff]">{{ $order->product_name }}</div>
                        <div class="text-sm text-gray-500">Theme: {{ $order->theme ?? 'N/A' }}</div>
                        <div class="text-sm text-gray-500">Quantity: {{ $order->quantity }} pcs</div>
                        <div class="text-sm text-gray-500">Paper: {{ $order->paper ?? 'Standard' }}</div>
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

                            if (!empty($order->addons)) {
                                if ($order->addons instanceof \Illuminate\Support\Collection || is_array($order->addons)) {
                                    $addonsDisplay = $collectAddons($order->addons);
                                } else {
                                    $decoded = json_decode($order->addons, true);
                                    if (is_array($decoded)) {
                                        $addonsDisplay = $collectAddons($decoded);
                                    } else {
                                        $addonsDisplay = $formatAddonLabel($order->addons);
                                    }
                                }

                                if ($addonsDisplay === '') {
                                    $addonsDisplay = 'NONE';
                                }
                            }
                        @endphp
                        <div class="text-sm text-gray-500">Add-ons: {{ $addonsDisplay }}</div>
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
                        <button class="bg-[#a6b7ff] hover:bg-[#bce6ff] text-white px-6 py-2 rounded font-semibold js-to-pay-checkout" type="button" data-summary='@json($summary)'>Checkout</button>
                        <button class="border border-[#a6b7ff] text-[#a6b7ff] px-5 py-2 rounded font-semibold bg-white hover:bg-[#d3b7ff]">Contact Shop</button>
                        <button class="border border-[#a6b7ff] text-[#a6b7ff] px-5 py-2 rounded font-semibold bg-white hover:bg-[#d3b7ff]">View Shop Rating</button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const buttons = Array.from(document.querySelectorAll('.js-to-pay-checkout'));
    if (!buttons.length) return;
    buttons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            try {
                const raw = btn.getAttribute('data-summary');
                const summary = raw ? JSON.parse(raw) : null;
                if (summary) {
                    // store under the same key other pages expect
                    window.sessionStorage.setItem('inkwise-finalstep', JSON.stringify(summary));
                }
            } catch (err) {
                // ignore, continue to redirect
                console.warn('failed to save order summary to sessionStorage', err);
            }
            // Redirect to the order summary page where the saved summary will be picked up
            window.location.href = '{{ route('order.summary') }}';
        });
    });
});
</script>

<script>
// If the user has a saved summary in sessionStorage, render it into the Topay list
document.addEventListener('DOMContentLoaded', function () {
    function renderAddons(addons) {
        if (!addons) return 'NONE';
        if (typeof addons === 'string') {
            try {
                const parsed = JSON.parse(addons);
                if (Array.isArray(parsed)) addons = parsed;
                else return addons;
            } catch (e) {
                return addons;
            }
        }

        const parsePrice = (value) => {
            if (value === null || value === undefined || value === '') return null;
            if (typeof value === 'number') return Number.isFinite(value) ? value : null;
            const numeric = Number.parseFloat(String(value).replace(/[^0-9.\-]/g, ''));
            return Number.isFinite(numeric) ? numeric : null;
        };

        const formatAddon = (item) => {
            if (!item) return '';
            if (typeof item === 'string') return item;

            const label = item.name || item.label || item.title || item.addon_name || item.value || '';
            const price = parsePrice(item.price ?? item.amount ?? item.total ?? item.addon_price);

            if (!label) {
                try { return JSON.stringify(item); } catch (e) { return String(item); }
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

    try {
        const raw = window.sessionStorage.getItem('inkwise-finalstep');
        if (!raw) return;
        const summary = JSON.parse(raw);
        if (!summary) return;

        const container = document.querySelector('.space-y-4');
        if (!container) return;
        // avoid duplicating the same order if already present
        if (summary.orderId) {
            const already = container.querySelector(`[data-summary-order-id="${summary.orderId}"]`);
            if (already) return;
        }

        // Create a card element that matches the purchase card used in this view
        const card = document.createElement('div');
        card.setAttribute('data-summary-order-id', summary.orderId ?? '');
        card.className = 'bg-white border rounded-xl mb-4 shadow-sm';
        card.innerHTML = `
            <div class="flex items-center justify-between px-4 py-3 border-b">
                <div></div>
                <div class="flex items-center gap-2">
                    <span class="text-yellow-600 flex items-center text-xs">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z M21 12.5C21 18 17.5 22 12 22S3 18 3 12.5 6.5 3 12 3s9 4.5 9 9.5z"/></svg>
                        Pending payment
                    </span>
                </div>
            </div>
            <div class="flex flex-col md:flex-row items-start md:items-center px-4 py-4 gap-4">
                <img src="${summary.previewImage || '{{ asset('images/placeholder.png') }}'}" alt="Invitation Design" class="w-24 h-24 object-cover rounded-lg border">
                    <div class="flex-1">
                    <div class="font-semibold text-lg text-[#a6b7ff]">${summary.productName || 'Custom invitation'}</div>
                    <div class="text-sm text-gray-500">Theme: ${summary.theme || 'N/A'}</div>
                    <div class="text-sm text-gray-500">Quantity: ${summary.quantity || 10} pcs</div>
                    <div class="text-sm text-gray-500">Paper: ${summary.paper || 'Standard'}</div>
                        <div class="text-sm text-gray-500">Add-ons: ${renderAddons(summary.addons)}</div>
                </div>
                <div class="text-right">
                    <div class="text-lg font-bold text-gray-700">₱${(Number(summary.totalAmount) || 0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</div>
                </div>
            </div>
            <div class="flex flex-col md:flex-row items-center justify-between px-4 py-3 bg-[#f7f8fa] rounded-b-xl">
                <div class="text-sm text-gray-500 mb-2 md:mb-0">
                    Order Total: <span class="text-[#a6b7ff] font-bold text-lg">₱${(Number(summary.totalAmount) || 0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</span>
                </div>
                <div class="flex gap-2">
                    <button class="bg-[#a6b7ff] hover:bg-[#bce6ff] text-white px-6 py-2 rounded font-semibold">Checkout</button>
                    <button class="border border-[#a6b7ff] text-[#a6b7ff] px-5 py-2 rounded font-semibold bg-white">Contact Shop</button>
                    <button class="border border-[#a6b7ff] text-[#a6b7ff] px-5 py-2 rounded font-semibold bg-white">View Shop Rating</button>
                </div>
            </div>
        `;

    // prepend so stored selection appears on top
        container.prepend(card);
    } catch (err) {
        // do nothing if parsing fails
        console.warn('failed to render stored summary in topay', err);
    }
});
</script>

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
        const blurSelector = '.js-purchase-tab, .space-y-4 button, .bg-white .flex.gap-2 button, .js-to-pay-checkout';
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
            activeTab = tabs[0];
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
