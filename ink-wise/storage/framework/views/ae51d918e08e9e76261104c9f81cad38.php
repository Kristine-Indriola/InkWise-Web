<?php $__env->startSection('title', 'To Pay'); ?>

<?php $__env->startSection('content'); ?>
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex border-b text-base font-semibold mb-4">
        <?php if(Route::has('customer.my_purchase.topay')): ?>
            <a href="<?php echo e(route('customer.my_purchase.topay')); ?>" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">To Pay</a>
        <?php else: ?>
            <a href="#" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">To Pay</a>
        <?php endif; ?>

        <?php if(Route::has('customer.my_purchase.inproduction')): ?>
            <a href="<?php echo e(route('customer.my_purchase.inproduction')); ?>" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">In Production</a>
        <?php else: ?>
            <a href="#" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">In Production</a>
        <?php endif; ?>

        <?php if(Route::has('customer.my_purchase.topickup')): ?>
            <a href="<?php echo e(route('customer.my_purchase.topickup')); ?>" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">Ready for Pickup</a>
        <?php else: ?>
            <a href="#" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">Ready for Pickup</a>
        <?php endif; ?>

        <?php if(Route::has('customer.my_purchase.completed')): ?>
            <a href="<?php echo e(route('customer.my_purchase.completed')); ?>" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">Completed</a>
        <?php else: ?>
            <a href="#" class="px-4 py-2 text-gray-500 hover:text-[#a6b7ff] js-purchase-tab">Completed</a>
        <?php endif; ?>
    </div>

    <?php
        $ordersSource = $orders ?? optional(auth()->user())->customer->orders ?? [];
        $ordersList = collect($ordersSource)->filter(function ($order) {
            $status = data_get($order, 'status', 'pending');
            $paymentMode = data_get($order, 'payment_mode') ?: data_get($order, 'metadata.payment_mode', null);
            // Do not display orders where full payment method was chosen
            if ($paymentMode === 'full') {
                return false;
            }
            return $status === 'pending';
        })->values();
        $placeholderImage = asset('images/placeholder.png');
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
    ?>

    <div class="space-y-4 js-to-pay-list">
        <?php $__empty_1 = true; $__currentLoopData = $ordersList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
                $orderId = data_get($order, 'id');
                $productId = data_get($order, 'product_id');
                $productName = data_get($order, 'product_name', 'Custom invitation');
                $previewImage = data_get($order, 'image', asset('customerimages/image/weddinginvite.png'));
                $resolvedPreviewImage = $previewImage ?: $placeholderImage;
                $theme = data_get($order, 'theme', 'N/A');
                $quantity = (int) data_get($order, 'quantity', 0);
                $paper = data_get($order, 'paper', 'Standard');
                $addonsRaw = data_get($order, 'addons', []);
                $totalAmount = (float) data_get($order, 'total_amount', data_get($order, 'total', 0));
                $originalTotal = data_get($order, 'original_total');
                $statusKey = data_get($order, 'status', 'pending');
                $statusLabel = $statusOptions[$statusKey] ?? ucfirst(str_replace('_', ' ', $statusKey));
                $metadata = $normalizeMetadata(data_get($order, 'metadata', []));
                $trackingNumber = $metadata['tracking_number'] ?? null;
                $statusNote = $metadata['status_note'] ?? null;
                $flowIndex = array_search($statusKey, $statusFlow, true);
                $nextStatusKey = $flowIndex !== false && $flowIndex < count($statusFlow) - 1 ? $statusFlow[$flowIndex + 1] : null;
                $nextStatusLabel = $nextStatusKey ? ($statusOptions[$nextStatusKey] ?? ucfirst(str_replace('_', ' ', $nextStatusKey))) : null;

                $summary = [
                    'orderId' => $orderId,
                    'productId' => $productId,
                    'productName' => $productName,
                    'quantity' => $quantity ?: 10,
                    'previewImage' => $resolvedPreviewImage,
                    'previewImages' => [$resolvedPreviewImage],
                    'totalAmount' => $totalAmount,
                    'originalTotal' => $originalTotal,
                    'theme' => $theme,
                    'paper' => $paper,
                    'addons' => $addonsRaw,
                    'status' => $statusKey,
                    'statusLabel' => $statusLabel,
                    'trackingNumber' => $trackingNumber,
                    'nextStatusLabel' => $nextStatusLabel,
                    'statusNote' => $statusNote,
                    'editUrl' => (function(){ try { return route('order.finalstep'); } catch (\Throwable $e) { return url('/order/finalstep'); } })()
                ];

                $formatAddonPrice = function ($value) {
                    if ($value === null || $value === '') {
                        return null;
                    }

                    if (is_numeric($value)) {
                        return (float) $value;
                    }

                    if (is_string($value)) {
                        $numeric = preg_replace('/[^0-9.\-]/', '', $value);
                        return $numeric === '' ? null : (float) $numeric;
                    }

                    return null;
                };

                $formatAddonLabel = function ($addon) use ($formatAddonPrice) {
                    $label = null;
                    $price = null;

                    if (is_array($addon)) {
                        $label = $addon['name'] ?? $addon['label'] ?? $addon['title'] ?? $addon['value'] ?? null;
                        $price = $formatAddonPrice($addon['price'] ?? $addon['amount'] ?? $addon['total'] ?? null);
                    } elseif (is_object($addon)) {
                        $label = $addon->name ?? $addon->label ?? $addon->title ?? $addon->addon_name ?? null;
                        $price = $formatAddonPrice($addon->price ?? $addon->amount ?? $addon->total ?? $addon->addon_price ?? null);
                    } else {
                        $label = trim((string) $addon);
                    }

                    if (!$label) {
                        try {
                            return json_encode($addon, JSON_UNESCAPED_UNICODE);
                        } catch (\Throwable $e) {
                            return 'Add-on';
                        }
                    }

                    if ($price !== null) {
                        if ($price > 0.009) {
                            return sprintf('%s — ₱%s', $label, number_format($price, 2));
                        }

                        return sprintf('%s — Included', $label);
                    }

                    return $label;
                };

                $collectAddons = function ($items) use ($formatAddonLabel) {
                    if ($items instanceof \Illuminate\Support\Collection) {
                        $items = $items->all();
                    }

                    if (!is_array($items)) {
                        return '';
                    }

                    $labels = array_filter(array_map($formatAddonLabel, $items));
                    return $labels ? implode(', ', $labels) : '';
                };

                $addonsDisplay = 'NONE';

                if (!empty($addonsRaw)) {
                    if ($addonsRaw instanceof \Illuminate\Support\Collection || is_array($addonsRaw)) {
                        $addonsDisplay = $collectAddons($addonsRaw);
                    } else {
                        $decoded = json_decode($addonsRaw, true);
                        if (is_array($decoded)) {
                            $addonsDisplay = $collectAddons($decoded);
                        } else {
                            $addonsDisplay = $formatAddonLabel($addonsRaw);
                        }
                    }

                    if ($addonsDisplay === '') {
                        $addonsDisplay = 'NONE';
                    }
                }
            ?>

            <div class="bg-white border rounded-xl mb-4 shadow-sm" data-summary-order-id="<?php echo e($orderId ?? ''); ?>">
                <div class="flex items-center justify-between px-4 py-3 border-b">
                    <div></div>
                    <div class="flex items-center gap-2">
                        <span class="text-yellow-600 flex items-center text-xs">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z M21 12.5C21 18 17.5 22 12 22S3 18 3 12.5 6.5 3 12 3s9 4.5 9 9.5z"/></svg>
                            <?php echo e($statusLabel); ?>

                        </span>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row items-start md:items-center px-4 py-4 gap-4">
                    <img src="<?php echo e($resolvedPreviewImage); ?>" alt="Invitation Design" class="w-24 h-24 object-cover rounded-lg border">
                    <div class="flex-1">
                        <div class="font-semibold text-lg text-[#a6b7ff]"><?php echo e($productName); ?></div>
                        <div class="text-sm text-gray-500">Theme: <?php echo e($theme); ?></div>
                        <div class="text-sm text-gray-500">Quantity: <?php echo e($quantity ?: '—'); ?> pcs</div>
                        <div class="text-sm text-gray-500">Paper: <?php echo e($paper); ?></div>
                        <div class="text-sm text-gray-500">Add-ons: <?php echo e($addonsDisplay); ?></div>
                        <div class="text-sm text-gray-500">Status: <span class="font-semibold text-[#a6b7ff]"><?php echo e($statusLabel); ?></span></div>
                        <div class="text-sm text-red-500">Pay within: <span class="js-payment-timer" data-order-id="<?php echo e($orderId); ?>" data-deadline="<?php echo e(now()->addHours(24)->toISOString()); ?>">24:00:00</span></div>
                        <?php if($trackingNumber): ?>
                            <div class="text-sm text-gray-500">Tracking: <?php echo e($trackingNumber); ?></div>
                        <?php endif; ?>
                        <?php if($statusNote): ?>
                            <div class="text-xs text-gray-400 mt-1">Note: <?php echo e($statusNote); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold text-gray-700">₱<?php echo e(number_format($totalAmount, 2)); ?></div>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row items-center justify-between px-4 py-3 bg-[#f7f8fa] rounded-b-xl">
                    <div class="text-sm text-gray-500 mb-2 md:mb-0">
                        Order Total: <span class="text-[#a6b7ff] font-bold text-lg">₱<?php echo e(number_format($totalAmount, 2)); ?></span>
                    </div>
                    <div class="flex gap-2">
                        <button class="bg-[#a6b7ff] hover:bg-[#bce6ff] text-white px-4 py-2 rounded font-semibold js-to-pay-checkout" type="button" data-summary='<?php echo json_encode($summary, 15, 512) ?>' data-mode="half">Pay Deposit (₱<?php echo e(number_format($totalAmount / 2, 2)); ?>)</button>
                        <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded font-semibold js-to-pay-checkout-full" type="button" data-summary='<?php echo json_encode($summary, 15, 512) ?>' data-mode="full">Pay in Full (₱<?php echo e(number_format($totalAmount, 2)); ?>)</button>
                        
                        
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="text-sm text-gray-500">No orders awaiting payment.</div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkoutUrl = <?php echo json_encode(route('customer.checkout'), 15, 512) ?>;
    const clearSummaryUrl = <?php echo json_encode(route('order.summary.clear'), 15, 512) ?>;
    const buttons = Array.from(document.querySelectorAll('.js-to-pay-checkout'));
    if (!buttons.length) {
        console.log('No checkout buttons found');
        return;
    }

    console.log('Found', buttons.length, 'checkout buttons');

    buttons.forEach((btn, index) => {
        btn.addEventListener('click', (event) => {
            event.preventDefault(); // Prevent any default behavior

            try {
                const raw = btn.getAttribute('data-summary');
                const summary = raw ? JSON.parse(raw) : null;
                const mode = btn.getAttribute('data-mode') || 'half'; // Default to half for backward compatibility

                if (summary) {
                    // Add payment mode to summary
                    summary.paymentMode = mode;
                    window.sessionStorage.setItem('inkwise-finalstep', JSON.stringify(summary));
                }
            } catch (err) {
                console.warn('failed to save order summary to sessionStorage', err);
            }

            window.location.href = checkoutUrl;
        });
    });

    // Handle full payment buttons
    const fullButtons = Array.from(document.querySelectorAll('.js-to-pay-checkout-full'));
    fullButtons.forEach((btn, index) => {
        btn.addEventListener('click', (event) => {
            event.preventDefault(); // Prevent any default behavior

            try {
                const raw = btn.getAttribute('data-summary');
                const summary = raw ? JSON.parse(raw) : null;

                if (summary) {
                    // Force full payment mode
                    summary.paymentMode = 'full';
                    window.sessionStorage.setItem('inkwise-finalstep', JSON.stringify(summary));
                }
            } catch (err) {
                console.warn('failed to save order summary to sessionStorage', err);
            }

            window.location.href = checkoutUrl;
        });
    });    const cancels = Array.from(document.querySelectorAll('.js-to-pay-cancel'));
    cancels.forEach(btn => {
        btn.addEventListener('click', async () => {
            const orderId = btn.getAttribute('data-order-id');
            if (!orderId) {
                console.warn('No order ID found for cancel button');
                return;
            }

            if (!confirm('Cancel this order? If you have already paid, a refund will be processed to your account.')) {
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
                    const card = btn.closest('.bg-white.border.rounded-xl') || btn.closest('[data-summary-order-id]');
                    if (card) {
                        card.remove();
                    }

                    // Clear sessionStorage if it matches this order
                    const stored = window.sessionStorage.getItem('inkwise-finalstep');
                    if (stored) {
                        try {
                            const summary = JSON.parse(stored);
                            if (summary && summary.orderId == orderId) {
                                window.sessionStorage.removeItem('inkwise-finalstep');
                                // Clear server summary
                                await fetch(clearSummaryUrl, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                                    }
                                });
                            }
                        } catch (sessionError) {
                            console.warn('failed to clear sessionStorage', sessionError);
                        }
                    }

                    // Show success message
                    const message = result.message || result.status || 'Order cancelled successfully.';
                    alert(message);
                } else {
                    const errorData = await response.json().catch(() => ({}));
                    alert(errorData.message || 'Failed to cancel order. Please try again.');
                }
            } catch (error) {
                console.error('Cancel order error:', error);
                alert('Failed to cancel order. Please check your connection and try again.');
            }
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.classList.contains('js-client-cancel')) {
            return;
        }

        const card = event.target.closest('.bg-white.border.rounded-xl') || event.target.closest('[data-summary-order-id]');
        if (card) {
            card.remove();
        }
        window.sessionStorage.removeItem('inkwise-finalstep');
    });

    document.addEventListener('click', (event) => {
        if (!event.target.classList.contains('js-client-checkout')) {
            return;
        }

        window.location.href = checkoutUrl;
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const placeholderImage = <?php echo json_encode($placeholderImage, 15, 512) ?>;

    function renderAddons(addons) {
        if (!addons) {
            return 'NONE';
        }

        if (typeof addons === 'string') {
            try {
                const parsed = JSON.parse(addons);
                if (Array.isArray(parsed)) {
                    addons = parsed;
                } else {
                    return addons;
                }
            } catch (e) {
                return addons;
            }
        }

        const parsePrice = (value) => {
            if (value === null || value === undefined || value === '') {
                return null;
            }
            if (typeof value === 'number') {
                return Number.isFinite(value) ? value : null;
            }
            const numeric = Number.parseFloat(String(value).replace(/[^0-9.\-]/g, ''));
            return Number.isFinite(numeric) ? numeric : null;
        };

        const formatAddon = (item) => {
            if (!item) {
                return '';
            }

            if (typeof item === 'string') {
                return item;
            }

            const label = item.name || item.label || item.title || item.addon_name || item.value || '';
            const price = parsePrice(item.price ?? item.amount ?? item.total ?? item.addon_price);

            if (!label) {
                try {
                    return JSON.stringify(item);
                } catch (e) {
                    return String(item);
                }
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
});
</script>

<script>
// Normalize nav hover/active behavior
(function () {
    const activeClasses = ['border-b-2', 'border-[#a6b7ff]', 'text-[#a6b7ff]'];
    const inactiveTextClass = 'text-gray-500';

    function blurAfterInteraction(event) {
        try {
            const control = event.currentTarget;
            setTimeout(() => {
                if (document.activeElement === control) {
                    control.blur();
                }
            }, 50);
        } catch (error) {
            // ignore
        }
    }

    function normalizePath(url) {
        try {
            return new URL(url, window.location.origin).pathname.replace(/\/+$/, '') || '/';
        } catch (error) {
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
        const blurSelector = '.js-purchase-tab, .js-to-pay-list button, .bg-white .flex.gap-2 button, .js-to-pay-checkout';
        const controls = Array.from(document.querySelectorAll(blurSelector));
        controls.forEach(ctrl => {
            ctrl.addEventListener('mouseup', blurAfterInteraction);
            ctrl.addEventListener('touchend', blurAfterInteraction);
            ctrl.addEventListener('click', blurAfterInteraction);
        });

        const tabs = controls.filter(ctrl => ctrl.classList.contains('js-purchase-tab'));
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
// Payment Timer Countdown
(function () {
    const clearSummaryUrl = <?php echo json_encode(route('order.summary.clear'), 15, 512) ?>;

    function formatTime(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    async function cancelOrder(card, orderId) {
        try {
            // Remove the card from DOM
            card.remove();

            // Clear session storage if it matches
            const stored = window.sessionStorage.getItem('inkwise-finalstep');
            if (stored) {
                const summary = JSON.parse(stored);
                if (summary && summary.orderId == orderId) {
                    window.sessionStorage.removeItem('inkwise-finalstep');
                    // Clear server summary
                    await fetch(clearSummaryUrl, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        }
                    });
                }
            }
        } catch (err) {
            console.warn('failed to auto-cancel expired order', err);
        }
    }

    function updateTimers() {
        const timers = document.querySelectorAll('.js-payment-timer');
        timers.forEach(timer => {
            const deadline = new Date(timer.getAttribute('data-deadline'));
            const now = new Date();
            const remaining = Math.max(0, Math.floor((deadline - now) / 1000));

            timer.textContent = formatTime(remaining);

            if (remaining === 0) {
                // Payment expired - auto cancel the order
                timer.textContent = 'EXPIRED';
                timer.classList.add('text-gray-500');
                timer.classList.remove('text-red-500');

                // Auto cancel the order
                const card = timer.closest('.bg-white.border.rounded-xl');
                if (card) {
                    const orderId = timer.getAttribute('data-order-id');
                    cancelOrder(card, orderId);
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateTimers();
        setInterval(updateTimers, 1000);
    });
})();
</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.customerprofile', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\leanne\xampp\htdocs\InkWise-Web\ink-wise\resources\views/customer/profile/purchase/topay.blade.php ENDPATH**/ ?>