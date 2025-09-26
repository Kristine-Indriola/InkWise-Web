{{-- filepath: c:\xampp\htdocs\InkWise-Web\ink-wise\resources\views\admin\products\create-invitation.blade.php --}}
@extends('layouts.admin')

@section('title', 'Create Invitation Product')

<link rel="stylesheet" href="{{ asset('css/admin-css/create_invite.css') }}">
<script src="{{ asset('js/admin/create_invite.js') }}"></script>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

@section('content')
{{-- Breadcrumb Navigation --}}
<nav aria-label="breadcrumb" class="breadcrumb-nav">
    <ol class="breadcrumb-list" itemscope itemtype="https://schema.org/BreadcrumbList">
        <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a href="{{ route('admin.products.index') }}" class="breadcrumb-link" aria-label="Go to Admin Dashboard" itemprop="item">
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
            <button type="button" id="breadcrumb-step3" class="breadcrumb-step" aria-live="polite" onclick="Navigation.showPage(2)">
                Customization
            </button>
            <span class="breadcrumb-separator" aria-hidden="true">›</span>
        </li>
        <li class="breadcrumb-item">
            <button type="button" id="breadcrumb-step4" class="breadcrumb-step" aria-live="polite" onclick="Navigation.showPage(3)">
                Production
            </button>
        </li>
    </ol>
</nav>

{{-- Page Title --}}
<h1 id="page-title">Templates</h1>

<form method="POST" action="{{ route('admin.products.store') }}" id="invitation-form">
    @csrf
    @if(isset($product) && $product->id)
        <input type="hidden" id="product_id" name="product_id" value="{{ $product->id }}">
    @endif
    <input type="hidden" id="template_id" name="template_id" value="">

    <div class="invitation-container">
        {{-- Progress Bar --}}
        <div class="progress-bar">
            <div class="progress-fill" id="progress-fill"></div>
        </div>

        {{-- Page 1: Templates --}}
        @include('admin.products.templates')

        {{-- Page 2: Basic Info --}}
        <div class="page page2" data-page="2" style="display: none;">
            <div class="error-summary" style="display: none;" role="alert" aria-live="polite">
                <h3>Please correct the following errors:</h3>
                <ul id="error-list-page2"></ul>
            </div>
            <div class="form-section">
                <h2><i class="fas fa-info-circle"></i> Basic Information</h2>
                <div class="responsive-grid grid-2-cols">
                    <div class="field">
                        <label for="invitationName">Invitation Name *</label>
                        <input type="text" id="invitationName" name="invitationName" placeholder="Invitation Name * (e.g. Elegant Pearl Wedding Invitation)" required aria-required="true" aria-describedby="invitationName-error" value="{{ old('invitationName', $selectedTemplate->name ?? '') }}">
                        <span id="invitationName-error" class="error-message" role="alert" aria-live="polite"></span>
                        @error('invitationName') <span class="error-message">{{ $message }}</span> @enderror
                    </div>
                    <div class="field">
                        <label for="eventType">Event Type *</label>
                        <select id="eventType" name="eventType" required aria-required="true" aria-describedby="eventType-error">
                            <option disabled selected>Event Type *</option>
                            @foreach(['Wedding', 'Birthday', 'Baptism', 'Corporate'] as $type)
                                <option value="{{ $type }}" {{ (old('eventType', $selectedTemplate->eventType ?? '') == $type) ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                        <span id="eventType-error" class="error-message"></span>
                        @error('eventType') <span class="error-message">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="responsive-grid grid-2-cols">
                    <div class="field">
                        <label for="productType">Product Type *</label>
                        <select id="productType" name="productType" required aria-required="true" aria-describedby="productType-error">
                            <option disabled selected>Product Type *</option>
                            <option value="Invitation" {{ (old('productType', $selectedTemplate->productType ?? '') == 'Invitation') ? 'selected' : '' }}>Invitation</option>
                            <option value="Giveaway" {{ (old('productType', $selectedTemplate->productType ?? '') == 'Giveaway') ? 'selected' : '' }}>Giveaway</option>
                        </select>
                        <span id="productType-error" class="error-message"></span>
                        @error('productType') <span class="error-message">{{ $message }}</span> @enderror
                    </div>
                    <div class="field">
                        <label for="themeStyle">Theme / Style *</label>
                        <input type="text" id="themeStyle" name="themeStyle" placeholder="Theme / Style * (e.g. Luxury, Minimalist, Floral)" required aria-required="true" aria-describedby="themeStyle-error" value="{{ old('themeStyle', $selectedTemplate->themeStyle ?? '') }}">
                        <span id="themeStyle-error" class="error-message"></span>
                        @error('themeStyle') <span class="error-message">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            {{-- Materials --}}
            <div class="form-section">
                <h2>Materials</h2>
                <div class="material-group">

                    <div class="material-rows">
                        @php
                            $materialRows = old('materials', isset($product) ? $product->materials->toArray() : [ [] ]);
                        @endphp
                        @foreach($materialRows as $i => $material)
                        <div class="material-row">
                            <input type="hidden" name="materials[{{ $i }}][id]" value="{{ old('materials.'.$i.'.id', $material['id'] ?? '') }}">
                            <div class="input-row">
                                <div class="field">
                                    <label for="materials_{{ $i }}_item">Material Name</label>
                                    <input type="text" id="materials_{{ $i }}_item" name="materials[{{ $i }}][item]" value="{{ old('materials.'.$i.'.item', $material['item'] ?? '') }}">
                                </div>
                                <div class="field">
                                    <label for="materials_{{ $i }}_type">Product Type</label>
                                    <input type="text" id="materials_{{ $i }}_type" name="materials[{{ $i }}][type]" placeholder="Type" aria-describedby="materials_{{ $i }}_type-error" value="{{ old('materials.'.$i.'.type', $material['type'] ?? '') }}">
                                    <span id="materials_{{ $i }}_type-error" class="error-message"></span>
                                </div>
                                <div class="field">
                                    <label for="materials_{{ $i }}_color">Color</label>
                                    <input type="text" id="materials_{{ $i }}_color" name="materials[{{ $i }}][color]" placeholder="Color" aria-describedby="materials_{{ $i }}_color-error" value="{{ old('materials.'.$i.'.color', $material['color'] ?? '') }}">
                                    <span id="materials_{{ $i }}_color-error" class="error-message"></span>
                                </div>
                                <div class="field">
                                    <label for="materials_{{ $i }}_size">Size</label>
                                    <input type="text" id="materials_{{ $i }}_size" name="materials[{{ $i }}][size]" placeholder="Size" aria-describedby="materials_{{ $i }}_size-error" value="{{ old('materials.'.$i.'.size', $material['size'] ?? '') }}">
                                    <span id="materials_{{ $i }}_size-error" class="error-message"></span>
                                </div>
                                <div class="field">
                                    <label for="materials_{{ $i }}_weight">Weight (GSM)</label>
                                    <input type="number" id="materials_{{ $i }}_weight" name="materials[{{ $i }}][weight]" placeholder="Weight (GSM)" aria-describedby="materials_{{ $i }}_weight-error" value="{{ old('materials.'.$i.'.weight', $material['weight'] ?? '') }}">
                                    <span id="materials_{{ $i }}_weight-error" class="error-message"></span>
                                </div>
                            </div>
                            <div class="input-row">
                                <div class="field">
                                    <label for="materials_{{ $i }}_unitPrice">Unit Price</label>
                                    <input type="number" id="materials_{{ $i }}_unitPrice" name="materials[{{ $i }}][unitPrice]" placeholder="Unit Price" aria-describedby="materials_{{ $i }}_unitPrice-error" value="{{ old('materials.'.$i.'.unitPrice', $material['unitPrice'] ?? $material['unit_price'] ?? '') }}">
                                    <span id="materials_{{ $i }}_unitPrice-error" class="error-message"></span>
                                </div>
                                <div class="field">
                                    <label for="materials_{{ $i }}_usage">Usage per invite (ml)</label>
                                    <input type="number" step="0.01" id="materials_{{ $i }}_usage" name="materials[{{ $i }}][usage]" placeholder="Usage per invite (ml)" aria-describedby="materials_{{ $i }}_usage-error" value="{{ old('materials.'.$i.'.usage', $material['usage'] ?? '') }}">
                                    <span id="materials_{{ $i }}_usage-error" class="error-message"></span>
                                </div>
                                
                                <button class="add-row" type="button" aria-label="Add another material row">+</button>
                                <button class="remove-row" type="button" aria-label="Remove this material row">−</button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                {{-- Ink inputs removed; usage moved into materials rows --}}
            </div>

            {{-- Description --}}
            <div class="form-section">
                <h2>Description</h2>
                <div class="editor-toolbar">
                    <button type="button" class="editor-btn" data-command="bold" aria-label="Bold text"><i class="fas fa-bold"></i></button>
                    <button type="button" class="editor-btn" data-command="italic" aria-label="Italic text"><i class="fas fa-italic"></i></button>
                    <button type="button" class="editor-btn" data-command="underline" aria-label="Underline text"><i class="fas fa-underline"></i></button>
                    <button type="button" class="editor-btn" data-command="redo" aria-label="Redo"><i class="fas fa-redo"></i></button>
                </div>
                <textarea id="description" name="description" style="display:none;" aria-describedby="description-error">{{ old('description', $selectedTemplate->description ?? '') }}</textarea>
                <div contenteditable="true" class="editor-content" id="description-editor" aria-label="Description editor" aria-describedby="description-error"></div>
                <span id="description-error" class="error-message"></span>
            </div>

            <div class="form-buttons">
                <button type="button" class="continue-btn">Continue</button>
            </div>
        </div>

        {{-- Page 3: Customization --}}
        <div class="page page3" data-page="3" style="display: none;">
            <div class="error-summary" style="display: none;" role="alert" aria-live="polite">
                <h3>Please correct the following errors:</h3>
                <ul id="error-list-page3"></ul>
            </div>
            <div class="form-section">
                <h2>Customization Options</h2>

                <div class="responsive-grid grid-2-cols">
                    <div class="field grid-span-2">
                        <label for="minOrderQtyCustomization">Minimum Order Quantity</label>
                        <input type="number" id="minOrderQtyCustomization" name="minOrderQtyCustomization" placeholder="Minimum Order Quantity (e.g., 50 pcs)" aria-describedby="minOrderQtyCustomization-error" value="{{ old('minOrderQtyCustomization', old('minOrderQty', '')) }}">
                        <span id="minOrderQtyCustomization-error" class="error-message"></span>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Group Fields</h3>
                    <div class="group-rows">
                        @php
                            $groupRows = old('groups', isset($product) && !empty($product->groups) ? $product->groups->toArray() : []);
                            if (empty($groupRows)) { $groupRows = [['name'=>'','size'=>'','colors'=>'','unit'=>'','unitPrice'=>'']]; }
                        @endphp
                        @foreach($groupRows as $i => $g)
                        <div class="group-row">
                            <input type="hidden" name="groups[{{ $i }}][id]" value="{{ old('groups.'.$i.'.id', $g['id'] ?? '') }}">
                            <div class="input-row">
                                <div class="field">
                                    <label for="groups_{{ $i }}_name">Material Name</label>
                                    <input type="text" id="groups_{{ $i }}_name" name="groups[{{ $i }}][name]" value="{{ old('groups.'.$i.'.name', $g['name'] ?? '') }}">
                                </div>
                                <div class="field">
                                    <label for="groups_{{ $i }}_size">Size</label>
                                    <input type="text" id="groups_{{ $i }}_size" name="groups[{{ $i }}][size]" value="{{ old('groups.'.$i.'.size', $g['size'] ?? '') }}">
                                </div>
                                <div class="field">
                                    <label for="groups_{{ $i }}_colors">Colors</label>
                                    <input type="text" id="groups_{{ $i }}_colors" name="groups[{{ $i }}][colors]" value="{{ old('groups.'.$i.'.colors', $g['colors'] ?? '') }}">
                                </div>
                                <div class="field">
                                    <label for="groups_{{ $i }}_unit">Unit</label>
                                    <input type="text" id="groups_{{ $i }}_unit" name="groups[{{ $i }}][unit]" value="{{ old('groups.'.$i.'.unit', $g['unit'] ?? '') }}">
                                </div>
                                <div class="field">
                                    <label for="groups_{{ $i }}_unitPrice">Unit Price</label>
                                    <input type="number" step="0.01" id="groups_{{ $i }}_unitPrice" name="groups[{{ $i }}][unitPrice]" value="{{ old('groups.'.$i.'.unitPrice', $g['unitPrice'] ?? '') }}">
                                </div>
                            </div>
                            <div class="input-row">
                                <button class="add-row" type="button" aria-label="Add another group row">+</button>
                                <button class="remove-row" type="button" aria-label="Remove this group row">−</button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2>Preview Image</h2>
                <div class="sample-image">
                    <p>Sample Image:</p>
                @php
                    $previewPath = '';
                    if (!empty($selectedTemplate)) {
                        $p = $selectedTemplate->preview ?? $selectedTemplate->image ?? null;
                        if ($p) {
                            // If already an absolute URL or starts with a slash, use as-is; otherwise ask Storage to build a URL
                            if (preg_match('/^(https?:)?\\/\\//i', $p) || strpos($p, '/') === 0) {
                                $previewPath = $p;
                            } else {
                                $previewPath = \Illuminate\Support\Facades\Storage::url($p);
                            }
                        }
                    }
                @endphp
                <img id="template-preview-img"
                    src="{{ $previewPath }}"
                    alt="Sample Invitation"
                    aria-describedby="sample-image-desc">
                    <span id="sample-image-desc" style="display: none;">This is a sample image of an invitation for reference.</span>
                </div>
            </div>

            <div class="form-buttons">
                <button type="button" class="continue-btn">Continue</button>
            </div>
        </div>

        {{-- Page 4: Production --}}
        <div class="page page4" data-page="4" style="display: none;">
            <div class="error-summary" style="display: none;" role="alert" aria-live="polite">
                <h3>Please correct the following errors:</h3>
                <ul id="error-list-page4"></ul>
            </div>
            <div class="form-section">
                <h2>Production Details</h2>
                <div class="responsive-grid grid-2-cols">
                    <div class="field">
                        <label for="leadTime">Lead Time / Production Days</label>
                        <input type="text" id="leadTime" name="leadTime" placeholder="Lead Time / Production Days (e.g., 5–7 working days)" aria-describedby="leadTime-error" value="{{ old('leadTime') }}">
                        <span id="leadTime-error" class="error-message"></span>
                    </div>
                    <div class="field">
                        <label for="stockAvailability">Stock Availability</label>
                        <input type="text" id="stockAvailability" name="stockAvailability" placeholder="Stock Availability (if limited)" aria-describedby="stockAvailability-error" value="{{ old('stockAvailability') }}">
                        <span id="stockAvailability-error" class="error-message"></span>
                    </div>
                </div>
            </div>

            {{-- Costing section removed as requested --}}

            <div class="form-buttons">
                <button type="submit" class="btn-save" id="submit-btn"
                    onclick="this.disabled=true; this.form.submit(); setTimeout(function(){ window.location='{{ route('admin.products.index') }}'; }, 2000);">
                    <span class="btn-text">Upload</span>
                    <span class="loading-spinner" style="display: none;"><i class="fas fa-spinner fa-spin"></i> Saving...</span>
                </button>
            </div>
        </div>
    </div>
</form>

<script>
    window.templatesData = @json($templates);
    window.materialsData = @json($materials);
    window.assetUrl = '{{ asset("") }}';
    
</script>

@endsection

{{-- IGNORE --}}
{{-- The following code is from ink-wise/resources/views/admin/products/index.blade.php for comparison --}}

{{-- filepath: c:\xampp\htdocs\InkWise-Web\ink-wise\resources\views\admin\products\index.blade.php --}}