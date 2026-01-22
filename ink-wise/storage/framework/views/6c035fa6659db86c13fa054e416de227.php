<?php $__env->startSection('title', 'Completed'); ?>

<?php $__env->startSection('content'); ?>
<div class="bg-white rounded-2xl shadow p-6">
         <div class="flex border-b text-base font-semibold mb-4">
    <a href="<?php echo e(route('customer.my_purchase.topay')); ?>" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">To Pay</a>
    <a href="<?php echo e(route('customer.my_purchase.inproduction')); ?>" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">In Production</a>
     <a href="<?php echo e(route('customer.my_purchase.topickup')); ?>" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">Ready for Pickup</a>
     <a href="<?php echo e(route('customer.my_purchase.completed')); ?>" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">Completed</a>
     </div>

    <?php
        $statusOptions = [
            'pending' => 'Order Received',
            'processing' => 'Processing',
            'in_production' => 'In Progress',
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
        $ordersList = collect($ordersSource)->filter(function ($order) {
            $status = data_get($order, 'status', 'pending');
            return $status === 'completed';
        })->values();
    ?>

    <div class="space-y-4">
        <?php $__empty_1 = true; $__currentLoopData = $ordersList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
                $firstItem = $order->items->first();
                $productName = $firstItem ? $firstItem->product_name : 'Completed order';
                $orderNumber = $order->order_number ?: $order->id;
                $quantity = $order->items->sum('quantity');
                $image = $firstItem && $firstItem->design_metadata ? data_get($firstItem->design_metadata, 'image', asset('images/placeholder.png')) : asset('images/placeholder.png');
                $totalAmount = $order->grandTotalAmount();
                $completedDate = $order->updated_at ? $order->updated_at->format('M d, Y') : null;
                $metadata = $order->metadata ?? [];
                $trackingNumber = $metadata['tracking_number'] ?? null;
                $statusNote = $metadata['status_note'] ?? null;
                $statusKey = $order->status;
                $statusLabel = $statusOptions[$statusKey] ?? ucfirst(str_replace('_', ' ', $statusKey));
                $previewUrl = '#';
                try {
                    if ($firstItem && $firstItem->product_id) {
                        $previewUrl = route('product.preview', $firstItem->product_id);
                    }
                } catch (\Throwable $e) {
                    $previewUrl = '#';
                }
                $rateUrl = Route::has('customer.my_purchase.rate') ? route('customer.my_purchase.rate') : '#';
            ?>

            <div class="bg-white border rounded-xl p-4 shadow-sm flex items-center gap-4">
                <img src="<?php echo e($image); ?>" alt="<?php echo e($productName); ?>" class="w-24 h-24 object-cover rounded-lg">
                <div class="flex-1">
                    <div class="font-semibold text-lg"><?php echo e($productName); ?></div>
                    <div class="text-sm text-gray-500">Order: <?php echo e($orderNumber); ?></div>
                    <div class="text-sm text-gray-500">Qty: <?php echo e($quantity ?: '—'); ?> pcs</div>
                    <div class="text-sm text-gray-500">Completed: <span class="font-medium"><?php echo e($completedDate ?? 'Not available'); ?></span></div>
                    <div class="text-sm text-gray-500">Status: <span class="text-[#a6b7ff] font-semibold"><?php echo e($statusLabel); ?></span></div>
                    <?php if($trackingNumber): ?>
                        <div class="text-sm text-gray-500">Tracking: <?php echo e($trackingNumber); ?></div>
                    <?php endif; ?>
                    <?php if($statusNote): ?>
                        <div class="text-xs text-gray-400 mt-1">Note: <?php echo e($statusNote); ?></div>
                    <?php endif; ?>
                </div>
                <div class="flex flex-col items-end gap-2">
                    <div class="text-gray-700 font-bold">₱<?php echo e(number_format($totalAmount, 2)); ?></div>
                    <div class="flex gap-2">
                        <a href="<?php echo e(route('customer.orders.invoice', ['order' => $order->id])); ?>" class="px-4 py-2 bg-[#e6f7fb] text-[#044e86] rounded font-semibold">View Invoice</a>
                        <a href="<?php echo e($previewUrl); ?>" class="px-4 py-2 bg-white border text-[#044e86] rounded font-semibold">Order Again</a>
                        <a href="<?php echo e($rateUrl); ?>" class="px-4 py-2 bg-[#ffdede] text-[#a80000] rounded font-semibold">Rate Inkwise</a>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="text-sm text-gray-500">No completed orders yet.</div>
        <?php endif; ?>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.customerprofile', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\leanne\xampp\htdocs\InkWise-Web\ink-wise\resources\views/customer/profile/purchase/completed.blade.php ENDPATH**/ ?>