<?php $__env->startSection('title', 'Uploaded Templates'); ?>

<?php $__env->startPush('styles'); ?>
    <?php echo app('Illuminate\Foundation\Vite')('resources/css/admin/template/template.css'); ?>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <main class="dashboard-container templates-page" role="main">
        <section class="templates-container" aria-labelledby="templates-heading">
            <div class="templates-header">
                <div>
                    <h2 id="templates-heading">Uploaded Templates</h2>
                    <p>View all templates that have been uploaded and are ready for use</p>
                </div>
                <div class="header-actions">
                    <a href="<?php echo e(route('staff.templates.index')); ?>" class="btn-secondary">
                        <span> Create New Templates</span>
                    </a>
                </div>
            </div>

        </section>

        <div class="templates-filters">
            <div class="filter-buttons" role="group" aria-label="Filter uploaded templates">
                <a href="<?php echo e(route('staff.templates.uploaded')); ?>" class="filter-btn active" data-filter="all">
                    All Uploaded
                </a>
                <a href="<?php echo e(route('staff.templates.uploaded', ['type' => 'invitation'])); ?>" class="filter-btn <?php echo e($type === 'invitation' ? 'active' : ''); ?>" data-filter="invitation">
                    Invitations
                </a>
                <a href="<?php echo e(route('staff.templates.uploaded', ['type' => 'giveaway'])); ?>" class="filter-btn <?php echo e($type === 'giveaway' ? 'active' : ''); ?>" data-filter="giveaway">
                    Giveaways
                </a>
                <a href="<?php echo e(route('staff.templates.uploaded', ['type' => 'envelope'])); ?>" class="filter-btn <?php echo e($type === 'envelope' ? 'active' : ''); ?>" data-filter="envelope">
                    Envelopes
                </a>
            </div>
        </div>

        <?php if(session('success')): ?>
            <div class="alert alert-success" role="status">
                <?php echo e(session('success')); ?>

            </div>
        <?php endif; ?>

        <?php if($templates->isEmpty()): ?>
            <div class="empty-state mt-gap">
                <div class="empty-state-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="empty-state-title">No templates</h3>
                <p class="empty-state-description">Upload from templates page.</p>
                <a href="<?php echo e(route('staff.templates.index')); ?>" class="btn-primary">Templates</a>
            </div>
        <?php else: ?>
            <div class="templates-grid mt-gap" role="list">
                <?php $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $template): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <article class="template-card uploaded-template-card" role="listitem">
                        <div class="template-preview">
                            <?php
                                $front = $template->front_image ?? $template->preview;
                                $back = $template->back_image ?? null;
                            ?>
                            <?php if($front): ?>
                                <img src="<?php echo e(\App\Support\ImageResolver::url($front)); ?>" alt="Preview of <?php echo e($template->name); ?>">
                            <?php else: ?>
                                <span>No preview</span>
                            <?php endif; ?>
                            <?php if($back): ?>
                                <img src="<?php echo e(\App\Support\ImageResolver::url($back)); ?>" alt="Back of <?php echo e($template->name); ?>" class="back-thumb">
                            <?php endif; ?>
                            <div class="uploaded-badge">
                                <i class="fas fa-check-circle"></i>
                                <span>Uploaded</span>
                            </div>
                        </div>
                        <div class="template-info">
                            <div class="template-meta">
                                <span class="template-category"><?php echo e($template->product_type ?? 'Uncategorized'); ?></span>
                                <?php if($template->updated_at): ?>
                                    <span class="template-date">Uploaded <?php echo e($template->updated_at->format('M d, Y')); ?></span>
                                <?php endif; ?>
                            </div>
                            <h3 class="template-title"><?php echo e($template->name); ?></h3>
                            <?php if($template->description): ?>
                                <p class="template-description"><?php echo e($template->description); ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        <div id="previewModal">
            <span id="closePreview" aria-label="Close preview" role="button">&times;</span>
            <img id="modalImg" src="" alt="Template preview modal">
        </div>
    </main>

    <script>
        // Preview modal functionality
        function openPreview(src) {
            document.getElementById('modalImg').src = src;
            document.getElementById('previewModal').classList.add('is-visible');
        }

        document.getElementById('closePreview').onclick = function() {
            document.getElementById('previewModal').classList.remove('is-visible');
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('previewModal')) {
                document.getElementById('previewModal').classList.remove('is-visible');
            }
        }

        // Template actions
        function editTemplate(id) {
            window.location.href = `/staff/templates/${id}/edit`;
        }

        function duplicateTemplate(id) {
            if (confirm('Are you sure you want to duplicate this template?')) {
                // Implement duplicate logic
                fetch(`/staff/templates/${id}/duplicate`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    }
                }).then(response => {
                    if (response.ok) {
                        location.reload();
                    } else {
                        alert('Failed to duplicate template');
                    }
                });
            }
        }

        function deleteTemplate(id) {
            if (confirm('Are you sure you want to delete this template? This action cannot be undone.')) {
                fetch(`/staff/templates/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                }).then(response => {
                    if (response.ok) {
                        location.reload();
                    } else {
                        alert('Failed to delete template');
                    }
                });
            }
        }

        // Search functionality
        document.getElementById('template-search').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.template-card');

            cards.forEach(card => {
                const title = card.querySelector('.template-title').textContent.toLowerCase();
                const description = card.querySelector('.template-description')?.textContent.toLowerCase() || '';
                const category = card.querySelector('.template-category').textContent.toLowerCase();

                if (title.includes(searchTerm) || description.includes(searchTerm) || category.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.staffapp', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\leanne\xampp\htdocs\InkWise-Web\ink-wise\resources\views/staff/templates/uploaded.blade.php ENDPATH**/ ?>