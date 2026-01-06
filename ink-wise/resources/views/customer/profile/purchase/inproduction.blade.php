@extends('layouts.customerprofile')

@section('title', 'In Production')

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
            'draft' => 'New Order',
            'pending' => 'Order Received',
            'pending_awaiting_materials' => 'Pending – Awaiting Materials',
            'processing' => 'Processing',
            'in_production' => 'In Production',
            'confirmed' => 'Ready for Pickup',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
        $statusFlow = ['draft', 'pending', 'pending_awaiting_materials', 'processing', 'in_production', 'confirmed', 'completed'];
        $formatStatusLabel = static function ($statusKey) use ($statusOptions) {
            $label = $statusOptions[$statusKey] ?? null;

            if (is_string($label) && $label !== '') {
                return $label;
            }

            return \Illuminate\Support\Str::title(str_replace('_', ' ', (string) $statusKey));
        };
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
        $resolveDesignImageUrl = static function ($path) {
            if (!is_string($path)) {
                return null;
            }

            $trimmed = trim($path);
            if ($trimmed === '') {
                return null;
            }

            if (preg_match('/^(data:|https?:|\/{2})/i', $trimmed)) {
                return $trimmed;
            }

            $normalized = ltrim($trimmed, '/');
            $normalized = str_replace('\\', '/', $normalized);
            if (\Illuminate\Support\Str::startsWith($normalized, 'storage/')) {
                return asset($normalized);
            }

            if (\Illuminate\Support\Str::startsWith($normalized, 'public/')) {
                $publicPath = substr($normalized, 7);
                return asset('storage/' . $publicPath);
            }

            try {
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($normalized)) {
                    return \Illuminate\Support\Facades\Storage::disk('public')->url($normalized);
                }
            } catch (\Throwable $e) {
                // ignore storage errors and fall back to asset below
            }

            return asset(str_replace('\\', '/', $normalized));
        };

        $ordersSource = $orders ?? optional(auth()->user())->customer->orders ?? [];
        $inProductionOrders = collect($ordersSource)->filter(function ($order) {
            $status = data_get($order, 'status', 'pending');
            return !in_array($status, ['completed', 'cancelled'], true);
        })->values();
    @endphp

    <div class="space-y-4">
        @forelse($inProductionOrders as $order)
            @php
                $items = data_get($order, 'items');
                if ($items instanceof \Illuminate\Support\Collection) {
                    $itemsCollection = $items;
                } elseif (is_array($items)) {
                    $itemsCollection = collect($items);
                } else {
                    $itemsCollection = collect();
                }

                $primaryItem = $itemsCollection->first();

                $productName = $primaryItem
                    ? data_get($primaryItem, 'product.name', data_get($primaryItem, 'product_name', 'Custom invitation'))
                    : data_get($order, 'product_name', 'Custom invitation');
                $rawImage = data_get($primaryItem, 'product.image');
                if (is_array($rawImage)) {
                    $rawImage = $rawImage['url'] ?? $rawImage['path'] ?? $rawImage['src'] ?? null;
                }
                $image = $resolveDesignImageUrl($rawImage) ?? asset('customerimages/image/weddinginvite.png');
                $totalQuantity = max(0, $itemsCollection->sum(fn ($item) => (int) data_get($item, 'quantity', 0)));
                $paperStock = $primaryItem ? data_get($primaryItem, 'paperStockSelection.paper_stock_name', 'Standard') : 'Standard';
                $metadata = $normalizeMetadata(data_get($order, 'metadata', []));
                $statusKey = data_get($order, 'status', 'pending');
                $statusLabel = $formatStatusLabel($statusKey);
                $customerCanCancel = false; // Customers cannot cancel orders
                $flowIndex = array_search($statusKey, $statusFlow, true);
                $nextStatusKey = $flowIndex !== false && $flowIndex < count($statusFlow) - 1 ? $statusFlow[$flowIndex + 1] : null;
                $nextStatusLabel = $nextStatusKey ? $formatStatusLabel($nextStatusKey) : null;
                $statusNote = $metadata['status_note'] ?? null;
                $totalAmount = data_get($order, 'total_amount', 0);
                $orderNumber = data_get($order, 'order_number', data_get($order, 'id'));
                $trackingNumber = $metadata['tracking_number'] ?? null;

                $designEntries = $itemsCollection->map(function ($item) use ($metadata, $resolveDesignImageUrl) {
                    $collectCandidates = static function ($value) {
                        if (is_string($value) && trim($value) !== '') {
                            return [trim($value)];
                        }

                        if (is_array($value)) {
                            $candidates = [];
                            foreach (['url', 'path', 'src', 'href'] as $key) {
                                if (isset($value[$key]) && is_string($value[$key]) && trim($value[$key]) !== '') {
                                    $candidates[] = trim($value[$key]);
                                }
                            }

                            if (empty($candidates)) {
                                foreach ($value as $nested) {
                                    if (is_string($nested) && trim($nested) !== '') {
                                        $candidates[] = trim($nested);
                                    }
                                }
                            }

                            return $candidates;
                        }

                        return [];
                    };

                    $designMeta = data_get($item, 'design_metadata');
                    if ($designMeta instanceof \JsonSerializable) {
                        $designMeta = (array) $designMeta;
                    }
                    if (is_string($designMeta) && $designMeta !== '') {
                        $decoded = json_decode($designMeta, true);
                        $designMeta = json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
                    } elseif (!is_array($designMeta)) {
                        $designMeta = [];
                    }

                    $snapshot = $designMeta['snapshot'] ?? [];
                    if (is_string($snapshot) && $snapshot !== '') {
                        $decodedSnapshot = json_decode($snapshot, true);
                        $snapshot = json_last_error() === JSON_ERROR_NONE && is_array($decodedSnapshot) ? $decodedSnapshot : [];
                    }

                    $images = collect();

                    $previewImages = data_get($snapshot, 'preview_images', []);
                    if (!is_array($previewImages)) {
                        $previewImages = $collectCandidates($previewImages);
                    }
                    foreach ((array) $previewImages as $img) {
                        foreach ($collectCandidates($img) as $candidate) {
                            $resolved = $resolveDesignImageUrl($candidate);
                            if ($resolved) {
                                $images->push($resolved);
                            }
                        }
                    }

                    $singlePreview = data_get($snapshot, 'preview_image');
                    foreach ($collectCandidates($singlePreview) as $candidate) {
                        $singleResolved = $resolveDesignImageUrl($candidate);
                        if ($singleResolved) {
                            $images->push($singleResolved);
                        }
                    }

                    if ($images->isEmpty()) {
                        $orderPreviewImages = data_get($metadata, 'design_preview.images', []);
                        if (is_array($orderPreviewImages)) {
                            foreach ($orderPreviewImages as $img) {
                                foreach ($collectCandidates($img) as $candidate) {
                                    $resolved = $resolveDesignImageUrl($candidate);
                                    if ($resolved) {
                                        $images->push($resolved);
                                    }
                                }
                            }
                        } else {
                            foreach ($collectCandidates($orderPreviewImages) as $candidate) {
                                $resolved = $resolveDesignImageUrl($candidate);
                                if ($resolved) {
                                    $images->push($resolved);
                                }
                            }
                        }
                        foreach ($collectCandidates(data_get($metadata, 'design_preview.image')) as $candidate) {
                            $orderPreviewImage = $resolveDesignImageUrl($candidate);
                            if ($orderPreviewImage) {
                                $images->push($orderPreviewImage);
                            }
                        }
                    }

                    if ($images->isEmpty()) {
                        $fallbackSource = data_get($item, 'product.image');
                        if (is_array($fallbackSource)) {
                            $fallbackSource = $fallbackSource['url'] ?? $fallbackSource['path'] ?? $fallbackSource['src'] ?? null;
                        }
                        $fallbackImage = $resolveDesignImageUrl($fallbackSource)
                            ?? asset('customerimages/image/weddinginvite.png');
                        if ($fallbackImage) {
                            $images->push($fallbackImage);
                        }
                    }

                    $images = $images->filter()->unique()->values();

                    if ($images->isEmpty()) {
                        return null;
                    }

                    return [
                        'name' => data_get($item, 'product.name', data_get($item, 'product_name', 'Custom item')),
                        'images' => $images->all(),
                    ];
                })->filter();

                $designToggleId = 'design-' . md5((string) $orderNumber . '-' . $loop->index);
                $hasDesignEntries = $designEntries->isNotEmpty();
            @endphp

            <div class="bg-white border rounded-xl p-4 shadow-sm flex flex-col gap-4 md:flex-row md:items-center">
                <img src="{{ $image }}" alt="{{ $productName }}" class="w-24 h-24 object-cover rounded-lg border">
                <div class="flex-1 space-y-1">
                    <div class="font-semibold text-lg text-[#a6b7ff]">{{ $productName }}</div>
                    <div class="text-sm text-gray-500">Order: {{ $orderNumber }}</div>
                    <div class="text-sm text-gray-500">Quantity: {{ $totalQuantity ?: '—' }} pcs</div>
                    <div class="text-sm text-gray-500">Primary paper: {{ $paperStock }}</div>
                    <div class="text-sm text-gray-500">Status: <span class="font-semibold text-[#a6b7ff]">{{ $statusLabel }}</span></div>
                    <div class="text-sm text-gray-500">Next step: {{ $nextStatusLabel ?? 'All steps complete' }}</div>
                    @if($trackingNumber)
                        <div class="text-sm text-gray-500">Tracking: {{ $trackingNumber }}</div>
                    @endif
                    @if($statusNote)
                        <div class="text-xs text-gray-400">Note: {{ $statusNote }}</div>
                    @endif
                    @if($itemsCollection->isNotEmpty())
                        <div class="pt-2">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">Items</div>
                            <ul class="mt-1 space-y-1">
                                @foreach($itemsCollection as $item)
                                    @php
                                        $itemName = data_get($item, 'product.name', data_get($item, 'product_name', 'Custom item'));
                                        $itemQty = (int) data_get($item, 'quantity', 0);
                                        $itemPaper = data_get($item, 'paperStockSelection.paper_stock_name');
                                    @endphp
                                    <li class="text-sm text-gray-500 flex flex-wrap gap-1">
                                        <span class="font-medium text-gray-600">{{ $itemName }}</span>
                                        <span>·</span>
                                        <span>{{ $itemQty > 0 ? $itemQty . ' pcs' : 'Qty —' }}</span>
                                        @if($itemPaper)
                                            <span>·</span>
                                            <span>Paper: {{ $itemPaper }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if($hasDesignEntries)
                        <div id="{{ $designToggleId }}" class="hidden pt-3 space-y-3">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">Design proofs</div>
                            @foreach($designEntries as $entry)
                                <div class="space-y-1">
                                    <div class="text-sm font-medium text-gray-600">{{ $entry['name'] }}</div>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($entry['images'] as $designImage)
                                            <a href="{{ $designImage }}" target="_blank" rel="noopener" class="group block">
                                                <img src="{{ $designImage }}" alt="Design preview for {{ $entry['name'] }}" class="w-20 h-20 object-cover rounded border border-gray-200 group-hover:border-transparent group-hover:ring-2 group-hover:ring-[#a6b7ff] transition">
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="flex flex-col items-end gap-2">
                    <div class="text-gray-700 font-bold">₱{{ number_format($totalAmount, 2) }}</div>
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
                    <div class="text-sm text-red-500">Balance remaining: ₱{{ number_format($remainingBalance, 2) }}</div>
                    @else
                    <div class="text-sm text-green-500">Fully paid</div>
                    @endif
                    <div class="flex gap-2">

                        @if($hasRemainingBalance)
                        <a href="{{ route('customer.pay.remaining.balance', ['order' => data_get($order, 'id')]) }}"
                           class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded font-semibold transition-colors duration-200">
                            Pay Remaining (₱{{ number_format($remainingBalance, 2) }})
                        </a>
                        @endif

                        <button type="button"
                                class="px-4 py-2 {{ $hasDesignEntries ? 'bg-[#a6b7ff] hover:bg-[#8f9ffd] text-white' : 'bg-gray-200 text-gray-400 cursor-not-allowed' }} rounded font-semibold transition-colors duration-200"
                                @if($hasDesignEntries)
                                    data-design-toggle="{{ $designToggleId }}"
                                @else
                                    disabled
                                @endif>
                            View Design Proof{{ $hasDesignEntries ? ($designEntries->count() > 1 ? 's' : '') : '' }}
                        </button>
                       
                        @if($customerCanCancel)
                        <button type="button" class="px-4 py-2 border border-red-500 text-red-500 hover:bg-red-50 rounded font-semibold js-cancel-production-order" data-order-id="{{ data_get($order, 'id') }}">Cancel Order</button>
                        @else
                        <button type="button" class="px-4 py-2 border border-gray-300 text-gray-400 rounded font-semibold cursor-not-allowed" disabled>Cancellation Locked</button>
                        @endif
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

            // Show confirmation for cancelling order
            const confirmed = confirm(
                'Are you sure you want to cancel this order? This action cannot be undone and may result in cancellation fees or loss of deposit.\n\n' +
                'For immediate assistance, please contact InkWise support.'
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

<script>
// Toggle design proof galleries
document.addEventListener('DOMContentLoaded', function () {
    const designButtons = document.querySelectorAll('[data-design-toggle]');

    designButtons.forEach(button => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            const targetId = button.getAttribute('data-design-toggle');
            if (!targetId) {
                return;
            }
            const panel = document.getElementById(targetId);
            if (!panel) {
                return;
            }
            panel.classList.toggle('hidden');
            if (!panel.classList.contains('hidden')) {
                panel.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
            }
        });
    });
});
</script>
@endsection
