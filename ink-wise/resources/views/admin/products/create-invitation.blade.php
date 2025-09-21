{{-- filepath: c:\xampp\htdocs\InkWise-Web\ink-wise\resources\views\admin\products\create-invitation.blade.php --}}
@extends('layouts.admin')

@section('title', 'Create Invitation Product')

<link rel="stylesheet" href="{{ asset('css/admin-css/create_invite.css') }}">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

@section('content')
{{-- Breadcrumb Navigation --}}
<nav aria-label="breadcrumb" class="breadcrumb-nav" style="margin-bottom: 24px;">
    <ol class="breadcrumb" style="display: flex; list-style: none; padding: 0; background: none;">
        <li style="display: flex; align-items: center;">
            <a href="{{ route('admin.dashboard') }}" style="color: #7cb18f; text-decoration: none;" aria-label="Go to Dashboard">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <span style="margin: 0 8px; color: #bdbdbd;" aria-hidden="true">›</span>
        </li>
        <li style="display: flex; align-items: center;">
            <a href="{{ route('admin.products.index') }}" style="color: #7cb18f; text-decoration: none;" aria-label="Go to Products">
                Products
            </a>
            <span style="margin: 0 8px; color: #bdbdbd;" aria-hidden="true">›</span>
        </li>
        <li style="display: flex; align-items: center;">
            <span id="breadcrumb-page" style="color: #5a6e8c;" aria-live="polite">
                Basic Info
            </span>
        </li>
    </ol>
</nav>

<form method="POST" action="{{ route('admin.products.store') }}">
    @csrf
<h1 style="text-align: left; color: #2a2a2a; margin-bottom: 20px; font-family: 'Poppins', sans-serif;">Create Invitation Product</h1>

<div class="invitation-container">
    
    <div>
        {{-- Page 1 --}}
        <div class="page page1" data-page="1">
            {{-- Error Summary --}}
            <div class="error-summary" style="display: none;" role="alert" aria-live="polite">
                <h3>Please correct the following errors:</h3>
                <ul id="error-list-page1"></ul>
            </div>
            {{-- STEP 1: Basic Info --}}
            <div class="form-section">
                <h2>Basic Information</h2>
                <div class="responsive-grid grid-2-cols">
                    <div class="field">
                        <label for="invitationName">Invitation Name *</label>
                        <input type="text" id="invitationName" name="invitationName" placeholder="Invitation Name * (e.g. Elegant Pearl Wedding Invitation)" required aria-required="true" aria-describedby="invitationName-error">
                        <span id="invitationName-error" class="error-message" role="alert"></span>
                        @error('invitationName') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="field">
                        <label for="eventType">Event Type *</label>
                        <select id="eventType" name="eventType" required aria-required="true" aria-describedby="eventType-error">
                            <option disabled selected>Event Type *</option>
                            @foreach(['Wedding', 'Birthday', 'Baptism', 'Corporate'] as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                        <span id="eventType-error" class="error-message" role="alert"></span>
                        @error('eventType') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="responsive-grid grid-2-cols">
                    <div class="field">
                        <label for="productType">Product Type *</label>
                        <select id="productType" name="productType" required aria-required="true" aria-describedby="productType-error">
                            <option disabled selected>Product Type *</option>
                            <option value="Invitation">Invitation</option>
                            <option value="Giveaway">Giveaway</option>
                        </select>
                        <span id="productType-error" class="error-message" role="alert"></span>
                        @error('productType') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="field">
                        <label for="themeStyle">Theme / Style *</label>
                        <input type="text" id="themeStyle" name="themeStyle" placeholder="Theme / Style * (e.g. Luxury, Minimalist, Floral)" required aria-required="true" aria-describedby="themeStyle-error">
                        <span id="themeStyle-error" class="error-message" role="alert"></span>
                        @error('themeStyle') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            {{-- STEP 2: Materials --}}
            <div class="form-section">
                <h2>Materials</h2>
                <div class="material-group">
                    <h3>Materials</h3>
                    <div class="material-rows">
                        <div class="material-row">
                            <div class="input-row">
                                <div class="field">
                                    <div class="input-container">
                                        <label for="materials_0_item">Item</label>
                                        <select id="materials_0_item" name="materials[0][item]" aria-describedby="materials_0_item-error">
                                            <option disabled selected>Item</option>
                                            <option value="Paper/Cardstock">Paper/Cardstock</option>
                                            <option value="Envelope">Envelope</option>
                                            <!-- Add more options as needed -->
                                        </select>
                                        <span id="materials_0_item-error" class="error-message" role="alert"></span>
                                    </div>
                                </div>
                                <div class="field">
                                    <div class="input-container">
                                        <label for="materials_0_type">Type</label>
                                        <input type="text" id="materials_0_type" name="materials[0][type]" placeholder="Type" aria-describedby="materials_0_type-error">
                                        <span id="materials_0_type-error" class="error-message" role="alert"></span>
                                    </div>
                                </div>
                                <div class="field">
                                    <div class="input-container">
                                        <label for="materials_0_color">Color</label>
                                        <input type="text" id="materials_0_color" name="materials[0][color]" placeholder="Color" aria-describedby="materials_0_color-error">
                                        <span id="materials_0_color-error" class="error-message" role="alert"></span>
                                    </div>
                                </div>
                                <div class="field">
                                    <div class="input-container">
                                        <label for="materials_0_weight">Weight (GSM)</label>
                                        <input type="number" id="materials_0_weight" name="materials[0][weight]" placeholder="Weight (GSM)" aria-describedby="materials_0_weight-error">
                                        <span id="materials_0_weight-error" class="error-message" role="alert"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="input-row">
                                <div class="field">
                                    <div class="input-container">
                                        <label for="materials_0_unitPrice">Unit Price</label>
                                        <input type="number" id="materials_0_unitPrice" name="materials[0][unitPrice]" readonly placeholder="Unit Price" aria-describedby="materials_0_unitPrice-error">
                                        <span id="materials_0_unitPrice-error" class="error-message" role="alert"></span>
                                    </div>
                                </div>
                                <div class="field">
                                    <div class="input-container">
                                        <label for="materials_0_qty">Qty</label>
                                        <input type="number" id="materials_0_qty" name="materials[0][qty]" placeholder="Qty" aria-describedby="materials_0_qty-error">
                                        <span id="materials_0_qty-error" class="error-message" role="alert"></span>
                                    </div>
                                </div>
                                <div class="field">
                                    <div class="input-container">
                                        <label for="materials_0_cost">Cost</label>
                                        <input type="number" id="materials_0_cost" name="materials[0][cost]" readonly placeholder="Cost" aria-describedby="materials_0_cost-error">
                                        <span id="materials_0_cost-error" class="error-message" role="alert"></span>
                                    </div>
                                </div>
                                <button class="add-row" type="button" aria-label="Add another material row">+</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="material-group">
                    <h3>Ink</h3>
                    <div class="material-rows">
                        <div class="material-row">
                            <div class="input-row">
                                <div class="field">
                                    <div class="input-container">
                                        <label for="inks_0_item">Item</label>
                                        <select id="inks_0_item" name="inks[0][item]" aria-describedby="inks_0_item-error">
                                            <option disabled selected>Item</option>
                                            <option value="Ink 1">Ink 1</option>
                                            <option value="Ink 2">Ink 2</option>
                                            <!-- Add more options as needed -->
                                        </select>
                                        <span id="inks_0_item-error" class="error-message" role="alert"></span>
                                    </div>
                                </div>
                                <div class="field">
                                    <div class="input-container">
                                        <label for="inks_0_type">Type</label>
                                        <input type="text" id="inks_0_type" name="inks[0][type]" placeholder="Type" aria-describedby="inks_0_type-error">
                                        <span id="inks_0_type-error" class="error-message" role="alert"></span>
                                    </div>
                                </div>
                                <div class="field">
                                    <div class="input-container">
                                        <label for="inks_0_usage">Usage per invite (ml)</label>
                                        <input type="number" id="inks_0_usage" name="inks[0][usage]" placeholder="Usage per invite (ml)" aria-describedby="inks_0_usage-error">
                                        <span id="inks_0_usage-error" class="error-message" role="alert"></span>
                                    </div>
                                </div>
                                <div class="field">
                                    <div class="input-container">
                                        <label for="inks_0_costPerMl">Cost per ml</label>
                                        <input type="number" id="inks_0_costPerMl" name="inks[0][costPerMl]" readonly placeholder="Cost per ml" aria-describedby="inks_0_costPerMl-error">
                                        <span id="inks_0_costPerMl-error" class="error-message" role="alert"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="input-row">
                                <div class="field">
                                    <div class="input-container">
                                        <label for="inks_0_totalCost">Total Cost</label>
                                        <input type="number" id="inks_0_totalCost" name="inks[0][totalCost]" readonly placeholder="Total Cost" aria-describedby="inks_0_totalCost-error">
                                        <span id="inks_0_totalCost-error" class="error-message" role="alert"></span>
                                    </div>
                                </div>
                                <button class="add-row" type="button" aria-label="Add another ink row">+</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- STEP 3: Description --}}
            <div class="form-section">
                <h2>Description</h2>
                <div class="editor-toolbar">
                    <button type="button" class="editor-btn" data-command="bold" aria-label="Bold text"><i class="fas fa-bold"></i></button>
                    <button type="button" class="editor-btn" data-command="italic" aria-label="Italic text"><i class="fas fa-italic"></i></button>
                    <button type="button" class="editor-btn" data-command="underline" aria-label="Underline text"><i class="fas fa-underline"></i></button>
                    <button type="button" class="editor-btn" data-command="redo" aria-label="Redo"><i class="fas fa-redo"></i></button>
                </div>
                <textarea id="description" name="description" class="editor-content" style="display:none;" aria-describedby="description-error"></textarea>
                <div contenteditable="true" class="editor-content" id="description-editor" aria-label="Description editor" aria-describedby="description-error"></div>
                <span id="description-error" class="error-message" role="alert"></span>
            </div>

            {{-- Buttons for Page 1 --}}
            <div class="form-buttons">
                <button type="button" class="btn-cancel" title="Cancel and return to products list">Cancel</button>
                <button type="button" class="continue-btn" title="Proceed to next step">Continue</button>
            </div>
        </div>

        {{-- Page 2 --}}
        <div class="page page2" data-page="2" style="display: none;">
            {{-- Error Summary --}}
            <div class="error-summary" style="display: none;" role="alert" aria-live="polite">
                <h3>Please correct the following errors:</h3>
                <ul id="error-list-page2"></ul>
            </div>
            {{-- STEP 4: Customization --}}
            <div class="form-section">
                <h2>Customization Options</h2>
                <div class="responsive-grid grid-2-cols">
                    <div class="field">
                        <label for="colorOptions">Color Options</label>
                        <select id="colorOptions" name="colorOptions" aria-describedby="colorOptions-error">
                            <option disabled selected>Color Options</option>
                            <option value="Gold">Gold</option>
                            <option value="White">White</option>
                            <option value="Silver">Silver</option>
                            <!-- Add more as needed -->
                        </select>
                        <span id="colorOptions-error" class="error-message" role="alert"></span>
                    </div>
                    <div class="field">
                        <label for="paperOptions">Paper Options</label>
                        <select id="paperOptions" name="paperOptions" aria-describedby="paperOptions-error">
                            <option disabled selected>Paper Options</option>
                            <option value="Pearl">Pearl</option>
                            <option value="Matte">Matte</option>
                            <option value="Glossy">Glossy</option>
                        </select>
                        <span id="paperOptions-error" class="error-message" role="alert"></span>
                    </div>
                    <div class="field">
                        <label for="envelopeOptions">Envelope Options</label>
                        <select id="envelopeOptions" name="envelopeOptions" aria-describedby="envelopeOptions-error">
                            <option disabled selected>Envelope Options</option>
                            <option value="Kraft">Kraft</option>
                            <option value="Metallic">Metallic</option>
                            <option value="Luxury">Luxury</option>
                        </select>
                        <span id="envelopeOptions-error" class="error-message" role="alert"></span>
                    </div>
                    <div class="field">
                        <label for="minOrderQty">Minimum Order Quantity</label>
                        <input type="number" id="minOrderQty" name="minOrderQty" placeholder="Minimum Order Quantity (e.g., 50 pcs)" aria-describedby="minOrderQty-error">
                        <span id="minOrderQty-error" class="error-message" role="alert"></span>
                    </div>
                    <div class="field" class="grid-span-2">
                        <label for="bulkPricing">Bulk Pricing Tiers</label>
                        <textarea id="bulkPricing" name="bulkPricing" placeholder="Bulk Pricing Tiers
    50 pcs = ₱xx each
    100 pcs = ₱xx each
    200 pcs = ₱xx each" aria-describedby="bulkPricing-error"></textarea>
                        <span id="bulkPricing-error" class="error-message" role="alert"></span>
                    </div>
                </div>
            </div>

            {{-- STEP 5: Upload Images --}}
            <div class="form-section">
                <h2>Upload Images</h2>
                <div class="field">
                    <label for="images">Upload Images</label>
                    <input type="file" id="images" name="images[]" multiple accept="image/*" placeholder="Upload Images" aria-describedby="images-error">
                    <span id="images-error" class="error-message" role="alert"></span>
                </div>
                <!-- Image Preview -->
                <div id="image-preview" class="image-preview" style="display: none;">
                    <h3>Image Previews:</h3>
                    <div id="preview-container" class="preview-container"></div>
                </div>
                <!-- Sample Image -->
                <div class="sample-image">
                    <p>Sample Image:</p>
                    <img src="{{ asset('customerimages/image/invite1.png') }}" alt="Sample Invitation" style="max-width: 200px; border: 1px solid #ddd; padding: 5px;" aria-describedby="sample-image-desc">
                    <span id="sample-image-desc" style="display: none;">This is a sample image of an invitation for reference.</span>
                </div>
            </div>

            {{-- Buttons for Page 2 --}}
            <div class="form-buttons">
                <button type="button" class="btn-back" title="Go back to previous step">Back</button>
                <button type="button" class="continue-btn" title="Proceed to next step">Continue</button>
            </div>
        </div>

        {{-- Page 3 --}}
        <div class="page page3" data-page="3" style="display: none;">
            {{-- Error Summary --}}
            <div class="error-summary" style="display: none;" role="alert" aria-live="polite">
                <h3>Please correct the following errors:</h3>
                <ul id="error-list-page3"></ul>
            </div>
            {{-- STEP 6: Production --}}
            <div class="form-section">
                <h2>Production Details</h2>
                <div class="responsive-grid grid-2-cols">
                    <div class="field">
                        <label for="leadTime">Lead Time / Production Days</label>
                        <input type="text" id="leadTime" name="leadTime" placeholder="Lead Time / Production Days (e.g., 5–7 working days)" aria-describedby="leadTime-error">
                        <span id="leadTime-error" class="error-message" role="alert"></span>
                    </div>
                    <div class="field">
                        <label for="stockAvailability">Stock Availability</label>
                        <input type="text" id="stockAvailability" name="stockAvailability" placeholder="Stock Availability (if limited)" aria-describedby="stockAvailability-error">
                        <span id="stockAvailability-error" class="error-message" role="alert"></span>
                    </div>
                </div>
            </div>

            {{-- STEP 7: Costing --}}
            <div class="form-section">
                <h2>Costing</h2>
                <div class="responsive-grid grid-2-cols">
                    <div class="field">
                        <label for="totalRawCost">Total Raw Material Cost (₱)</label>
                        <input type="number" readonly id="totalRawCost" name="totalRawCost" placeholder="Total Raw Material Cost (₱)" aria-describedby="totalRawCost-error">
                        <span id="totalRawCost-error" class="error-message" role="alert"></span>
                    </div>
                    <div class="field">
                        <label for="quantityOrdered">Quantity Ordered</label>
                        <input type="number" id="quantityOrdered" name="quantityOrdered" value="100" placeholder="Quantity Ordered (admin sets default batch size, e.g., 100 pcs)" aria-describedby="quantityOrdered-error">
                        <span id="quantityOrdered-error" class="error-message" role="alert"></span>
                    </div>
                    <div class="field">
                        <label for="costPerInvite">Cost per Invitation (₱)</label>
                        <input type="number" readonly id="costPerInvite" name="costPerInvite" placeholder="Cost per Invitation (₱)" aria-describedby="costPerInvite-error">
                        <span id="costPerInvite-error" class="error-message" role="alert"></span>
                    </div>
                    <div class="field">
                        <label for="markup">Markup %</label>
                        <select id="markup" name="markup" aria-describedby="markup-error">
                            <option disabled selected>Markup %</option>
                            <option value="50">50%</option>
                            <option value="100">100%</option>
                            <option value="150">150%</option>
                        </select>
                        <span id="markup-error" class="error-message" role="alert"></span>
                    </div>
                    <div class="field">
                        <label for="sellingPrice">Selling Price per Invitation (₱)</label>
                        <input type="number" readonly id="sellingPrice" name="sellingPrice" placeholder="Selling Price per Invitation (₱)" aria-describedby="sellingPrice-error">
                        <span id="sellingPrice-error" class="error-message" role="alert"></span>
                    </div>
                    <div class="field">
                        <label for="totalSellingPrice">Total Selling Price (₱)</label>
                        <input type="number" readonly id="totalSellingPrice" name="totalSellingPrice" placeholder="Total Selling Price (₱)" aria-describedby="totalSellingPrice-error">
                        <span id="totalSellingPrice-error" class="error-message" role="alert"></span>
                    </div>
                </div>
            </div>

            {{-- Buttons for Page 3 --}}
            <div class="form-buttons">
                <button type="button" class="btn-back" title="Go back to previous step">Back</button>
                <button type="button" class="btn-draft" title="Save as draft for later editing">Draft</button>
                <button type="button" class="btn-cancel" title="Cancel and return to products list">Cancel</button>
                <button type="submit" class="btn-save" title="Save and publish to customer page" id="submit-btn">
                    <span class="btn-text">Show in Customer Page</span>
                    <span class="loading-spinner" style="display: none;"><i class="fas fa-spinner fa-spin"></i> Saving...</span>
                </button>
            </div>
        </div>
    </div>
</div>
</form>

{{-- JS --}}
<script src="{{ asset('js/admin-css/create_invite.js') }}"></script>

<script>
    // Breadcrumb page update by step
    document.addEventListener('DOMContentLoaded', function() {
        const breadcrumbPage = document.getElementById('breadcrumb-page');
        const pages = [
            'Basic Info',
            'Customization & Images',
            'Production & Costing'
        ];
        const pageDivs = document.querySelectorAll('.page');
        let currentPageIndex = 0;

        function updateBreadcrumb() {
            const currentPageDiv = pageDivs[currentPageIndex];
            const pageNumber = currentPageDiv.getAttribute('data-page');
            breadcrumbPage.textContent = pages[pageNumber - 1];
        }

        function showPage(index) {
            pageDivs.forEach((div, i) => {
                div.style.display = i === index ? '' : 'none';
            });
            currentPageIndex = index;
            updateBreadcrumb();
        }

        // Navigation logic
        document.querySelectorAll('.continue-btn').forEach((btn) => {
            btn.addEventListener('click', function() {
                if (currentPageIndex < pageDivs.length - 1) {
                    showPage(currentPageIndex + 1);
                }
            });
        });
        document.querySelectorAll('.btn-back').forEach((btn) => {
            btn.addEventListener('click', function() {
                if (currentPageIndex > 0) {
                    showPage(currentPageIndex - 1);
                }
            });
        });

        // Initial setup
        showPage(0);
    });
</script>
@endsection
