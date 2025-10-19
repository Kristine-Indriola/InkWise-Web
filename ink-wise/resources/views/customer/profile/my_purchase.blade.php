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
        $ordersList = collect($orders ?? []);

        $statusCategories = [
            'to_pay' => 'To Pay',
            'to_ship' => 'To Ship',
            'to_receive' => 'To Receive',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'return_refund' => 'Return/Refund',
        ];

        $statusLabels = [
            'pending' => 'Awaiting Payment',
            'in_production' => 'In Progress',
            'processing' => 'In Progress',
            'confirmed' => 'Out for Delivery',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'return_requested' => 'Return Requested',
            'refund_in_review' => 'Refund in Review',
            'refunded' => 'Refunded',
        ];

        $statusToCategory = [
            'pending' => 'to_pay',
            'awaiting_payment' => 'to_pay',
            'processing' => 'to_ship',
            'in_production' => 'to_ship',
            'confirmed' => 'to_receive',
            'ready_to_ship' => 'to_receive',
            'completed' => 'completed',
            'delivered' => 'completed',
            'cancelled' => 'cancelled',
            'void' => 'cancelled',
            'return_requested' => 'return_refund',
            'refund_in_review' => 'return_refund',
            'refunded' => 'return_refund',
        ];

        $statusBadgeClasses = [
            'pending' => 'bg-yellow-100 text-yellow-700',
            'processing' => 'bg-blue-100 text-blue-700',
            'in_production' => 'bg-blue-100 text-blue-700',
            'confirmed' => 'bg-sky-100 text-sky-700',
            'completed' => 'bg-green-100 text-green-700',
            'cancelled' => 'bg-red-100 text-red-600',
            'return_requested' => 'bg-amber-100 text-amber-700',
            'refund_in_review' => 'bg-amber-100 text-amber-700',
            'refunded' => 'bg-emerald-100 text-emerald-700',
        ];

        $formatCurrency = function ($value) {
            if ($value === null || $value === '') {
                return '0.00';
            }
            if (is_numeric($value)) {
                return number_format((float) $value, 2);
            }
            $numeric = preg_replace('/[^0-9.\-]/', '', (string) $value);
            return $numeric === '' ? '0.00' : number_format((float) $numeric, 2);
        };

        $describeAddons = function ($addons) use ($formatCurrency) {
            if (!$addons) {
                return 'None';
            }

            $normalize = function ($item) use (&$normalize) {
                if ($item instanceof \Illuminate\Support\Collection) {
                    return $item->toArray();
                }
                if (is_object($item)) {
                    return json_decode(json_encode($item), true);
                }
                if (is_string($item)) {
                    $decoded = json_decode($item, true);
                    return json_last_error() === JSON_ERROR_NONE ? $decoded : $item;
                }
                return $item;
            };

            $addons = $normalize($addons);

            $formatAddon = function ($addon) use ($formatCurrency, $normalize) {
                if ($addon === null || $addon === '') {
                    return null;
                }

                if (is_string($addon)) {
                    return $addon;
                }

                if (!is_array($addon)) {
                    $addon = $normalize($addon);
                }

                if (!is_array($addon)) {
                    return null;
                }

                $label = $addon['name'] ?? $addon['label'] ?? $addon['title'] ?? $addon['value'] ?? null;
                $price = $addon['price'] ?? $addon['amount'] ?? $addon['total'] ?? $addon['addon_price'] ?? null;

                if (!$label) {
                    return null;
                }

                if ($price === null || $price === '' || (float) $price === 0.0) {
                    return $label . ' — Included';
                }

                return $label . ' — ₱' . $formatCurrency($price);
            };

            if (is_array($addons)) {
                $labels = array_filter(array_map($formatAddon, $addons));
                return $labels ? implode(', ', $labels) : 'None';
            }

            return $formatAddon($addons) ?? 'None';
        };

        $fallbackImage = asset('customerimages/image/weddinginvite.png');

        $grouped = [];
        foreach ($ordersList as $orderItem) {
            $rawStatus = strtolower((string) data_get($orderItem, 'status', 'pending'));
            $categoryKey = $statusToCategory[$rawStatus] ?? (($statusCategories[$rawStatus] ?? null) ? $rawStatus : 'to_pay');
            $grouped[$categoryKey][] = $orderItem;
        }
    @endphp

    @foreach($statusCategories as $statusKey => $statusLabel)
        <section class="mb-6">
            <h3 class="text-lg font-semibold mb-3">{{ $statusLabel }}</h3>
            @if(!empty($grouped[$statusKey]))
                <div class="space-y-4">
                    @foreach($grouped[$statusKey] as $order)
                        @php
                            $rawStatus = strtolower((string) data_get($order, 'status', 'pending'));
                            $customerStatusLabel = $statusLabels[$rawStatus] ?? ucfirst(str_replace('_', ' ', $rawStatus));
                            $badgeClass = $statusBadgeClasses[$rawStatus] ?? 'bg-gray-100 text-gray-600';

                            $orderId = data_get($order, 'id');
                            $orderNumber = data_get($order, 'order_number', ($orderId ? 'ORD-' . str_pad((string) $orderId, 5, '0', STR_PAD_LEFT) : 'Order'));
                            $placedAtRaw = data_get($order, 'order_date', data_get($order, 'created_at'));
                            try {
                                $placedAtDisplay = $placedAtRaw ? \Illuminate\Support\Carbon::parse($placedAtRaw)->format('M j, Y') : '—';
                            } catch (\Throwable $e) {
                                $placedAtDisplay = '—';
                            }

                            $items = collect(data_get($order, 'items', []));
                            $primaryItem = $items->first();
                            $productName = data_get($primaryItem, 'name', data_get($order, 'product_name', 'Custom invitation'));
                            $previewImage = data_get($primaryItem, 'preview_images.0', data_get($primaryItem, 'images.0', $fallbackImage));
                            $quantityValue = data_get($primaryItem, 'quantity', data_get($order, 'items_count', 0));
                            $quantityLabel = $quantityValue ? (int) $quantityValue . ' pcs' : '—';

                            $paperOption = data_get($primaryItem, 'options.paper_stock', data_get($order, 'paper', 'Standard'));
                            if (is_array($paperOption)) {
                                $paperOption = implode(', ', array_filter($paperOption));
                            }
                            $paperLabel = $paperOption ?: 'Standard';

                            $addonsDisplay = $describeAddons(data_get($primaryItem, 'options.addons', data_get($order, 'addons', [])));

                            $totalAmountRaw = data_get($order, 'grand_total', data_get($order, 'total_amount', data_get($order, 'subtotal', 0)));
                            $totalAmount = '₱' . $formatCurrency($totalAmountRaw);

                            $trackingNumber = data_get($order, 'metadata.tracking_number', data_get($order, 'tracking_number'));

                            $cancelableStatuses = ['pending', 'awaiting_payment'];
                            $canCancel = $orderId && in_array($rawStatus, $cancelableStatuses, true);
                        @endphp

                        <div class="bg-white border rounded-xl shadow-sm" data-customer-order-card data-order-id="{{ $orderId }}">
                            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between px-4 py-3 border-b">
                                <div class="text-sm text-gray-500">
                                    <span class="font-semibold text-gray-700">{{ $orderNumber }}</span>
                                    <span class="ml-2">Placed {{ $placedAtDisplay }}</span>
                                    @if($trackingNumber)
                                        <span class="ml-2">· Tracking # {{ $trackingNumber }}</span>
                                    @endif
                                </div>
                                <span class="inline-flex items-center gap-2 text-xs font-semibold px-3 py-1 rounded-full {{ $badgeClass }}">
                                    {{ $customerStatusLabel }}
                                </span>
                            </div>

                            <div class="flex flex-col md:flex-row items-start md:items-center px-4 py-4 gap-4">
                                <img src="{{ $previewImage }}" alt="Order Preview" class="w-24 h-24 object-cover rounded-lg border">
                                <div class="flex-1">
                                    <div class="font-semibold text-lg text-[#a6b7ff]">{{ $productName }}</div>
                                    <dl class="mt-2 space-y-1 text-sm text-gray-500">
                                        <div class="flex gap-2">
                                            <dt class="min-w-[88px]">Theme:</dt>
                                            <dd>{{ data_get($order, 'theme', 'N/A') }}</dd>
                                        </div>
                                        <div class="flex gap-2">
                                            <dt class="min-w-[88px]">Quantity:</dt>
                                            <dd>{{ $quantityLabel }}</dd>
                                        </div>
                                        <div class="flex gap-2">
                                            <dt class="min-w-[88px]">Paper:</dt>
                                            <dd>{{ $paperLabel }}</dd>
                                        </div>
                                        <div class="flex gap-2">
                                            <dt class="min-w-[88px]">Add-ons:</dt>
                                            <dd>{{ $addonsDisplay }}</dd>
                                        </div>
                                    </dl>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs uppercase tracking-wide text-gray-400">Total</div>
                                    <div class="text-lg font-bold text-gray-700">{{ $totalAmount }}</div>
                                </div>
                            </div>

                            <div class="flex flex-col md:flex-row items-center justify-between px-4 py-3 bg-[#f7f8fa] rounded-b-xl">
                                <div class="text-sm text-gray-500 mb-2 md:mb-0">
                                    Thank you for shopping with InkWise! Need help? Contact us anytime.
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @if($canCancel)
                                        <form method="POST" action="{{ route('customer.orders.cancel', $orderId) }}" class="inline-flex" onsubmit="return confirm('Cancel this order? This cannot be undone.');">
                                            @csrf
                                            <button class="border border-red-200 text-red-600 hover:text-white hover:bg-red-500 px-5 py-2 rounded font-semibold transition-colors duration-150" type="submit">Cancel Order</button>
                                        </form>
                                    @endif
                                    <button class="bg-[#a6b7ff] hover:bg-[#8f9ef5] text-white px-5 py-2 rounded font-semibold transition-colors duration-150" type="button">Order Again</button>
                                    <button class="border border-[#a6b7ff] text-[#a6b7ff] px-5 py-2 rounded font-semibold bg-white hover:bg-[#e6edff] transition-colors duration-150" type="button">Contact Shop</button>
                                    <button class="border border-[#a6b7ff] text-[#a6b7ff] px-5 py-2 rounded font-semibold bg-white hover:bg-[#e6edff] transition-colors duration-150" type="button">View Shop Rating</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-sm text-gray-500">No orders in this category.</div>
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