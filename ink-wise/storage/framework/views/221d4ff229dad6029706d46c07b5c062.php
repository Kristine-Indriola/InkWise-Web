<?php $__env->startSection('title', 'Create Invitation Product'); ?>

<?php $__env->startPush('styles'); ?>
<link rel="stylesheet" href="<?php echo e(asset('css/admin-css/create_invite.css')); ?>">
<link rel="stylesheet" href="<?php echo e(asset('css/admin-css/template/template.css')); ?>">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap">
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="<?php echo e(asset('js/admin/create_invite.js')); ?>" defer></script>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php
    $product = $product ?? null;
    $selectedTemplate = $selectedTemplate ?? ($product->template ?? null);

    $requestedType = request('type');

    $defaults = [
        'name' => old('invitationName', $product->name ?? $selectedTemplate->name ?? ''),
        'event_type' => old('eventType', $product->event_type ?? $selectedTemplate->event_type ?? ''),
        'product_type' => old('productType', $product->product_type ?? $selectedTemplate->product_type ?? ($requestedType ?: 'Invitation')),
        'theme_style' => old('themeStyle', $product->theme_style ?? $selectedTemplate->theme_style ?? ''),
        'description' => old('description', $product->description ?? $selectedTemplate->description ?? ''),
        'size' => old('invitation_size', $product->size ?? $selectedTemplate->size ?? ''),
        'base_price' => old('base_price', $product->base_price ?? ''),
        'lead_time' => old('lead_time', $product->lead_time ?? ''),
        'date_available' => old('date_available', isset($product) && !empty($product->date_available)
            ? optional(\Illuminate\Support\Carbon::parse($product->date_available))->format('Y-m-d')
            : ''),
    ];

    $productPaperStocks = isset($product) ? $product->paperStocks->map(function ($stock) {
        return [
            'id' => $stock->id,
            'material_id' => $stock->material_id,
            'name' => $stock->name,
            'price' => $stock->price,
            'image_path' => $stock->image_path,
            'image_url' => $stock->image_path ? \Illuminate\Support\Facades\Storage::url($stock->image_path) : null,
        ];
    })->toArray() : [];

    $productAddons = isset($product) ? $product->addons->map(function ($addon) {
        return [
            'id' => $addon->id,
            'addon_type' => $addon->addon_type,
            'name' => $addon->name,
            'price' => $addon->price,
            'image_path' => $addon->image_path,
            'image_url' => $addon->image_path ? \Illuminate\Support\Facades\Storage::url($addon->image_path) : null,
        ];
    })->toArray() : [];

    $productColors = isset($product) ? $product->colors->map(function ($color) {
        return [
            'id' => $color->id,
            'name' => $color->name,
            'color_code' => $color->color_code,
        ];
    })->toArray() : [];

    $averageUsageMl = old('average_usage_ml', optional($product?->colors?->first())->average_usage_ml ?? '');

    $paperStockRows = old('paper_stocks', !empty($productPaperStocks) ? $productPaperStocks : [[]]);
    $addonRows = old('addons', !empty($productAddons) ? $productAddons : [[]]);
    $colorRows = old('colors', !empty($productColors) ? $productColors : [[]]);
?>

<nav aria-label="breadcrumb" class="breadcrumb-nav">
    <ol class="breadcrumb-list" itemscope itemtype="https://schema.org/BreadcrumbList">
        <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a href="<?php echo e(route('admin.products.index')); ?>" class="breadcrumb-link" aria-label="Go to Admin Dashboard" itemprop="item">
                <span itemprop="name"><i class="fas fa-home"></i> Dashboard</span>
            </a>
            <meta itemprop="position" content="1" />
            <span class="breadcrumb-separator" aria-hidden="true">›</span>
        </li>
        <li class="breadcrumb-item">
            <button type="button" id="breadcrumb-step1" class="breadcrumb-step active" aria-live="polite" aria-current="page" onclick="Navigation.showPage(0)">
                Templates
            </button>
            <span class="breadcrumb-separator" aria-hidden="true">›</span>
        </li>
        <li class="breadcrumb-item">
            <button type="button" id="breadcrumb-step2" class="breadcrumb-step" aria-live="polite" onclick="Navigation.showPage(1)">
                Basic Info
            </button>
            <span class="breadcrumb-separator" aria-hidden="true">›</span>
        </li>
        <li class="breadcrumb-item">
            <button type="button" id="breadcrumb-step3" class="breadcrumb-step" aria-live="polite" onclick="Navigation.showPage(2); updatePreviewImages();">
                Production
            </button>
            <span class="breadcrumb-separator" aria-hidden="true">›</span>
        </li>
        
    </ol>
</nav>


<h1 id="page-title">Templates</h1>

<form method="POST" action="<?php echo e(route('admin.products.store')); ?>" id="invitation-form" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <?php if(isset($product) && $product->id): ?>
        <input type="hidden" id="product_id" name="product_id" value="<?php echo e($product->id); ?>">
    <?php endif; ?>
    <input type="hidden" id="template_id" name="template_id" value="">

    <div class="invitation-container">
        
        <div class="progress-bar">
            <div class="progress-fill" id="progress-fill"></div>
        </div>

        
        <?php echo $__env->make('admin.products.templates', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <div class="page page2" data-page="1">
            <div class="error-summary" style="display: none;" role="alert" aria-live="polite">
                <h3>Please correct the following errors:</h3>
                <ul id="error-list-page2"></ul>
            </div>
            <div class="form-section">
                <h2><i class="fas fa-info-circle"></i> Basic Information</h2>
                <div class="responsive-grid grid-2-cols">
                    <div class="field">
                        <label for="invitationName">Invitation Name</label>
                        <input type="text" id="invitationName" name="invitationName" placeholder="Invitation Name (e.g. Elegant Pearl Wedding Invitation)" required aria-required="true" aria-describedby="invitationName-error" value="<?php echo e($defaults['name']); ?>">
                        <span id="invitationName-error" class="error-message" role="alert" aria-live="polite"></span>
                        <?php $__errorArgs = ['invitationName'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="error-message"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="field">
                        <label for="eventType">Event Type</label>
                        <select id="eventType" name="eventType" required aria-required="true" aria-describedby="eventType-error">
                            <option value="" disabled <?php echo e($defaults['event_type'] ? '' : 'selected'); ?>>Event Type</option>
                            <?php $__currentLoopData = ['Wedding', 'Birthday', 'Baptism', 'Corporate']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($type); ?>" <?php echo e(($defaults['event_type'] == $type) ? 'selected' : ''); ?>><?php echo e($type); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span id="eventType-error" class="error-message"></span>
                        <?php $__errorArgs = ['eventType'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="error-message"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
                <div class="responsive-grid grid-2-cols">
                    <div class="field">
                        <label for="productType">Product Type</label>
                        <input type="hidden" id="productType" name="productType" value="Invitation">
                        <input type="text" class="styled-select" value="INVITATION" readonly>
                    </div>
                    <div class="field">
                        <label for="themeStyle">Theme / Style</label>
                        <input type="text" id="themeStyle" name="themeStyle" placeholder="Theme / Style (e.g. Luxury, Minimalist, Floral)" required aria-required="true" aria-describedby="themeStyle-error" value="<?php echo e($defaults['theme_style']); ?>">
                        <span id="themeStyle-error" class="error-message"></span>
                        <?php $__errorArgs = ['themeStyle'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="error-message"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <div class="responsive-grid grid-2-cols">
                    <div class="field">
                        <label for="invitationSize">Size</label>
                        <input type="text" id="invitationSize" name="invitation_size" list="invitation-sizes" placeholder="e.g. 5x7, 4x6, A5" aria-describedby="invitationSize-error" value="<?php echo e($defaults['size']); ?>">
                        <datalist id="invitation-sizes">
                            <option value="5x7">5x7</option>
                            <option value="4x6">4x6</option>
                            <option value="A5">A5</option>
                            <option value="A6">A6</option>
                            <option value="6x8">6x8</option>
                            <option value="Square">Square</option>
                        </datalist>
                        <span id="invitationSize-error" class="error-message"></span>
                        <?php $__errorArgs = ['invitation_size'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="error-message"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="field">
                        <!-- reserved for future -->
                    </div>
                </div>

                <div class="responsive-grid grid-2-cols">
                    <div class="field">
                        <label for="basePrice">Base Price</label>
                        <input type="number" step="0.01" id="basePrice" name="base_price" placeholder="Base Price (e.g. 100.00)" aria-describedby="basePrice-error" value="<?php echo e($defaults['base_price']); ?>">
                        <span id="basePrice-error" class="error-message"></span>
                    </div>
                    <div class="field">
                        <label for="leadTime">Lead Time</label>
                        <input type="text" id="leadTime" name="lead_time" placeholder="Lead Time (e.g. 5-7 days)" aria-describedby="leadTime-error" value="<?php echo e($defaults['lead_time']); ?>">
                        <span id="leadTime-error" class="error-message"></span>
                        <?php $__errorArgs = ['lead_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="error-message"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
                <div class="responsive-grid grid-2-cols">
                    <div class="field">
                        <label for="dateAvailable">Date Available</label>
                        <input type="date" id="dateAvailable" name="date_available" aria-describedby="dateAvailable-error" value="<?php echo e($defaults['date_available']); ?>">
                        <span id="dateAvailable-error" class="error-message"></span>
                        <?php $__errorArgs = ['date_available'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="error-message"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </div>
            
            
            <div class="form-section">
                <h2>Description</h2>
                <div class="editor-toolbar">
                    <button type="button" class="editor-btn" data-command="bold" aria-label="Bold text"><i class="fas fa-bold"></i></button>
                    <button type="button" class="editor-btn" data-command="italic" aria-label="Italic text"><i class="fas fa-italic"></i></button>
                    <button type="button" class="editor-btn" data-command="underline" aria-label="Underline text"><i class="fas fa-underline"></i></button>
                    <button type="button" class="editor-btn" data-command="redo" aria-label="Redo"><i class="fas fa-redo"></i></button>
                </div>
                <textarea id="description" name="description" style="display:none;" aria-describedby="description-error"><?php echo e($defaults['description']); ?></textarea>
                <div contenteditable="true" class="editor-content" id="description-editor" aria-label="Description editor" aria-describedby="description-error"><?php echo $defaults['description']; ?></div>
                <span id="description-error" class="error-message"></span>
            </div>

            
            <div class="form-section">
                <h2>Paper Stocks</h2>
                <div class="paper-stock-rows">
                    <?php $__currentLoopData = $paperStockRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $stock): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="paper-stock-row" data-row-index="<?php echo e($i); ?>">
                        <?php
                            $matchedMaterial = null;
                            if (!empty($stock['material_id'])) {
                                $matchedMaterial = $materials->firstWhere('material_id', $stock['material_id']);
                            } elseif (!empty($stock['name'])) {
                                $matchedMaterial = $materials->firstWhere('material_name', $stock['name']);
                            }
                            $paperMaterialId = $stock['material_id'] ?? optional($matchedMaterial)->material_id;
                        ?>
                        <input type="hidden" name="paper_stocks[<?php echo e($i); ?>][id]" value="<?php echo e($stock['id'] ?? ''); ?>">
                        <input type="hidden" name="paper_stocks[<?php echo e($i); ?>][material_id]" class="paper-stock-material-id" value="<?php echo e($paperMaterialId ?? ''); ?>">
                        <input type="hidden" name="paper_stocks[<?php echo e($i); ?>][name]" class="paper-stock-name-hidden" value="<?php echo e($stock['name'] ?? ''); ?>">
                        <div class="input-row">
                            <div class="field paper-stock-material-field">
                                <label for="paper_stocks_<?php echo e($i); ?>_material_select">Material</label>
                                <select class="paper-stock-name-select" data-placeholder="Select paper material..." name="paper_stocks[<?php echo e($i); ?>][material_select]" aria-label="Select paper material">
                                    <option value="">Select paper material...</option>
                                    <?php $__currentLoopData = $materials->where('material_type', 'paper'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $materialOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            $optionLabel = $materialOption->material_name . (!empty($materialOption->material_type) ? ' (' . $materialOption->material_type . ')' : '');
                                            $isSelected = !empty($stock['material_id']) && $stock['material_id'] == $materialOption->material_id;
                                        ?>
                                        <option value="<?php echo e($materialOption->material_id); ?>"
                                                data-material-id="<?php echo e($materialOption->material_id); ?>"
                                                data-name="<?php echo e($materialOption->material_name); ?>"
                                                data-unit-cost="<?php echo e($materialOption->unit_cost ?? ''); ?>"
                                                <?php echo e($isSelected ? 'selected' : ''); ?>>
                                            <?php echo e($optionLabel); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            <div class="field">
                                <label for="paper_stocks_<?php echo e($i); ?>_price">Price</label>
                                <input type="number" step="0.01" id="paper_stocks_<?php echo e($i); ?>_price" name="paper_stocks[<?php echo e($i); ?>][price]" value="<?php echo e($stock['price'] ?? ''); ?>" placeholder="Price">
                            </div>
                            <div class="field">
                                <label for="paper_stocks_<?php echo e($i); ?>_image_path">Image</label>
                                <input type="file" id="paper_stocks_<?php echo e($i); ?>_image_path" name="paper_stocks[<?php echo e($i); ?>][image_path]" accept="image/*">
                                <?php if(!empty($stock['image_url'])): ?>
                                    <small class="existing-file">Current: <a href="<?php echo e($stock['image_url']); ?>" target="_blank" rel="noopener">View</a></small>
                                <?php endif; ?>
                            </div>
                            <button class="add-row" type="button" aria-label="Add another paper stock row">+</button>
                            <button class="remove-row" type="button" aria-label="Remove this paper stock row">−</button>
                        </div>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>

            

            

            <div class="form-buttons">
                <button type="button" class="continue-btn">Continue</button>
            </div>
        </div>

    
    <div class="page page3" data-page="2">

            <div class="error-summary" style="display: none;" role="alert" aria-live="polite">
                <h3>Please correct the following errors:</h3>
                <ul id="error-list-page3"></ul>
            </div>
            <div class="form-section">
                <h2>Average Usage</h2>
                <div class="input-row">
                    <div class="field">
                        <label for="average_usage_ml">Average usage (ml)</label>
                        <input type="number" step="0.01" id="average_usage_ml" name="average_usage_ml" value="<?php echo e($averageUsageMl); ?>" placeholder="e.g. 12.5">
                    </div>
                </div>
            </div>

            
            <div class="form-section">
                <h2>Preview Images</h2>
                <div class="preview-grid">

                    <div class="preview-card">
                        <h3>Front</h3>
                        <div id="preview-front-img" class="preview-placeholder">No front image available</div>
                    </div>

                    <div class="preview-card">
                        <h3>Back</h3>
                        <div id="preview-back-img" class="preview-placeholder">No back image available</div>
                    </div>

                </div>
                <div class="preview-size-display" style="text-align:center;margin-top:10px;font-weight:600;">
                    Selected size: <span id="preview-size"><?php echo e($defaults['size'] ? $defaults['size'] : '—'); ?></span>
                </div>
            </div>

            <div class="form-buttons">
                <button type="submit" class="btn-save" id="submit-btn">
                    <span class="btn-text">Create Invitation</span>
                    <span class="loading-spinner" style="display: none;"><i class="fas fa-spinner fa-spin"></i> Saving...</span>
                </button>
            </div>
        </div>
    </div>
</form>


<?php
    $placeholderImage = \App\Support\ImageResolver::url(null);
    $resolveAsset = function ($candidates) use ($placeholderImage) {
        foreach ((array) $candidates as $candidate) {
            if (!$candidate) {
                continue;
            }

            if (is_string($candidate) && \Illuminate\Support\Str::startsWith(trim($candidate), 'data:image')) {
                return $candidate;
            }

            $url = \App\Support\ImageResolver::url($candidate);
            if ($url && $url !== $placeholderImage) {
                return $url;
            }
        }

        return null;
    };

    $templatePayload = collect($templates ?? [])->map(function ($template) use ($resolveAsset) {
        $data = is_array($template) ? $template : $template->toArray();
        $design = $data['design'] ?? [];
        $metadata = $data['metadata'] ?? [];
        $pages = is_array(data_get($design, 'pages')) ? data_get($design, 'pages', []) : [];

        $collectPageSources = function ($page) {
            return [
                data_get($page, 'preview'),
                data_get($page, 'preview_url'),
                data_get($page, 'previewUrl'),
                data_get($page, 'thumbnail'),
                data_get($page, 'thumbnail_url'),
                data_get($page, 'thumbnailUrl'),
                data_get($page, 'image'),
                data_get($page, 'image_url'),
                data_get($page, 'imageUrl'),
            ];
        };

        $frontCandidates = [
            $data['front_image'] ?? null,
            $data['preview_front'] ?? null,
            data_get($metadata, 'front_image'),
            data_get($metadata, 'preview_front'),
            data_get($design, 'front_image'),
            data_get($design, 'preview_front'),
            data_get($design, 'front.preview'),
            $data['preview'] ?? null,
            $data['image'] ?? null,
        ];

        $backCandidates = [
            $data['back_image'] ?? null,
            $data['preview_back'] ?? null,
            data_get($metadata, 'back_image'),
            data_get($metadata, 'preview_back'),
            data_get($design, 'back_image'),
            data_get($design, 'preview_back'),
            data_get($design, 'back.preview'),
        ];

        foreach ($pages as $index => $page) {
            $side = strtolower((string) data_get($page, 'side', ''));
            $sources = $collectPageSources($page);

            if ($side === 'front' || ($side === '' && $index === 0)) {
                $frontCandidates = array_merge($frontCandidates, $sources);
            }

            if ($side === 'back' || ($side === '' && $index === 1)) {
                $backCandidates = array_merge($backCandidates, $sources);
            }
        }

        $frontUrl = $resolveAsset($frontCandidates);
        $backUrl = $resolveAsset($backCandidates);
        $previewUrl = $resolveAsset([
            $data['preview'] ?? null,
            $data['preview_image'] ?? null,
            $frontUrl,
        ]);

        $data['preview'] = $data['preview'] ?? $data['preview_image'] ?? null;
        $data['preview_image'] = $data['preview'];
        $data['preview_url'] = $previewUrl;
        $data['image_url'] = $resolveAsset([
            $data['image'] ?? null,
            $data['preview'] ?? null,
            $frontUrl,
        ]);
        $data['front_url'] = $frontUrl;
        $data['back_url'] = $backUrl;

        return $data;
    })->values();
?>

<script>
    window.templatesData = <?php echo json_encode($templatePayload, 15, 512) ?>;
    window.materialsData = <?php echo json_encode($materials, 15, 512) ?>;
    window.assetUrl = '<?php echo e(asset("")); ?>';

    function updatePreviewImages() {
        var templateId = document.getElementById('template_id').value;
        if (!templateId || !window.templatesData) return;
        var template = window.templatesData.find(function(t) { return t.id == templateId; });
        if (!template) return;

        var frontContainer = document.getElementById('preview-front-img');
        var frontSource = template.front_url || template.preview_url || '';
        if (frontSource) {
            if (frontContainer.tagName !== 'IMG') {
                var img = document.createElement('img');
                img.id = 'preview-front-img';
                img.src = frontSource;
                img.alt = 'Front preview';
                frontContainer.parentNode.replaceChild(img, frontContainer);
            } else {
                frontContainer.src = frontSource;
                frontContainer.alt = 'Front preview';
            }
        } else {
            if (frontContainer.tagName === 'IMG') {
                var div = document.createElement('div');
                div.id = 'preview-front-img';
                div.className = 'preview-placeholder';
                div.textContent = 'No front image available';
                frontContainer.parentNode.replaceChild(div, frontContainer);
            }
        }

        var backContainer = document.getElementById('preview-back-img');
        var backSource = template.back_url || template.preview_url || '';
        if (backSource) {
            if (backContainer.tagName !== 'IMG') {
                var img = document.createElement('img');
                img.id = 'preview-back-img';
                img.src = backSource;
                img.alt = 'Back preview';
                backContainer.parentNode.replaceChild(img, backContainer);
            } else {
                backContainer.src = backSource;
                backContainer.alt = 'Back preview';
            }
        } else {
            if (backContainer.tagName === 'IMG') {
                var div = document.createElement('div');
                div.id = 'preview-back-img';
                div.className = 'preview-placeholder';
                div.textContent = 'No back image available';
                backContainer.parentNode.replaceChild(div, backContainer);
            }
        }
        // Ensure size display updates to reflect current input or template
        if (typeof updateSizeDisplay === 'function') {
            updateSizeDisplay();
        }
    }

    // Update the preview size label and adjust preview thumbnail classes
    function updateSizeDisplay() {
        var sizeInput = document.getElementById('invitationSize');
        var sizeText = sizeInput && sizeInput.value ? String(sizeInput.value).trim() : '';
        var displayEl = document.getElementById('preview-size');
        if (displayEl) {
            displayEl.textContent = sizeText || '—';
        }

        // Apply simple ratio classes to preview images for visual cue
        var ratios = ['ratio-5x7','ratio-4x6','ratio-a5','ratio-square'];
        ['preview-front-img','preview-back-img'].forEach(function(id){
            var el = document.getElementById(id);
            if (!el) return;
            ratios.forEach(function(r){ el.classList.remove(r); });
            if (!sizeText) return;
            var s = sizeText.toLowerCase();
            if (s.indexOf('5x7') !== -1) el.classList.add('ratio-5x7');
            else if (s.indexOf('4x6') !== -1 || s.indexOf('6x4') !== -1) el.classList.add('ratio-4x6');
            else if (s.indexOf('a5') !== -1) el.classList.add('ratio-a5');
            else if (s.indexOf('square') !== -1 || /\d+x\d+/.test(s) && (s.split('x')[0] === s.split('x')[1])) el.classList.add('ratio-square');
        });
    }

    (function(){
        function parseLeadTimeToDays(str) {
            if (!str) return null;
            str = String(str).toLowerCase();
            var nums = str.match(/\d+/g);
            if (!nums) return null;
            var n = parseInt(nums[0], 10);
            if (isNaN(n)) return null;
            if (/week/.test(str)) return n * 7;
            if (/month/.test(str)) return n * 30;
            return n;
        }

        function formatDateToYMD(date) {
            var y = date.getFullYear();
            var m = ('0' + (date.getMonth() + 1)).slice(-2);
            var d = ('0' + date.getDate()).slice(-2);
            return y + '-' + m + '-' + d;
        }

        function updateDateAvailableFromLead() {
            var leadEl = document.getElementById('leadTime');
            var dateEl = document.getElementById('dateAvailable');
            if (!leadEl || !dateEl) return;
            var leadVal = leadEl.value;
            var days = parseLeadTimeToDays(leadVal);
            if (days === null || isNaN(days)) return;
            var today = new Date();
            today.setHours(0,0,0,0);
            var target = new Date(today);
            target.setDate(target.getDate() + days);
            dateEl.value = formatDateToYMD(target);
        }

        document.addEventListener('DOMContentLoaded', function(){
            var leadEl = document.getElementById('leadTime');
            if (leadEl) {
                leadEl.addEventListener('input', updateDateAvailableFromLead);
                leadEl.addEventListener('change', updateDateAvailableFromLead);
            }
            var dateEl = document.getElementById('dateAvailable');
            if (leadEl && leadEl.value && dateEl && !dateEl.value) {
                updateDateAvailableFromLead();
            }

            // Size input: update preview label live
            var sizeEl = document.getElementById('invitationSize');
            if (sizeEl) {
                sizeEl.addEventListener('input', updateSizeDisplay);
                sizeEl.addEventListener('change', updateSizeDisplay);
                // initialize display
                updateSizeDisplay();
            }

            // Sync description editor to textarea on form submit
            var form = document.getElementById('invitation-form');
            if (form) {
                form.addEventListener('submit', function() {
                    var editor = document.getElementById('description-editor');
                    var textarea = document.getElementById('description');
                    if (editor && textarea) {
                        textarea.value = editor.innerHTML;
                    }
                });
            }
        });
    })();
</script>
<style>
    /* Simple preview ratio cues */
    #preview-front-img.ratio-5x7, #preview-back-img.ratio-5x7 { max-width: 140px; height: auto; }
    #preview-front-img.ratio-4x6, #preview-back-img.ratio-4x6 { max-width: 120px; height: auto; }
    #preview-front-img.ratio-a5, #preview-back-img.ratio-a5 { max-width: 150px; height: auto; }
    #preview-front-img.ratio-square, #preview-back-img.ratio-square { max-width: 130px; height: auto; }
    .preview-size-display { color: #1f2937; }
    /* Remove native dropdown/arrow for the size input across browsers */
    #invitationSize {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background-image: none;
    }
    /* Edge/IE clear & expand */
    #invitationSize::-ms-clear,
    #invitationSize::-ms-expand {
        display: none;
    }
    /* WebKit specific */
    #invitationSize::-webkit-search-decoration,
    #invitationSize::-webkit-search-cancel-button,
    #invitationSize::-webkit-search-results-button,
    #invitationSize::-webkit-search-results-decoration,
    #invitationSize::-webkit-credentials-auto-fill-button,
    #invitationSize::-webkit-calendar-picker-indicator {
        display: none !important;
        -webkit-appearance: none;
    }
    /* Hide number input spinners within the invitation form (scoped) */
    #invitation-form input[type="number"]::-webkit-outer-spin-button,
    #invitation-form input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    #invitation-form input[type="number"] {
        -moz-appearance: textfield;
    }
</style>

<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>


<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\leanne\xampp\htdocs\InkWise-Web\ink-wise\resources\views/admin/products/create-invitation.blade.php ENDPATH**/ ?>