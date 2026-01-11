{{-- filepath: c:\xampp\htdocs\InkWise-Web\ink-wise\resources\views\admin\products\edit.blade.php --}}
@extends('layouts.admin')

@section('title', 'Edit ' . ($product->product_type ?? 'Product') . ': ' . $product->name)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/create_invite.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-css/product-edit.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/admin/create_invite.js') }}"></script>
    <script src="{{ asset('js/admin/product-edit.js') }}" defer></script>
@endpush

@section('content')
@php
    $product = $product ?? null;
    $selectedTemplate = $selectedTemplate ?? ($product->template ?? null);
    $templates = $templates ?? [];
    $materials = $materials ?? collect();

    // Normalize default values with explicit fallbacks to avoid nested ternaries in Blade.
    $basePriceDefault = $product->base_price
        ?? $product->unit_price
        ?? optional($product->envelope)->price_per_unit
        ?? '';

    $inkUsageDefault = $product->estimated_ink_usage_ml
        ?? optional($product->envelope)->average_usage_ml
        ?? optional(optional($product)->inkUsage->first())->average_usage_ml
        ?? '';

    $leadTimeDefault = $product->lead_time
        ?? $product->lead_time_days
        ?? 7; // Default 7 days if not set

    $dateAvailableValue = '';
    if (isset($product)) {
        $dateRaw = $product->date_available ?: $product->created_at;
        if (!empty($dateRaw)) {
            try {
                $dateAvailableValue = optional(\Illuminate\Support\Carbon::parse($dateRaw))->format('Y-m-d');
            } catch (\Throwable $e) {
                $dateAvailableValue = '';
            }
        }
    }

    $defaults = [
        'name' => old('invitationName', $product->name ?? $selectedTemplate->name ?? ''),
        'event_type' => old('eventType', $product->event_type ?? $selectedTemplate->event_type ?? ''),
        'product_type' => old('productType', $product->product_type ?? $selectedTemplate->product_type ?? 'Invitation'),
        'theme_style' => old('themeStyle', $product->theme_style ?? $selectedTemplate->theme_style ?? ''),
        'description' => old('description', $product->description ?? $selectedTemplate->description ?? ''),
        'base_price' => old('base_price', $basePriceDefault),
        'estimated_ink_usage_ml' => old('estimated_ink_usage_ml', $inkUsageDefault),
        'lead_time' => old('lead_time', $leadTimeDefault),
        'date_available' => old('date_available', $dateAvailableValue),
        'sizes' => old('sizes', is_array($product->sizes ?? null) ? implode(', ', $product->sizes) : ($product->sizes ?? '')),
    ];

    $productTypeNormalized = strtolower($defaults['product_type'] ?? '');

    // Prepare existing data for editing
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

    // Initialize with existing data or empty arrays
    $paperStockRows = old('paper_stocks', !empty($productPaperStocks) ? $productPaperStocks : [[]]);
    $addonRows = old('addons', !empty($productAddons) ? $productAddons : [[]]);

    // Envelope specific data
    $envelope = $product->envelope ?? null;
    $envelopeDefaults = [
        'material_type' => old('material_type', $envelope->envelope_material_name ?? ''),
        'envelope_material_id' => old('envelope_material_id', $envelope->material_id ?? ''),
        'max_qty' => old('max_qty', $envelope->max_qty ?? ''),
        'max_quantity' => old('max_quantity', $envelope->max_quantity ?? ''),
        'price_per_unit' => old('price_per_unit', $envelope->price_per_unit ?? ''),
    ];
@endphp

<main class="product-edit-container" role="main">
    <h1>Edit Product: {{ $product->name }}</h1>

    @if(session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <form method="POST" action="{{ route('admin.products.store') }}" id="product-edit-form" enctype="multipart/form-data" class="product-edit-form">
        @csrf
        @if(isset($product) && $product->id)
            <input type="hidden" name="product_id" value="{{ $product->id }}">
        @endif
        {{-- Template selector will populate template_id --}}

        {{-- Basic Product Information --}}
        <div class="form-section">
            <h2 id="basicInfoHeader">{{ $defaults['product_type'] }} Information</h2>
            <div class="form-grid grid-2-cols">
                <div class="field">
                    <label for="invitationName" id="productNameLabel">{{ $defaults['product_type'] }} Name</label>
                    <input type="text" id="invitationName" name="invitationName" required value="{{ $defaults['name'] }}">
                </div>

                <div class="field">
                    <label for="eventType">Event Type</label>
                    <select id="eventType" name="eventType" required>
                        <option value="">Select event type</option>
                        @foreach(['Wedding','Birthday','Baptism','Corporate'] as $type)
                            <option value="{{ $type }}" {{ $defaults['event_type'] == $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="productType">Product Type</label>
                    <select id="productType" name="productType" required>
                        <option value="Invitation" {{ $productTypeNormalized === 'invitation' ? 'selected' : '' }}>Invitation</option>
                        <option value="Giveaway" {{ $productTypeNormalized === 'giveaway' ? 'selected' : '' }}>Giveaway</option>
                        <option value="Envelope" {{ $productTypeNormalized === 'envelope' ? 'selected' : '' }}>Envelope</option>
                    </select>
                </div>

                @if($productTypeNormalized === 'invitation')
                <div class="field">
                    <label for="themeStyle">Theme / Style</label>
                    <input type="text" id="themeStyle" name="themeStyle" value="{{ $defaults['theme_style'] }}">
                </div>
                <div class="field">
                    <label for="productSizes">Sizes</label>
                    <input type="text" id="productSizes" name="sizes" list="product-sizes" placeholder="e.g. 5x7, 4x6, A5" value="{{ $defaults['sizes'] }}">
                    <datalist id="product-sizes">
                        <option value="5x7">5x7</option>
                        <option value="4x6">4x6</option>
                        <option value="A5">A5</option>
                        <option value="A6">A6</option>
                        <option value="6x8">6x8</option>
                        <option value="Square">Square</option>
                    </datalist>
                    <small class="field-help">Comma-separated sizes (stored in product sizes)</small>
                </div>
                @endif

                <div class="field">
                    <label for="basePrice">Base Price</label>
                    <input type="number" step="0.01" id="basePrice" name="base_price" value="{{ $defaults['base_price'] }}">
                </div>

                <div class="field">
                    <label for="estimatedInkUsage">Estimated Ink Usage (mL per invitation)</label>
                    <input type="number" step="0.01" min="0" id="estimatedInkUsage" name="estimated_ink_usage_ml" value="{{ $defaults['estimated_ink_usage_ml'] }}" placeholder="e.g., 2.50">
                    <small class="field-help">Used for printing and cost calculations.</small>
                </div>

                <div class="field">
                    <label for="leadTime">Lead Time (days)</label>
                    <input type="number" id="leadTime" name="lead_time" value="{{ $defaults['lead_time'] }}">
                </div>

                <div class="field">
                    <label for="dateAvailable">Date Available</label>
                    <input type="date" id="dateAvailable" name="date_available" value="{{ $defaults['date_available'] }}">
                </div>

                <div class="field full-width">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4">{{ $defaults['description'] }}</textarea>
                </div>
            </div>
        </div>

        {{-- Envelope Material Selection --}}
        <div class="form-section" id="envelope-fields" style="display: {{ $productTypeNormalized === 'envelope' ? 'block' : 'none' }};">
            <h2>Material</h2>
            @php
                $envelopeMaterialName = optional(optional($product->envelope)->material)->material_name
                    ?? optional($product->envelope)->envelope_material_name
                    ?? null;
                $envelopeMaterialType = optional(optional($product->envelope)->material)->material_type
                    ?? optional($product->envelope)->envelope_material_name
                    ?? 'ENVELOPE';
            @endphp
            <div class="form-grid grid-2-cols">
                <div class="field">
                    <label>Material</label>
                    <input type="text" class="styled-select" value="{{ strtoupper($envelopeMaterialType) }}" readonly>
                </div>

                <div class="field">
                    <label>Envelope Material Name</label>
                    <input type="text" class="styled-select" value="{{ $envelopeMaterialName ?? 'Not set' }}" readonly>
                </div>

                <div class="field">
                    <label for="pricePerUnit">Price per Unit</label>
                    <input type="number" step="0.01" id="pricePerUnit" name="price_per_unit" value="{{ $envelopeDefaults['price_per_unit'] }}">
                </div>
            </div>
            @if(!$envelopeMaterialName)
                <p class="muted">Envelope material is already managed in the envelope record; no selection available here.</p>
            @endif
        </div>

        {{-- Paper Stocks Section --}}
        @if($productTypeNormalized !== 'envelope' && $productTypeNormalized !== 'giveaway')
        <div class="form-section">
            <h2>Paper Stocks</h2>
            <div id="paper-stocks-container">
                @foreach($paperStockRows as $index => $stock)
                    <div class="dynamic-row paper-stock-row" data-index="{{ $index }}">
                        <div class="form-grid grid-4-cols">
                            <div class="field">
                                <label>Paper *</label>
                                <select name="paper_stocks[{{ $index }}][material_id]" required>
                                    <option value="">Select paper</option>
                                    @foreach($materials as $material)
                                        <option value="{{ $material->id }}" {{ ($stock['material_id'] ?? '') == $material->id ? 'selected' : '' }}>
                                            {{ $material->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label>Name *</label>
                                <input type="text" name="paper_stocks[{{ $index }}][name]" required value="{{ $stock['name'] ?? '' }}">
                            </div>
                            <div class="field">
                                <label>Price *</label>
                                <input type="number" step="0.01" name="paper_stocks[{{ $index }}][price]" required value="{{ $stock['price'] ?? '' }}">
                            </div>
                            <div class="field">
                                <label>Image</label>
                                <input type="file" name="paper_stocks[{{ $index }}][image]" accept="image/*">
                                @if(!empty($stock['image_url']))
                                    <div class="existing-file">Current: <a href="{{ $stock['image_url'] }}" target="_blank">View</a></div>
                                @endif
                            </div>
                            <div class="field actions">
                                <button type="button" class="btn-remove remove-paper-stock" {{ $index === 0 ? 'disabled' : '' }}>Remove</button>
                            </div>
                        </div>
                        @if(isset($stock['id']))
                            <input type="hidden" name="paper_stocks[{{ $index }}][id]" value="{{ $stock['id'] }}">
                        @endif
                    </div>
                @endforeach
            </div>
            <button type="button" id="add-paper-stock" class="btn-add">Add Paper Stock</button>
        </div>
        @endif

        {{-- Size Section --}}
        @if($productTypeNormalized !== 'envelope' && $productTypeNormalized !== 'giveaway')
        <div class="form-section">
            <h2>Size</h2>
            <div id="addons-container">
                @foreach($addonRows as $index => $addon)
                    <div class="dynamic-row addon-row" data-index="{{ $index }}">
                        <div class="form-grid grid-4-cols">
                            <div class="field">
                                <label>Type *</label>
                                <select name="addons[{{ $index }}][addon_type]" required>
                                    <option value="">Select type</option>
                                    @foreach(['Printing','Embellishment','Packaging'] as $type)
                                        <option value="{{ $type }}" {{ ($addon['addon_type'] ?? '') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label>Name *</label>
                                <input type="text" name="addons[{{ $index }}][name]" required value="{{ $addon['name'] ?? '' }}">
                            </div>
                            <div class="field">
                                <label>Price *</label>
                                <input type="number" step="0.01" name="addons[{{ $index }}][price]" required value="{{ $addon['price'] ?? '' }}">
                            </div>
                            <div class="field">
                                <label>Image</label>
                                <input type="file" name="addons[{{ $index }}][image]" accept="image/*">
                                @if(!empty($addon['image_url']))
                                    <div class="existing-file">Current: <a href="{{ $addon['image_url'] }}" target="_blank">View</a></div>
                                @endif
                            </div>
                            <div class="field actions">
                                <button type="button" class="btn-remove remove-addon" {{ $index === 0 ? 'disabled' : '' }}>Remove</button>
                            </div>
                        </div>
                        @if(isset($addon['id']))
                            <input type="hidden" name="addons[{{ $index }}][id]" value="{{ $addon['id'] }}">
                        @endif
                    </div>
                @endforeach
            </div>
            <button type="button" id="add-addon" class="btn-add">Add Size</button>
        </div>
        @endif

        {{-- Product Template Section --}}
        <div class="form-section">
            <h2>Product Preview</h2>
            <div class="template-preview">
                @if($product && $product->template)
                    <h3>{{ $product->template->name }}</h3>
                    @php
                        $template = $product->template;
                        $placeholder = \App\Support\ImageResolver::url(null);

                        $design = $template->design ?? [];
                        $metadata = $template->metadata ?? [];
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
                            $template->front_image,
                            data_get($template, 'preview_front'),
                            data_get($metadata, 'front_image'),
                            data_get($metadata, 'preview_front'),
                            data_get($design, 'preview_front'),
                            data_get($design, 'front_image'),
                            data_get($design, 'front.preview'),
                            $template->preview,
                        ];

                        $backCandidates = [
                            $template->back_image,
                            data_get($template, 'preview_back'),
                            data_get($metadata, 'back_image'),
                            data_get($metadata, 'preview_back'),
                            data_get($design, 'preview_back'),
                            data_get($design, 'back_image'),
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

                        $resolveAsset = function (array $candidates) use ($placeholder) {
                            foreach ($candidates as $candidate) {
                                if (!$candidate) {
                                    continue;
                                }

                                if (is_string($candidate) && \Illuminate\Support\Str::startsWith(trim($candidate), 'data:image')) {
                                    return $candidate;
                                }

                                $url = \App\Support\ImageResolver::url($candidate);
                                if ($url && $url !== $placeholder) {
                                    return $url;
                                }
                            }

                            return null;
                        };

                        $frontUrl = $resolveAsset($frontCandidates);
                        $backUrl = $resolveAsset($backCandidates);
                    @endphp
                    <div class="template-svg-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 16px;">
                        @if($frontUrl)
                            <div class="template-svg-item">
                                <h4>Front Design</h4>
                                <div class="svg-container" style="width: 100%; height: 200px; border: 1px solid #eee; background: #fff; padding: 8px; border-radius: 4px;">
                                    <img src="{{ $frontUrl }}" alt="Front template" style="width: 100%; height: 100%; object-fit: contain;">
                                </div>
                            </div>
                        @endif
                        @if($backUrl)
                            <div class="template-svg-item">
                                <h4>Back Design</h4>
                                <div class="svg-container" style="width: 100%; height: 200px; border: 1px solid #eee; background: #fff; padding: 8px; border-radius: 4px;">
                                    <img src="{{ $backUrl }}" alt="Back template" style="width: 100%; height: 100%; object-fit: contain;">
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <p class="muted">No template selected for this product.</p>
                @endif
            </div>
        </div>

        <div class="form-section">
            <div class="field actions">
                <button type="submit" class="btn-save">Update Product</button>
                <a href="{{ route('admin.products.index') }}" class="btn-cancel">Cancel</a>
            </div>
        </div>
    </form>
</main>

@endsection
                            
