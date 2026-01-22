<div class="table-wrapper">
    <table class="table" role="grid">
        <thead>
            <tr>
                <th scope="col">Staff ID</th>
                <th scope="col">Role</th>
                <th scope="col">Name</th>
                <th scope="col">Email</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $staffProfile = $user->staff;
                    $fullName = collect([
                        optional($staffProfile)->first_name,
                        optional($staffProfile)->middle_name,
                        optional($staffProfile)->last_name,
                    ])->filter()->implode(' ');
                    $roleClass = match($user->role) {
                        'owner' => 'badge-role badge-role--owner',
                        'admin' => 'badge-role badge-role--admin',
                        default => 'badge-role badge-role--staff',
                    };
                    $status = optional($staffProfile)->status ?? $user->status;
                    $statusClass = match($status) {
                        'approved', 'active' => 'badge-status badge-status--active',
                        'pending' => 'badge-status badge-status--pending',
                        'archived' => 'badge-status badge-status--archived',
                        default => 'badge-status badge-status--inactive',
                    };
                    $rowClass = $status === 'pending' ? 'staff-row staff-row--pending' : 'staff-row';
                    $isHighlighted = request('highlight') && (int) request('highlight') === (int) $user->user_id;
                ?>
                <tr <?php echo e($isHighlighted ? 'id=highlighted-staff' : ''); ?> class="<?php echo e($isHighlighted ? 'staff-row highlight-row' : $rowClass); ?>" onclick="window.location='<?php echo e(route('admin.users.show', $user->user_id)); ?>'">
                    <td><?php echo e($staffProfile->staff_id ?? '—'); ?></td>
                    <td>
                        <span class="<?php echo e($roleClass); ?>"><?php echo e(ucfirst($user->role)); ?></span>
                    </td>
                    <td class="fw-bold"><?php echo e($fullName ?: '—'); ?></td>
                    <td><?php echo e($user->email); ?></td>
                    <td>
                        <span class="<?php echo e($statusClass); ?>"><?php echo e(ucfirst($status)); ?></span>
                    </td>
                    <td class="table-actions" onclick="event.stopPropagation();">
                        <?php if(optional($staffProfile)->status !== 'archived'): ?>
                            <a href="<?php echo e(route('admin.users.edit', $user->user_id)); ?>" class="btn btn-warning">
                                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                <span>Edit</span>
                            </a>
                            <form action="<?php echo e(route('admin.users.destroy', $user->user_id)); ?>" method="POST" class="inline">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" onclick="return confirm('Archive this staff account?')" class="btn btn-danger">
                                    <i class="fa-solid fa-box-archive" aria-hidden="true"></i>
                                    <span>Archive</span>
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="badge-status badge-status--archived">Archived</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="6" class="text-center">No staff found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php /**PATH C:\Users\leanne\xampp\htdocs\InkWise-Web\ink-wise\resources\views/admin/users/partials/table.blade.php ENDPATH**/ ?>