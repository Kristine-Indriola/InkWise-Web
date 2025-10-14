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
    $imageSlotMap = collect($imageSlots ?? [])->keyBy('side');
    $frontSlot = $imageSlotMap->get('front');
    $backSlot = $imageSlotMap->get('back');
    $frontDefault = isset($frontSlot['default']) && $frontSlot['default'] !== ''
        ? asset($frontSlot['default'])
        : asset('images/placeholder.png');
    $backDefault = isset($backSlot['default']) && $backSlot['default'] !== ''
        ? asset($backSlot['default'])
        : $frontDefault;
    $frontPreview = $frontImage ?? $frontDefault;
    $backPreview = $backImage ?? $backDefault;
    $presetQuantity = $defaultQuantity ?? 50;
    $frontSvg = $frontSvg ?? null;
    $backSvg = $backSvg ?? null;
    $providedPresets = $textFieldPresets ?? [];
    if (empty($providedPresets)) {
        $providedPresets = [
            [
                'node' => 'front-date',
                'placeholder' => '06.28.26',
                'side' => 'front',
                'top' => 18,
                'left' => 50,
                'align' => 'center',
                'font_size' => 28,
            ],
            [
                'node' => 'front-save',
                'placeholder' => 'SAVE',
                'side' => 'front',
                'top' => 28,
                'left' => 50,
                'align' => 'center',
                'font_size' => 32,
                'letter_spacing' => 0.35,
            ],
            [
                'node' => 'front-dateword',
                'placeholder' => 'DATE',
                'side' => 'front',
                'top' => 36,
                'left' => 50,
                'align' => 'center',
                'font_size' => 16,
                'letter_spacing' => 0.5,
            ],
            [
                'node' => 'front-names',
                'placeholder' => 'KENDRA AND ANDREW',
                'side' => 'front',
                'top' => 48,
                'left' => 50,
                'align' => 'center',
                'font_size' => 22,
            ],
            [
                'node' => 'front-location',
                'placeholder' => 'BROOKLYN, NY',
                'side' => 'front',
                'top' => 58,
                'left' => 50,
                'align' => 'center',
                'font_size' => 16,
                'letter_spacing' => 0.1,
            ],
            [
                'node' => 'back-heading',
                'placeholder' => 'RECEPTION DETAILS',
                'side' => 'back',
                'top' => 26,
                'left' => 50,
                'align' => 'center',
                'font_size' => 26,
            ],
            [
                'node' => 'back-body',
                'placeholder' => "Join us for dinner at seven o'clock in the evening.\nThe Foundry, Long Island City.",
                'side' => 'back',
                'top' => 43,
                'left' => 50,
                'align' => 'center',
                'font_size' => 16,
                'letter_spacing' => 0.05,
            ],
        ];
    }

    $textFieldPresets = collect($providedPresets)->map(function ($field) {
        $default = $field['default'] ?? ($field['value'] ?? '');
        return [
            'node' => $field['node'] ?? uniqid('field-', true),
            'value' => $field['value'] ?? $default,
            'default' => $default,
            'placeholder' => $field['placeholder'] ?? '',
            'side' => $field['side'] ?? 'front',
            'top' => $field['top'] ?? 0,
            'left' => $field['left'] ?? 50,
            'align' => $field['align'] ?? 'center',
            'font_size' => $field['font_size'] ?? null,
            'letter_spacing' => $field['letter_spacing'] ?? null,
        ];
    })->toArray();
@endphp

    <!-- TOP BAR -->
    <div class="editor-topbar">
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
            <button class="side-btn active" type="button" data-panel="text">Text</button>
            <button class="side-btn" type="button" data-panel="images">Images</button>
            <button class="side-btn" type="button" data-panel="graphics">Graphics</button>
            <button class="side-btn" type="button" data-panel="tables">Tables</button>
            <button class="side-btn" type="button" data-panel="colors">Design color</button>
        </div>

        <!-- MIDDLE CANVAS -->
        <div class="canvas-area">
            <div class="canvas">
                <div class="safety-area">Safety Area</div>
                <div class="bleed-line">Bleed</div>
                <div id="cardFront" class="card active" data-card="front" data-default-image="{{ $frontPreview }}" role="img" aria-label="Front design preview">
                    @if(!empty($frontSvg))
                        {!! $frontSvg !!}
                    @else
                        <svg viewBox="0 0 500 700" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-labelledby="frontTitle">
                            <title id="frontTitle">Editable front invitation preview</title>
                            <defs>
                                <linearGradient id="frontGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" stop-color="#f6f7ff"/>
                                    <stop offset="100%" stop-color="#dde4ff"/>
                                </linearGradient>
                            </defs>
                <rect x="0" y="0" width="500" height="700" fill="url(#frontGradient)" rx="24" data-background-layer="true"/>
                <image data-editable-image="front"
                    x="0"
                    y="0"
                    width="500"
                    height="700"
                    preserveAspectRatio="xMidYMid slice"
                    href="{{ $frontPreview }}"
                    xlink:href="{{ $frontPreview }}"
                    data-default-src="{{ $frontPreview }}"
                    @if(empty($frontPreview)) style="display:none;" @endif
                />
                            <text data-text-node="front-date"
                                  x="250"
                                  y="126"
                                  text-anchor="middle"
                                  dominant-baseline="middle"
                        font-size="28"
                        fill="#1f2933"></text>
                            <text data-text-node="front-save"
                                  x="250"
                                  y="196"
                                  text-anchor="middle"
                                  dominant-baseline="middle"
                                  font-size="32"
                        letter-spacing="0.35"
                        fill="#1f2933"></text>
                            <text data-text-node="front-dateword"
                                  x="250"
                                  y="252"
                                  text-anchor="middle"
                                  dominant-baseline="middle"
                                  font-size="16"
                        letter-spacing="0.5"
                        fill="#4b5563"></text>
                            <text data-text-node="front-names"
                                  x="250"
                                  y="336"
                                  text-anchor="middle"
                                  dominant-baseline="middle"
                                  font-size="22"
                        fill="#1f2933"></text>
                            <text data-text-node="front-location"
                                  x="250"
                                  y="406"
                                  text-anchor="middle"
                                  dominant-baseline="middle"
                                  font-size="16"
                        letter-spacing="0.1"
                        fill="#4b5563"></text>
                        </svg>
                    @endif
                </div>
                <div id="cardBack" class="card" data-card="back" data-default-image="{{ $backPreview }}" role="img" aria-label="Back design preview">
                    @if(!empty($backSvg))
                        {!! $backSvg !!}
                    @else
                        <svg viewBox="0 0 500 700" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-labelledby="backTitle">
                            <title id="backTitle">Editable back invitation preview</title>
                            <rect x="0" y="0" width="500" height="700" fill="#fdfcfa" rx="24" data-background-layer="true"/>
                <image data-editable-image="back"
                                   x="0"
                                   y="0"
                                   width="500"
                                   height="700"
                                   preserveAspectRatio="xMidYMid slice"
                    href="{{ $backPreview }}"
                    xlink:href="{{ $backPreview }}"
                                   data-default-src="{{ $backPreview }}"
                                   @if(empty($backPreview)) style="display:none;" @endif
                            />
                            <text data-text-node="back-heading"
                                  x="250"
                                  y="180"
                                  text-anchor="middle"
                                  dominant-baseline="middle"
                        font-size="26"
                        fill="#1f2933"></text>
                            <text data-text-node="back-body"
                                  x="250"
                                  y="300"
                                  text-anchor="middle"
                                  dominant-baseline="middle"
                                  font-size="16"
                                                                    letter-spacing="0.05"
                                              fill="#4b5563"></text>
                        </svg>
                    @endif
                </div>
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

            <div class="editor-panels">
                <section class="editor-panel active" data-panel="text">
                    <div class="text-editor">
                        <h3>Placeholder text</h3>
                        <div id="textFields">
                            @foreach($textFieldPresets as $field)
                                @php
                                    $inputValue = old('text_fields.' . $field['node'], $field['value']);
                                @endphp
                                <div class="text-field" data-card-side="{{ $field['side'] }}" data-text-node="{{ $field['node'] }}">
                                    <input
                                        type="text"
                                        name="text_fields[{{ $field['node'] }}]"
                                        value="{{ $inputValue }}"
                                        placeholder="{{ $field['placeholder'] ?? '' }}"
                                        data-default-value="{{ $field['default'] ?? '' }}"
                                        data-card-side="{{ $field['side'] }}"
                                        data-text-node="{{ $field['node'] }}"
                                        data-top-percent="{{ $field['top'] }}"
                                        data-left-percent="{{ $field['left'] }}"
                                        data-align="{{ $field['align'] }}"
                                        @if(!empty($field['font_size'])) data-font-size="{{ $field['font_size'] }}" @endif
                                        @if(!empty($field['letter_spacing'])) data-letter-spacing="{{ $field['letter_spacing'] }}" @endif
                                    >
                                    <button class="delete-text" type="button" aria-label="Remove text field">🗑</button>
                                </div>
                            @endforeach
                        </div>
                        <button id="addTextField" class="add-btn" type="button">+ New Text Field</button>
                    </div>
                </section>

                <section class="editor-panel" data-panel="images">
                    <div class="image-panel">
                        <h3>Images</h3>
                        <p class="panel-note">Replace the invitation backgrounds with your own images. Supported formats: JPG, PNG, WEBP.</p>

                        <div class="image-control" data-image-side="front">
                            <div class="image-control-header">
                                <span>Front background</span>
                                <button class="reset-image-btn" type="button" data-reset-image="front">Reset</button>
                            </div>
                            <div class="image-preview">
                                <img src="{{ $frontPreview }}" alt="Front preview" data-image-preview="front" data-default-src="{{ $frontDefault }}">
                            </div>
                            <label class="image-upload">
                                <span class="upload-label">Choose image</span>
                                <input type="file" accept="image/*" data-image-input="front">
                            </label>
                        </div>

                        <div class="image-control" data-image-side="back">
                            <div class="image-control-header">
                                <span>Back background</span>
                                <button class="reset-image-btn" type="button" data-reset-image="back">Reset</button>
                            </div>
                            <div class="image-preview">
                                <img src="{{ $backPreview }}" alt="Back preview" data-image-preview="back" data-default-src="{{ $backDefault }}">
                            </div>
                            <label class="image-upload">
                                <span class="upload-label">Choose image</span>
                                <input type="file" accept="image/*" data-image-input="back">
                            </label>
                        </div>
                    </div>
                </section>

                <section class="editor-panel" data-panel="graphics">
                    <div class="panel-placeholder">
                        <h3>Graphics</h3>
                        <p>Graphic elements customization is coming soon.</p>
                    </div>
                </section>

                <section class="editor-panel" data-panel="tables">
                    <div class="panel-placeholder">
                        <h3>Tables</h3>
                        <p>Table layouts will be available in a future update.</p>
                    </div>
                </section>

                <section class="editor-panel" data-panel="colors">
                    <div class="panel-placeholder">
                        <h3>Design colors</h3>
                        <p>Color presets and palettes are not yet customizable.</p>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script>
        window.sessionStorage.removeItem('inkwise-finalstep');
    </script>
</body>
</html>
