<?php $__env->startSection('title', 'View Product: ' . ($product->product_type === 'Giveaway' && $product->materials && $product->materials->first() && $product->materials->first()->material ? $product->materials->first()->material->material_name : $product->name)); ?>

<?php $__env->startPush('styles'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('css/admin-css/product-view.css')); ?>">
    <style>
        .rating-summary {
            margin-bottom: 1rem;
        }
        .stars-display {
            display: flex;
            gap: 2px;
            margin: 0.5rem 0;
        }
        .stars-display .star {
            font-size: 1.2rem;
            color: #ddd;
        }
        .stars-display .star.filled {
            color: #f59e0b;
        }
        .ratings-list {
            list-style: none;
            padding: 0;
        }
        .rating-item {
            border-bottom: 1px solid #eee;
            padding: 1rem 0;
        }
        .rating-item:last-child {
            border-bottom: none;
        }
        .rating-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
            gap: 1rem;
        }
        .rating-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            flex: 1;
        }
        .rating-customer {
            font-weight: 600;
            color: #111827;
            font-size: 0.9rem;
        }
        .rating-date {
            font-size: 0.9rem;
            color: #666;
        }
        .rating-review {
            margin: 0.5rem 0;
            font-style: italic;
        }
        .rating-photos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 8px;
            margin-top: 0.75rem;
            padding: 0.5rem;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .rating-photo {
            width: 100%;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            border: 2px solid #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
        }
        .rating-photo:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<main class="product-view-wrapper" role="main">
    <header class="product-view-header">
        <div class="product-view-header__text">
            <a href="<?php echo e(route('admin.products.index')); ?>" class="back-link" aria-label="Back to product list">
                <i class="fi fi-rr-angle-left"></i>
                Back to Products
            </a>
            <h1>
                <?php if($product->product_type === 'Giveaway' && $product->materials && $product->materials->first() && $product->materials->first()->material): ?>
                    <?php echo e($product->materials->first()->material->material_name); ?>

                <?php else: ?>
                    <?php echo e($product->name); ?>

                <?php endif; ?>
            </h1>
            <?php $taglineParts = collect([$product->theme_style, $product->event_type])->filter(); ?>
            <?php if($taglineParts->isNotEmpty()): ?>
                <p class="product-tagline"><?php echo e($taglineParts->implode(' • ')); ?></p>
            <?php endif; ?>
        </div>
        <div class="product-view-actions">
            <a href="<?php echo e(route('admin.products.edit', $product->id)); ?>" class="btn-action btn-edit">
                <i class="fa-solid fa-pen-to-square"></i>
                Edit Product
            </a>
        </div>
    </header>

    <?php
        $basePriceSummaryValue = '—';
        $basePrice = $product->base_price
            ?? $product->unit_price
            ?? ($product->product_type === 'Envelope' ? optional($product->envelope)->price_per_unit : null);
        if (!is_null($basePrice)) {
            $basePriceSummaryValue = '₱' . number_format($basePrice, 2);
        }

        $dateAvailableRaw = $product->getAttribute('date_available')
            ?? $product->getAttribute('date_Available')
            ?? $product->created_at;
        $dateAvailableDisplay = '—';
        if (!empty($dateAvailableRaw)) {
            try {
                $dateAvailableDisplay = \Illuminate\Support\Carbon::parse($dateAvailableRaw)->format('M d, Y');
            } catch (\Throwable $e) {
                $dateAvailableDisplay = is_string($dateAvailableRaw) ? $dateAvailableRaw : '—';
            }
        }

    ?>

    <section class="product-summary" aria-label="Key product metrics">
        <article class="summary-card">
            <span class="summary-label">Product Type</span>
            <span class="summary-value"><?php echo e($product->product_type ?? '—'); ?></span>
        </article>
        <article class="summary-card">
            <span class="summary-label">Base Price</span>
            <span class="summary-value"><?php echo e($basePriceSummaryValue); ?></span>
        </article>
    </section>

    <div class="product-content-grid">
        <aside class="product-side-panel">
            <div class="product-card media-card">
                <div class="media-card__image">
                    <?php
                        $displayImages = [];
                        $imgRecord = $product->images ?? $product->product_images ?? null;

                        // First priority: Product images (front/back/preview)
                        if ($imgRecord) {
                            if (!empty($imgRecord->front)) {
                                $displayImages['front'] = [
                                    'url' => \App\Support\ImageResolver::url($imgRecord->front),
                                    'alt' => $product->name . ' front preview'
                                ];
                            }
                            if (!empty($imgRecord->back)) {
                                $displayImages['back'] = [
                                    'url' => \App\Support\ImageResolver::url($imgRecord->back),
                                    'alt' => $product->name . ' back preview'
                                ];
                            }
                            if (!empty($imgRecord->preview) && empty($displayImages)) {
                                $displayImages['preview'] = [
                                    'url' => \App\Support\ImageResolver::url($imgRecord->preview),
                                    'alt' => $product->name . ' preview'
                                ];
                            }
                        }

                        // Second priority: Template images (for envelope products)
                        $templateRef = $product->template ?? null;
                        if (empty($displayImages) && $templateRef) {
                            $tFront = $templateRef->front_image ?? $templateRef->preview_front ?? null;
                            $tBack = $templateRef->back_image ?? $templateRef->preview_back ?? null;
                            $tPreview = $templateRef->preview ?? $templateRef->image ?? null;

                            if ($tFront) {
                                $displayImages['front'] = [
                                    'url' => \App\Support\ImageResolver::url($tFront),
                                    'alt' => $product->name . ' template front'
                                ];
                            }
                            if ($tBack) {
                                $displayImages['back'] = [
                                    'url' => \App\Support\ImageResolver::url($tBack),
                                    'alt' => $product->name . ' template back'
                                ];
                            }
                            if (empty($displayImages) && $tPreview) {
                                $displayImages['preview'] = [
                                    'url' => \App\Support\ImageResolver::url($tPreview),
                                    'alt' => $product->name . ' template preview'
                                ];
                            }
                        }

                        // Fallback: Main product image
                        if (empty($displayImages) && !empty($product->image)) {
                            $displayImages['main'] = [
                                'url' => \App\Support\ImageResolver::url($product->image),
                                'alt' => $product->name . ' preview'
                            ];
                        }

                        $imageCount = count($displayImages);
                    ?>

                    <?php if($imageCount > 0): ?>
                        <div style="display: grid; grid-template-columns: repeat(<?php echo e($imageCount); ?>, 1fr); gap: 8px; align-items: center;">
                            <?php $__currentLoopData = $displayImages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <img src="<?php echo e($image['url']); ?>" alt="<?php echo e($image['alt']); ?>" style="width: 100%; max-height: 140px; height: auto; object-fit: contain; <?php echo e($imageCount === 1 ? 'max-width: 100%;' : 'max-width: 48%;'); ?>">
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php else: ?>
                        <div style="width: 100%; height: 140px; background: #f3f4f6; border: 2px dashed #d1d5db; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #6b7280;">
                            <span>No images available</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="media-card__meta">
                    <span class="meta-pill">Stock: <?php echo e($product->stock_availability ?? '—'); ?></span>
                    <span class="meta-pill">Lead Time: <?php echo e($product->lead_time ?? '—'); ?></span>
                </div>
            </div>

            <?php if($product->template): ?>
                <div class="product-card template-card">
                    <div class="card-heading">
                        <h2>Template Reference</h2>
                        <a href="<?php echo e(route('admin.templates.editor', $product->template->id)); ?>" class="text-link" aria-label="Open template editor">
                            Open editor
                            <i class="fi fi-rr-arrow-right"></i>
                        </a>
                    </div>
                    <p class="template-name"><?php echo e($product->template->name); ?></p>
                    <ul class="meta-list">
                        <li><span>Event Type</span><strong><?php echo e($product->template->event_type ?? '—'); ?></strong></li>
                        <li><span>Updated</span><strong><?php echo e(optional($product->template->updated_at)->format('M d, Y') ?? '—'); ?></strong></li>
                    </ul>
                </div>
            <?php endif; ?>

            
            <div class="product-card">
                <h2>Ratings</h2>
                <?php
                    $ratings = $product->ratings ?? collect();
                    $averageRating = $ratings->avg('rating');
                ?>
                <?php if($ratings->isNotEmpty()): ?>
                    <div class="rating-summary">
                        <p><strong>Average Rating:</strong> <?php echo e(number_format($averageRating, 1)); ?> / 5 (<?php echo e($ratings->count()); ?> review<?php echo e($ratings->count() > 1 ? 's' : ''); ?>)</p>
                        <div class="stars-display">
                            <?php $__currentLoopData = range(1, 5); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <span class="star <?php echo e($i <= round($averageRating) ? 'filled' : ''); ?>">&#9733;</span>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                    <ul class="ratings-list">
                        <?php $__currentLoopData = $ratings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rating): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li class="rating-item">
                                <div class="rating-header">
                                    <div class="rating-info">
                                        <strong class="rating-customer"><?php echo e($rating->customer->name ?? 'Customer'); ?></strong>
                                        <div class="stars-display">
                                            <?php $__currentLoopData = range(1, 5); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <span class="star <?php echo e($i <= $rating->rating ? 'filled' : ''); ?>">&#9733;</span>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    </div>
                                    <span class="rating-date"><?php echo e(optional($rating->submitted_at)->format('M d, Y')); ?></span>
                                </div>
                                <?php if($rating->review): ?>
                                    <p class="rating-review"><?php echo e($rating->review); ?></p>
                                <?php endif; ?>
                                <?php if($rating->photos && count($rating->photos)): ?>
                                    <div class="rating-photos">
                                        <?php $__currentLoopData = $rating->photos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $photo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php
                                                $photoUrl = \Illuminate\Support\Str::startsWith($photo, ['http://', 'https://'])
                                                    ? $photo
                                                    : \Illuminate\Support\Facades\Storage::disk('public')->url($photo);
                                            ?>
                                            <img src="<?php echo e($photoUrl); ?>" alt="Rating photo" class="rating-photo" onclick="window.open('<?php echo e($photoUrl); ?>', '_blank')">
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                <?php else: ?>
                    <p class="muted">No ratings yet.</p>
                <?php endif; ?>
            </div>

            <?php if($product->uploads && $product->uploads->count() && !in_array($product->product_type, ['Envelope', 'Giveaway'])): ?>
                <div class="product-card uploads-card">
                    <div class="card-heading">
                        <h2>Uploaded Assets</h2>
                        <a href="<?php echo e(route('admin.products.edit', $product->id)); ?>#uploads" class="text-link">Manage files</a>
                    </div>
                    <ul class="uploads-list">
                        <?php $__currentLoopData = $product->uploads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $upload): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $fileUrl = \Illuminate\Support\Facades\Storage::disk('public')->url('uploads/products/' . $product->id . '/' . $upload->filename);
                                $sizeKb = $upload->size ? round($upload->size / 1024, 1) : null;
                            ?>
                            <li>
                                <div class="upload-name">
                                    <i class="fi fi-rr-file"></i>
                                    <a href="<?php echo e($fileUrl); ?>" target="_blank" rel="noopener"><?php echo e($upload->original_name); ?></a>
                                </div>
                                <span class="upload-meta"><?php echo e(strtoupper($upload->mime_type ?? 'file')); ?> <?php if($sizeKb): ?> · <?php echo e($sizeKb); ?> KB <?php endif; ?></span>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>
        </aside>

        <section class="product-details">
            <div class="product-card">
                <h2><?php echo e($product->product_type ?? 'Product'); ?> Information</h2>
                <dl class="info-grid">
                    <div><dt><?php echo e($product->product_type ?? 'Product'); ?> Name</dt><dd>
                        <?php if($product->product_type === 'Giveaway' && $product->materials && $product->materials->first() && $product->materials->first()->material): ?>
                            <?php echo e($product->materials->first()->material->material_name); ?>

                        <?php elseif($product->product_type === 'Envelope' && $product->envelope && $product->envelope->material): ?>
                            <?php echo e($product->envelope->material->material_name); ?>

                        <?php else: ?>
                            <?php echo e($product->name); ?>

                        <?php endif; ?>
                    </dd></div>
                    <div><dt>Event Type</dt><dd><?php echo e($product->event_type ?? '—'); ?></dd></div>
                    <div><dt>Product Type</dt><dd><?php echo e($product->product_type ?? '—'); ?></dd></div>
                    <?php if($product->product_type === 'Invitation'): ?>
                    <div><dt>Theme / Style</dt><dd><?php echo e($product->theme_style ?? '—'); ?></dd></div>
                    <?php
                        $sizeDisplay = '—';
                        // Prefer template explicit inch columns
                        if ($product->template) {
                            $t = $product->template;
                            if (!empty($t->width_inch) || !empty($t->height_inch)) {
                                $fmt = function ($v) {
                                    if ($v === null || $v === '') return null;
                                    $f = floatval($v);
                                    if (floor($f) == $f) return (string) intval($f);
                                    return rtrim(rtrim(number_format($f, 2, '.', ''), '0'), '.');
                                };
                                $w = $fmt($t->width_inch);
                                $h = $fmt($t->height_inch);
                                if ($w !== null && $h !== null) {
                                    $sizeDisplay = $w . 'x' . $h . ' in';
                                } elseif ($w !== null) {
                                    $sizeDisplay = $w . 'x in';
                                } elseif ($h !== null) {
                                    $sizeDisplay = 'x' . $h . ' in';
                                }
                            } elseif (!empty($product->size)) {
                                $sizeDisplay = $product->size;
                            } elseif (!empty($product->invitation_size)) {
                                $sizeDisplay = $product->invitation_size;
                            } elseif (!empty($t->size)) {
                                $sizeDisplay = $t->size;
                            }
                        } else {
                            if (!empty($product->size)) $sizeDisplay = $product->size;
                            elseif (!empty($product->invitation_size)) $sizeDisplay = $product->invitation_size;
                        }
                    ?>
                    <div><dt>Size</dt><dd><?php echo e($sizeDisplay); ?></dd></div>
                    <?php endif; ?>
                    <div><dt>Base Price</dt><dd>
                        <?php if($product->base_price !== null): ?>
                            ₱<?php echo e(number_format($product->base_price, 2)); ?>

                        <?php elseif($product->unit_price !== null): ?>
                            ₱<?php echo e(number_format($product->unit_price, 2)); ?>

                        <?php elseif($product->product_type === 'Envelope' && optional($product->envelope)->price_per_unit !== null): ?>
                            ₱<?php echo e(number_format($product->envelope->price_per_unit, 2)); ?>

                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </dd></div>
                    <div><dt>Date Available</dt><dd><?php echo e($dateAvailableDisplay); ?></dd></div>
                </dl>
            </div>

            <div class="product-card">
                <h2>Description</h2>
                <div class="product-description">
                    <?php echo $product->description ? nl2br(e($product->description)) : '<p>No description provided.</p>'; ?>

                </div>
            </div>

            <?php if($product->product_type === 'Invitation'): ?>
            <div class="product-card">
                <h2>Paper Stocks</h2>
                <?php $paperStocks = $product->paper_stocks ?? $product->paperStocks ?? collect(); ?>
                <?php if($paperStocks && $paperStocks->count()): ?>
                    <ul class="meta-list">
                        <?php $__currentLoopData = $paperStocks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ps): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li>
                                <span><?php echo e($ps->name ?? 'Paper Stock'); ?></span>
                                <strong>
                                    <?php echo e(isset($ps->price) ? '₱' . number_format($ps->price, 2) : '—'); ?>

                                    <?php if(!empty($ps->image_path) || !empty($ps->image)): ?>
                                        <img src="<?php echo e(\App\Support\ImageResolver::url($ps->image_path ?? $ps->image)); ?>" alt="<?php echo e($ps->name ?? 'paper'); ?>" style="width:48px; height:48px; object-fit:contain; margin-left:8px; vertical-align:middle; border:1px solid #eee; background:#fff; padding:4px;">
                                    <?php endif; ?>
                                </strong>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                <?php else: ?>
                    <p class="muted">No paper stocks defined.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if($product->product_type === 'Envelope'): ?>
            <div class="product-card">
                <h2>Envelope Material</h2>
                <?php $envelope = $product->envelope; ?>
                <?php if($envelope && ($envelope->material || $envelope->envelope_material_name)): ?>
                    <ul class="meta-list">
                        <li>
                            <span>Material</span>
                            <strong><?php echo e($envelope->material->material_name ?? $envelope->envelope_material_name ?? '—'); ?></strong>
                        </li>
                        <li>
                            <span>Type</span>
                            <strong><?php echo e(strtoupper($envelope->material->material_type ?? $envelope->envelope_material_name ?? 'ENVELOPE')); ?></strong>
                        </li>
                    </ul>
                <?php else: ?>
                    <p class="muted">No envelope material defined.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if($product->product_type === 'Giveaway'): ?>
            <div class="product-card">
                <h2>Giveaway Material</h2>
                <?php $materials = $product->materials ?? collect(); ?>
                <?php if($materials && $materials->count()): ?>
                    <ul class="meta-list">
                        <?php $__currentLoopData = $materials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $matName = $mat->material->material_name ?? $mat->material_name ?? '—';
                                $matType = strtoupper($mat->material->material_type ?? $mat->material_type ?? 'GIVEAWAY');
                            ?>
                            <li>
                                <span><?php echo e($matName); ?></span>
                                <strong><?php echo e($matType); ?></strong>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                <?php else: ?>
                    <p class="muted">No giveaway material defined.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            
            <?php if($product->product_type !== 'Envelope' && $product->product_type !== 'Giveaway'): ?>
            <div class="product-card">
                <h2>Size</h2>
                <?php $addons = $product->addons ?? $product->product_addons ?? $product->addOns ?? collect(); ?>
                <?php if($addons && $addons->count()): ?>
                    <ul class="meta-list">
                        <?php $__currentLoopData = $addons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ad): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <?php if(!empty($ad->image_path) || !empty($ad->image)): ?>
                                            <img src="<?php echo e(\App\Support\ImageResolver::url($ad->image_path ?? $ad->image)); ?>" alt="<?php echo e($ad->name ?? 'addon'); ?>" style="width:48px; height:48px; object-fit:contain; border:1px solid #eee; background:#fff; padding:4px;">
                                        <?php endif; ?>
                                        <span><?php echo e($ad->name ?? $ad->addon_type ?? 'Addon'); ?></span>
                                    </div>
                                    <strong><?php echo e(isset($ad->price) ? '₱' . number_format($ad->price, 2) : '—'); ?></strong>
                                </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                <?php else: ?>
                    <p class="muted">No size defined.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            
            <div class="product-card">
                <h2>Ink Usage</h2>
                <?php $inkUsage = $product->inkUsage ?? $product->colors ?? collect(); ?>
                <?php if($inkUsage && $inkUsage->count()): ?>
                    <ul class="meta-list">
                        <?php $__currentLoopData = $inkUsage; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $usage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li>
                                <div><strong>Average Usage:</strong> <?php echo e($usage->average_usage_ml ?? '—'); ?> ml</div>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                <?php else: ?>
                    <p class="muted">No ink usage defined.</p>
                <?php endif; ?>
            </div>
    </section>
    </div>
</main>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\InkWise-Web\ink-wise\resources\views/admin/products/view.blade.php ENDPATH**/ ?>