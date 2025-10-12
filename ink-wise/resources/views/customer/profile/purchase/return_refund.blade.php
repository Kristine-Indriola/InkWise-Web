@extends('layouts.customerprofile')

@section('title', 'Return / Refund')

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
        if (!empty($requests) && is_iterable($requests)) {
            $requestsList = $requests;
        } else {
            $requestsList = [
                (object)[
                    'id' => 5001,
                    'order_id' => 'ORD-5001',
                    'product_name' => 'Defective Giveaway Item',
                    'requested_date' => now()->subDays(2)->format('M d, Y'),
                    'status' => 'Pending',
                    'amount_refund' => 250.00
                ],
            ];
        }
    @endphp

    <div class="space-y-4">
        @foreach($requestsList as $r)
            <div class="bg-white border rounded-xl p-4 shadow-sm flex items-center gap-4">
                <div class="flex-1">
                    <div class="font-semibold text-lg">{{ $r->product_name }}</div>
                    <div class="text-sm text-gray-500">Order: {{ $r->order_id }}</div>
                    <div class="text-sm text-gray-500">Requested: <span class="font-medium">{{ $r->requested_date }}</span></div>
                    <div class="text-sm text-gray-500">Status: <span class="font-semibold">{{ $r->status }}</span></div>
                </div>
                <div class="text-gray-700 font-bold">₱{{ number_format($r->amount_refund,2) }}</div>
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
