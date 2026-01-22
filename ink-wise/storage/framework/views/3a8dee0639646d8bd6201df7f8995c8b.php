<?php $__env->startSection('title', 'Customer Profiles'); ?>

<?php $__env->startPush('styles'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('css/admin-css/materials.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/admin-css/customer.css')); ?>">
    <style>
        .clickable-row {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .clickable-row:hover {
            background-color: rgba(79, 70, 229, 0.05);
            transform: scale(1.005);
        }
        .clickable-row:active {
            background-color: rgba(79, 70, 229, 0.1);
        }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<main class="admin-page-shell customer-page" role="main">
    <header class="page-header">
        <div>
            <h1 class="page-title">Customer Profiles</h1>
            <p class="page-subtitle">Keep track of registered customers and their contact details.</p>
        </div>
    </header>

    <section class="summary-grid" aria-label="Customer summary">
        <div class="summary-card">
            <div class="summary-card-header">
                <span class="summary-card-label">Total Customers</span>
                <span class="summary-card-chip accent">Directory</span>
            </div>
            <div class="summary-card-body">
                <span class="summary-card-value"><?php echo e(number_format($customers->count())); ?></span>
                <span class="summary-card-icon" aria-hidden="true">👥</span>
            </div>
            <span class="summary-card-meta">Accounts on record</span>
        </div>
    </section>

    <section aria-label="Customer list" class="customers-table">
        <div class="table-wrapper">
            <table class="table" role="grid">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Profile</th>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Contact</th>
                        <th scope="col">Address</th>
                        <th scope="col">Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $profile = $customer->customer;
                            $address = $customer->address;
                            $avatar = $profile && $profile->photo
                                ? \App\Support\ImageResolver::url($profile->photo)
                                : 'https://via.placeholder.com/64?text=User';
                            $fullName = collect([
                                $profile->first_name ?? null,
                                $profile->middle_name ?? null,
                                $profile->last_name ?? null,
                            ])->filter()->implode(' ');
                        ?>
                        <tr class="clickable-row" data-href="<?php echo e(route('admin.customers.show', $customer->user_id)); ?>" role="button" tabindex="0">
                            <td><?php echo e($customer->user_id); ?></td>
                            <td>
                                <span class="customer-avatar" aria-hidden="true">
                                    <img src="<?php echo e($avatar); ?>" alt="<?php echo e($fullName ?: 'Customer avatar'); ?>">
                                </span>
                            </td>
                            <td class="fw-bold"><?php echo e($fullName ?: '—'); ?></td>
                            <td>
                                <span class="text-emphasis"><?php echo e($customer->email ?? '—'); ?></span>
                            </td>
                            <td><?php echo e($profile->contact_number ?? '—'); ?></td>
                            <td>
                                <?php if($address): ?>
                                    <?php
                                        $addressParts = collect([
                                            $address->street ?? null,
                                            $address->barangay ?? null,
                                            $address->city ?? null,
                                            $address->province ?? null,
                                            $address->postal_code ?? null,
                                            $address->country ?? null,
                                        ])->filter()->implode(', ');
                                    ?>
                                    <span class="address-block"><?php echo e($addressParts ?: '—'); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <time datetime="<?php echo e($customer->created_at->toIso8601String()); ?>">
                                    <?php echo e($customer->created_at->format('M d, Y')); ?>

                                </time>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="text-center">No customers found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Make table rows clickable
            document.querySelectorAll('.clickable-row').forEach(function(row) {
                row.addEventListener('click', function() {
                    window.location.href = this.dataset.href;
                });
                
                // Handle keyboard navigation
                row.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        window.location.href = this.dataset.href;
                    }
                });
            });
        });
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\leanne\xampp\htdocs\InkWise-Web\ink-wise\resources\views/admin/customers/index.blade.php ENDPATH**/ ?>