<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Design - Inkwise</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" referrerpolicy="no-referrer">
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

    $placeholderImage = asset('images/no-image.png');

    $frontDefault = isset($frontSlot['default']) && $frontSlot['default'] !== ''
        ? asset($frontSlot['default'])
        : $placeholderImage;
    $backDefault = isset($backSlot['default']) && $backSlot['default'] !== ''
        ? asset($backSlot['default'])
        : $frontDefault;
    use App\Support\TemplatePreview;

    $frontPreview = TemplatePreview::resolvePreview($frontImage ?? ($frontSlot['default'] ?? null), $frontDefault);
    $backPreview = TemplatePreview::resolvePreview($backImage ?? ($backSlot['default'] ?? null), $backDefault);

    $frontPreview = TemplatePreview::normalizeToWebUrl($frontPreview);
    $backPreview = TemplatePreview::normalizeToWebUrl($backPreview);
    $presetQuantity = $defaultQuantity ?? 50;
    $frontSvg = isset($frontSvg) ? trim($frontSvg) : null;
    if ($frontSvg === '') {
        $frontSvg = null;
    }

    $backSvg = isset($backSvg) ? trim($backSvg) : null;
    if ($backSvg === '') {
        $backSvg = null;
    }
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

    if (!empty($frontSvg) || !empty($backSvg)) {
        $textFieldPresets = [];
    }
@endphp

    <!-- TOP BAR -->
    <div class="editor-topbar">
        <div class="left-tools">
            <button class="save-btn" type="button">Save</button>
            <button class="undo-btn" type="button">↶ Undo</button>
            <button class="redo-btn" type="button">↷ Redo</button>
            <button id="editModeToggle" class="edit-mode-btn" type="button" title="Toggle Edit Mode">Edit Mode</button>
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
            <div id="textToolbar" class="text-toolbar" role="dialog" aria-hidden="true">
                <button class="toolbar-btn" type="button" data-tool="font" aria-label="Font family">
                    <i class="fa-solid fa-font" aria-hidden="true"></i>
                </button>
                <div class="toolbar-item font-size-control" data-dropdown-open="false">
                    <label for="toolbarFontSizeInput" class="sr-only">Font size</label>
                    <input id="toolbarFontSizeInput" class="toolbar-fontsize-input" type="number" min="6" max="200" step="1" value="16" aria-label="Font size input">
                    <div class="font-size-dropdown" role="listbox" aria-hidden="true">
                        <button type="button" class="font-size-option">8</button>
                        <button type="button" class="font-size-option">10</button>
                        <button type="button" class="font-size-option">12</button>
                        <button type="button" class="font-size-option">14</button>
                        <button type="button" class="font-size-option">16</button>
                        <button type="button" class="font-size-option">18</button>
                        <button type="button" class="font-size-option">20</button>
                        <button type="button" class="font-size-option">24</button>
                        <button type="button" class="font-size-option">28</button>
                        <button type="button" class="font-size-option">32</button>
                        <button type="button" class="font-size-option">36</button>
                        <button type="button" class="font-size-option">48</button>
                    </div>
                </div>
                <div class="toolbar-item">
                    <!-- toolbar color swatch button (replaces native color input) -->
                    <button id="toolbarColorBtn" class="toolbar-color" type="button" aria-label="Text color">
                        <span class="toolbar-color-swatch" data-color="#1f2933" style="background:#1f2933"></span>
                    </button>
                </div>
                <button class="toolbar-btn" type="button" data-tool="bold" aria-label="Bold text">
                    <i class="fa-solid fa-bold" aria-hidden="true"></i>
                </button>
                <div class="toolbar-item align-dropdown">
                    <button id="toolbarAlignBtn" class="toolbar-btn" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Text alignment">
                        <i class="fa-solid fa-align-justify" aria-hidden="true"></i>
                    </button>
                    <div class="align-dropdown-menu" aria-hidden="true" role="menu" aria-label="Alignment options">
                        <button type="button" class="align-option" data-align="left" role="menuitem" title="Align left">
                            <i class="fa-solid fa-align-left" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="align-option" data-align="center" role="menuitem" title="Align center">
                            <i class="fa-solid fa-align-center" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="align-option" data-align="right" role="menuitem" title="Align right">
                            <i class="fa-solid fa-align-right" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="align-option" data-align="justify" role="menuitem" title="Justify">
                            <i class="fa-solid fa-align-justify" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="toolbar-item list-dropdown">
                    <button id="toolbarListBtn" class="toolbar-btn" type="button" aria-haspopup="true" aria-expanded="false" aria-label="List formatting">
                        <i class="fa-solid fa-list-ul" aria-hidden="true"></i>
                    </button>
                    <div class="list-dropdown-menu" aria-hidden="true" role="menu" aria-label="List options">
                        <button type="button" class="list-option" data-list="ul" role="menuitem" title="Bulleted list">
                            <i class="fa-solid fa-list-ul" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="list-option" data-list="ol" role="menuitem" title="Numbered list">
                            <i class="fa-solid fa-list-ol" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="list-option" data-list="none" role="menuitem" title="Remove list">
                            <i class="fa-solid fa-ban" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="toolbar-item format-dropdown">
                    <button id="toolbarFormatBtn" class="toolbar-btn" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Format">
                        Format
                    </button>
                    <div class="format-dropdown-menu" aria-hidden="true" role="menu" aria-label="Format options">
                        <!-- Uppercase button -->
                        <button id="uppercaseBtn" class="format-option" type="button" data-format="uppercase" title="Uppercase" role="menuitem">
                            <i class="fa-solid fa-a" aria-hidden="true"></i>
                            <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
                        </button>
                        <!-- Lowercase button (small 'a' icon) -->
                        <button id="lowercaseBtn" class="format-option" type="button" data-format="lowercase" title="Lowercase" role="menuitem">
                            <span class="small-a" aria-hidden="true">a</span>
                            <i class="fa-solid fa-arrow-down" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
            <!-- Font selection modal (hidden) -->
            <div id="fontModal" class="font-modal" data-google-fonts-key="AIzaSyBRCDdZjTcR4brOsHV_OBsDO11We11BVi0" aria-hidden="true">
                <div class="font-modal-card">
                    <div class="font-modal-header">
                        <input id="fontSearch" class="font-search" placeholder="Search fonts...">
                        <button id="fontClose" class="font-close" aria-label="Close">×</button>
                    </div>
                    <div id="recentFonts" class="recent-fonts" aria-hidden="true">
                        <div class="recent-title">Recent</div>
                        <div class="recent-list">No recent fonts</div>
                    </div>
                    <div id="fontList" class="font-list">
                        <div class="font-list-loading">Loading fonts…</div>
                    </div>
                </div>
            </div>
            <!-- Color selection modal (hidden) -->
            <div id="colorModal" class="color-modal" aria-hidden="true">
                <div class="color-modal-card">
                    <div class="color-modal-header">
                        <div class="color-title">Text color</div>
                        <button id="colorClose" class="color-close" aria-label="Close">×</button>
                    </div>
                    <div class="color-modal-body">
                        <div class="color-picker-grid">
                            <div class="color-picker-left">
                                <div class="color-gradient" id="colorGradient" aria-label="Color gradient" role="application">
                                    <div class="color-gradient-pointer" id="colorGradientPointer" aria-hidden="true"></div>
                                </div>
                                <div class="hue-row">
                                    <div class="hue-slider" id="hueSlider" aria-label="Hue slider">
                                        <div class="hue-pointer" id="huePointer" aria-hidden="true"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="color-picker-right">
                                <div class="hex-row">
                                    <div class="hex-input" style="width:100%; display:flex; gap:8px; align-items:center;">
                                        <input id="colorHexInput" class="color-hex-input" value="#1f2933" aria-label="Hex color input">
                                        <button id="eyedropperBtn" class="eyedropper-btn" type="button" title="Eyedropper">
                                            <i class="fa-solid fa-eye-dropper" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="color-tabs">
                                    <button type="button" class="tab active" data-tab="swatches">Swatches</button>
                                    <button type="button" class="tab" data-tab="cmyk">CMYK</button>
                                </div>

                                <div class="tab-panels">
                                    <div class="tab-panel" data-panel="swatches">
                                        <div class="section-title">Recent colors</div>
                                        <div id="recentColors" class="recent-colors">
                                            <div class="recent-list">
                                                <button type="button" class="swatch" data-color="#FFFFFF" style="background:#FFFFFF"></button>
                                                <button type="button" class="swatch" data-color="#000000" style="background:#000000"></button>
                                            </div>
                                        </div>

                                        <div class="section-title">Pre-set colors</div>
                                        <div class="preset-swatches grid-5x5" id="presetSwatches">
                                            <!-- 5x5 grid of preset swatches generated inline -->
                                        </div>
                                    </div>
                                    <div class="tab-panel" data-panel="cmyk" style="display:none;">
                                        <div class="section-title">CMYK</div>
                                        <div class="cmyk-controls">
                                            <label> C <input type="number" min="0" max="100" class="cmyk-input" data-cmyk="c" value="0">%</label>
                                            <label> M <input type="number" min="0" max="100" class="cmyk-input" data-cmyk="m" value="0">%</label>
                                            <label> Y <input type="number" min="0" max="100" class="cmyk-input" data-cmyk="y" value="0">%</label>
                                            <label> K <input type="number" min="0" max="100" class="cmyk-input" data-cmyk="k" value="0">%</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
            </div>

            <div class="view-toggle">
                <button id="showFront" class="active" type="button">Front</button>
                <button id="showBack" type="button">Back</button>
            </div>

            <div class="editor-panels">
                <section class="editor-panel active" data-panel="text">
                    <div class="text-editor-panel">
                        <!-- Panel Header -->
                        <div class="panel-header">
                            <h3 class="panel-title">Text</h3>
                            <button class="expand-btn" type="button" title="Expand panel">
                                <span class="expand-icon">↗</span>
                            </button>
                        </div>

                        <!-- Helper Text -->
                        <p class="helper-text">
                            Edit your text below, or click on the field you'd like to edit directly on your design.
                        </p>

                        <!-- Text Fields Container -->
                        <div class="text-fields-container">
                            @foreach($textFieldPresets as $field)
                                @php
                                    $inputValue = old('text_fields.' . $field['node'], $field['value']);
                                @endphp
                                <div class="text-input-wrapper" data-text-node="{{ $field['node'] }}" data-card-side="{{ $field['side'] }}">
                                    @php $inputId = 'text_' . $field['node']; @endphp
                                    <label class="sr-only" for="{{ $inputId }}">{{ $field['placeholder'] ?? 'Text field' }}</label>
                                    <input
                                        id="{{ $inputId }}"
                                        type="text"
                                        class="text-input"
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
                                </div>
                            @endforeach
                        </div>

                        <!-- Add New Field Button -->
                        <div class="add-field-container">
                            <button id="addTextField" class="new-text-btn" type="button">
                                New Text Field
                            </button>
                        </div>
                    </div>
                </section>

                <script>
                    (function(){
                        // Basic helpers to find the active SVG canvas for the current side
                        function getActiveSvg() {
                            // prefer existing selectors in page
                            var front = document.querySelector('#cardFront svg');
                            var back = document.querySelector('#cardBack svg');
                            return front || back || document.querySelector('svg');
                        }

                        function insertSvgStringToCanvas(svgString) {
                            try {
                                var parser = new DOMParser();
                                var doc = parser.parseFromString(svgString, 'image/svg+xml');
                                var node = doc.documentElement;
                                var svg = getActiveSvg();
                                if (!svg) return;
                                // import node into canvas SVG
                                var imported = document.importNode(node, true);
                                // wrap in a group so transforms don't conflict
                                var g = document.createElementNS('http://www.w3.org/2000/svg','g');
                                g.setAttribute('transform','translate(40,40)');
                                g.appendChild(imported);
                                svg.appendChild(g);
                            } catch (e) { console.error(e); }
                        }

                        function insertImageToCanvas(url) {
                            var svg = getActiveSvg();
                            if (!svg) return;
                            var img = document.createElementNS('http://www.w3.org/2000/svg','image');
                            img.setAttributeNS(null,'href', url);
                            img.setAttribute('x','20');
                            img.setAttribute('y','20');
                            img.setAttribute('width','140');
                            img.setAttribute('height','90');
                            svg.appendChild(img);
                        }

                        // Wire grid item clicks
                        document.addEventListener('click', function(e){
                            var btn = e.target.closest('.graphic-item');
                            if (!btn) return;
                            var type = btn.getAttribute('data-type');
                            if (type === 'image' || type === 'illustration') {
                                var src = btn.getAttribute('data-src');
                                if (src) insertImageToCanvas(src);
                                return;
                            }
                            if (type === 'shape') {
                                var shape = btn.getAttribute('data-shape');
                                var svg = getActiveSvg();
                                if (!svg) return;
                                if (shape === 'square') {
                                    var r = document.createElementNS('http://www.w3.org/2000/svg','rect');
                                    r.setAttribute('x',20); r.setAttribute('y',20); r.setAttribute('width',80); r.setAttribute('height',80); r.setAttribute('fill','#111');
                                    svg.appendChild(r);
                                } else if (shape === 'circle') {
                                    var c = document.createElementNS('http://www.w3.org/2000/svg','circle');
                                    c.setAttribute('cx',60); c.setAttribute('cy',60); c.setAttribute('r',40); c.setAttribute('fill','#111');
                                    svg.appendChild(c);
                                } else if (shape === 'triangle') {
                                    var p = document.createElementNS('http://www.w3.org/2000/svg','polygon');
                                    p.setAttribute('points','60,20 100,100 20,100'); p.setAttribute('fill','#111');
                                    svg.appendChild(p);
                                }
                                return;
                            }
                        });

                        // SVGRepo search: fetch icons and populate iconsGrid
                        var iconsGrid = document.getElementById('iconsGrid');
                        var searchInput = document.getElementById('graphicsSearchInput');

                        // Use server-side proxy to avoid CORS/key exposure
                        var svgRepoEndpoint = function(q){ return '/graphics/svgrepo?q=' + encodeURIComponent(q) + '&limit=12'; };

                        function renderSvgRepoResults(items) {
                            if (!iconsGrid) return;
                            iconsGrid.innerHTML = '';
                            items.forEach(function(it, idx){
                                try {
                                    var cell = document.createElement('button');
                                    cell.className = 'graphic-item';
                                    cell.setAttribute('aria-label', it.title || 'icon');
                                    cell.setAttribute('data-type','icon');
                                    cell.setAttribute('data-idx', String(idx));
                                    // roving tabindex: only first is 0
                                    cell.setAttribute('tabindex', idx === 0 ? '0' : '-1');
                                    // prefer raw svg content
                                    var svgContent = it.svg && it.svg.replace(/^\s+|\s+$/g, '');
                                    if (svgContent) {
                                        var wrapper = document.createElement('div');
                                        wrapper.style.width = '100%'; wrapper.style.height='64px'; wrapper.style.display='flex'; wrapper.style.alignItems='center'; wrapper.style.justifyContent='center';
                                        wrapper.innerHTML = svgContent;
                                        cell.appendChild(wrapper);
                                        cell.__svg = svgContent;
                                    } else {
                                        cell.textContent = it.title || 'icon';
                                    }
                                    // click handler
                                    cell.addEventListener('click', function(ev){
                                        ev.preventDefault();
                                        if (cell.__svg) insertSvgStringToCanvas(cell.__svg);
                                    });
                                    // keyboard handler (Enter to insert)
                                    cell.addEventListener('keydown', function(ev){
                                        if (ev.key === 'Enter' || ev.key === ' ') {
                                            ev.preventDefault();
                                            if (cell.__svg) insertSvgStringToCanvas(cell.__svg);
                                        }
                                    });
                                    iconsGrid.appendChild(cell);
                                } catch (e) { console.error('render item', e); }
                            });

                            // set aria-live summary for screen readers
                            var live = document.getElementById('graphicsIconsLive');
                            if (live) live.textContent = items.length + ' icons available';
                            // ensure none are aria-selected by default
                            var first = iconsGrid.querySelector('.graphic-item');
                            if (first) first.setAttribute('aria-selected','false');
                        }

                        var lastTimer;
                        function doSvgRepoSearch(q) {
                            if (!q || q.length < 2) { iconsGrid.innerHTML = '' ; return; }
                            // debounce
                            clearTimeout(lastTimer);
                            lastTimer = setTimeout(function(){
                                var url = svgRepoEndpoint(q);
                                fetch(url).then(function(r){ return r.json(); }).then(function(json){
                                    if (!json || !json.results) { iconsGrid.innerHTML = ''; return; }
                                    // results contain objects with svg property
                                    renderSvgRepoResults(json.results);
                                }).catch(function(err){
                                    console.error('SVGRepo search error', err);
                                });
                            }, 250);
                        }

                        if (searchInput) {
                            searchInput.addEventListener('input', function(e){
                                var v = (e.target.value || '').trim();
                                doSvgRepoSearch(v);
                            });
                        }

                        // keyboard navigation for icons grid: arrow keys to move focus
                        (function wireIconGridKeyboard() {
                            var currentFocus = -1;
                            iconsGrid && iconsGrid.addEventListener('keydown', function(e){
                                var cols = 3; // matches CSS grid columns
                                var items = Array.from(iconsGrid.querySelectorAll('.graphic-item'));
                                if (!items.length) return;
                                if (e.key === 'ArrowRight') {
                                    e.preventDefault(); currentFocus = Math.min(items.length - 1, currentFocus + 1);
                                    items[currentFocus].focus();
                                } else if (e.key === 'ArrowLeft') {
                                    e.preventDefault(); currentFocus = Math.max(0, currentFocus - 1);
                                    items[currentFocus].focus();
                                } else if (e.key === 'ArrowDown') {
                                    e.preventDefault(); currentFocus = Math.min(items.length - 1, currentFocus + cols);
                                    items[currentFocus].focus();
                                } else if (e.key === 'ArrowUp') {
                                    e.preventDefault(); currentFocus = Math.max(0, currentFocus - cols);
                                    items[currentFocus].focus();
                                }
                            });
                            // clicking items should update currentFocus
                            iconsGrid && iconsGrid.addEventListener('click', function(e){
                                var b = e.target.closest('.graphic-item');
                                if (!b) return; var items = Array.from(iconsGrid.querySelectorAll('.graphic-item')); currentFocus = items.indexOf(b);
                            });
                        })();

                        // Scroll fade toggles
                        (function wireScrollFades() {
                            var pane = document.querySelector('.graphics-panel .overflow-y-auto');
                            if (!pane) return;
                            function update() {
                                var top = pane.scrollTop > 8;
                                var bottom = (pane.scrollHeight - pane.clientHeight - pane.scrollTop) > 8;
                                pane.classList.toggle('has-scroll-top', top);
                                pane.classList.toggle('has-scroll-bottom', bottom);
                            }
                            pane.addEventListener('scroll', update);
                            // call once to initialize
                            setTimeout(update, 120);
                        })();

                        // load an initial set of popular icons on panel open
                        (function loadInitialIcons(){
                            var popular = ['star','heart','leaf','gift','calendar','camera','envelope','bell','map','clock','user','phone'];
                            // query svgrepo for "star" to populate initially
                            doSvgRepoSearch(popular.join(' '));
                        })();

                        // Handle section expansion arrows
                        (function setupSectionExpansion() {
                            document.addEventListener('click', function(e) {
                                var btn = e.target.closest('.expand-section-btn');
                                if (!btn) return;

                                var section = btn.getAttribute('data-section');
                                var expandedGrid = document.querySelector('.' + section + '-expanded');
                                var isExpanded = !expandedGrid.classList.contains('hidden');

                                if (isExpanded) {
                                    // Collapse
                                    expandedGrid.classList.add('hidden');
                                    btn.textContent = '→';
                                    btn.setAttribute('aria-expanded', 'false');
                                    btn.setAttribute('title', 'Show all ' + section);
                                } else {
                                    // Expand
                                    expandedGrid.classList.remove('hidden');
                                    btn.textContent = '↓';
                                    btn.setAttribute('aria-expanded', 'true');
                                    btn.setAttribute('title', 'Hide ' + section);
                                }
                            });
                        })();

                        // allow pressing Enter to run a broader Unsplash search for images
                        if (searchInput) {
                            searchInput.addEventListener('keydown', function(e){
                                if (e.key === 'Enter') {
                                    var term = (e.target.value||'').trim();
                                    if (!term) return;
                                    // attempt to trigger existing images search flow if available
                                    var btn = document.getElementById('graphicsSearchBtn');
                                    if (btn) {
                                        var input = document.getElementById('graphicsImageSearch');
                                        if (input) { input.value = term; btn.click(); }
                                    }
                                }
                            });
                        }

                    })();
                </script>

                <!-- legacy images panel removed; new Canva-style Images sidebar inserted below -->

                                    <!-- Images Sidebar (Canva-style) -->
                                    <section class="editor-panel" data-panel="images" id="imagesPanel">
                                        <div class="images-sidebar" aria-labelledby="imagesTitle">
                                            <div class="images-header">
                                                <h3 id="imagesTitle">Images</h3>
                                            </div>
                                            <div class="images-tabs" role="tablist" aria-label="Images tabs" style="margin-top:8px;">
                                                <button class="images-tab active" data-tab="upload" role="tab" aria-selected="true">Upload</button>
                                                <button class="images-tab" data-tab="discover" role="tab" aria-selected="false">Discover</button>
                                            </div>

                                            <div class="images-body">
                                                <div class="images-panel upload-panel" data-panel="upload">
                                                    <p class="accepted-formats">Accepted formats: .jpg, .jpeg, .jfif, .bmp, .png, .gif, .heic, .svg, .webp, .pdf, .psd, .ai, .eps, .ait, .ppt, .pptx, .tif, .tiff</p>

                                                    <div class="images-actions">
                                                        <button type="button" class="btn-upload-primary" id="btnUploadFiles">Upload files</button>
                                                        <!-- phone upload removed -->
                                                        <input type="file" id="imagesFileInput" multiple accept="image/*,.pdf,.psd,.ai,.eps,.ppt,.pptx,.tif,.tiff" style="display:none" />
                                                    </div>

                                                    <div class="recent-section">
                                                        <h4>Recently uploaded</h4>
                                                        <div class="recent-grid" id="recentUploads" aria-live="polite">
                                                            <!-- thumbnails inserted here -->
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="images-panel discover-panel" data-panel="discover" style="display:none">
                                                    <div class="discover-search">
                                                        <label for="discoverSearchInput" class="sr-only">Search images</label>
                                                        <div style="display:flex; gap:8px;">
                                                            <input id="discoverSearchInput" type="search" placeholder="Search images (Unsplash)…" aria-label="Search images" style="flex:1; padding:8px; border:1px solid #ddd; border-radius:6px;">
                                                            <button id="discoverSearchBtn" type="button" class="btn-upload-primary">Search</button>
                                                        </div>
                                                    </div>
                                                    <div class="discover-results" id="discoverResults">
                                                        <!-- results rendered here -->
                                                    </div>
                                                    <div id="discoverSpinner" class="discover-spinner" aria-hidden="true" style="display:none;">
                                                        <div class="spinner" role="status" aria-hidden="true"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </section>

                <section class="editor-panel" data-panel="graphics">
                    <div class="graphics-panel p-4 flex flex-col h-full" style="height:100%;">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-lg font-semibold">Graphics</h3>
                        </div>

                        <!-- Search bar -->
                        <div class="mb-3">
                            <label for="graphicsSearchInput" class="sr-only">Search for content</label>
                            <div class="relative">
                                <input id="graphicsSearchInput" class="w-full pr-3 py-2 rounded-lg border text-sm" placeholder="Search for content" autocomplete="off" />
                            </div>
                        </div>

                        <div class="overflow-y-auto" style="flex:1;">
                            <!-- Shapes Section -->
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="font-medium">Shapes</div>
                                    <button type="button" class="text-gray-400 hover:text-blue-500 transition-colors expand-section-btn" data-section="shapes" title="Show all shapes" aria-label="Expand shapes section">
                                        →
                                    </button>
                                </div>
                                <div class="shapes-grid grid grid-cols-3 gap-3">
                                    <button class="graphic-item rounded-lg p-3 bg-white shadow-sm hover:shadow-md" data-type="shape" data-shape="square" aria-label="Insert square">
                                        <div class="w-full h-16 flex items-center justify-center text-black">
                                            <div style="width:40px;height:40px;background:#111;border-radius:4px;"></div>
                                        </div>
                                    </button>
                                    <button class="graphic-item rounded-lg p-3 bg-white shadow-sm hover:shadow-md" data-type="shape" data-shape="circle" aria-label="Insert circle">
                                        <div class="w-full h-16 flex items-center justify-center text-black">
                                            <div style="width:40px;height:40px;background:#111;border-radius:9999px;"></div>
                                        </div>
                                    </button>
                                    <button class="graphic-item rounded-lg p-3 bg-white shadow-sm hover:shadow-md" data-type="shape" data-shape="triangle" aria-label="Insert triangle">
                                        <div class="w-full h-16 flex items-center justify-center text-black">
                                            <svg width="40" height="40" viewBox="0 0 100 100"><polygon points="50,10 90,90 10,90" fill="#111"/></svg>
                                        </div>
                                    </button>
                                </div>
                                <div class="shapes-expanded hidden mt-3 grid grid-cols-3 gap-3">
                                    <button class="graphic-item rounded-lg p-3 bg-white shadow-sm hover:shadow-md" data-type="shape" data-shape="rectangle" aria-label="Insert rectangle">
                                        <div class="w-full h-16 flex items-center justify-center text-black">
                                            <div style="width:60px;height:30px;background:#111;border-radius:4px;"></div>
                                        </div>
                                    </button>
                                    <button class="graphic-item rounded-lg p-3 bg-white shadow-sm hover:shadow-md" data-type="shape" data-shape="oval" aria-label="Insert oval">
                                        <div class="w-full h-16 flex items-center justify-center text-black">
                                            <div style="width:50px;height:35px;background:#111;border-radius:50%;"></div>
                                        </div>
                                    </button>
                                    <button class="graphic-item rounded-lg p-3 bg-white shadow-sm hover:shadow-md" data-type="shape" data-shape="diamond" aria-label="Insert diamond">
                                        <div class="w-full h-16 flex items-center justify-center text-black">
                                            <div style="width:40px;height:40px;background:#111;transform:rotate(45deg);border-radius:4px;"></div>
                                        </div>
                                    </button>
                                    <button class="graphic-item rounded-lg p-3 bg-white shadow-sm hover:shadow-md" data-type="shape" data-shape="star" aria-label="Insert star">
                                        <div class="w-full h-16 flex items-center justify-center text-black">
                                            <svg width="40" height="40" viewBox="0 0 100 100"><polygon points="50,5 61,35 95,35 68,57 79,91 50,70 21,91 32,57 5,35 39,35" fill="#111"/></svg>
                                        </div>
                                    </button>
                                    <button class="graphic-item rounded-lg p-3 bg-white shadow-sm hover:shadow-md" data-type="shape" data-shape="hexagon" aria-label="Insert hexagon">
                                        <div class="w-full h-16 flex items-center justify-center text-black">
                                            <svg width="40" height="40" viewBox="0 0 100 100"><polygon points="50,5 90,25 90,75 50,95 10,75 10,25" fill="#111"/></svg>
                                        </div>
                                    </button>
                                    <button class="graphic-item rounded-lg p-3 bg-white shadow-sm hover:shadow-md" data-type="shape" data-shape="pentagon" aria-label="Insert pentagon">
                                        <div class="w-full h-16 flex items-center justify-center text-black">
                                            <svg width="40" height="40" viewBox="0 0 100 100"><polygon points="50,5 90,40 75,90 25,90 10,40" fill="#111"/></svg>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- Images Section -->
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="font-medium">Images</div>
                                    <button type="button" class="text-gray-400 hover:text-blue-500 transition-colors expand-section-btn" data-section="images" title="Show all images" aria-label="Expand images section">
                                        →
                                    </button>
                                </div>
                                <div class="images-grid grid grid-cols-3 gap-3">
                                    <button class="graphic-item rounded-lg p-0 bg-white shadow-sm hover:shadow-md" data-type="image" data-src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?q=80&w=400&auto=format&fit=crop&crop=faces">
                                        <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?q=80&w=400&auto=format&fit=crop&crop=faces" alt="sample" class="w-full h-20 object-cover rounded-lg" />
                                    </button>
                                    <button class="graphic-item rounded-lg p-0 bg-white shadow-sm hover:shadow-md" data-type="image" data-src="https://images.unsplash.com/photo-1529516540096-5b5f30b9b8d4?q=80&w=400&auto=format&fit=crop&crop=faces">
                                        <img src="https://images.unsplash.com/photo-1529516540096-5b5f30b9b8d4?q=80&w=400&auto=format&fit=crop&crop=faces" alt="sample" class="w-full h-20 object-cover rounded-lg" />
                                    </button>
                                    <button class="graphic-item rounded-lg p-0 bg-white shadow-sm hover:shadow-md" data-type="image" data-src="https://images.unsplash.com/photo-1469474968028-56623f02e42e?q=80&w=400&auto=format&fit=crop&crop=faces">
                                        <img src="https://images.unsplash.com/photo-1469474968028-56623f02e42e?q=80&w=400&auto=format&fit=crop&crop=faces" alt="sample" class="w-full h-20 object-cover rounded-lg" />
                                    </button>
                                </div>
                                <div class="images-expanded hidden mt-3 grid grid-cols-3 gap-3">
                                    <button class="graphic-item rounded-lg p-0 bg-white shadow-sm hover:shadow-md" data-type="image" data-src="https://images.unsplash.com/photo-1441974231531-c6227db76b6e?q=80&w=400&auto=format&fit=crop">
                                        <img src="https://images.unsplash.com/photo-1441974231531-c6227db76b6e?q=80&w=400&auto=format&fit=crop" alt="nature" class="w-full h-20 object-cover rounded-lg" />
                                    </button>
                                    <button class="graphic-item rounded-lg p-0 bg-white shadow-sm hover:shadow-md" data-type="image" data-src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?q=80&w=400&auto=format&fit=crop">
                                        <img src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?q=80&w=400&auto=format&fit=crop" alt="mountains" class="w-full h-20 object-cover rounded-lg" />
                                    </button>
                                    <button class="graphic-item rounded-lg p-0 bg-white shadow-sm hover:shadow-md" data-type="image" data-src="https://images.unsplash.com/photo-1518837695005-2083093ee35b?q=80&w=400&auto=format&fit=crop">
                                        <img src="https://images.unsplash.com/photo-1518837695005-2083093ee35b?q=80&w=400&auto=format&fit=crop" alt="city" class="w-full h-20 object-cover rounded-lg" />
                                    </button>
                                    <button class="graphic-item rounded-lg p-0 bg-white shadow-sm hover:shadow-md" data-type="image" data-src="https://images.unsplash.com/photo-1547036967-23d11aacaee0?q=80&w=400&auto=format&fit=crop">
                                        <img src="https://images.unsplash.com/photo-1547036967-23d11aacaee0?q=80&w=400&auto=format&fit=crop" alt="ocean" class="w-full h-20 object-cover rounded-lg" />
                                    </button>
                                    <button class="graphic-item rounded-lg p-0 bg-white shadow-sm hover:shadow-md" data-type="image" data-src="https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?q=80&w=400&auto=format&fit=crop">
                                        <img src="https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?q=80&w=400&auto=format&fit=crop" alt="sky" class="w-full h-20 object-cover rounded-lg" />
                                    </button>
                                    <button class="graphic-item rounded-lg p-0 bg-white shadow-sm hover:shadow-md" data-type="image" data-src="https://images.unsplash.com/photo-1501594907352-04cda38ebc29?q=80&w=400&auto=format&fit=crop">
                                        <img src="https://images.unsplash.com/photo-1501594907352-04cda38ebc29?q=80&w=400&auto=format&fit=crop" alt="flowers" class="w-full h-20 object-cover rounded-lg" />
                                    </button>
                                </div>
                            </div>

                            <!-- Icons Section -->
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="font-medium">Icons</div>
                                    <a href="https://www.svgrepo.com/" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-blue-500 transition-colors" title="Browse SVGRepo for more icons" aria-label="Visit SVGRepo website (opens in new tab)">
                                        <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                                    </a>
                                </div>
                                <div id="iconsGrid" class="grid grid-cols-3 gap-3" role="list">
                                    <!-- icons will be populated here by SVGRepo search -->
                                </div>
                                <div id="graphicsIconsLive" class="sr-only" aria-live="polite" aria-atomic="true"></div>
                            </div>

                            <!-- Illustrations Section -->
                            <div class="mb-6">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="font-medium">Illustrations</div>
                                    <button type="button" class="text-gray-400 hover:text-blue-500 transition-colors expand-section-btn" data-section="illustrations" title="Show all illustrations" aria-label="Expand illustrations section">
                                        →
                                    </button>
                                </div>
                                <div class="illustrations-grid grid grid-cols-3 gap-3">
                                    <button class="graphic-item rounded-lg p-0 bg-white shadow-sm hover:shadow-md" data-type="illustration" data-src="https://images.unsplash.com/photo-1526318472351-c75fcf070b04?q=80&w=400&auto=format&fit=crop">
                                        <img src="https://images.unsplash.com/photo-1526318472351-c75fcf070b04?q=80&w=400&auto=format&fit=crop" alt="illus" class="w-full h-20 object-cover rounded-lg" />
                                    </button>
                                    <button class="graphic-item rounded-lg p-0 bg-white shadow-sm hover:shadow-md" data-type="illustration" data-src="https://images.unsplash.com/photo-1526318489415-4d6f9a2b0f3d?q=80&w=400&auto=format&fit=crop">
                                        <img src="https://images.unsplash.com/photo-1526318489415-4d6f9a2b0f3d?q=80&w=400&auto=format&fit=crop" alt="illus" class="w-full h-20 object-cover rounded-lg" />
                                    </button>
                                    <button class="graphic-item rounded-lg p-0 bg-white shadow-sm hover:shadow-md" data-type="illustration" data-src="https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?q=80&w=400&auto=format&fit=crop">
                                        <img src="https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?q=80&w=400&auto=format&fit=crop" alt="illus" class="w-full h-20 object-cover rounded-lg" />
                                    </button>
                                </div>
                                <div class="illustrations-expanded hidden mt-3 grid grid-cols-3 gap-3">
                                    <button class="graphic-item rounded-lg p-0 bg-white shadow-sm hover:shadow-md" data-type="illustration" data-src="https://images.unsplash.com/photo-1559028006-448665bd7c7f?q=80&w=400&auto=format&fit=crop">
                                        <img src="https://images.unsplash.com/photo-1559028006-448665bd7c7f?q=80&w=400&auto=format&fit=crop" alt="abstract art" class="w-full h-20 object-cover rounded-lg" />
                                    </button>
                                    <button class="graphic-item rounded-lg p-0 bg-white shadow-sm hover:shadow-md" data-type="illustration" data-src="https://images.unsplash.com/photo-1578662996442-48f60103fc96?q=80&w=400&auto=format&fit=crop">
                                        <img src="https://images.unsplash.com/photo-1578662996442-48f60103fc96?q=80&w=400&auto=format&fit=crop" alt="geometric" class="w-full h-20 object-cover rounded-lg" />
                                    </button>
                                    <button class="graphic-item rounded-lg p-0 bg-white shadow-sm hover:shadow-md" data-type="illustration" data-src="https://images.unsplash.com/photo-1541961017774-22349e4a1262?q=80&w=400&auto=format&fit=crop">
                                        <img src="https://images.unsplash.com/photo-1541961017774-22349e4a1262?q=80&w=400&auto=format&fit=crop" alt="minimalist" class="w-full h-20 object-cover rounded-lg" />
                                    </button>
                                    <button class="graphic-item rounded-lg p-0 bg-white shadow-sm hover:shadow-md" data-type="illustration" data-src="https://images.unsplash.com/photo-1578321272176-b7bbc0679853?q=80&w=400&auto=format&fit=crop">
                                        <img src="https://images.unsplash.com/photo-1578321272176-b7bbc0679853?q=80&w=400&auto=format&fit=crop" alt="watercolor" class="w-full h-20 object-cover rounded-lg" />
                                    </button>
                                    <button class="graphic-item rounded-lg p-0 bg-white shadow-sm hover:shadow-md" data-type="illustration" data-src="https://images.unsplash.com/photo-1578662996442-48f60103fc96?q=80&w=400&auto=format&fit=crop">
                                        <img src="https://images.unsplash.com/photo-1578662996442-48f60103fc96?q=80&w=400&auto=format&fit=crop" alt="pattern" class="w-full h-20 object-cover rounded-lg" />
                                    </button>
                                    <button class="graphic-item rounded-lg p-0 bg-white shadow-sm hover:shadow-md" data-type="illustration" data-src="https://images.unsplash.com/photo-1557804506-669a67965ba0?q=80&w=400&auto=format&fit=crop">
                                        <img src="https://images.unsplash.com/photo-1557804506-669a67965ba0?q=80&w=400&auto=format&fit=crop" alt="digital art" class="w-full h-20 object-cover rounded-lg" />
                                    </button>
                                </div>
                            </div>
                        </div>
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
        // --- Graphics panel wiring (Unsplash search + insert shapes/icons) ---
        (function wireGraphicsPanel() {

            function $(sel, ctx) { return (ctx || document).querySelector(sel); }
            function $all(sel, ctx) { return Array.from((ctx || document).querySelectorAll(sel)); }

            // Tab switching
            $all('.graphics-tab').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const tab = btn.dataset.tab;
                    $all('.graphics-tab').forEach(b => b.classList.toggle('active', b === btn));
                    $all('.graphics-panel-pane').forEach(p => p.style.display = (p.dataset.panel === tab) ? '' : 'none');
                });
            });

            // Unsplash search
            const searchInput = $('#graphicsImageSearch');
            const searchBtn = $('#graphicsSearchBtn');
            const resultsEl = $('#graphicsResults');
            const spinner = $('#graphicsSpinner');

            // Use server proxy for Unsplash (keeps access key on server)
            async function doSearch(q) {
                if (!q || q.trim().length === 0) return;
                resultsEl.innerHTML = '';
                spinner.style.display = '';
                try {
                    const res = await fetch(`/graphics/unsplash?q=${encodeURIComponent(q)}&per_page=24`);
                    if (!res.ok) throw new Error('Unsplash proxy fetch failed ' + res.status);
                    const data = await res.json();
                    const items = data.results || [];
                    renderResults(items);
                } catch (err) {
                    console.warn('Graphics search failed', err);
                    resultsEl.innerHTML = '<div class="panel-placeholder">Failed to search images.</div>';
                } finally {
                    spinner.style.display = 'none';
                }
            }

            function renderResults(items) {
                resultsEl.innerHTML = '';
                if (!items || items.length === 0) {
                    resultsEl.innerHTML = '<div class="panel-placeholder">No images found.</div>';
                    return;
                }
                items.forEach(it => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'thumb';
                    btn.style.padding = '0';
                    btn.style.border = 'none';
                    btn.style.background = 'transparent';
                    btn.style.margin = '6px';
                    btn.style.width = '120px';
                    btn.style.height = '84px';
                    btn.style.overflow = 'hidden';
                    const img = document.createElement('img');
                    img.alt = it.alt_description || it.description || 'Image';
                    img.src = it.urls && (it.urls.small || it.urls.thumb) || '';
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.style.objectFit = 'cover';
                    btn.appendChild(img);
                    btn.addEventListener('click', () => {
                        // insert full size (regular) into current view as SVG <image>
                        const url = it.urls && (it.urls.regular || it.urls.full || it.urls.small);
                        if (url) insertImageToCanvas(url);
                    });
                    resultsEl.appendChild(btn);
                });
            }

            function insertImageToCanvas(url) {
                try {
                    const currentView = (window.currentView || 'front');
                    const svg = document.querySelector(currentView === 'front' ? '#cardFront svg' : '#cardBack svg');
                    if (!svg) {
                        alert('Canvas not available');
                        return;
                    }
                    // create an <image> element that covers the card and is editable
                    const ns = 'http://www.w3.org/2000/svg';
                    const img = document.createElementNS(ns, 'image');
                    img.setAttribute('href', url);
                    img.setAttribute('x', '0');
                    img.setAttribute('y', '0');
                    img.setAttribute('width', '100%');
                    img.setAttribute('height', '100%');
                    img.setAttribute('preserveAspectRatio', 'xMidYMid slice');
                    img.setAttribute('data-uploaded', 'true');
                    // insert as first child so text stays above
                    if (svg.firstChild) svg.insertBefore(img, svg.firstChild);
                    else svg.appendChild(img);
                    // apply preview update if preview panel exists
                    try { if (typeof updatePreviewFromSvg === 'function') updatePreviewFromSvg(currentView); } catch (e) {}
                } catch (err) {
                    console.error('Insert image failed', err);
                }
            }

            if (searchBtn && searchInput) {
                searchBtn.addEventListener('click', () => doSearch(searchInput.value));
                searchInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); doSearch(searchInput.value); } });
            }

            // shapes insertion
            $all('.shape-btn').forEach(b => b.addEventListener('click', () => {
                const shape = b.dataset.shape;
                const currentView = (window.currentView || 'front');
                const svg = document.querySelector(currentView === 'front' ? '#cardFront svg' : '#cardBack svg');
                if (!svg) return;
                const ns = 'http://www.w3.org/2000/svg';
                let el = null;
                if (shape === 'rect') {
                    el = document.createElementNS(ns, 'rect');
                    el.setAttribute('x', 100); el.setAttribute('y', 120); el.setAttribute('width', 300); el.setAttribute('height', 200); el.setAttribute('fill', '#ffffff'); el.setAttribute('stroke', '#d1d5db'); el.setAttribute('rx', 12);
                } else if (shape === 'circle') {
                    el = document.createElementNS(ns, 'circle');
                    el.setAttribute('cx', 250); el.setAttribute('cy', 250); el.setAttribute('r', 80); el.setAttribute('fill', '#fff'); el.setAttribute('stroke', '#d1d5db');
                } else if (shape === 'triangle') {
                    el = document.createElementNS(ns, 'path');
                    el.setAttribute('d', 'M250 140 L330 320 L170 320 Z'); el.setAttribute('fill', '#fff'); el.setAttribute('stroke', '#d1d5db');
                } else if (shape === 'star') {
                    el = document.createElementNS(ns, 'path');
                    el.setAttribute('d', 'M250 150 L270 220 L340 220 L290 260 L310 330 L250 290 L190 330 L210 260 L160 220 L230 220 Z'); el.setAttribute('fill', '#fff'); el.setAttribute('stroke', '#d1d5db');
                }
                if (el) svg.appendChild(el);
            }));

            // icons insertion (simple text-based fallback using <text> elements or small SVG groups)
            $all('.icon-btn').forEach(b => b.addEventListener('click', () => {
                const icon = b.dataset.icon;
                const currentView = (window.currentView || 'front');
                const svg = document.querySelector(currentView === 'front' ? '#cardFront svg' : '#cardBack svg');
                if (!svg) return;
                const ns = 'http://www.w3.org/2000/svg';
                const g = document.createElementNS(ns, 'g');
                const txt = document.createElementNS(ns, 'text');
                txt.setAttribute('x', '250'); txt.setAttribute('y', '250'); txt.setAttribute('text-anchor', 'middle'); txt.setAttribute('dominant-baseline', 'middle'); txt.setAttribute('font-size', '48'); txt.textContent = icon === 'fa-heart' ? '❤' : icon === 'fa-star' ? '★' : icon === 'fa-leaf' ? '🍃' : '★';
                g.appendChild(txt);
                svg.appendChild(g);
            }));

        })();
        window.sessionStorage.removeItem('inkwise-finalstep');

        // Edit Mode Toggle
        document.addEventListener('DOMContentLoaded', function() {
            const editModeToggle = document.getElementById('editModeToggle');
            const editorContainer = document.querySelector('.editor-container');
            const rightPanel = document.querySelector('.right-panel');
            const canvasArea = document.querySelector('.canvas-area');

            if (editModeToggle && editorContainer) {
                editModeToggle.addEventListener('click', function() {
                    const isEditMode = editorContainer.classList.toggle('edit-mode');
                    editModeToggle.textContent = isEditMode ? 'Panel Mode' : 'Edit Mode';
                    editModeToggle.title = isEditMode ? 'Show sidebar panels' : 'Hide sidebar for full canvas editing';
                    editModeToggle.classList.toggle('active', isEditMode);
                    // Optionally adjust canvas area width
                    if (canvasArea) {
                        canvasArea.style.width = isEditMode ? '100%' : '';
                    }
                });
            }

            // Text Editor Enhancements
            initializeTextEditor();
        });

        function initializeTextEditor() {
            // Group toggle functionality
            document.querySelectorAll('.group-toggle').forEach(button => {
                button.addEventListener('click', function() {
                    const group = this.closest('.text-fields-group');
                    const content = group.querySelector('.group-content');
                    const isActive = this.classList.toggle('active');

                    content.classList.toggle('active', isActive);
                    updateFieldCounts();
                });
            });

            // Enhanced delete functionality
            document.addEventListener('click', function(e) {
                if (e.target.closest('.delete-text')) {
                    e.preventDefault();
                    const fieldElement = e.target.closest('.text-field');
                    const placeholder = fieldElement.querySelector('.field-placeholder').textContent;

                    if (confirm(`Are you sure you want to delete "${placeholder}"? This action cannot be undone.`)) {
                        fieldElement.remove();
                        updateFieldCounts();
                        clearActiveField();
                    }
                }
            });

            // Field selection and canvas connection
            document.addEventListener('click', function(e) {
                if (e.target.closest('.text-field')) {
                    const fieldElement = e.target.closest('.text-field');
                    const textNode = fieldElement.dataset.textNode;
                    const placeholder = fieldElement.querySelector('.field-placeholder').textContent;

                    // Remove active state from all fields
                    document.querySelectorAll('.text-field').forEach(field => {
                        field.classList.remove('is-active');
                    });

                    // Add active state to clicked field
                    fieldElement.classList.add('is-active');

                    // Update active field indicator
                    showActiveField(placeholder);

                    // Highlight corresponding text on canvas
                    highlightCanvasText(textNode);
                }
            });

            // Canvas text click handler (would be connected to canvas text elements)
            document.addEventListener('canvasTextClick', function(e) {
                const textNode = e.detail.textNode;
                selectFieldByTextNode(textNode);
            });

            // Sync with canvas button
            document.getElementById('syncCanvas').addEventListener('click', function() {
                syncWithCanvas();
            });

            // Initialize
            updateFieldCounts();
        }

        function updateFieldCounts() {
            // Update front side count
            const frontCount = document.querySelectorAll('#frontFields .text-field').length;
            document.getElementById('front-count').textContent = frontCount;

            // Update back side count
            const backCount = document.querySelectorAll('#backFields .text-field').length;
            document.getElementById('back-count').textContent = backCount;

            // Update total count
            const totalCount = frontCount + backCount;
            document.getElementById('total-fields').textContent = totalCount;
        }

        function showActiveField(fieldName) {
            const indicator = document.getElementById('activeFieldIndicator');
            const nameElement = document.getElementById('activeFieldName');

            nameElement.textContent = fieldName;
            indicator.style.display = 'flex';
        }

        function clearActiveField() {
            const indicator = document.getElementById('activeFieldIndicator');
            indicator.style.display = 'none';

            // Remove active state from all fields
            document.querySelectorAll('.text-field').forEach(field => {
                field.classList.remove('is-active');
            });
        }

        function selectFieldByTextNode(textNode) {
            const fieldElement = document.querySelector(`[data-text-node="${textNode}"]`);
            if (fieldElement) {
                // Remove active state from all fields
                document.querySelectorAll('.text-field').forEach(field => {
                    field.classList.remove('is-active');
                });

                // Add active state to matching field
                fieldElement.classList.add('is-active');

                // Update active field indicator
                const placeholder = fieldElement.querySelector('.field-placeholder').textContent;
                showActiveField(placeholder);

                // Scroll field into view
                fieldElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        function highlightCanvasText(textNode) {
            // This would integrate with the canvas to highlight the corresponding text
            // For now, we'll dispatch a custom event that the canvas can listen to
            const event = new CustomEvent('highlightTextNode', {
                detail: { textNode: textNode }
            });
            document.dispatchEvent(event);
        }

        function syncWithCanvas() {
            // This would sync the panel state with the current canvas selection
            // For now, we'll just clear any active field
            clearActiveField();

            // Dispatch sync event for canvas integration
            const event = new CustomEvent('syncTextPanel');
            document.dispatchEvent(event);
        }
    </script>
</body>
</html>
