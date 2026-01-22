<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Material</title>
    <link rel="stylesheet" href="<?php echo e(asset('css/admin-css/create_materials.css')); ?>">
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap" rel="stylesheet">
    <style>
        body, input, select, textarea, button, .form-control, .styled-select {
            font-family: 'Poppins', Arial, sans-serif !important;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const materialTypeSelect = document.getElementById('materialTypeSelect');
            const defaultFields = document.getElementById('default-fields');
            const inkFields = document.getElementById('ink-fields');
            const defaultInputs = defaultFields.querySelectorAll('input, select, textarea');
            const inkInputs = inkFields.querySelectorAll('input, select, textarea');

            function recordOriginalRequirement(inputs) {
                inputs.forEach(input => {
                    input.dataset.originalRequired = input.hasAttribute('required') ? 'true' : 'false';
                });
            }

            function setGroupState(inputs, enable) {
                inputs.forEach(input => {
                    if (enable) {
                        input.disabled = false;
                        if (input.dataset.originalRequired === 'true') {
                            input.setAttribute('required', 'required');
                        } else {
                            input.removeAttribute('required');
                        }
                    } else {
                        input.disabled = true;
                        input.removeAttribute('required');
                    }
                });
            }

            recordOriginalRequirement(defaultInputs);
            recordOriginalRequirement(inkInputs);

            function toggleFields() {
                const selectedType = materialTypeSelect.value;
                if (selectedType === 'ink') {
                    defaultFields.style.display = 'none';
                    inkFields.style.display = 'block';
                    setGroupState(defaultInputs, false);
                    setGroupState(inkInputs, true);
                } else {
                    defaultFields.style.display = 'block';
                    inkFields.style.display = 'none';
                    setGroupState(defaultInputs, true);
                    setGroupState(inkInputs, false);
                }
            }

            materialTypeSelect.addEventListener('change', toggleFields);
            toggleFields(); // Initial check
        });
    </script>
</head>
<body>
    <div class="container">
        <h2>Edit Material</h2>

        
        <?php if(session('success')): ?>
            <div class="success"><?php echo e(session('success')); ?></div>
        <?php endif; ?>

        <?php if($errors->any()): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?php echo e(route('admin.materials.update', $material->material_id)); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>

            <div class="form-row">
                <div class="form-group">
                    <label>Material Name</label>
                    <input type="text" name="material_name" value="<?php echo e(old('material_name', $material->material_name)); ?>" required class="form-control">
                    <?php $__errorArgs = ['material_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small style="color:red;"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-group">
                    <label>Material Type</label>
                    <select name="material_type" id="materialTypeSelect" required class="form-control styled-select">
                        <option value="">-- Select Material Type --</option>
                        <option value="paper" <?php echo e(old('material_type', $material->material_type) == 'paper' ? 'selected' : ''); ?>>PAPER</option>
                        <option value="ink" <?php echo e(old('material_type', $material->material_type) == 'ink' ? 'selected' : ''); ?>>INK</option>
                        <option value="envelopes" <?php echo e(old('material_type', $material->material_type) == 'envelopes' ? 'selected' : ''); ?>>ENVELOPES</option>
                        <option value="ribbon" <?php echo e(old('material_type', $material->material_type) == 'ribbon' ? 'selected' : ''); ?>>RIBBON</option>
                        <option value="powder" <?php echo e(old('material_type', $material->material_type) == 'powder' ? 'selected' : ''); ?>>POWDER</option>
                        <option value="souvenirs" <?php echo e(old('material_type', $material->material_type) == 'souvenirs' ? 'selected' : ''); ?>>SOUVENIRS</option>
                    </select>
                    <?php $__errorArgs = ['material_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small style="color:red;"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            <div id="default-fields">
                <div class="form-row">
                    <div class="form-group">
                        <label>Unit (e.g. pcs, ream, liter)</label>
                        <input type="text" name="unit" value="<?php echo e(old('unit', $material->unit)); ?>" required class="form-control">
                        <?php $__errorArgs = ['unit'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small style="color:red;"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="form-group">
                        <label>Unit Price</label>
                        <input type="number" step="0.01" name="unit_cost" value="<?php echo e(old('unit_cost', $material->unit_cost)); ?>" required class="form-control">
                        <?php $__errorArgs = ['unit_cost'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small style="color:red;"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Stock Level</label>
                        <input type="number" name="stock_level" value="<?php echo e(old('stock_level', $material->inventory->stock_level ?? 0)); ?>" required class="form-control">
                        <?php $__errorArgs = ['stock_level'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small style="color:red;"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="form-group">
                        <label>Reorder Level</label>
                        <input type="number" name="reorder_level" value="<?php echo e(old('reorder_level', $material->inventory->reorder_level ?? 0)); ?>" required class="form-control">
                        <?php $__errorArgs = ['reorder_level'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small style="color:red;"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </div>

            <!-- Ink fields (hidden by default) -->
            <div id="ink-fields" style="display:none;">
                <div class="form-row">
                    <div class="form-group">
                        <label>Material Name</label>
                        <input type="text" name="material_name" value="<?php echo e(old('material_name', $material->material_name)); ?>" required class="form-control styled-select">
                        <small style="color:#6b7280;">Required. Example: "Premium Black Ink"</small>
                        <?php $__errorArgs = ['material_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small style="color:red;"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Ink Color</label>
                        <input type="text" name="ink_color" value="<?php echo e(old('ink_color', $material->ink_color ?? '')); ?>" required class="form-control styled-select">
                        <small style="color:#6b7280;">Required. Color name or code (e.g. Black, Cyan, Magenta).</small>
                        <?php $__errorArgs = ['ink_color'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small style="color:red;"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="form-group">
                        <label>Unit (for cans)</label>
                        <input type="text" name="unit" value="<?php echo e(old('unit', $material->unit ?? 'can')); ?>" required class="form-control styled-select" placeholder="e.g. can">
                        <small style="color:#6b7280;">Required. Usually "can" for inks.</small>
                        <?php $__errorArgs = ['unit'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small style="color:red;"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Size (ml per can)</label>
                        <input type="text" name="size" value="<?php echo e(old('size', $material->size ?? '')); ?>" required class="form-control styled-select" placeholder="e.g. 500 or 500ml">
                        <small style="color:#6b7280;">Required. Enter a number (e.g. 500) or include unit (e.g. 500ml). Numeric values will display as "500 ml" in lists.</small>
                        <?php $__errorArgs = ['size'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small style="color:red;"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="form-group">
                        <label>Stock Qty (number of cans)</label>
                        <input type="number" name="stock_qty" value="<?php echo e(old('stock_qty', $material->inventory->stock_level ?? 0)); ?>" required class="form-control styled-select" min="0">
                        <small style="color:#6b7280;">Required. Enter how many cans you currently have (integer).</small>
                        <?php $__errorArgs = ['stock_qty'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small style="color:red;"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Cost per ml (₱)</label>
                        <input type="number" step="0.01" name="cost_per_ml" value="<?php echo e(old('cost_per_ml', $material->cost_per_ml ?? '')); ?>" required class="form-control styled-select">
                        <small style="color:#6b7280;">Required. Unit price per milliliter (e.g. 0.25).</small>
                        <?php $__errorArgs = ['cost_per_ml'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small style="color:red;"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </div>

            <div class="btn-group">
                <button type="submit">Update Material</button>
                <a href="<?php echo e(route('admin.materials.index')); ?>" class="btn-back">Back to Materials</a>
            </div>
        </form>
    </div>
</body>
</html>
<?php /**PATH C:\Users\leanne\xampp\htdocs\InkWise-Web\ink-wise\resources\views/admin/materials/edit.blade.php ENDPATH**/ ?>