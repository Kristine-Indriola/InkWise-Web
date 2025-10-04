<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Design - Inkwise</title>
    <link rel="stylesheet" href="{{ asset('css/customer/editing.css') }}">
    <script src="{{ asset('js/customer/editing.js') }}" defer></script>
</head>
<body>
@php
    $selectedProduct = $product ?? null;
    $productName = $selectedProduct->name ?? 'Custom Invitation';
    $productTheme = $selectedProduct->theme_style ?? 'Personalized theme';
    $frontPreview = $frontImage ?? asset('images/placeholder.png');
    $backPreview = $backImage ?? $frontPreview;
    $presetQuantity = $defaultQuantity ?? 50;
@endphp

    <!-- TOP BAR -->
    <div class="topbar">
        <div class="left-tools">
            <button class="save-btn" type="button">Save</button>
            <button class="undo-btn" type="button">↶ Undo</button>
            <button class="redo-btn" type="button">↷ Redo</button>
        </div>
        <div class="right-tools">
            <a href="{{ route('templates.wedding.invitations') }}" class="change-template">Change template</a>
            <button class="preview-btn" type="button">Preview</button>
            <form method="POST" action="{{ route('order.cart.add') }}" class="next-form">
                @csrf
                <input type="hidden" name="product_id" value="{{ $selectedProduct?->id }}">
                <input type="hidden" name="quantity" value="{{ $presetQuantity }}">
                <button type="submit" class="next-btn" @if(!$selectedProduct) disabled @endif>
                    Next
                </button>
            </form>
        </div>
    </div>

    <div class="editor-container" data-product-id="{{ $selectedProduct?->id }}">
        <!-- LEFT SIDEBAR -->
        <div class="sidebar">
            <button class="side-btn active" type="button">Text</button>
            <button class="side-btn" type="button">Images</button>
            <button class="side-btn" type="button">Graphics</button>
            <button class="side-btn" type="button">Tables</button>
            <button class="side-btn" type="button">Design color</button>
        </div>

        <!-- MIDDLE CANVAS -->
        <div class="canvas-area">
            <div class="canvas">
                <div class="safety-area">Safety Area</div>
                <div class="bleed-line">Bleed</div>
                <img id="cardFront" src="{{ $frontPreview }}" class="card active" alt="Front design preview">
                <img id="cardBack" src="{{ $backPreview }}" class="card" alt="Back design preview">
            </div>
            <div class="zoom-controls">
                <button id="zoomOut" type="button">-</button>
                <span id="zoomLevel">100%</span>
                <button id="zoomIn" type="button">+</button>
            </div>
        </div>

        <!-- RIGHT PANEL (Front/Back toggle + text fields) -->
        <div class="right-panel">
            <div class="product-summary">
                <h2>{{ $productName }}</h2>
                <p>{{ $productTheme }}</p>
                <p class="summary-note">Quantity preset: {{ $presetQuantity }} invitations</p>
            </div>

            <div class="view-toggle">
                <button id="showFront" class="active" type="button">Front</button>
                <button id="showBack" type="button">Back</button>
            </div>

            <div class="text-editor">
                <h3>Placeholder text</h3>
                <div id="textFields">
                    <div class="text-field">
                        <input type="text" value="06.28.26">
                        <button class="delete-text" type="button">🗑</button>
                    </div>
                    <div class="text-field">
                        <input type="text" value="SAVE">
                        <button class="delete-text" type="button">🗑</button>
                    </div>
                    <div class="text-field">
                        <input type="text" value="DATE">
                        <button class="delete-text" type="button">🗑</button>
                    </div>
                    <div class="text-field">
                        <input type="text" value="KENDRA AND ANDREW">
                        <button class="delete-text" type="button">🗑</button>
                    </div>
                    <div class="text-field">
                        <input type="text" value="BROOKLYN, NY">
                        <button class="delete-text" type="button">🗑</button>
                    </div>
                </div>
                <button id="addTextField" class="add-btn" type="button">+ New Text Field</button>
            </div>
        </div>
    </div>

    <script>
        window.sessionStorage.removeItem('inkwise-finalstep');
    </script>
</body>
</html>
