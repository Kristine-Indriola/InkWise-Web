{{-- filepath: c:\xampp\htdocs\InkWise-Web\ink-wise\resources\views\admin\products\create-giveaways.blade.php --}}
@extends('layouts.admin')

@section('title', 'Create Giveaway Product')

<link rel="stylesheet" href="{{ asset('css/admin-css/create_invite.css') }}">
<script src="{{ asset('js/admin/create_invite.js') }}"></script>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

@section('content')
@php
	$product = $product ?? null;
	$selectedTemplate = $selectedTemplate ?? ($product->template ?? null);
	$isEditing = isset($product) && $product->id;

	$defaults = [
		'name' => old('invitationName', $product->name ?? $selectedTemplate->name ?? ''),
		'event_type' => old('eventType', $product->event_type ?? $selectedTemplate->event_type ?? ''),
		'base_price' => old('base_price', $product->base_price ?? ''),
		'lead_time' => old('lead_time', $product->lead_time ?? ''),
		'date_available' => old('date_available', isset($product) && !empty($product->date_available)
			? optional(\Illuminate\Support\Carbon::parse($product->date_available))->format('Y-m-d')
			: ''),
	];

	$existingBulkOrder = optional($product?->bulkOrders?->first());
	$existingEnvelope = optional($product?->envelope);

	$materialCollection = collect($materials ?? []);
	$materialTypes = $materialCollection
		->pluck('material_type')
		->filter()
		->unique()
		->sort()
		->values();
	$materialOptions = $materialCollection
		->sortBy(fn($material) => \Illuminate\Support\Str::lower($material->material_name ?? ''))
		->values();

	$materialDefaults = [
		'material_type' => old('material_type', $existingEnvelope->envelope_material_name ?? ''),
		'material_id' => old('envelope_material_id', $existingEnvelope->material_id ?? ''),
		'max_qty' => old('max_qty', $existingBulkOrder?->min_qty ?? $existingEnvelope->max_qty ?? ''),
		'max_quantity' => old('max_quantity', $existingBulkOrder?->max_qty ?? $existingEnvelope->max_quantity ?? ''),
	];
@endphp

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
                Giveaway Details
            </button>
        </li>
    </ol>
</nav>

{{-- Page Title --}}
<h1 id="page-title">Templates</h1>

<form method="POST" action="{{ route('admin.products.store') }}" id="giveaway-form" enctype="multipart/form-data">
    @csrf
    @if($isEditing)
        <input type="hidden" id="product_id" name="product_id" value="{{ $product->id }}">
    @endif
    <input type="hidden" id="template_id" name="template_id" value="">
    <input type="hidden" name="productType" value="Giveaway">

    <div class="invitation-container">
        {{-- Progress Bar --}}
        <div class="progress-bar">
            <div class="progress-fill" id="progress-fill"></div>
        </div>

        {{-- Page 1: Templates --}}
        @include('admin.products.templates')

        {{-- Page 2: Giveaway Details --}}
        <div class="page page2" data-page="1" style="display: none;">
            <div class="error-summary" style="display: none;" role="alert" aria-live="polite">
                <h3>Please correct the following errors:</h3>
                <ul id="error-list-page2"></ul>
            </div>

            <div class="form-section">
                <h2><i class="fas fa-gift"></i> Giveaway Details</h2>

                <div class="responsive-grid grid-2-cols">
                    <div class="field">
                        <label for="invitationName">Giveaway Name *</label>
                        <input type="text"
                               id="invitationName"
                               name="invitationName"
                               placeholder="Giveaway Name * (e.g. Scented Candle Favor)"
                               required
                               aria-required="true"
                               aria-describedby="invitationName-error"
                               value="{{ $defaults['name'] }}">
                        <span id="invitationName-error" class="error-message" role="alert" aria-live="polite"></span>
                        @error('invitationName') <span class="error-message">{{ $message }}</span> @enderror
                    </div>
                    <div class="field">
                        <label for="eventType">Event Type *</label>
                        <select id="eventType" name="eventType" required aria-required="true" aria-describedby="eventType-error">
                            <option value="" disabled {{ $defaults['event_type'] ? '' : 'selected' }}>Event Type *</option>
                            @foreach(['Wedding', 'Birthday', 'Baptism', 'Corporate', 'Debut', 'Holiday'] as $type)
                                <option value="{{ $type }}" {{ $defaults['event_type'] === $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                        <span id="eventType-error" class="error-message" role="alert"></span>
                        @error('eventType') <span class="error-message">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="responsive-grid grid-2-cols">
                    <div class="field">
                        <label for="material_type">Material *</label>
                        <select id="material_type"
                                name="material_type"
                                required
                                aria-required="true"
                                aria-describedby="material_type-error">
                            <option value="" disabled {{ $materialDefaults['material_type'] ? '' : 'selected' }}>Select Material Type *</option>
                            @foreach($materialTypes as $materialType)
                                <option value="{{ $materialType }}" {{ $materialDefaults['material_type'] === $materialType ? 'selected' : '' }}>
                                    {{ $materialType }}
                                </option>
                            @endforeach
                        </select>
                        <span id="material_type-error" class="error-message" role="alert"></span>
                        @error('material_type') <span class="error-message">{{ $message }}</span> @enderror
                    </div>
                    <div class="field">
                        <label for="envelope_material_id">Giveaway Material Name *</label>
                        <select id="envelope_material_id"
                                name="envelope_material_id"
                                required
                                aria-required="true"
                                aria-describedby="envelope_material_id-error">
                            <option value="" disabled {{ $materialDefaults['material_id'] ? '' : 'selected' }}>Select Giveaway Material *</option>
                            @foreach($materialOptions as $material)
                                <option value="{{ $material->material_id }}"
                                        data-material-type="{{ $material->material_type }}"
                                        data-unit-cost="{{ $material->unit_cost ?? '' }}"
                                    {{ (string) $materialDefaults['material_id'] === (string) $material->material_id ? 'selected' : '' }}>
                                    {{ $material->material_name }}
                                </option>
                            @endforeach
                        </select>
                        <span id="envelope_material_id-error" class="error-message" role="alert"></span>
                        @error('envelope_material_id') <span class="error-message">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="responsive-grid grid-2-cols">
                    <div class="field">
                        <label for="basePrice">Base Price</label>
                        <input type="number"
                               step="0.01"
                               min="0"
                               id="basePrice"
                               name="base_price"
                               placeholder="Base Price (e.g. 150.00)"
                               aria-describedby="basePrice-error"
                               value="{{ $defaults['base_price'] }}">
                        <span id="basePrice-error" class="error-message"></span>
                        @error('base_price') <span class="error-message">{{ $message }}</span> @enderror
                    </div>
                    <div class="field">
                        <label for="leadTime">Production Lead Time</label>
                        <input type="text"
                               id="leadTime"
                               name="lead_time"
                               placeholder="Lead Time (e.g. 7-10 days)"
                               aria-describedby="leadTime-error"
                               value="{{ $defaults['lead_time'] }}">
                        <span id="leadTime-error" class="error-message"></span>
                        @error('lead_time') <span class="error-message">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="responsive-grid grid-2-cols">
                    <div class="field">
                        <label for="max_qty">Max Qty *</label>
                        <input type="number"
                               id="max_qty"
                               name="max_qty"
                               min="1"
                               placeholder="Max Qty"
                               required
                               aria-required="true"
                               aria-describedby="max_qty-error"
                               value="{{ $materialDefaults['max_qty'] }}">
                        <span id="max_qty-error" class="error-message"></span>
                        @error('max_qty') <span class="error-message">{{ $message }}</span> @enderror
                    </div>
                    <div class="field">
                        <label for="max_quantity">Maximum Quantity *</label>
                        <input type="number"
                               id="max_quantity"
                               name="max_quantity"
                               min="1"
                               placeholder="Maximum Quantity"
                               required
                               aria-required="true"
                               aria-describedby="max_quantity-error"
                               value="{{ $materialDefaults['max_quantity'] }}">
                        <span id="max_quantity-error" class="error-message"></span>
                        @error('max_quantity') <span class="error-message">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="field">
                    <label for="dateAvailable">Date Available</label>
                    <input type="date"
                           id="dateAvailable"
                           name="date_available"
                           aria-describedby="dateAvailable-error"
                           value="{{ $defaults['date_available'] }}">
                    <span id="dateAvailable-error" class="error-message"></span>
                    @error('date_available') <span class="error-message">{{ $message }}</span> @enderror
                </div>

                {{-- Template Preview Section --}}
                <div class="field">
                    <label>Selected Template Preview</label>
                    <div id="template-preview" class="template-preview-container">
                        <div class="template-preview-placeholder">
                            <i class="fas fa-image"></i>
                            <p>Select a template to see preview</p>
                        </div>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn-save" id="submit-btn">
                        <span class="btn-text">{{ $isEditing ? 'Update Giveaways' : 'Create Giveaways' }}</span>
                        <span class="loading-spinner" style="display: none;"><i class="fas fa-spinner fa-spin"></i> Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
	document.addEventListener('DOMContentLoaded', function () {
		const materialSelect = document.getElementById('envelope_material_id');
		const materialTypeSelect = document.getElementById('material_type');
		const basePriceInput = document.getElementById('basePrice');
		const leadTimeInput = document.getElementById('leadTime');
		const dateAvailableInput = document.getElementById('dateAvailable');
		if (!materialSelect || !basePriceInput) {
			return;
		}

		const hasInitialBasePrice = basePriceInput.value.trim() !== '';
		const hasInitialDateAvailable = dateAvailableInput && dateAvailableInput.value.trim() !== '';

		function applyMaterial(option, forceUpdate = false) {
			if (!option) {
				return;
			}

			const materialType = option.dataset.materialType;
			const unitCost = option.dataset.unitCost;

			if (materialTypeSelect && materialType) {
				materialTypeSelect.value = materialType;
			}

			if (unitCost !== undefined && unitCost !== '') {
				const parsed = parseFloat(unitCost);
				if (Number.isFinite(parsed) && (forceUpdate || !basePriceInput.value.trim())) {
					basePriceInput.value = parsed.toFixed(2);
				}
			}
		}

		materialSelect.addEventListener('change', function () {
			const selected = materialSelect.selectedOptions[0];
			applyMaterial(selected, true);
		});

		applyMaterial(materialSelect.selectedOptions[0], !hasInitialBasePrice);

		function parseLeadTimeDays(value) {
			if (!value) {
				return null;
			}
			const matches = String(value).match(/\d+/g);
			if (!matches || !matches.length) {
				return null;
			}
			const numbers = matches
				.map(num => parseInt(num, 10))
				.filter(num => Number.isFinite(num) && num >= 0);
			if (!numbers.length) {
				return null;
			}
			return Math.max(...numbers);
		}

		function updateDateAvailable(forceUpdate = false) {
			if (!leadTimeInput || !dateAvailableInput) {
				return;
			}

			const days = parseLeadTimeDays(leadTimeInput.value);
			if (!Number.isFinite(days)) {
				if (forceUpdate) {
					dateAvailableInput.value = '';
				}
				return;
			}

			const targetDate = new Date();
			targetDate.setHours(0, 0, 0, 0);
			targetDate.setDate(targetDate.getDate() + days);
			const formatted = targetDate.toISOString().split('T')[0];
			dateAvailableInput.value = formatted;
		}

		if (leadTimeInput && dateAvailableInput) {
			leadTimeInput.addEventListener('input', function () {
				updateDateAvailable(true);
			});

			if (!hasInitialDateAvailable) {
				updateDateAvailable(false);
			}
		}
	});

	// Function to update template preview images
	function updatePreviewImages() {
		const templateId = document.getElementById('template_id').value;
		const previewContainer = document.getElementById('template-preview');

		if (!templateId) {
			previewContainer.innerHTML = `
				<div class="template-preview-placeholder">
					<i class="fas fa-image"></i>
					<p>Select a template to see preview</p>
				</div>
			`;
			return;
		}

		// Find the selected template button to get its data
		const templateBtn = document.querySelector(`.continue-btn[data-template-id="${templateId}"]`);
		if (!templateBtn) {
			previewContainer.innerHTML = `
				<div class="template-preview-placeholder">
					<i class="fas fa-exclamation-triangle"></i>
					<p>Template data not found</p>
				</div>
			`;
			return;
		}

		const templateName = templateBtn.dataset.templateName;
		const frontImageUrl = templateBtn.dataset.frontUrl || templateBtn.dataset.templatePreview;

		let previewHtml = `<div class="template-preview-content">`;

		if (frontImageUrl) {
			previewHtml += `
				<div class="template-preview-image">
					<img src="${frontImageUrl}" alt="${templateName}" style="max-width: 200px; max-height: 200px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
				</div>
			`;
		} else {
			previewHtml += `
				<div class="template-preview-placeholder">
					<i class="fas fa-image"></i>
					<p>No preview available</p>
				</div>
			`;
		}

		previewHtml += `
			<div class="template-preview-info">
				<h4>${templateName}</h4>
			</div>
		</div>`;

		previewContainer.innerHTML = previewHtml;
	}

	// Initialize preview on page load
	document.addEventListener('DOMContentLoaded', function() {
		updatePreviewImages();
	});
</script>

<style>
.template-preview-container {
	min-height: 120px;
	border: 2px dashed #e2e8f0;
	border-radius: 8px;
	padding: 16px;
	background: #f8fafc;
	display: flex;
	align-items: center;
	justify-content: center;
}

.template-preview-placeholder {
	text-align: center;
	color: #64748b;
}

.template-preview-placeholder i {
	font-size: 2rem;
	margin-bottom: 8px;
	display: block;
}

.template-preview-placeholder p {
	margin: 0;
	font-size: 0.9rem;
}

.template-preview-content {
	display: flex;
	align-items: center;
	gap: 16px;
	width: 100%;
}

.template-preview-image {
	flex-shrink: 0;
}

.template-preview-info {
	flex: 1;
}

.template-preview-info h4 {
	margin: 0 0 8px 0;
	font-size: 1.1rem;
	font-weight: 600;
	color: #1e293b;
}
</style>

@endsection
