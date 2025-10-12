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
        if (!empty($orders) && is_iterable($orders)) {
            $ordersList = $orders;
        } else {
            $ordersList = [
                (object)[
                    'id' => 2001,
                    'product_id' => 601,
                    'product_name' => 'Classic RSVP Invitation',
                    'quantity' => 50,
                    'image' => asset('customerimages/image/invitation.png'),
                    'total_amount' => 1200.00,
                    'tracking_number' => 'TRK-20251012-01',
                    'carrier' => 'Inkwise Logistics',
                    'status' => 'Out for delivery',
                    'expected_date' => now()->addDays(1)->format('M d, Y'),
                ],
                (object)[
                    'id' => 2002,
                    'product_id' => 602,
                    'product_name' => 'Corporate Giveaway Set',
                    'quantity' => 30,
                    'image' => asset('customerimages/image/giveaway.png'),
                    'total_amount' => 1750.00,
                    'tracking_number' => 'TRK-20251012-02',
                    'carrier' => 'Local Courier',
                    'status' => 'In transit',
                    'expected_date' => now()->addDays(2)->format('M d, Y'),
                ],
            ];
        }
    @endphp

    <div class="space-y-4">
        @foreach($ordersList as $order)
            <div class="bg-white border rounded-xl p-4 shadow-sm flex items-center gap-4">
                <img src="{{ $order->image ?? asset('images/placeholder.png') }}" alt="{{ $order->product_name }}" class="w-24 h-24 object-cover rounded-lg">
                <div class="flex-1">
                    <div class="font-semibold text-lg">{{ $order->product_name }}</div>
                    <div class="text-sm text-gray-500">Qty: {{ $order->quantity }} pcs</div>
                    <div class="text-sm text-gray-500 mt-2">Tracking: <span class="font-medium">{{ $order->tracking_number }}</span> — {{ $order->carrier }}</div>
                    <div class="text-sm text-gray-500">Status: <span class="text-[#a6b7ff] font-semibold">{{ $order->status }}</span></div>
                    <div class="text-sm text-gray-500">Expected: {{ $order->expected_date }}</div>
                </div>
                <div class="flex flex-col gap-2">
                    <form method="POST" action="#" onsubmit="return false;">
                        <button type="button" class="bg-[#a6b7ff] text-white px-4 py-2 rounded font-semibold js-confirm-received" data-order-id="{{ $order->id }}">Confirm Received</button>
                    </form>
                    <div class="text-gray-600 text-sm">₱{{ number_format($order->total_amount,2) }}</div>
                </div>
            </div>
        @endforeach
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
