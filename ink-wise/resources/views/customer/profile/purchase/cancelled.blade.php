@extends('layouts.customerprofile')

@section('title', 'Cancelled')

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
                    'id' => 4001,
                    'product_name' => 'Cancelled Invitation Order',
                    'quantity' => 80,
                    'image' => asset('customerimages/image/invitation.png'),
                    'total_amount' => 1600.00,
                    'cancelled_date' => now()->subDays(3)->format('M d, Y'),
                    'reason' => 'Customer requested cancellation'
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
                    <div class="text-sm text-gray-500">Cancelled: <span class="font-medium">{{ $order->cancelled_date }}</span></div>
                    <div class="text-sm text-gray-500">Reason: {{ $order->reason }}</div>
                </div>
                <div class="text-gray-700 font-bold">₱{{ number_format($order->total_amount,2) }}</div>
            </div>
        @endforeach
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
