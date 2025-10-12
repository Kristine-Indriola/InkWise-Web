@extends('layouts.customerprofile')

@section('title', 'My Purchases')

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <!-- Tabs -->
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
        // Expect a collection/array of orders passed in as $orders
        $ordersList = $orders ?? [];
        // Map of status keys to human labels and optional classes
        $statusMap = [
            'to_pay' => 'To Pay',
            'to_ship' => 'To Ship',
            'to_receive' => 'To Receive',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'return_refund' => 'Return/Refund',
        ];

        // Group orders by status (assumes each order has a ->status property or attribute)
        $grouped = [];
        foreach ($ordersList as $o) {
            $s = $o->status ?? ($o['status'] ?? 'to_pay');
            $grouped[$s][] = $o;
        }
    @endphp

    @foreach($statusMap as $statusKey => $statusLabel)
        <section class="mb-6">
            <h3 class="text-lg font-semibold mb-3">{{ $statusLabel }}</h3>
            @if(!empty($grouped[$statusKey]))
                <div class="space-y-4">
                    @foreach($grouped[$statusKey] as $order)
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
        
                                </div>
                            </div>
                            <div class="flex flex-col md:flex-row items-start md:items-center px-4 py-4 gap-4">
                                <img src="{{ $previewImage }}" alt="Invitation Design" class="w-24 h-24 object-cover rounded-lg border">
                                <div class="flex-1">
                                    <div class="font-semibold text-lg text-[#a6b7ff]">{{ $productName }}</div>
                                    <div class="text-sm text-gray-500">Theme: {{ $order->theme ?? ($order['theme'] ?? 'N/A') }}</div>
                                    <div class="text-sm text-gray-500">Quantity: {{ $quantity }} pcs</div>
                                    <div class="text-sm text-gray-500">Paper: {{ $paper }}</div>
                                    <div class="text-sm text-gray-500">Add-ons: {{ $addonsDisplay }}</div>
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
                                    <button class="bg-[#a6b7ff] hover:bg-[#bce6ff] text-white px-6 py-2 rounded font-semibold transition-colors duration-150">Order Again</button>
                                    <button class="border border-[#a6b7ff] text-[#a6b7ff] px-5 py-2 rounded font-semibold bg-white hover:bg-[#bce6ff] hover:text-white transition-colors duration-150">Contact Shop</button>
                                    <button class="border border-[#a6b7ff] text-[#a6b7ff] px-5 py-2 rounded font-semibold bg-white hover:bg-[#bce6ff] hover:text-white transition-colors duration-150">View Shop Rating</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                @if($statusKey === 'completed')
                    <!-- Sample Completed card (fallback) -->
                    <div class="bg-white border rounded-xl mb-4 shadow-sm">
                        <div class="flex items-center justify-between px-4 py-3 border-b">
                            <div></div>
                            <div class="flex items-center gap-2">
                                <span class="text-green-600 flex items-center text-xs">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2l4-4"/></svg>
                                    Parcel has been delivered
                                </span>
                                <span class="text-[#f4511e] text-xs font-semibold">RATED</span>
                            </div>
                        </div>
                        <div class="flex flex-col md:flex-row items-start md:items-center px-4 py-4 gap-4">
                            <img src="{{ asset('customerimages/image/weddinginvite.png') }}" alt="Invitation Design" class="w-24 h-24 object-cover rounded-lg border">
                            <div class="flex-1">
                                <div class="font-semibold text-lg text-[#a6b7ff]">Elegant Wedding Invitation</div>
                                <div class="text-sm text-gray-500">Theme: Rustic Floral</div>
                                <div class="text-sm text-gray-500">Quantity: 50 pcs</div>
                                <div class="text-sm text-gray-500">Paper: Premium Matte</div>
                                <div class="text-sm text-gray-500">Add-ons: Wax Seal, Envelope</div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold text-gray-700">₱2,500</div>
                            </div>
                        </div>
                        <div class="flex flex-col md:flex-row items-center justify-between px-4 py-3 bg-[#f7f8fa] rounded-b-xl">
                            <div class="text-sm text-gray-500 mb-2 md:mb-0">
                                Order Total: <span class="text-[#a6b7ff] font-bold text-lg">₱2,500</span>
                            </div>
                            <div class="flex gap-2">
                                <button class="bg-[#a6b7ff] hover:bg-[#bce6ff] text-white px-6 py-2 rounded font-semibold transition-colors duration-150">Order Again</button>
                                <button class="border border-[#a6b7ff] text-[#a6b7ff] px-5 py-2 rounded font-semibold bg-white hover:bg-[#bce6ff] hover:text-white transition-colors duration-150">Contact Shop</button>
                                <button class="border border-[#a6b7ff] text-[#a6b7ff] px-5 py-2 rounded font-semibold bg-white hover:bg-[#bce6ff] hover:text-white transition-colors duration-150">View Shop Rating</button>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-sm text-gray-500">No orders in this category.</div>
                @endif
            @endif
        </section>
    @endforeach
    
    <script>
    // Render stored summary into the My Purchases card area when present
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

            // find the existing purchase card container
            const existingCard = document.querySelector('.bg-white.border.rounded-xl.mb-4.shadow-sm');
            // if the page already contains a stored summary with same order id, don't overwrite
            if (summary.orderId) {
                const existingSummary = document.querySelector(`[data-summary-order-id="${summary.orderId}"]`);
                if (existingSummary) return;
            }
            if (!existingCard) return;

            // replace inner content with the summary card markup
            existingCard.innerHTML = `
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
                    <img src="${summary.previewImage || '{{ asset('customerimages/image/weddinginvite.png') }}'}" alt="Invitation Design" class="w-24 h-24 object-cover rounded-lg border">
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
                        <button class="bg-[#a6b7ff] hover:bg-[#bce6ff] text-white px-6 py-2 rounded font-semibold">Order Again</button>
                        <button class="border border-[#a6b7ff] text-[#a6b7ff] px-5 py-2 rounded font-semibold bg-white">Contact Shop</button>
                        <button class="border border-[#a6b7ff] text-[#a6b7ff] px-5 py-2 rounded font-semibold bg-white">View Shop Rating</button>
                    </div>
                </div>
            `;
        } catch (err) {
            console.warn('failed to render stored summary in my_purchase', err);
        }
    });
    </script>

    <script>
    // Prevent buttons from getting stuck in a hover/focus state on some browsers/devices
    (function () {
        function handleInteraction(ev) {
            try {
                const btn = ev.currentTarget;
                // Delay slightly to allow any click handlers to run
                setTimeout(() => {
                    if (document.activeElement === btn) {
                        btn.blur();
                    }
                }, 50);
            } catch (e) {
                // ignore
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const selector = '.bg-white .flex.gap-2 button, .flex.border-b button, .flex.gap-2 button';
            const buttons = Array.from(document.querySelectorAll(selector));
            buttons.forEach(b => {
                b.addEventListener('mouseup', handleInteraction);
                b.addEventListener('touchend', handleInteraction);
                b.addEventListener('click', handleInteraction);
            });
        });
    })();
    </script>
    <script>
    // Active-tab underline + blur handler (mirrors Topay behavior)
    (function () {
        const activeClasses = ['border-b-2', 'border-[#a6b7ff]', 'text-[#a6b7ff]'];
        const inactiveTextClass = 'text-gray-500';

        function blurAfterInteraction(ev) {
            try {
                const control = ev.currentTarget;
                setTimeout(() => {
                    if (document.activeElement === control) control.blur();
                }, 50);
            } catch (e) { /* ignore */ }
        }

        function normalizePath(url) {
            try { return new URL(url, window.location.origin).pathname.replace(/\/+$/, '') || '/'; }
            catch (e) { return url; }
        }

        function setActiveTab(tabs, target) {
            tabs.forEach(tab => {
                tab.classList.remove(...activeClasses);
                if (!tab.classList.contains(inactiveTextClass)) tab.classList.add(inactiveTextClass);
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
            if (!tabs.length) return;

            const currentPath = normalizePath(window.location.href);
            let activeTab = null;

            tabs.forEach(tab => {
                if (tab.tagName === 'A') {
                    const tabPath = normalizePath(tab.getAttribute('href'));
                    if (tabPath === currentPath) activeTab = tab;
                } else if (!activeTab && tab.dataset.route === 'all') {
                    activeTab = tab;
                }
            });

            if (!activeTab) activeTab = tabs.find(tab => tab.dataset.route === 'all') || tabs[0];

            setActiveTab(tabs, activeTab);

            tabs.forEach(tab => tab.addEventListener('click', () => setActiveTab(tabs, tab)));
        });
    })();
    </script>
</div>
@endsection