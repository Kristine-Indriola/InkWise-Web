<?php
    $templateType = 'envelope';
    $productTypeMap = [
        'invitation' => 'Invitation',
        'giveaway' => 'Giveaway',
        'envelope' => 'Envelope'
    ];
    $selectedProductType = $productTypeMap[$templateType] ?? 'Envelope';

    // Check if editing a preview
    $editPreviewId = request('edit_preview');
    $previewData = null;
    if ($editPreviewId) {
        $previews = session('preview_templates', []);
        foreach ($previews as $preview) {
            if (isset($preview['id']) && $preview['id'] === $editPreviewId) {
                $previewData = $preview;
                break;
            }
        }
    }
?>

<?php $__env->startPush('styles'); ?>
    <?php echo app('Illuminate\Foundation\Vite')('resources/css/admin/template/template.css'); ?>
    <style>
        /* Make the create container bigger */
        .create-container{max-width:1400px;margin:0 auto;padding:20px}
        /* Make the preview area scale inside the square */
        .svg-preview svg{width:100%;height:100%;object-fit:contain}
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
    <script>
        // Figma integration variables
        let figmaAnalyzedData = null;
        let selectedFrames = [];

        function getCsrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta && meta.getAttribute) return meta.getAttribute('content') || '';
            const hidden = document.querySelector('input[name="_token"]');
            return hidden ? hidden.value : '';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.create-form');
            if (!form) return;

            // Handle form submission
            form.addEventListener('submit', function(e) {
                // Basic validation
                const nameField = document.getElementById('name');

                if (!nameField || !nameField.value.trim()) {
                    e.preventDefault();
                    alert('Please provide a template name.');
                    return false;
                }

                // Set design data for envelope
                const designInput = document.getElementById('design');
                if (designInput) {
                    designInput.value = JSON.stringify({
                        text: "Envelope Design",
                        type: "envelope"
                    });
                }

                // Let the form submit normally
                return true;
            });
        });
    </script>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<main class="dashboard-container templates-page" role="main">
    <section class="create-container" aria-labelledby="create-template-heading">
        <div>
            <h2 id="create-template-heading">Create New Envelope Template</h2>
            <p class="create-subtitle">Upload a single SVG to create an envelope template</p>
        </div>

    <form action="<?php echo e(route('staff.templates.store')); ?>" method="POST" class="create-form" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>

            <input type="hidden" name="design" id="design" value="{}">
            <?php if($editPreviewId): ?>
                <input type="hidden" name="edit_preview_id" value="<?php echo e($editPreviewId); ?>">
            <?php endif; ?>

            <!-- Hidden fields for Figma import data -->
            <input type="hidden" name="figma_url" id="figma_url_hidden">
            <input type="hidden" name="figma_file_key" id="figma_file_key_hidden">
            <input type="hidden" name="front_svg_content" id="front_svg_content_hidden">
            <input type="hidden" name="import_method" id="import_method_hidden" value="manual">

            <div class="create-row">
                <div class="create-group flex-1">
                    <label for="name">Template Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter template name" value="<?php echo e($previewData['name'] ?? ''); ?>" required>
                </div>
                <div class="create-group flex-1">
                    <label for="product_type_display">Product Type</label>
                    <div class="readonly-field">
                        <span id="product_type_display"><?php echo e($selectedProductType); ?></span>
                        <input type="hidden" id="product_type" name="product_type" value="<?php echo e($selectedProductType); ?>" required>
                    </div>
                </div>
            </div>

            <div class="create-row">
                <div class="create-group flex-1">
                    <label for="event_type">Event Type</label>
                    <select id="event_type" name="event_type">
                        <option value="">Select event type</option>
                        <option value="Wedding" <?php echo e(($previewData['event_type'] ?? '') === 'Wedding' ? 'selected' : ''); ?>>Wedding</option>
                        <option value="Birthday" <?php echo e(($previewData['event_type'] ?? '') === 'Birthday' ? 'selected' : ''); ?>>Birthday</option>
                        <option value="Baby Shower" <?php echo e(($previewData['event_type'] ?? '') === 'Baby Shower' ? 'selected' : ''); ?>>Baby Shower</option>
                        <option value="Corporate" <?php echo e(($previewData['event_type'] ?? '') === 'Corporate' ? 'selected' : ''); ?>>Corporate</option>
                        <option value="Other" <?php echo e(($previewData['event_type'] ?? '') === 'Other' ? 'selected' : ''); ?>>Other</option>
                    </select>
                </div>
                <div class="create-group flex-1">
                    <label for="theme_style">Theme / Style</label>
                    <input type="text" id="theme_style" name="theme_style" placeholder="e.g. Minimalist, Floral" value="<?php echo e($previewData['theme_style'] ?? ''); ?>">
                </div>
            </div>

            <div class="create-group">
                <label for="description">Design Description</label>
                <textarea id="description" name="description" rows="4" placeholder="Describe the design or special instructions (optional)"><?php echo e($previewData['description'] ?? ''); ?></textarea>
            </div>

            <div class="create-actions">
                <a href="<?php echo e(route('staff.templates.index')); ?>" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-submit">Create Template</button>
            </div>
        </form>
    </section>
</main>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.staffapp', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\leanne\xampp\htdocs\InkWise-Web\ink-wise\resources\views/staff/templates/create-envelope.blade.php ENDPATH**/ ?>