@extends('layouts.customerprofile')

@section('title', 'Completed')

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
                $productName = data_get($order, 'product_name', 'Completed order');
                $orderNumber = data_get($order, 'order_number', data_get($order, 'id'));
                $quantity = (int) data_get($order, 'quantity', 0);
                $image = data_get($order, 'image', asset('images/placeholder.png'));
                $totalAmount = data_get($order, 'total_amount', 0);
                $completedDate = data_get($order, 'completed_date');
                if (!$completedDate && $timestamp = data_get($order, 'updated_at')) {
                    try {
                        $completedDate = \Illuminate\Support\Carbon::parse($timestamp)->format('M d, Y');
                    } catch (\Throwable $e) {
                        $completedDate = null;
                    }
                }

                $previewUrl = '#';
                try {
                    if ($productId = data_get($order, 'product_id')) {
                        $previewUrl = route('product.preview', $productId);
                    }
                } catch (\Throwable $e) {
                    $previewUrl = '#';
                }
                $rateUrl = Route::has('chatbot') ? route('chatbot') : '#';
            @endphp

            <div class="bg-white border rounded-xl p-4 shadow-sm flex items-center gap-4">
                <img src="{{ $image }}" alt="{{ $productName }}" class="w-24 h-24 object-cover rounded-lg">
                <div class="flex-1">
                    <div class="font-semibold text-lg">{{ $productName }}</div>
                    <div class="text-sm text-gray-500">Order: {{ $orderNumber }}</div>
                    <div class="text-sm text-gray-500">Qty: {{ $quantity ?: '—' }} pcs</div>
                    <div class="text-sm text-gray-500">Completed: <span class="font-medium">{{ $completedDate ?? 'Not available' }}</span></div>
                </div>
                <div class="flex flex-col items-end gap-2">
                    <div class="text-gray-700 font-bold">₱{{ number_format($totalAmount, 2) }}</div>
                    <div class="flex gap-2">
                        <a href="#" class="px-4 py-2 bg-[#e6f7fb] text-[#044e86] rounded font-semibold">View Invoice</a>
                        <a href="{{ $previewUrl }}" class="px-4 py-2 bg-white border text-[#044e86] rounded font-semibold">Order Again</a>
                        <a href="{{ $rateUrl }}" class="px-4 py-2 bg-[#ffdede] text-[#a80000] rounded font-semibold">Rate Inkwise</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-sm text-gray-500">No completed orders yet.</div>
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
