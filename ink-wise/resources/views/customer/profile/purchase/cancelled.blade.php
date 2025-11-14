@extends('layouts.customerprofile')

@section('title', 'Cancelled')

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
            'in_production' => 'In Progress',
            'confirmed' => 'To Ship',
            'to_receive' => 'To Receive',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
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
        $ordersList = collect($ordersSource)->filter(function ($order) {
            return data_get($order, 'status') === 'cancelled';
        })->values();
    @endphp

    <div class="space-y-4">
        @forelse($ordersList as $order)
            @php
                $productName = data_get($order, 'product_name', 'Order');
                $quantity = (int) data_get($order, 'quantity', 0);
                $cancelledDate = $formatDate(data_get($order, 'cancelled_date'));
                if (!$cancelledDate) {
                    $cancelledDate = $formatDate(data_get($order, 'updated_at'));
                }
                $reason = data_get($order, 'reason', 'Cancelled by customer');
                $image = data_get($order, 'image', asset('images/placeholder.png'));
                $totalAmount = data_get($order, 'total_amount', 0);
                $metadata = $normalizeMetadata(data_get($order, 'metadata', []));
                $statusNote = $metadata['status_note'] ?? null;
                $statusKey = data_get($order, 'status', 'cancelled');
                $statusLabel = $statusOptions[$statusKey] ?? ucfirst(str_replace('_', ' ', $statusKey));
                $refundProcessed = $metadata['refund_processed'] ?? false;
                $totalRefunded = $metadata['total_refunded'] ?? 0;
                $totalPaid = $metadata['total_paid'] ?? 0;
                $restockingFee = $metadata['restocking_fee'] ?? 0;
                $refundProcessedAt = isset($metadata['refund_processed_at']) ? $formatDate($metadata['refund_processed_at'], 'M d, Y g:i A') : null;
                $paymentStatus = data_get($order, 'payment_status', 'cancelled');
            @endphp

            <div class="bg-white border rounded-xl p-4 shadow-sm flex items-center gap-4">
                <img src="{{ $image }}" alt="{{ $productName }}" class="w-24 h-24 object-cover rounded-lg">
                <div class="flex-1">
                    <div class="font-semibold text-lg">{{ $productName }}</div>
                    <div class="text-sm text-gray-500">Qty: {{ $quantity ?: '—' }} pcs</div>
                    <div class="text-sm text-gray-500">Cancelled: <span class="font-medium">{{ $cancelledDate ?? 'Not available' }}</span></div>
                    <div class="text-sm text-gray-500">Reason: {{ $reason }}</div>
                    <div class="text-sm text-gray-500">Status: <span class="text-[#a6b7ff] font-semibold">{{ $statusLabel }}</span></div>
                    @if($refundProcessed && $totalRefunded > 0)
                        <div class="text-sm text-green-600 font-semibold mt-1">
                            <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Refunded: ₱{{ number_format($totalRefunded, 2) }}
                        </div>
                        @if($restockingFee > 0)
                            <div class="text-xs text-orange-600 mt-1">
                                <svg class="inline w-3 h-3 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Restocking fee: ₱{{ number_format($restockingFee, 2) }} (20%)
                            </div>
                            <div class="text-xs text-gray-500">Original payment: ₱{{ number_format($totalPaid, 2) }}</div>
                        @endif
                        @if($refundProcessedAt)
                            <div class="text-xs text-gray-500">Refund processed: {{ $refundProcessedAt }}</div>
                        @endif
                    @elseif($paymentStatus === 'refunded')
                        <div class="text-sm text-green-600 font-semibold mt-1">
                            <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Payment Refunded
                        </div>
                    @endif
                    @if($statusNote)
                        <div class="text-xs text-gray-400 mt-1">Note: {{ $statusNote }}</div>
                    @endif
                </div>
                <div class="text-gray-700 font-bold">₱{{ number_format($totalAmount, 2) }}</div>
            </div>
        @empty
            <div class="text-sm text-gray-500">No cancelled orders.</div>
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
