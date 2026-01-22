<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo e($product->name); ?> Preview</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo e(asset('css/customer/preview.css')); ?>">
  <script src="<?php echo e(asset('js/customer/preview.js')); ?>" defer></script>
</head>
<?php
  // Resolve selected size early so body attribute can use it
  $selectedSize = trim((string) (request()->query('size') ?? request('size') ?? ''));
  $formatInch = function ($val) {
    if ($val === null || $val === '') return null;
    if (!is_numeric($val)) return (string) $val;
    $n = (float) $val;
    $s = rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    return $s;
  };

  if (empty($selectedSize)) {
    if (!empty($product->sizes) && is_array($product->sizes) && count($product->sizes)) {
      $selectedSize = trim((string) ($product->sizes[0] ?? ''));
    }
  }

  if (empty($selectedSize) && ($product->template ?? null)) {
    $tRef = $product->template;
    $w = $tRef->width_inch ?? null;
    $h = $tRef->height_inch ?? null;
    if ($w || $h) {
      $fw = $formatInch($w) ?? '';
      $fh = $formatInch($h) ?? '';
      if ($fw !== '' && $fh !== '') {
        $selectedSize = $fw . 'x' . $fh;
      } elseif ($fw !== '') {
        $selectedSize = $fw . 'x';
      } elseif ($fh !== '') {
        $selectedSize = 'x' . $fh;
      }
    }
  }

  if (empty($selectedSize)) {
    $selectedSize = config('invitations.default_size', '5x7');
  }
?>

<body data-product-id="<?php echo e($product->id ?? ''); ?>" data-product-name="<?php echo e($product->name ?? ''); ?>" data-selected-size="<?php echo e($selectedSize); ?>">
<?php
  $uploads = $product->uploads ?? collect();
  $images = $product->product_images ?? $product->images ?? null;
  $templateRef = $product->template ?? null;

  $frontImage = null;
  $backImage = null;

  if ($images) {
    if (!empty($images->front)) {
      $frontImage = \App\Support\ImageResolver::url($images->front);
    }
    if (!empty($images->back)) {
      $backImage = \App\Support\ImageResolver::url($images->back);
    }
    if (!$frontImage && !empty($images->preview)) {
      $frontImage = \App\Support\ImageResolver::url($images->preview);
    }
  }

  if (!$frontImage && $uploads->isNotEmpty()) {
    $primaryUpload = $uploads->first();
    if (str_starts_with($primaryUpload->mime_type ?? '', 'image/')) {
      $frontImage = asset('storage/uploads/products/' . $product->id . '/' . $primaryUpload->filename);
    }
  }

  if (!$backImage && $uploads->count() > 1) {
    $secondaryUpload = $uploads->get(1);
    if ($secondaryUpload && str_starts_with($secondaryUpload->mime_type ?? '', 'image/')) {
      $backImage = asset('storage/uploads/products/' . $product->id . '/' . $secondaryUpload->filename);
    }
  }

  if ($templateRef) {
    if (!$frontImage) {
      $tFront = $templateRef->preview_front ?? $templateRef->front_image ?? $templateRef->preview ?? $templateRef->image ?? null;
      if ($tFront) {
        $frontImage = preg_match('/^(https?:)?\/\//i', $tFront) || str_starts_with($tFront, '/')
          ? $tFront
          : \Illuminate\Support\Facades\Storage::url($tFront);
      }
    }
    if (!$backImage) {
      $tBack = $templateRef->preview_back ?? $templateRef->back_image ?? null;
      if ($tBack) {
        $backImage = preg_match('/^(https?:)?\/\//i', $tBack) || str_starts_with($tBack, '/')
          ? $tBack
          : \Illuminate\Support\Facades\Storage::url($tBack);
      }
    }
  }

  // Determine if the template has a back design
  $hasBackDesign = !empty($backImage) || ($templateRef && ($templateRef->has_back_design ?? false));
  
  // If template has back design but no back image, try to get it from svg_path
  if ($hasBackDesign && !$backImage && $templateRef) {
    $backSvgPath = $templateRef->back_svg_path ?? null;
    if ($backSvgPath) {
      $backImage = preg_match('/^(https?:)?\/\//i', $backSvgPath) || str_starts_with($backSvgPath, '/')
        ? $backSvgPath
        : \Illuminate\Support\Facades\Storage::url($backSvgPath);
    }
  }

  $defaultImage = $frontImage ?? $backImage ?? asset('images/placeholder.png');
  $priceValue = $product->base_price ?? $product->unit_price ?? optional($templateRef)->base_price ?? optional($templateRef)->unit_price;
  $paperStocksRaw = $product->paper_stocks ?? $product->paperStocks ?? collect();
  $stockAvailability = $product->stock_availability ?? optional($templateRef)->stock_availability;
  if ($product->materials && $product->materials->count()) {
    $allMaterialsAvailable = true;
    foreach ($product->materials as $pm) {
      $material = $pm->material;
      if ($material && ($material->stock ?? 0) < $pm->quantity) {
        $allMaterialsAvailable = false;
        break;
      }
    }
    $stockAvailability = $allMaterialsAvailable ? 'In Stock' : 'Out of Stock';
  }
  $dateAvailable = $product->date_available ?? optional($templateRef)->date_available;
  $formattedAvailability = null;
  if ($dateAvailable) {
    try {
      $formattedAvailability = \Illuminate\Support\Carbon::parse($dateAvailable)->format('F d, Y');
    } catch (\Throwable $e) {
      $formattedAvailability = is_string($dateAvailable) ? $dateAvailable : null;
    }
  }

  $placeholderImage = asset('images/placeholder.png');
  $resolveMedia = function ($path) use ($placeholderImage) {
    if (!$path) {
      return $placeholderImage;
    }
    if (preg_match('/^(https?:)?\/\//i', $path) || str_starts_with($path, '/')) {
      return $path;
    }

    try {
      return \Illuminate\Support\Facades\Storage::url($path);
    } catch (\Throwable $e) {
      return $placeholderImage;
    }
  };

  $paperStocks = collect($paperStocksRaw)->map(function ($stock) use ($resolveMedia) {
    $stock->display_image = $resolveMedia($stock->image_path ?? $stock->image ?? null);
    return $stock;
  })->values();

  $normalizeAddonKey = function ($value) {
    $value = strtolower(trim((string) ($value ?? 'additional')));
    $value = preg_replace('/[^a-z0-9]+/i', '_', $value);
    $value = trim($value, '_');
    return $value === '' ? 'additional' : $value;
  };

  $addonsCollection = collect($product->addons ?? collect())->map(function ($addon) use ($resolveMedia) {
    $addon->display_image = $resolveMedia($addon->image_path ?? null);
    return $addon;
  });

  $addonsByType = $addonsCollection->groupBy(function ($addon) use ($normalizeAddonKey) {
    return $normalizeAddonKey($addon->addon_type ?? 'additional');
  });

  $addonLabels = [
    'trim' => 'Trim Options',
    'embossed_powder' => 'Embossed Powder',
    'orientation' => 'Orientation',
    'size' => 'Size',
    'additional' => 'Size',
  ];

  foreach ($addonsByType->keys() as $key) {
    if (!array_key_exists($key, $addonLabels)) {
      $addonLabels[$key] = ucwords(str_replace('_', ' ', $key));
    }
  }

  $orderedAddonGroups = collect();
  foreach ($addonLabels as $key => $label) {
    if ($addonsByType->has($key)) {
      $items = $addonsByType->get($key)->values();
      if ($items->count()) {
        $orderedAddonGroups->push([
          'key' => $key,
          'label' => $label,
          'items' => $items,
        ]);
      }
    }
  }
?>



  <div class="container">
    <div class="preview">
      <div class="flip-container" id="flipContainer">
        <div class="flipper" id="flipper">
          <div class="front">
            <img src="<?php echo e($frontImage ?? $defaultImage); ?>" alt="Front of <?php echo e($product->name); ?>" onerror="this.src='<?php echo e($defaultImage); ?>'">
          </div>
          <div class="back">
            <img src="<?php echo e($backImage ?? $frontImage ?? $defaultImage); ?>" alt="Back of <?php echo e($product->name); ?>" onerror="this.src='<?php echo e($defaultImage); ?>'">
          </div>
        </div>
      </div>
      
      <?php if($hasBackDesign): ?>
      <div class="toggle-buttons" data-has-back="true">
        <button id="frontBtn" class="active" type="button" aria-label="View front design">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
            <circle cx="8.5" cy="8.5" r="1.5"></circle>
            <polyline points="21 15 16 10 5 21"></polyline>
          </svg>
          <span>Front</span>
        </button>
        <button id="backBtn" type="button" aria-label="View back design">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="9" y1="9" x2="15" y2="15"></line>
            <line x1="15" y1="9" x2="9" y2="15"></line>
          </svg>
          <span>Back</span>
        </button>
      </div>
      <?php else: ?>
      <div class="toggle-buttons" data-has-back="false" style="display: none;">
        <button id="frontBtn" class="active" type="button">Front</button>
        <button id="backBtn" type="button" style="display:none">Back</button>
      </div>
      <?php endif; ?>
    </div>

    <div class="details">
      <div class="details-header">
        <h2><?php echo e($product->name); ?></h2>
        <p class="size-label">Template + Size: <strong id="selectedSizeLabel"><?php echo e($selectedSize); ?></strong></p>
        <p class="price">
          <?php if(!is_null($priceValue)): ?>
            As low as <span class="new-price">₱<?php echo e(number_format($priceValue, 2)); ?></span> per piece
          <?php else: ?>
            <span class="new-price">Pricing available on request</span>
          <?php endif; ?>
        </p>

        <?php if($formattedAvailability || $stockAvailability): ?>
          <p class="delivery">
            <?php if($formattedAvailability): ?>
              Get as soon <b><?php echo e($formattedAvailability); ?></b>
            <?php endif; ?>
            <?php if($stockAvailability): ?>
              <?php if($formattedAvailability): ?><br><?php endif; ?>
              Stock: <b><?php echo e($stockAvailability); ?></b>
            <?php endif; ?>
          </p>
        <?php endif; ?>
      </div>

      <div class="options-scroll">
        <?php if($paperStocks->count()): ?>
          <section class="option-block">
            <h3>Paper Stocks</h3>
            <p class="selection-hint">Choose your preferred stock (optional).</p>
            <div class="feature-grid">
              <?php $__currentLoopData = $paperStocks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stock): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <button
                  type="button"
                  class="feature-card selectable-card"
                  data-option-group="paper_stock"
                  data-option-id="<?php echo e($stock->id ?? ('paper_' . $loop->index)); ?>"
                  data-option-name="<?php echo e(e($stock->name ?? 'Paper Stock')); ?>"
                  data-option-price="<?php echo e(isset($stock->price) ? $stock->price : ''); ?>"
                  data-option-image="<?php echo e(e($stock->display_image)); ?>"
                  aria-pressed="false"
                >
                  <div class="feature-card-media">
                    <img src="<?php echo e($stock->display_image); ?>" alt="<?php echo e($stock->name ?? 'Paper stock image'); ?>">
                  </div>
                  <div class="feature-card-info">
                    <span class="feature-card-title"><?php echo e($stock->name ?? 'Paper Stock'); ?></span>
                    <span class="feature-card-price">
                      <?php if(isset($stock->price)): ?>
                        ₱<?php echo e(number_format($stock->price, 2)); ?>

                      <?php else: ?>
                        On request
                      <?php endif; ?>
                    </span>
                  </div>
                </button>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
          </section>
        <?php endif; ?>

        <?php if($orderedAddonGroups->count()): ?>
          <section class="option-block">
            <h3>Size</h3>
            <?php $__currentLoopData = $orderedAddonGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <div class="addon-group addon-<?php echo e($group['key']); ?>">
                <h4><?php echo e($group['label']); ?></h4>
                <div class="feature-grid">
                  <?php $__currentLoopData = $group['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $addon): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <button
                      type="button"
                      class="feature-card selectable-card"
                      data-option-group="<?php echo e($group['key']); ?>"
                      data-option-id="<?php echo e($addon->id ?? ($group['key'] . '_' . $loop->index)); ?>"
                      data-option-name="<?php echo e(e($addon->name ?? $group['label'])); ?>"
                      data-option-price="<?php echo e(isset($addon->price) ? $addon->price : ''); ?>"
                      data-option-image="<?php echo e(e($addon->display_image)); ?>"
                      aria-pressed="false"
                      title="Select <?php echo e($addon->name ?? $group['label']); ?>"
                    >
                      <div class="feature-card-media">
                        <img src="<?php echo e($addon->display_image); ?>" alt="<?php echo e($addon->name ?? ($group['label'] . ' option')); ?>">
                      </div>
                      <div class="feature-card-info">
                        <span class="feature-card-title"><?php echo e($addon->name ?? $group['label']); ?></span>
                        <span class="feature-card-price">
                          <?php if(isset($addon->price)): ?>
                            ₱<?php echo e(number_format($addon->price, 2)); ?>

                          <?php else: ?>
                            <?php if($group['key'] !== 'additional'): ?>
                              On request
                            <?php endif; ?>
                          <?php endif; ?>
                        </span>
                      </div>
                    </button>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
              </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </section>
        <?php endif; ?>
      </div>
  <?php
      $templateId = $product->template_id ?? optional($product->template)->id;
      $editDesignBase = $templateId
          ? route('design.studio', ['template' => $templateId, 'product' => $product->id])
          : null;
      // initial edit URL includes the selected size so page loads with size
      $editDesignUrl = $editDesignBase ? ($editDesignBase . (str_contains($editDesignBase, '?') ? '&' : '?') . 'size=' . urlencode($selectedSize)) : null;
  ?>
  <?php if($editDesignUrl): ?>
    <a href="<?php echo e($editDesignUrl); ?>" data-base-href="<?php echo e($editDesignBase); ?>" class="edit-btn" data-edit-link target="_top" rel="noopener">
      <span>Edit my design</span>
      <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M17 7L7 17"></path>
        <path d="M8 7h9v9"></path>
      </svg>
    </a>
  <?php endif; ?>
      <div id="addonToast" class="selection-toast" role="status" aria-live="polite"></div>
    </div>
  </div>
</body>
</html>
<?php /**PATH C:\Users\leanne\xampp\htdocs\InkWise-Web\ink-wise\resources\views/customer/Invitations/productpreview.blade.php ENDPATH**/ ?>