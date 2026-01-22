<?php $__env->startPush('styles'); ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@flaticon/flaticon-uicons/css/all/all.css">
    <link rel="stylesheet" href="<?php echo e(asset('css/admin-css/materials.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/staff-css/customer-profiles.css')); ?>">
    <style>
        .clickable-row {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .clickable-row:hover {
            background-color: rgba(79, 70, 229, 0.05);
            transform: scale(1.01);
        }
        .clickable-row:active {
            background-color: rgba(79, 70, 229, 0.1);
        }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('title', 'Customer Profiles'); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $customersCollection = $customers instanceof \Illuminate\Support\Collection ? $customers : collect($customers);
    ?>

<main class="materials-page admin-page-shell staff-customer-profiles-page" role="main">
    <header class="page-header">
        <div>
            <h1 class="page-title">Customer Profiles</h1>
            <p class="page-subtitle">Manage customer information and registration details.</p>
        </div>
    </header>

    <?php if(session('success')): ?>
        <div class="alert staff-customer-profiles-alert" role="alert" aria-live="polite">
            ✅ <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    <section class="customer-profiles-table" aria-label="Customer list">
        <div class="table-wrapper">
            <table class="table" role="grid">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Profile Picture</th>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Contact Number</th>
                        <th scope="col">Address</th>
                        <th scope="col">Registered At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $customersCollection; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $profile = $customer->customer;
                            $address = $customer->address;
                            $avatar = $profile && ($profile->photo ?? null)
                                ? \App\Support\ImageResolver::url($profile->photo)
                                : 'https://via.placeholder.com/64?text=User';
                            $fullName = collect([
                                $profile->first_name ?? null,
                                $profile->middle_name ?? null,
                                $profile->last_name ?? null,
                            ])->filter()->implode(' ');
                        ?>

                        <tr class="clickable-row" data-href="<?php echo e(route('staff.customer_profile.show', $customer->user_id)); ?>" role="button" tabindex="0">
                            <td><?php echo e($customer->user_id); ?></td>
                            <td class="profile-pic-cell">
                                <img src="<?php echo e($avatar); ?>" alt="Profile" class="profile-pic">
                            </td>
                            <td class="customer-name"><?php echo e($fullName ?: ($customer->name ?? 'N/A')); ?></td>
                            <td><?php echo e($customer->email); ?></td>
                            <td><?php echo e($profile->contact_number ?? '—'); ?></td>
                            <td><?php echo e($address ? ($address->street . ', ' . $address->city . ', ' . $address->province) : '—'); ?></td>
                            <td><?php echo e($customer->created_at->format('M d, Y')); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="table-empty">No customers found.</td>
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
            const alertBanner = document.querySelector('.staff-customer-profiles-alert');
            if (alertBanner) {
                setTimeout(function () {
                    alertBanner.classList.add('is-dismissing');
                    setTimeout(() => alertBanner.remove(), 600);
                }, 4000);
            }

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

<?php echo $__env->make('layouts.staffapp', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\leanne\xampp\htdocs\InkWise-Web\ink-wise\resources\views/staff/customer_profile.blade.php ENDPATH**/ ?>