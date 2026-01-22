<?php $__env->startSection('title', 'Notifications'); ?>

<?php $__env->startPush('styles'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('css/admin-css/materials.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/admin-css/notifications.css')); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<main class="admin-page-shell notifications-page" role="main">
    <header class="page-header">
        <div>
            <h1 class="page-title">All Notifications</h1>
            <p class="page-subtitle">Review recent system alerts and updates delivered to your account.</p>
        </div>
    </header>

    <section class="notification-card" aria-label="Notifications list">
        <header class="notification-card__header">
            <div class="notification-card__icon" aria-hidden="true">
                <i class="fa-solid fa-bell"></i>
            </div>
            <div>
                <h2 class="notification-card__title">Latest activity</h2>
                <p class="notification-card__description">Messages, approvals, and reminders sent to your admin inbox.</p>
            </div>
        </header>

        <?php
            $defaultNotificationRoutes = [
                'StaffApprovedNotification' => route('admin.users.index'),
                'NewStaffCreated' => route('owner.staff.index'),
                'StockNotification' => route('admin.inventory.index'),
                'PasswordResetCompleted' => route('admin.users.index'),
                'NewOrderPlaced' => route('admin.orders.index'),
            ];
        ?>

        <ul class="notification-list" id="notificationsList">
            <?php $__empty_1 = true; $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $isRead = !is_null($notification->read_at);
                    $targetUrl = $notification->data['url'] ?? null;

                    if (! $targetUrl) {
                        $type = class_basename($notification->type);
                        $targetUrl = $defaultNotificationRoutes[$type] ?? null;
                    }
                ?>
                <li class="notification-list__item <?php echo e($isRead ? 'is-read' : 'is-unread'); ?>">
                    <a href="<?php echo e($targetUrl ?? '#'); ?>"
                       class="notification-list__link"
                       data-notification-id="<?php echo e($notification->id); ?>"
                       data-read-url="<?php echo e(route('notifications.read', $notification->id)); ?>"
                       data-target-url="<?php echo e($targetUrl ?? ''); ?>">
                        <div class="notification-list__content">
                            <p class="notification-list__message"><?php echo e($notification->data['message'] ?? 'New notification'); ?></p>
                            <time class="notification-list__time" datetime="<?php echo e($notification->created_at->toIso8601String()); ?>">
                                <?php echo e($notification->created_at->diffForHumans()); ?>

                            </time>
                        </div>
                        <span class="notification-list__status">
                            <i class="fa-solid <?php echo e($isRead ? 'fa-circle-check' : 'fa-envelope'); ?>" aria-hidden="true"></i>
                            <span><?php echo e($isRead ? 'Read' : 'Unread'); ?></span>
                        </span>
                    </a>
                </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <li class="notification-list__empty">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    <p>No notifications yet. You're all caught up!</p>
                </li>
            <?php endif; ?>
        </ul>
    </section>
</main>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const list = document.getElementById('notificationsList');

    if (!list) return;

    list.addEventListener('click', async (event) => {
        const link = event.target.closest('.notification-list__link');
        if (!link) return;

        const targetUrl = link.dataset.targetUrl;
        if (!targetUrl) return;

        event.preventDefault();

        const readUrl = link.dataset.readUrl;

        if (readUrl) {
            try {
                await fetch(readUrl, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
            } catch (error) {
                console.error('Failed to mark notification as read', error);
            }
        }

        window.location.href = targetUrl;
    });
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\leanne\xampp\htdocs\InkWise-Web\ink-wise\resources\views/admin/notifications/index.blade.php ENDPATH**/ ?>