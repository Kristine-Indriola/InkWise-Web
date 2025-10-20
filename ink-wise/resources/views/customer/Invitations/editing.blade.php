<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Design - Inkwise</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="{{ asset('css/customer/editing.css') }}">
    <script src="{{ asset('js/customer/editing.js') }}" defer></script>
    <style>
        /* Hide the in-editor image debug panel output for production/editor view */
        .editor-debug-panel { display: none !important; visibility: hidden !important; }
    </style>
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
    // Resolve preview URLs robustly: accept absolute URLs, '/...' paths, storage disk keys, or existing public paths
    // Normalize common incorrect filesystem paths (for example: "ink-wise/public/storage/templates/..")
    $resolvePreview = function ($raw, $fallback) {
        $raw = $raw ?? null;
        if (empty($raw)) return $fallback;
    $raw = trim($raw);
    // normalize windows backslashes to slashes
    $raw = str_replace('\\', '/', $raw);
    $pathOnly = parse_url($raw, PHP_URL_PATH) ?: $raw;
        // absolute URL
        if (preg_match('#^https?://#i', $raw)) return $raw;
        // public/storage full paths or paths that include an application folder (e.g. ink-wise/public/storage/...)
        if (stripos($raw, 'public/storage') !== false || stripos($raw, '/storage/templates/') !== false || stripos($raw, 'storage/templates') !== false) {
            // try to extract the path after 'public' or 'storage' and prefer public templates when available
            $after = preg_split('#public#i', $raw);
            $candidate = end($after);
            $candidate = ltrim($candidate, '/\\');
            // if candidate starts with storage/, keep it; if it starts with storage/templates, prefer templates public folder
            $base = basename($pathOnly);
            if ($base && file_exists(public_path('templates/' . $base))) {
                return asset('templates/' . $base);
            }
            if ($candidate) {
                // ensure we return a /storage/... URL
                if (stripos($candidate, 'storage/') === 0) {
                    return asset($candidate);
                }
                return asset('storage/' . ltrim($candidate, '/'));
            }
        }
        // explicit /storage/templates path -> prefer public/templates when available
        if (str_contains($pathOnly, '/storage/templates/')) {
            $base = basename($pathOnly);
            if ($base && file_exists(public_path('templates/' . $base))) {
                return asset('templates/' . $base);
            }
            // otherwise map to /storage/templates/<base>
            return asset('storage/templates/' . $base);
        }
        // leading slash -> asset
        if (str_starts_with($raw, '/')) {
            if (str_starts_with($pathOnly, '/storage/templates/')) {
                $base = basename($pathOnly);
                if ($base && file_exists(public_path('templates/' . $base))) {
                    return asset('templates/' . $base);
                }
            }
            return asset(ltrim($raw, '/'));
        }
        // already storage/ prefix
        if (str_starts_with($raw, 'storage/')) {
            if (str_starts_with($raw, 'storage/templates/')) {
                $base = basename($pathOnly);
                if ($base && file_exists(public_path('templates/' . $base))) {
                    return asset('templates/' . $base);
                }
            }
            return asset($raw);
        }
        // templates/ relative stored path -> map to storage/templates/
        if (str_starts_with($raw, 'templates/')) {
            $base = basename($pathOnly);
            if ($base && file_exists(public_path('templates/' . $base))) {
                return asset('templates/' . $base);
            }
            return asset('storage/' . ltrim($raw, '/'));
        }
        // public disk stored path (stored via Storage::disk('public')->put)
        try {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists(ltrim($raw, '/'))) {
                return asset('storage/' . ltrim($raw, '/'));
            }
        } catch (\Throwable $e) {
            // ignore and continue to file check
        }
        // If raw looks like an absolute OS path (Windows C:/ or Unix /var/...), try to map by basename into storage/templates
        if (preg_match('#^[A-Za-z]:/#', $raw) || preg_match('#^/#', $raw)) {
            $base = basename($pathOnly);
            if ($base) {
                try {
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists('templates/' . $base)) {
                        if (file_exists(public_path('templates/' . $base))) {
                            return asset('templates/' . $base);
                        }
                        return asset('storage/templates/' . $base);
                    }
                } catch (\Throwable $e) {}
                if (file_exists(public_path('storage/templates/' . $base))) {
                    return asset('storage/templates/' . $base);
                }
                if (file_exists(public_path('templates/' . $base))) {
                    return asset('templates/' . $base);
                }
            }
        }
        // raw path relative to public
        if (file_exists(public_path($raw))) return asset($raw);
        // fallback
        return $fallback;
    };

    $frontPreview = $resolvePreview($frontImage ?? ($frontSlot['default'] ?? null), $frontDefault);
    $backPreview = $resolvePreview($backImage ?? ($backSlot['default'] ?? null), $backDefault);

    // Additional normalization: if a preview was returned as an absolute filesystem path
    // (for example when stored paths include the project folder: "ink-wise/public/storage/…" or "C:/xampp/…/public/…"),
    // convert it to a root-relative URL so the browser can fetch it (e.g. "/storage/..." or "/templates/...").
    $normalizeToWebUrl = function ($u) {
        if (empty($u)) return $u;
        $u = str_replace('\\', '/', $u);
        // if it already looks like a URL or root-relative path, return as-is
        if (preg_match('#^https?://#i', $u) || str_starts_with($u, '/')) return $u;
        // if it contains '/public/' return the portion after public as a root-relative path
        if (stripos($u, '/public/') !== false) {
            $parts = preg_split('#/public/#i', $u);
            $after = end($parts);
            return '/' . ltrim(str_replace('\\', '/', $after), '/');
        }
        // if it contains "public\\" windows style
        if (stripos($u, 'public/') !== false) {
            $parts = preg_split('#public/#i', $u);
            $after = end($parts);
            return '/' . ltrim(str_replace('\\', '/', $after), '/');
        }
        // if it contains the storage/templates folder anywhere, prefer /storage/templates/
        if (stripos($u, 'storage/templates/') !== false) {
            $idx = stripos($u, 'storage/templates/');
            return '/' . ltrim(substr($u, $idx), '/\\');
        }
        // fallback: if path contains 'templates/' try mapping to /storage/templates/<basename> first
        if (stripos($u, 'templates/') !== false) {
            $base = basename($u);
            if ($base) {
                // prefer storage/templates when present
                if (file_exists(public_path('storage/templates/' . $base))) {
                    return '/storage/templates/' . $base;
                }
                // if it exists under public/templates, return that
                if (file_exists(public_path('templates/' . $base))) {
                    return '/templates/' . $base;
                }
            }
        }
        return $u;
    };

    $frontPreview = $normalizeToWebUrl($frontPreview);
    $backPreview = $normalizeToWebUrl($backPreview);
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
