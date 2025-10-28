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

    $defaults = [
        'name' => old('invitationName', $product->name ?? $selectedTemplate->name ?? ''),
        'event_type' => old('eventType', $product->event_type ?? $selectedTemplate->event_type ?? ''),
        'product_type' => old('productType', $product->product_type ?? $selectedTemplate->product_type ?? 'Invitation'),
        'theme_style' => old('themeStyle', $product->theme_style ?? $selectedTemplate->theme_style ?? ''),
        'description' => old('description', $product->description ?? $selectedTemplate->description ?? ''),
        'base_price' => old('base_price', $product->base_price ?? $product->unit_price ?? ''),
        'lead_time' => old('lead_time', $product->lead_time ?? ''),
        'date_available' => old('date_available', isset($product) && !empty($product->date_available)
            ? optional(\Illuminate\Support\Carbon::parse($product->date_available))->format('Y-m-d')
            : ''),
    ];

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

    $productColors = isset($product) ? $product->colors->map(function ($color) {
        return [
            'id' => $color->id,
            'name' => $color->name,
            'color_code' => $color->color_code,
        ];
    })->toArray() : [];

    $productBulkOrders = isset($product) ? $product->bulkOrders->map(function ($bulk) {
        return [
            'id' => $bulk->id,
            'min_qty' => $bulk->min_qty,
            'max_qty' => $bulk->max_qty,
            'price_per_unit' => $bulk->price_per_unit,
        ];
    })->toArray() : [];

    // Initialize with existing data or empty arrays
    $paperStockRows = old('paper_stocks', !empty($productPaperStocks) ? $productPaperStocks : [[]]);
    $addonRows = old('addons', !empty($productAddons) ? $productAddons : [[]]);
    $colorRows = old('colors', !empty($productColors) ? $productColors : [[]]);
    $bulkOrderRows = old('bulk_orders', !empty($productBulkOrders) ? $productBulkOrders : [[]]);

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
        @if($product && $product->template_id)
            <input type="hidden" name="template_id" value="{{ $product->template_id }}">
        @endif

        {{-- Basic Product Information --}}
        <div class="form-section">
            <h2 id="basicInfoHeader">{{ $defaults['product_type'] }} Information</h2>
            <div class="form-grid grid-2-cols">
                <div class="field">
                    <label for="invitationName" id="productNameLabel">{{ $defaults['product_type'] }} Name *</label>
                    <input type="text" id="invitationName" name="invitationName" required value="{{ $defaults['name'] }}">
                </div>

                <div class="field">
                    <label for="eventType">Event Type *</label>
                    <select id="eventType" name="eventType" required>
                        <option value="">Select event type</option>
                        @foreach(['Wedding','Birthday','Baptism','Corporate'] as $type)
                            <option value="{{ $type }}" {{ $defaults['event_type'] == $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="productType">Product Type *</label>
                    <select id="productType" name="productType" required>
                        <option value="Invitation" {{ $defaults['product_type'] == 'Invitation' ? 'selected' : '' }}>Invitation</option>
                        <option value="Giveaway" {{ $defaults['product_type'] == 'Giveaway' ? 'selected' : '' }}>Giveaway</option>
                        <option value="Envelope" {{ $defaults['product_type'] == 'Envelope' ? 'selected' : '' }}>Envelope</option>
                    </select>
                </div>

                <div class="field">
                    <label for="themeStyle">Theme / Style *</label>
                    <input type="text" id="themeStyle" name="themeStyle" required value="{{ $defaults['theme_style'] }}">
                </div>

                <div class="field">
                    <label for="basePrice">Base Price</label>
                    <input type="number" step="0.01" id="basePrice" name="base_price" value="{{ $defaults['base_price'] }}">
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

        {{-- Paper Stocks Section --}}
        @if($defaults['product_type'] !== 'Envelope' && $defaults['product_type'] !== 'Giveaway')
        <div class="form-section">
            <h2>Paper Stocks</h2>
            <div id="paper-stocks-container">
                @foreach($paperStockRows as $index => $stock)
                    <div class="dynamic-row paper-stock-row" data-index="{{ $index }}">
                        <div class="form-grid grid-4-cols">
                            <div class="field">
                                <label>Material *</label>
                                <select name="paper_stocks[{{ $index }}][material_id]" required>
                                    <option value="">Select material</option>
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

        {{-- Addons Section --}}
        @if($defaults['product_type'] !== 'Envelope' && $defaults['product_type'] !== 'Giveaway')
        <div class="form-section">
            <h2>Addons</h2>
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
            <button type="button" id="add-addon" class="btn-add">Add Addon</button>
        </div>
        @endif

        {{-- Colors Section --}}
        <div class="form-section">
            <h2>Colors</h2>
            <div id="colors-container">
                @foreach($colorRows as $index => $color)
                    <div class="dynamic-row color-row" data-index="{{ $index }}">
                        <div class="form-grid grid-3-cols">
                            <div class="field">
                                <label>Name *</label>
                                <input type="text" name="colors[{{ $index }}][name]" required value="{{ $color['name'] ?? '' }}">
                            </div>
                            <div class="field">
                                <label>Color Code *</label>
                                <input type="color" name="colors[{{ $index }}][color_code]" required value="{{ $color['color_code'] ?? '#000000' }}">
                            </div>
                            <div class="field actions">
                                <button type="button" class="btn-remove remove-color" {{ $index === 0 ? 'disabled' : '' }}>Remove</button>
                            </div>
                        </div>
                        @if(isset($color['id']))
                            <input type="hidden" name="colors[{{ $index }}][id]" value="{{ $color['id'] }}">
                        @endif
                    </div>
                @endforeach
            </div>
            <button type="button" id="add-color" class="btn-add">Add Color</button>
        </div>

        {{-- Bulk Orders Section --}}
        <div class="form-section">
            <h2>Bulk Orders</h2>
            <div id="bulk-orders-container">
                @foreach($bulkOrderRows as $index => $bulk)
                    <div class="dynamic-row bulk-order-row" data-index="{{ $index }}">
                        <div class="form-grid grid-4-cols">
                            <div class="field">
                                <label>Min Qty *</label>
                                <input type="number" name="bulk_orders[{{ $index }}][min_qty]" required value="{{ $bulk['min_qty'] ?? '' }}">
                            </div>
                            <div class="field">
                                <label>Max Qty *</label>
                                <input type="number" name="bulk_orders[{{ $index }}][max_qty]" required value="{{ $bulk['max_qty'] ?? '' }}">
                            </div>
                            <div class="field">
                                <label>Price per Unit *</label>
                                <input type="number" step="0.01" name="bulk_orders[{{ $index }}][price_per_unit]" required value="{{ $bulk['price_per_unit'] ?? '' }}">
                            </div>
                            <div class="field actions">
                                <button type="button" class="btn-remove remove-bulk-order" {{ $index === 0 ? 'disabled' : '' }}>Remove</button>
                            </div>
                        </div>
                        @if(isset($bulk['id']))
                            <input type="hidden" name="bulk_orders[{{ $index }}][id]" value="{{ $bulk['id'] }}">
                        @endif
                    </div>
                @endforeach
            </div>
            <button type="button" id="add-bulk-order" class="btn-add">Add Bulk Order</button>
        </div>

        {{-- Product Template Section --}}
        <div class="form-section">
            <h2>Product Template</h2>
            @if($product && $product->template)
                <div class="template-preview">
                    <h3>Selected Template: {{ $product->template->name }}</h3>
                    <div class="template-svg-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 16px;">
                        @if($product->template->front_image)
                            <div class="template-svg-item">
                                <h4>Front Design</h4>
                                <div class="svg-container" style="width: 100%; height: 200px; border: 1px solid #eee; background: #fff; padding: 8px; border-radius: 4px;">
                                    @php
                                        $frontSvgPath = $product->template->front_image;
                                        if (!preg_match('/^(https?:)?\/\//i', $frontSvgPath) && !str_starts_with($frontSvgPath, '/')) {
                                            $frontSvgPath = \Illuminate\Support\Facades\Storage::url($frontSvgPath);
                                        }
                                    @endphp
                                    <img src="{{ $frontSvgPath }}" alt="Front template" style="width: 100%; height: 100%; object-fit: contain;">
                                </div>
                            </div>
                        @endif
                        @if($product->template->back_image)
                            <div class="template-svg-item">
                                <h4>Back Design</h4>
                                <div class="svg-container" style="width: 100%; height: 200px; border: 1px solid #eee; background: #fff; padding: 8px; border-radius: 4px;">
                                    @php
                                        $backSvgPath = $product->template->back_image;
                                        if (!preg_match('/^(https?:)?\/\//i', $backSvgPath) && !str_starts_with($backSvgPath, '/')) {
                                            $backSvgPath = \Illuminate\Support\Facades\Storage::url($backSvgPath);
                                        }
                                    @endphp
                                    <img src="{{ $backSvgPath }}" alt="Back template" style="width: 100%; height: 100%; object-fit: contain;">
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <p class="muted">No template selected for this product.</p>
            @endif
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
                            
