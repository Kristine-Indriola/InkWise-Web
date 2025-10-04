<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $product->name }} Preview</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/customer/preview.css') }}">
  <script src="{{ asset('js/customer/preview.js') }}" defer></script>
</head>
<body data-product-id="{{ $product->id ?? '' }}" data-product-name="{{ $product->name ?? '' }}">
@php
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

  $defaultImage = $frontImage ?? $backImage ?? asset('images/placeholder.png');
  $priceValue = $product->base_price ?? $product->unit_price ?? optional($templateRef)->base_price ?? optional($templateRef)->unit_price;
  $paperStocksRaw = $product->paper_stocks ?? $product->paperStocks ?? collect();
  $colors = $product->product_colors ?? $product->colors ?? collect();
  $stockAvailability = $product->stock_availability ?? optional($templateRef)->stock_availability;
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
@endphp

  <div class="container">
    <div class="preview">
      <div class="flip-container" id="flipContainer">
        <div class="flipper" id="flipper">
          <div class="front">
            <img src="{{ $frontImage ?? $defaultImage }}" alt="Front of {{ $product->name }}" onerror="this.src='{{ $defaultImage }}'">
          </div>
          <div class="back">
            <img src="{{ $backImage ?? $frontImage ?? $defaultImage }}" alt="Back of {{ $product->name }}" onerror="this.src='{{ $defaultImage }}'">
          </div>
        </div>
      </div>
      <div class="toggle-buttons">
        <button id="frontBtn" class="active">Front</button>
        <button id="backBtn" @if(!$backImage) style="display:none" @endif>Back</button>
      </div>
    </div>

    <div class="details">
      <div class="details-header">
        <h2>{{ $product->name }}</h2>
        <p class="price">
          @if(!is_null($priceValue))
            As low as <span class="new-price">₱{{ number_format($priceValue, 2) }}</span> per piece
          @else
            <span class="new-price">Pricing available on request</span>
          @endif
        </p>

        @if($formattedAvailability || $stockAvailability)
          <p class="delivery">
            @if($formattedAvailability)
              Get as soon <b>{{ $formattedAvailability }}</b>
            @endif
            @if($stockAvailability)
              @if($formattedAvailability)<br>@endif
              Stock: <b>{{ $stockAvailability }}</b>
            @endif
          </p>
        @endif
      </div>

      <div class="options-scroll">
        <section class="option-block">
          <h3>Colors</h3>
          @if($colors->count())
            <div class="colors">
              @foreach($colors as $color)
                <button class="color-btn @if($loop->first) active @endif"
                    style="background: {{ $color->color_code ?? '#d1d5db' }}"
                    data-front="{{ $frontImage ?? $defaultImage }}"
                    data-back="{{ $backImage ?? $frontImage ?? $defaultImage }}"
                    title="{{ $color->name ?? 'Color option' }}">
                </button>
              @endforeach
            </div>
          @else
            <p class="muted">Single colorway.</p>
          @endif
        </section>

        @if($paperStocks->count())
          <section class="option-block">
            <h3>Paper Stocks</h3>
            <p class="selection-hint">Choose your preferred stock (optional).</p>
            <div class="feature-grid">
              @foreach($paperStocks as $stock)
                <button
                  type="button"
                  class="feature-card selectable-card"
                  data-option-group="paper_stock"
                  data-option-id="{{ $stock->id ?? ('paper_' . $loop->index) }}"
                  data-option-name="{{ e($stock->name ?? 'Paper Stock') }}"
                  data-option-price="{{ isset($stock->price) ? $stock->price : '' }}"
                  data-option-image="{{ e($stock->display_image) }}"
                  aria-pressed="false"
                >
                  <div class="feature-card-media">
                    <img src="{{ $stock->display_image }}" alt="{{ $stock->name ?? 'Paper stock image' }}">
                  </div>
                  <div class="feature-card-info">
                    <span class="feature-card-title">{{ $stock->name ?? 'Paper Stock' }}</span>
                    <span class="feature-card-price">
                      @if(isset($stock->price))
                        ₱{{ number_format($stock->price, 2) }}
                      @else
                        On request
                      @endif
                    </span>
                  </div>
                </button>
              @endforeach
            </div>
          </section>
        @endif

        @if($orderedAddonGroups->count())
          <section class="option-block">
            <h3>Add-ons</h3>
            <p class="selection-hint">Pick one per category to personalize (optional).</p>
            @foreach($orderedAddonGroups as $group)
              <div class="addon-group addon-{{ $group['key'] }}">
                <h4>{{ $group['label'] }}</h4>
                <div class="feature-grid">
                  @foreach($group['items'] as $addon)
                    <button
                      type="button"
                      class="feature-card selectable-card"
                      data-option-group="{{ $group['key'] }}"
                      data-option-id="{{ $addon->id ?? ($group['key'] . '_' . $loop->index) }}"
                      data-option-name="{{ e($addon->name ?? $group['label']) }}"
                      data-option-price="{{ isset($addon->price) ? $addon->price : '' }}"
                      data-option-image="{{ e($addon->display_image) }}"
                      aria-pressed="false"
                      title="Select {{ $addon->name ?? $group['label'] }}"
                    >
                      <div class="feature-card-media">
                        <img src="{{ $addon->display_image }}" alt="{{ $addon->name ?? ($group['label'] . ' option') }}">
                      </div>
                      <div class="feature-card-info">
                        <span class="feature-card-title">{{ $addon->name ?? $group['label'] }}</span>
                        <span class="feature-card-price">
                          @if(isset($addon->price))
                            ₱{{ number_format($addon->price, 2) }}
                          @else
                            On request
                          @endif
                        </span>
                      </div>
                    </button>
                  @endforeach
                </div>
              </div>
            @endforeach
          </section>
        @endif
      </div>
  <a href="{{ route('design.edit', ['product' => $product->id]) }}" class="edit-btn" data-edit-link target="_parent">
    <span>Edit my design</span>
    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M17 7L7 17"></path>
      <path d="M8 7h9v9"></path>
    </svg>
  </a>
      <div id="addonToast" class="selection-toast" role="status" aria-live="polite"></div>
    </div>
  </div>
</body>
</html>
