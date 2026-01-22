<?php
    $isPublished = !is_null($product->published_at) || ($product->relationLoaded('uploads') ? $product->uploads->isNotEmpty() : $product->uploads()->exists());
    $catalogMap = [
        'invitation' => url('/templates/wedding/invitations'),
        'giveaway' => url('/templates/giveaways'),
        'envelope' => url('/templates/envelopes'),
    ];
    $catalogKey = strtolower($product->product_type ?? optional($product->template)->product_type ?? '');
    $customerCatalogUrl = $catalogMap[$catalogKey] ?? url('/templates/wedding/invitations');
?>

<div 
    class="product-card"
    role="listitem"
    tabindex="0"
    data-view-url="<?php echo e(route('admin.products.view', $product->id)); ?>"
    data-id="<?php echo e($product->id); ?>"
    data-name="<?php echo e(e($product->name)); ?>"
    data-description="<?php echo e(e(strip_tags($product->description ?? ''))); ?>"
    data-event-type="<?php echo e(e($product->event_type ?? optional($product->template)->event_type ?? '-')); ?>"
    data-product-type="<?php echo e(e($product->product_type ?? optional($product->template)->product_type ?? '-')); ?>"
    data-theme-style="<?php echo e(e($product->theme_style ?? optional($product->template)->theme_style ?? '-')); ?>"
    data-unit-price="<?php echo e($product->base_price ?? $product->unit_price ?? ''); ?>"
    data-image="<?php echo e(\App\Support\ImageResolver::url([
        $product->image,
        optional($product->images)->front ?? optional($product->images)->preview,
        optional($product->template)->front_image,
        optional($product->template)->preview_front,
        optional($product->template)->preview,
    ])); ?>"
    data-published="<?php echo e($isPublished ? '1' : '0'); ?>"
    data-unupload-url="<?php echo e(route('admin.products.unupload', $product->id)); ?>"
    data-customer-url="<?php echo e($customerCatalogUrl); ?>"
>
    <?php
        // front image fallback: envelope image -> product images -> product.image -> template front
        $imgRec = $product->images ?? $product->product_images ?? null;
        $frontThumb = \App\Support\ImageResolver::url([
            optional($product->envelope)->envelope_image,
            $imgRec ? ($imgRec->front ?? $imgRec->preview ?? null) : null,
            $product->image,
            optional($product->template)->front_image,
            optional($product->template)->preview_front,
            optional($product->template)->preview,
            optional($product->template)->image,
        ]);

        $evType = $product->event_type ?? optional($product->template)->event_type ?? '—';
        $prodType = $product->product_type ?? optional($product->template)->product_type ?? '—';
        $theme = $product->theme_style ?? optional($product->template)->theme_style ?? '—';
        $basePrice = null;

        // Handle pricing based on product type
        if ($prodType === 'Envelope' && $product->envelope) {
            $basePrice = $product->envelope->price_per_unit;
        } else {
            $basePrice = $product->base_price ?? $product->unit_price ?? optional($product->template)->base_price ?? optional($product->template)->unit_price ?? null;
        }
    ?>

    <div class="product-card-media">
        <div class="product-select">
            <input type="checkbox" class="product-checkbox" value="<?php echo e($product->id); ?>" id="product-<?php echo e($product->id); ?>" aria-label="Select <?php echo e($product->name); ?> for bulk actions">
            <label for="product-<?php echo e($product->id); ?>" class="checkbox-label"></label>
        </div>
        <img src="<?php echo e($frontThumb); ?>" alt="<?php echo e($product->name); ?>" class="product-card-thumb" loading="lazy">
    </div>

    <div class="product-card-body">
        <h3 class="product-card-title">
            <?php if($product->product_type === 'Giveaway'): ?>
                <?php echo e($product->name); ?>

            <?php else: ?>
                <?php echo e($product->name); ?>

            <?php endif; ?>
        </h3>
        <?php if($product->description): ?>
            <p class="product-card-desc"><?php echo e(Str::limit(strip_tags($product->description), 80)); ?></p>
        <?php endif; ?>

        <div class="product-card-meta">
            <span class="meta-item"><?php echo e($evType); ?></span>
            <span class="meta-sep">-</span>
            <span class="meta-item"><?php echo e($prodType); ?></span>
            <span class="meta-sep">-</span>
            <?php if(in_array($prodType, ['Envelope', 'Giveaway'])): ?>
                <?php
                    $materialName = '—';
                    if ($prodType === 'Envelope' && $product->envelope && $product->envelope->material) {
                        $materialName = $product->envelope->material->material_name ?? $product->envelope->envelope_material_name ?? '—';
                    } elseif ($product->paperStocks && $product->paperStocks->first() && $product->paperStocks->first()->material) {
                        $materialName = $product->paperStocks->first()->material->name ?? '—';
                    } elseif ($product->materials && $product->materials->first() && $product->materials->first()->material) {
                        $materialName = $product->materials->first()->material->material_name ?? '—';
                    }
                ?>
                <span class="meta-item"><?php echo e($materialName); ?></span>
            <?php else: ?>
                <span class="meta-item"><?php echo e($theme); ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="product-card-footer">
        <div class="price"><?php echo e($basePrice !== null ? '₱' . number_format($basePrice, 2) : '—'); ?></div>
        <div class="card-actions">
            <a href="<?php echo e(route('admin.products.view', $product->id)); ?>" class="btn-view" title="View <?php echo e($product->name); ?>" aria-label="View <?php echo e($product->name); ?>"><i class="fi fi-sr-eye"></i></a>
            <a href="<?php echo e(route('admin.products.edit', $product->id)); ?>" class="btn-update" title="Edit <?php echo e($product->name); ?>" aria-label="Edit <?php echo e($product->name); ?>"><i class="fa-solid fa-pen-to-square"></i></a>
            <?php $isPublished = $product->published_at !== null; ?>
            <button
                type="button"
                class="btn-upload <?php echo e($isPublished ? 'disabled' : ''); ?>"
                data-id="<?php echo e($product->id); ?>"
                data-upload-url="<?php echo e(route('admin.products.upload', $product->id)); ?>"
                data-customer-url="<?php echo e($customerCatalogUrl); ?>"
                title="Publish <?php echo e($product->name); ?>"
                aria-label="Publish <?php echo e($product->name); ?>"
                <?php echo e($isPublished ? 'disabled' : ''); ?>

            >
                <i class="fa-solid fa-upload"></i>
            </button>
            <button
                type="button"
                class="btn-unupload <?php echo e(!$isPublished ? 'disabled' : ''); ?>"
                data-id="<?php echo e($product->id); ?>"
                data-unupload-url="<?php echo e(route('admin.products.unupload', $product->id)); ?>"
                title="Unpublish <?php echo e($product->name); ?>"
                aria-label="Unpublish <?php echo e($product->name); ?>"
                <?php echo e(!$isPublished ? 'disabled' : ''); ?>

            >
                <i class="fa-solid fa-rotate-left"></i>
            </button>
            <form method="POST" action="<?php echo e(route('admin.products.destroy', $product->id)); ?>" style="display:inline;" class="ajax-delete-form" data-id="<?php echo e($product->id); ?>">
                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                <button type="submit" class="btn-delete ajax-delete" data-id="<?php echo e($product->id); ?>" data-name="<?php echo e($product->name); ?>" title="Delete <?php echo e($product->name); ?>" aria-label="Delete <?php echo e($product->name); ?>">
                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                    <span class="sr-only">Delete <?php echo e($product->name); ?></span>
                </button>
            </form>
        </div>
    </div>

</div>
<?php /**PATH C:\Users\leanne\xampp\htdocs\InkWise-Web\ink-wise\resources\views/admin/products/partials/card.blade.php ENDPATH**/ ?>