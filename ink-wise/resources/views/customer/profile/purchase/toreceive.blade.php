@extends('layouts.customerprofile')

@section('title', 'To Receive')

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
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
    @endphp

    <div class="space-y-4">
        @forelse($ordersList as $order)
            @php
                $productName = data_get($order, 'product_name', 'Order in transit');
                $quantity = (int) data_get($order, 'quantity', 0);
                $image = data_get($order, 'image', asset('images/placeholder.png'));
                $trackingNumber = data_get($order, 'tracking_number');
                $carrier = data_get($order, 'carrier', '—');
                $statusLabel = data_get($order, 'status', 'In transit');
                $expectedDate = data_get($order, 'expected_date');
                if (!$expectedDate && $timestamp = data_get($order, 'expected_delivery_at')) {
                    try {
                        $expectedDate = \Illuminate\Support\Carbon::parse($timestamp)->format('M d, Y');
                    } catch (\Throwable $e) {
                        $expectedDate = null;
                    }
                }
                $totalAmount = data_get($order, 'total_amount', 0);
                $orderId = data_get($order, 'id');
            @endphp

            <div class="bg-white border rounded-xl p-4 shadow-sm flex items-center gap-4">
                <img src="{{ $image }}" alt="{{ $productName }}" class="w-24 h-24 object-cover rounded-lg">
                <div class="flex-1">
                    <div class="font-semibold text-lg">{{ $productName }}</div>
                    <div class="text-sm text-gray-500">Qty: {{ $quantity ?: '—' }} pcs</div>
                    <div class="text-sm text-gray-500 mt-2">Tracking: <span class="font-medium">{{ $trackingNumber ?? 'Pending' }}</span> — {{ $carrier }}</div>
                    <div class="text-sm text-gray-500">Status: <span class="text-[#a6b7ff] font-semibold">{{ $statusLabel }}</span></div>
                    <div class="text-sm text-gray-500">Expected: {{ $expectedDate ?? 'To be announced' }}</div>
                </div>
                <div class="flex flex-col gap-2">
                    <form method="POST" action="#" onsubmit="return false;">
                        <button type="button" class="bg-[#a6b7ff] text-white px-4 py-2 rounded font-semibold js-confirm-received" data-order-id="{{ $orderId }}">Confirm Received</button>
                    </form>
                    <div class="text-gray-600 text-sm">₱{{ number_format($totalAmount, 2) }}</div>
                </div>
            </div>
        @empty
            <div class="text-sm text-gray-500">No orders are currently on their way.</div>
        @endforelse
    </div>

<script>
// Normalize nav hover/active behavior (copied from Topay)
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
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const buttons = Array.from(document.querySelectorAll('.js-confirm-received'));
    buttons.forEach(btn => {
        btn.addEventListener('click', function () {
            const id = btn.dataset.orderId;
            if (!confirm('Mark order #' + id + ' as received?')) return;
            const card = btn.closest('.bg-white.border.rounded-xl');
            if (card) card.remove();
            // TODO: call server endpoint to confirm receipt and update order status
        });
    });
});
</script>
@endpush
