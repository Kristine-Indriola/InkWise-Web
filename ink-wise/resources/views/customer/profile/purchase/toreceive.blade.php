@extends('layouts.customerprofile')

@section('title', 'Order Status')

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
       <div class="flex border-b text-base font-semibold mb-4">

    <a href="{{ route('customer.my_purchase.topay') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">To Pay</a>
    <a href="{{ route('customer.my_purchase.inproduction') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">In Production</a>
    <a href="{{ route('customer.my_purchase.completed') }}" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">Completed</a>
    </div>
    @php
        $statusOptions = [
            'pending' => 'Order Received',
            'processing' => 'Processing',
            'in_production' => 'In Production',
            'confirmed' => 'Ready for Pickup',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
        $statusFlow = ['draft', 'pending', 'processing', 'in_production', 'confirmed', 'completed'];
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
        $ordersList = collect($ordersSource)->filter(function ($order) use ($normalizeMetadata) {
            $status = data_get($order, 'status', 'pending');
            if (!in_array($status, ['to_receive', 'confirmed'], true)) {
                return false;
            }

            $metadata = $normalizeMetadata(data_get($order, 'metadata', []));
            $trackingNumber = $metadata['tracking_number'] ?? data_get($order, 'tracking_number');

            if (empty($trackingNumber) && $status !== 'to_receive') {
                return false;
            }

            return true;
        })->values();
    @endphp

    @if(session('status'))
        <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="space-y-4">
        @forelse($ordersList as $order)
            @php
                $productName = data_get($order, 'product_name', 'Order in transit');
                $quantity = (int) data_get($order, 'quantity', 0);
                $image = data_get($order, 'image', asset('images/placeholder.png'));
                $metadata = $normalizeMetadata(data_get($order, 'metadata', []));
                $trackingNumber = $metadata['tracking_number'] ?? data_get($order, 'tracking_number');
                $statusNote = $metadata['status_note'] ?? null;
                $statusKey = data_get($order, 'status', 'pending');
                $statusLabel = $statusOptions[$statusKey] ?? ucfirst(str_replace('_', ' ', $statusKey));
                $flowIndex = array_search($statusKey, $statusFlow, true);
                $nextStatusKey = $flowIndex !== false && $flowIndex < count($statusFlow) - 1 ? $statusFlow[$flowIndex + 1] : null;
                $nextStatusLabel = $nextStatusKey ? ($statusOptions[$nextStatusKey] ?? ucfirst(str_replace('_', ' ', $nextStatusKey))) : null;
                $carrier = data_get($order, 'carrier', '—');
                $expectedDate = data_get($order, 'expected_date');
                $expectedDate = $expectedDate ?: data_get($order, 'expected_delivery_at');
                $expectedDate = $formatDate($expectedDate);
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
                    <div class="text-sm text-gray-500">Next step: {{ $nextStatusLabel ?? 'All steps complete' }}</div>
                    <div class="text-sm text-gray-500">Expected: {{ $expectedDate ?? 'To be announced' }}</div>
                    @if($statusNote)
                        <div class="text-xs text-gray-400 mt-1">Note: {{ $statusNote }}</div>
                    @endif
                </div>
                <div class="flex flex-col gap-2">
                    @if($orderId)
                        <form method="POST" action="{{ route('customer.orders.confirm_received', $orderId) }}" class="js-confirm-received-form">
                            @csrf
                            <button type="submit" class="bg-[#a6b7ff] text-white px-4 py-2 rounded font-semibold js-confirm-received" data-order-id="{{ $orderId }}">Confirm Received</button>
                        </form>
                    @endif
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
    const forms = Array.from(document.querySelectorAll('.js-confirm-received-form'));
    forms.forEach(form => {
        form.addEventListener('submit', function (event) {
            const button = form.querySelector('button.js-confirm-received');
            const id = button ? button.dataset.orderId : 'this';
            if (!confirm('Mark order #' + id + ' as received?')) {
                event.preventDefault();
                return;
            }

            if (button) {
                button.disabled = true;
                button.classList.add('opacity-60', 'cursor-not-allowed');
            }
        });
    });
});
</script>
@endpush
