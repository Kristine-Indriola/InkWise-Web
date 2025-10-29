<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Design - Inkwise</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="{{ asset('css/customer/editing.css') }}">
    <style>
        .canvas-status-bar {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-top: 2px solid #dee2e6;
            padding: 12px 20px;
            font-size: 13px;
            color: #495057;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .canvas-status-bar:hover {
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
        }
        .canvas-status-bar span {
            margin: 0 12px;
            padding: 4px 8px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.7);
            transition: all 0.2s ease;
        }
        .canvas-status-bar span:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-1px);
        }
        .canvas-area {
            position: relative;
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            box-shadow:
                0 8px 32px rgba(0, 0, 0, 0.12),
                0 2px 8px rgba(0, 0, 0, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.8);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .canvas-area:hover {
            box-shadow:
                0 12px 40px rgba(0, 0, 0, 0.15),
                0 4px 12px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.9);
            transform: translateY(-1px);
        }
        /* Shape-specific styles */
        .canvas-area.shape-circle {
            border-radius: 50%;
            width: var(--canvas-width, 500px);
            height: var(--canvas-height, 500px);
        }
        .canvas-area.shape-circle .canvas {
            border-radius: 50%;
            overflow: hidden;
        }
        .canvas-area.shape-ellipse {
            border-radius: 50%;
            width: var(--canvas-width, 500px);
            height: var(--canvas-height, 500px);
        }
        .canvas-area.shape-ellipse .canvas {
            border-radius: 50%;
            overflow: hidden;
        }
        .canvas-area.shape-rounded-rectangle {
            border-radius: 25px;
        }
        .canvas-area.shape-rounded-rectangle .canvas {
            border-radius: 23px; /* Slightly less than container for visual effect */
            overflow: hidden;
        }
        .canvas-area.shape-custom {
            /* Custom shapes can be extended here */
            border-radius: 12px;
        }
        .canvas-area.shape-custom .canvas {
            border-radius: 10px;
            overflow: hidden;
        }
        .canvas {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 400px; /* Reduced from 720px to be more flexible */
            max-height: 1000px; /* Add max height constraint */
            background:
                radial-gradient(circle at 25% 25%, rgba(0, 0, 0, 0.02) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(0, 0, 0, 0.02) 0%, transparent 50%),
                linear-gradient(45deg, #f5f5f5 25%, transparent 25%),
                linear-gradient(-45deg, #f5f5f5 25%, transparent 25%),
                linear-gradient(45deg, transparent 75%, #f5f5f5 75%),
                linear-gradient(-45deg, transparent 75%, #f5f5f5 75%);
            background-size: 20px 20px, 20px 20px, 20px 20px, 20px 20px, 20px 20px, 20px 20px;
            background-position:
                0 0, 0 0,
                0 10px, 0 10px,
                10px -10px, 10px -10px;
            transition: all 0.3s ease;
            /* Removed overflow: auto to prevent scrolling */
        }
        .zoom-controls {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow:
                0 8px 32px rgba(0, 0, 0, 0.12),
                0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            z-index: 1000;
            max-width: 200px; /* Prevent overflow on small screens */
        }
        .zoom-controls:hover {
            transform: translateY(-2px);
            box-shadow:
                0 12px 40px rgba(0, 0, 0, 0.15),
                0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .zoom-controls button {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border: 1px solid #d1d5db;
            border-radius: 8px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            font-weight: 600;
            color: #374151;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .zoom-controls button:hover {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .zoom-controls button:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .zoom-controls #zoomLevel {
            font-size: 14px;
            font-weight: 600;
            min-width: 55px;
            text-align: center;
            color: #374151;
            background: rgba(0, 0, 0, 0.05);
            padding: 6px 8px;
            border-radius: 6px;
        }
        .grid-overlay {
            position: absolute;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: 1;
        }
        .safety-area {
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            border: 2px dashed rgba(59, 130, 246, 0.3);
            border-radius: 8px;
            pointer-events: none;
            z-index: 2;
        }
        .bleed-line {
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            bottom: 10px;
            border: 1px solid rgba(239, 68, 68, 0.4);
            border-radius: 4px;
            pointer-events: none;
            z-index: 2;
        }
        @media (max-width: 768px) {
            .canvas-area {
                margin: 0 12px;
                border-radius: 8px;
            }
            .canvas {
                min-height: 300px; /* Smaller minimum height for mobile */
                max-height: 600px; /* Smaller maximum height for mobile */
            }
            .zoom-controls {
                top: 12px;
                right: 12px;
                padding: 8px;
                border-radius: 8px;
                max-width: 160px; /* Smaller max width for mobile */
            }
            .zoom-controls button {
                width: 32px;
                height: 32px;
                font-size: 16px;
            }
            .canvas-status-bar {
                padding: 8px 12px;
                font-size: 12px;
                flex-wrap: wrap; /* Allow wrapping on small screens */
            }
            .canvas-status-bar span {
                margin: 0 4px 2px 0; /* Reduced margins and added bottom margin for wrapping */
                padding: 2px 6px;
            }
            .safety-area {
                top: 10px; /* Smaller margins for mobile */
                left: 10px;
                right: 10px;
                bottom: 10px;
            }
            .bleed-line {
                top: 5px; /* Smaller margins for mobile */
                left: 5px;
                right: 5px;
                bottom: 5px;
            }
        }
        .undo-redo-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-right: 16px;
        }
        .undo-redo-controls button {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border: 1px solid #d1d5db;
            border-radius: 6px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            color: #374151;
            transition: all 0.2s ease;
        }
        .undo-redo-controls button:hover:not(:disabled) {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .undo-redo-controls button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        .canvas-layers-panel {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            padding: 12px;
            min-width: 200px;
            max-height: 300px;
            overflow-y: auto;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            z-index: 1000;
            display: none;
        }
        .canvas-layers-panel.show {
            display: block;
        }
        .layers-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
        }
        .layer-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 8px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .layer-item:hover {
            background: rgba(0, 0, 0, 0.05);
        }
        .layer-item.active {
            background: rgba(59, 130, 246, 0.1);
            border-left: 3px solid #3b82f6;
        }
        .layer-visibility, .layer-icon {
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        .layer-name {
            flex: 1;
            font-size: 12px;
            color: #374151;
        }
        .canvas-stats {
            position: absolute;
            bottom: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 11px;
            color: #6b7280;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
        }
        .canvas-stats.show {
            display: block;
        }
    </style>
    <script src="{{ asset('js/customer/editing.js') }}" defer></script>
    <!-- SVG Template Editor for auto-parser enhanced editing -->
    <script src="{{ asset('js/svg-template-editor.js') }}" defer></script>
    <!-- Fabric.js for SVG -> editable canvas support -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.2.4/fabric.min.js" defer integrity="sha512-HkRNCiaZYxQAkHpLFYI90ObSzL0vaIXL8Xe3bM51vhdYI79RDFMLTAsmVH1xVPREmTlUWexgrQMk+c3RBTsLGw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
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

    $backSlotDefault = data_get($backSlot, 'default');
    $backSlotPreviewMedia = data_get($backSlot, 'preview');
    $backSlotImage = data_get($backSlot, 'image');
    $hasBackDesign = !empty($backSvg)
        || !empty($backImage ?? null)
        || !empty($backSlotDefault)
        || !empty($backSlotPreviewMedia)
        || !empty($backSlotImage);

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

    $textFieldCollection = collect($providedPresets)->map(function ($field) {
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
    });

    if (!$hasBackDesign) {
        $textFieldCollection = $textFieldCollection->filter(function ($field) {
            return ($field['side'] ?? 'front') !== 'back';
        });
    }

    $textFieldPresets = $textFieldCollection->values()->toArray();

    if (!empty($frontSvg) || !empty($backSvg)) {
        $textFieldPresets = [];
    }
@endphp

    <!-- TOP BAR -->
    <div class="editor-topbar">
        <div class="left-tools">
            <div class="undo-redo-controls">
                <button class="undo-btn" id="canvasUndo" type="button" title="Undo last action" disabled>↶</button>
                <button class="redo-btn" id="canvasRedo" type="button" title="Redo last action" disabled>↷</button>
            </div>
            <button id="editModeToggle" class="edit-mode-btn" type="button" title="Toggle Edit Mode">Edit Mode</button>
            <button id="showLayers" class="layers-btn" type="button" title="Show layers panel">Layers</button>
            <button id="showStats" class="stats-btn" type="button" title="Show canvas stats">Stats</button>
        </div>
        <div class="right-tools">
            <a href="{{ route('templates.wedding.invitations') }}" class="change-template">Change template</a>
            <button class="preview-btn" type="button">Preview</button>
            <button id="changeImageBtn" class="change-image-btn" type="button" title="Change background image">Change Image</button>
            <input type="file" id="backgroundFileInput" accept="image/*" style="display:none" />
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
        <div class="canvas-column">
            <div class="canvas-area">
            <!-- Canvas Layers Panel -->
            <div class="canvas-layers-panel" id="layersPanel">
                <div class="layers-header">
                    <span>Layers</span>
                    <button id="toggleLayers" type="button" title="Toggle layers panel">×</button>
                </div>
                <div id="layersList" class="layers-list">
                    <!-- Layer items will be populated here -->
                </div>
            </div>

            <!-- Canvas Stats -->
            <div class="canvas-stats" id="canvasStats">
                <span id="statsObjects">Objects: 0</span> |
                <span id="statsMemory">Memory: 0MB</span> |
                <span id="statsFps">FPS: 60</span>
            </div>
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
                        {{-- Render the provided SVG from Figma (kept visible so editor/parser can read it) --}}
                        {!! $frontSvg !!}
                    @else
                        <svg viewBox="0 0 500 700" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-labelledby="frontTitle" data-svg-editor>
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
                    <!-- Always include the Fabric canvas overlay so imported SVGs become editable -->
                    <canvas id="fabricFront" width="500" height="700" style="display:block; width:500px; height:700px;"></canvas>
                </div>
                @if($hasBackDesign)
                    <div id="cardBack" class="card" data-card="back" data-default-image="{{ $backPreview }}" role="img" aria-label="Back design preview">
                        @if(!empty($backSvg))
                            {{-- Render provided back SVG from Figma --}}
                            {!! $backSvg !!}
                        @else
                            <svg viewBox="0 0 500 700" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-labelledby="backTitle" data-svg-editor>
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
                        <!-- Always include the Fabric canvas overlay for back too -->
                        <canvas id="fabricBack" width="500" height="700" style="display:block; width:500px; height:700px;"></canvas>
                </div>
                @endif
            </div>
            <div class="zoom-controls">
                <button id="zoomOut" type="button">-</button>
                <span id="zoomLevel">100%</span>
                <button id="zoomIn" type="button">+</button>
                <button id="zoomFit" type="button" title="Zoom to fit">Fit</button>
            </div>
            <!-- Canvas Status Bar (placed below the canvas area) -->
            <div class="canvas-status-bar">
                <span id="statusZoom">Zoom: 100%</span>
                <span id="statusSelection">Selected: None</span>
                <span id="statusDimensions">Canvas: 500x700px (rectangle)</span>
            </div>
        </div> <!-- .canvas-column -->

        <!-- RIGHT PANEL (Front/Back toggle + text fields) -->
        <div class="right-panel">
            <div class="product-summary">
                <h2>{{ $productName }}</h2>
            </div>

            <div class="view-toggle">
                <button id="showFront" class="active" type="button">Front</button>
                @if($hasBackDesign)
                    <button id="showBack" type="button">Back</button>
                @endif
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
                                fetch(url).then(function(r){
                                    if (r.status === 404) {
                                        console.warn('SVGRepo endpoint not available (404)');
                                        iconsGrid.innerHTML = '<div style="padding:8px; color:#666; font-size:12px;">Icon search unavailable</div>';
                                        return;
                                    }
                                    if (!r.ok) throw new Error('HTTP ' + r.status);
                                    return r.json();
                                }).then(function(json){
                                    if (!json || !json.results) { iconsGrid.innerHTML = ''; return; }
                                    // results contain objects with svg property
                                    renderSvgRepoResults(json.results);
                                }).catch(function(err){
                                    console.error('SVGRepo search error', err);
                                    iconsGrid.innerHTML = '<div style="padding:8px; color:#666; font-size:12px;">Icon search failed</div>';
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

    <!-- Hidden fields for exported SVGs (populated on Save) -->
    <input type="hidden" id="exportFrontSvg" name="front_svg" />
    <input type="hidden" id="exportBackSvg" name="back_svg" />

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
            // Group toggle functionality - only if elements exist
            const groupToggles = document.querySelectorAll('.group-toggle');
            if (groupToggles.length > 0) {
                groupToggles.forEach(button => {
                    button.addEventListener('click', function() {
                        const group = this.closest('.text-fields-group');
                        const content = group ? group.querySelector('.group-content') : null;
                        if (content) {
                            const isActive = this.classList.toggle('active');
                            content.classList.toggle('active', isActive);
                            updateFieldCounts();
                        }
                    });
                });
            }

            // Enhanced delete functionality - only if elements exist
            document.addEventListener('click', function(e) {
                const deleteBtn = e.target.closest('.delete-text');
                if (deleteBtn) {
                    e.preventDefault();
                    const fieldElement = deleteBtn.closest('.text-field');
                    const placeholder = fieldElement ? fieldElement.querySelector('.field-placeholder') : null;
                    if (fieldElement && placeholder) {
                        const placeholderText = placeholder.textContent;
                        if (confirm(`Are you sure you want to delete "${placeholderText}"? This action cannot be undone.`)) {
                            fieldElement.remove();
                            updateFieldCounts();
                            clearActiveField();
                        }
                    }
                }
            });

            // Field selection and canvas connection - only if elements exist
            document.addEventListener('click', function(e) {
                const fieldElement = e.target.closest('.text-field');
                if (fieldElement) {
                    const textNode = fieldElement.dataset.textNode;
                    const placeholder = fieldElement.querySelector('.field-placeholder');
                    if (placeholder) {
                        const placeholderText = placeholder.textContent;

                        // Remove active state from all fields
                        document.querySelectorAll('.text-field').forEach(field => {
                            field.classList.remove('is-active');
                        });

                        // Add active state to clicked field
                        fieldElement.classList.add('is-active');

                        // Update active field indicator if it exists
                        const indicator = document.getElementById('activeFieldIndicator');
                        const nameElement = document.getElementById('activeFieldName');
                        if (indicator && nameElement) {
                            nameElement.textContent = placeholderText;
                            indicator.style.display = 'flex';
                        }

                        // Highlight corresponding text on canvas
                        highlightCanvasText(textNode);
                    }
                }
            });

            // Canvas text click handler (would be connected to canvas text elements)
            document.addEventListener('canvasTextClick', function(e) {
                const textNode = e.detail.textNode;
                selectFieldByTextNode(textNode);
            });

            // Sync with canvas button - only if it exists
            const syncBtn = document.getElementById('syncCanvas');
            if (syncBtn) {
                syncBtn.addEventListener('click', function() {
                    syncWithCanvas();
                });
            }

            // Initialize
            updateFieldCounts();
        }

        function updateFieldCounts() {
            // Update front side count - only if elements exist
            const frontFields = document.getElementById('frontFields');
            const frontCountEl = document.getElementById('front-count');
            if (frontFields && frontCountEl) {
                const frontCount = frontFields.querySelectorAll('.text-field').length;
                frontCountEl.textContent = frontCount;
            }

            // Update back side count - only if elements exist
            const backFields = document.getElementById('backFields');
            const backCountEl = document.getElementById('back-count');
            if (backFields && backCountEl) {
                const backCount = backFields.querySelectorAll('.text-field').length;
                backCountEl.textContent = backCount;
            }

            // Update total count - only if element exists
            const totalFieldsEl = document.getElementById('total-fields');
            if (totalFieldsEl) {
                const frontCount = frontFields ? frontFields.querySelectorAll('.text-field').length : 0;
                const backCount = backFields ? backFields.querySelectorAll('.text-field').length : 0;
                const totalCount = frontCount + backCount;
                totalFieldsEl.textContent = totalCount;
            }
        }

        function showActiveField(fieldName) {
            const indicator = document.getElementById('activeFieldIndicator');
            const nameElement = document.getElementById('activeFieldName');

            if (indicator && nameElement) {
                nameElement.textContent = fieldName;
                indicator.style.display = 'flex';
            }
        }

        function clearActiveField() {
            const indicator = document.getElementById('activeFieldIndicator');
            if (indicator) {
                indicator.style.display = 'none';
            }

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

    <script>
        // Fabric-based SVG -> editable canvas support
        (function(){
            // wait until DOM + fabric available
            function whenReady(cb){
                if (document.readyState === 'complete' && window.fabric) return cb();
                document.addEventListener('DOMContentLoaded', function(){
                    var wait = function(){ if (window.fabric) cb(); else setTimeout(wait, 50); };
                    wait();
                });
            }

            whenReady(function(){
                // Globals
                window.currentView = window.currentView || 'front';
                var canvases = { front: null, back: null };

                // Undo/Redo System
                var history = { front: [], back: [] };
                var historyIndex = { front: -1, back: -1 };
                var maxHistorySize = 50;

                function saveCanvasState(canvas, view) {
                    var state = JSON.stringify(canvas.toJSON());
                    var viewHistory = history[view];
                    var viewIndex = historyIndex[view];

                    // Remove any history after current index
                    viewHistory.splice(viewIndex + 1);

                    // Add new state
                    viewHistory.push(state);

                    // Limit history size
                    if (viewHistory.length > maxHistorySize) {
                        viewHistory.shift();
                    } else {
                        viewIndex++;
                    }

                    historyIndex[view] = viewIndex;
                    updateUndoRedoButtons(view);
                }

                function undoCanvas(view) {
                    var viewHistory = history[view];
                    var viewIndex = historyIndex[view];

                    if (viewIndex > 0) {
                        viewIndex--;
                        historyIndex[view] = viewIndex;
                        loadCanvasState(canvases[view], viewHistory[viewIndex]);
                        updateUndoRedoButtons(view);
                        showNotification('Action undone', 'info');
                    }
                }

                function redoCanvas(view) {
                    var viewHistory = history[view];
                    var viewIndex = historyIndex[view];

                    if (viewIndex < viewHistory.length - 1) {
                        viewIndex++;
                        historyIndex[view] = viewIndex;
                        loadCanvasState(canvases[view], viewHistory[viewIndex]);
                        updateUndoRedoButtons(view);
                        showNotification('Action redone', 'info');
                    }
                }

                function loadCanvasState(canvas, stateJson) {
                    try {
                        var state = JSON.parse(stateJson);
                        canvas.loadFromJSON(state, function() {
                            canvas.requestRenderAll();
                            updateSelectionDisplay(canvas);
                            updateLayersPanel(canvas);
                        });
                    } catch (error) {
                        console.error('Failed to load canvas state:', error);
                        showNotification('Failed to undo/redo action', 'error');
                    }
                }

                function updateUndoRedoButtons(view) {
                    var undoBtn = document.getElementById('canvasUndo');
                    var redoBtn = document.getElementById('canvasRedo');

                    if (undoBtn) {
                        undoBtn.disabled = historyIndex[view] <= 0;
                    }
                    if (redoBtn) {
                        redoBtn.disabled = historyIndex[view] >= history[view].length - 1;
                    }
                }

                // Layers Panel System
                function updateLayersPanel(canvas) {
                    var layersList = document.getElementById('layersList');
                    if (!layersList) return;

                    layersList.innerHTML = '';
                    var objects = canvas.getObjects();

                    // Add layers in reverse order (top to bottom)
                    for (var i = objects.length - 1; i >= 0; i--) {
                        var obj = objects[i];
                        var layerItem = document.createElement('div');
                        layerItem.className = 'layer-item';
                        layerItem.dataset.objectIndex = i;

                        var visibilityIcon = obj.visible ? '👁' : '🙈';
                        var typeIcon = getObjectTypeIcon(obj.type);
                        var objectName = getObjectDisplayName(obj, i);

                        layerItem.innerHTML = `
                            <span class="layer-visibility">${visibilityIcon}</span>
                            <span class="layer-icon">${typeIcon}</span>
                            <span class="layer-name">${objectName}</span>
                        `;

                        if (canvas.getActiveObject() === obj) {
                            layerItem.classList.add('active');
                        }

                        layerItem.addEventListener('click', function() {
                            var index = parseInt(this.dataset.objectIndex);
                            var targetObj = canvas.getObjects()[index];
                            canvas.setActiveObject(targetObj);
                            canvas.requestRenderAll();
                            updateSelectionDisplay(canvas);
                            updateLayersPanel(canvas);
                        });

                        layersList.appendChild(layerItem);
                    }
                }

                function getObjectTypeIcon(type) {
                    switch (type) {
                        case 'i-text':
                        case 'textbox':
                            return '📝';
                        case 'image':
                            return '🖼️';
                        case 'rect':
                            return '▭';
                        case 'circle':
                            return '○';
                        case 'triangle':
                            return '△';
                        case 'path':
                            return '✏️';
                        default:
                            return '📄';
                    }
                }

                function getObjectDisplayName(obj, index) {
                    if (obj.textNodeName) {
                        return obj.textNodeName;
                    }
                    if (obj.text) {
                        return obj.text.substring(0, 15) + (obj.text.length > 15 ? '...' : '');
                    }
                    return obj.type + ' ' + (index + 1);
                }

                // Canvas Stats System
                var statsUpdateInterval;
                var lastFrameTime = performance.now();
                var frameCount = 0;
                var fps = 60;

                function startStatsMonitoring(canvas) {
                    if (statsUpdateInterval) clearInterval(statsUpdateInterval);

                    statsUpdateInterval = setInterval(function() {
                        updateCanvasStats(canvas);
                    }, 1000);

                    // FPS monitoring
                    function updateFPS() {
                        var now = performance.now();
                        frameCount++;
                        if (now - lastFrameTime >= 1000) {
                            fps = Math.round((frameCount * 1000) / (now - lastFrameTime));
                            frameCount = 0;
                            lastFrameTime = now;
                        }
                        requestAnimationFrame(updateFPS);
                    }
                    updateFPS();
                }

                function updateCanvasStats(canvas) {
                    var statsObjects = document.getElementById('statsObjects');
                    var statsMemory = document.getElementById('statsMemory');
                    var statsFps = document.getElementById('statsFps');

                    if (statsObjects) {
                        statsObjects.textContent = 'Objects: ' + canvas.getObjects().length;
                    }

                    if (statsMemory) {
                        // Estimate memory usage (rough calculation)
                        var objectCount = canvas.getObjects().length;
                        var estimatedMemory = Math.round((objectCount * 0.5) + (canvas.width * canvas.height * 4 / 1024 / 1024));
                        statsMemory.textContent = 'Memory: ~' + estimatedMemory + 'MB';
                    }

                    if (statsFps) {
                        statsFps.textContent = 'FPS: ' + fps;
                    }
                }

                function getSvgDimensions(svgElement) {
                    if (!svgElement) return { width: 500, height: 700 };

                    // Try to get dimensions from viewBox first
                    var viewBox = svgElement.getAttribute('viewBox');
                    if (viewBox) {
                        var parts = viewBox.split(/\s+/);
                        if (parts.length >= 4) {
                            var width = parseFloat(parts[2]);
                            var height = parseFloat(parts[3]);
                            if (!isNaN(width) && !isNaN(height) && width > 0 && height > 0) {
                                return { width: width, height: height };
                            }
                        }
                    }

                    // Fallback to width/height attributes
                    var width = parseFloat(svgElement.getAttribute('width'));
                    var height = parseFloat(svgElement.getAttribute('height'));

                    if (!isNaN(width) && !isNaN(height) && width > 0 && height > 0) {
                        return { width: width, height: height };
                    }

                    // Final fallback to default dimensions
                    return { width: 500, height: 700 };
                }

                function detectSvgShape(svgElement) {
                    if (!svgElement) return 'rectangle';

                    // Look for shape-defining elements
                    var circles = svgElement.querySelectorAll('circle');
                    var ellipses = svgElement.querySelectorAll('ellipse');
                    var rects = svgElement.querySelectorAll('rect');
                    var paths = svgElement.querySelectorAll('path');

                    // Check for circular shapes
                    if (circles.length > 0) {
                        // Check if there's a large circle that covers most of the SVG
                        var svgRect = svgElement.getBoundingClientRect();
                        var circle = circles[0];
                        var cx = parseFloat(circle.getAttribute('cx') || 0);
                        var cy = parseFloat(circle.getAttribute('cy') || 0);
                        var r = parseFloat(circle.getAttribute('r') || 0);

                        // If circle covers most of the viewBox, consider it circular
                        var viewBox = svgElement.getAttribute('viewBox');
                        if (viewBox) {
                            var parts = viewBox.split(/\s+/);
                            if (parts.length >= 4) {
                                var vbWidth = parseFloat(parts[2]);
                                var vbHeight = parseFloat(parts[3]);
                                // If circle radius is close to half the smaller dimension, it's likely a circle template
                                var minDim = Math.min(vbWidth, vbHeight);
                                if (r >= minDim * 0.4) {
                                    return 'circle';
                                }
                            }
                        }
                    }

                    // Check for elliptical shapes
                    if (ellipses.length > 0) {
                        var ellipse = ellipses[0];
                        var rx = parseFloat(ellipse.getAttribute('rx') || 0);
                        var ry = parseFloat(ellipse.getAttribute('ry') || 0);

                        if (rx !== ry && rx > 0 && ry > 0) {
                            return 'ellipse';
                        }
                    }

                    // Check for paths that might be custom shapes
                    if (paths.length > 0) {
                        var path = paths[0];
                        var d = path.getAttribute('d') || '';

                        // Simple heuristic: if path contains arc commands, might be circular
                        if (d.includes('A') || d.includes('a')) {
                            return 'custom';
                        }
                    }

                    // Check for rounded rectangles
                    if (rects.length > 0) {
                        var rect = rects[0];
                        var rx = rect.getAttribute('rx');
                        var ry = rect.getAttribute('ry');

                        if (rx || ry) {
                            var rxVal = parseFloat(rx || ry || 0);
                            var ryVal = parseFloat(ry || rx || 0);

                            // If rounded corners are significant, consider it rounded
                            var width = parseFloat(rect.getAttribute('width') || 0);
                            var height = parseFloat(rect.getAttribute('height') || 0);

                            if (rxVal > Math.min(width, height) * 0.1 || ryVal > Math.min(width, height) * 0.1) {
                                return 'rounded-rectangle';
                            }
                        }
                    }

                    // Default to rectangle
                    return 'rectangle';
                }

                function updateCanvasAreaShape(cardId, shape) {
                    var card = document.getElementById(cardId);
                    if (!card) return;

                    var canvasArea = card.querySelector('.canvas-area');
                    if (!canvasArea) return;

                    // Remove all shape classes
                    canvasArea.classList.remove('shape-circle', 'shape-ellipse', 'shape-rounded-rectangle', 'shape-custom');

                    // Add the appropriate shape class
                    if (shape !== 'rectangle') {
                        canvasArea.classList.add('shape-' + shape);
                    }

                    // Update CSS custom properties for dynamic styling
                    var dimensions = getSvgDimensions(card.querySelector('svg'));
                    canvasArea.style.setProperty('--canvas-width', dimensions.width + 'px');
                    canvasArea.style.setProperty('--canvas-height', dimensions.height + 'px');
                }

                function updateCanvasContainerDimensions(cardId, dimensions) {
                    var card = document.getElementById(cardId);
                    if (!card) return;

                    // Get the canvas container (the .canvas element)
                    var canvasContainer = card.querySelector('.canvas');
                    if (!canvasContainer) return;

                    // Get viewport dimensions (accounting for padding and other elements)
                    var viewportWidth = window.innerWidth;
                    var viewportHeight = window.innerHeight;

                    // Account for sidebar and other UI elements (approximate)
                    var sidebarWidth = 320; // Approximate sidebar width
                    var headerHeight = 80; // Approximate header height
                    var statusBarHeight = 60; // Approximate status bar height
                    var padding = 40; // Padding around canvas

                    var availableWidth = viewportWidth - sidebarWidth - (padding * 2);
                    var availableHeight = viewportHeight - headerHeight - statusBarHeight - (padding * 2);

                    // Calculate scale to fit canvas within available space
                    var scaleX = availableWidth / dimensions.width;
                    var scaleY = availableHeight / dimensions.height;
                    var scale = Math.min(scaleX, scaleY, 1); // Don't scale up beyond 100%

                    // Apply minimum and maximum scale limits
                    scale = Math.max(0.1, Math.min(1, scale));

                    // Update container dimensions to fit scaled canvas
                    var scaledWidth = dimensions.width * scale;
                    var scaledHeight = dimensions.height * scale;

                    canvasContainer.style.width = scaledWidth + 'px';
                    canvasContainer.style.height = scaledHeight + 'px';
                    canvasContainer.style.maxWidth = '100%';
                    canvasContainer.style.maxHeight = '100%';

                    // Ensure the canvas itself fits within the container
                    var canvasEl = card.querySelector('canvas');
                    if (canvasEl) {
                        canvasEl.style.width = scaledWidth + 'px';
                        canvasEl.style.height = scaledHeight + 'px';
                        canvasEl.style.maxWidth = '100%';
                        canvasEl.style.maxHeight = '100%';
                    }

                    // Update zoom level to reflect the scaling
                    var currentCanvas = canvases[window.currentView];
                    if (currentCanvas) {
                        currentCanvas.setZoom(scale);
                        updateZoomDisplay(scale);
                    }
                }

                function resizeCanvasToSvg(canvas, svgElement, cardId) {
                    if (!canvas || !svgElement) return;

                    var dimensions = getSvgDimensions(svgElement);
                    var shape = detectSvgShape(svgElement);

                    // Update canvas area shape
                    updateCanvasAreaShape(cardId, shape);

                    // Update canvas dimensions
                    canvas.setWidth(dimensions.width);
                    canvas.setHeight(dimensions.height);

                    // Update canvas element styles
                    var canvasEl = canvas.getElement();
                    if (canvasEl) {
                        canvasEl.style.width = dimensions.width + 'px';
                        canvasEl.style.height = dimensions.height + 'px';
                    }

                    // Update container dimensions
                    updateCanvasContainerDimensions(cardId, dimensions);

                    // Update status bar
                    var statusDimensionsEl = document.getElementById('statusDimensions');
                    if (statusDimensionsEl) {
                        statusDimensionsEl.textContent = 'Canvas: ' + Math.round(dimensions.width) + 'x' + Math.round(dimensions.height) + 'px (' + shape + ')';
                    }

                    // Re-render canvas
                    canvas.requestRenderAll();

                    // Update grid background
                    addGridBackground(canvas);
                }

                function addGridBackground(canvas) {
                    var gridSize = 20; // pixels
                    var ctx = canvas.getContext('2d');
                    var width = canvas.width;
                    var height = canvas.height;

                    // Create a pattern for the grid with enhanced styling
                    var patternCanvas = document.createElement('canvas');
                    patternCanvas.width = gridSize;
                    patternCanvas.height = gridSize;
                    var patternCtx = patternCanvas.getContext('2d');

                    // Clear pattern canvas
                    patternCtx.clearRect(0, 0, gridSize, gridSize);

                    // Create subtle gradient for grid lines
                    var gradient = patternCtx.createLinearGradient(0, 0, gridSize, gridSize);
                    gradient.addColorStop(0, 'rgba(0, 0, 0, 0.04)');
                    gradient.addColorStop(0.5, 'rgba(0, 0, 0, 0.08)');
                    gradient.addColorStop(1, 'rgba(0, 0, 0, 0.04)');

                    // Draw main grid lines
                    patternCtx.strokeStyle = gradient;
                    patternCtx.lineWidth = 1;
                    patternCtx.beginPath();
                    patternCtx.moveTo(0, 0);
                    patternCtx.lineTo(gridSize, 0);
                    patternCtx.moveTo(0, 0);
                    patternCtx.lineTo(0, gridSize);
                    patternCtx.stroke();

                    // Add subtle secondary grid lines (every 5 units)
                    patternCtx.strokeStyle = 'rgba(0, 0, 0, 0.02)';
                    patternCtx.lineWidth = 0.5;
                    patternCtx.beginPath();
                    patternCtx.moveTo(gridSize/2, 0);
                    patternCtx.lineTo(gridSize/2, gridSize);
                    patternCtx.moveTo(0, gridSize/2);
                    patternCtx.lineTo(gridSize, gridSize/2);
                    patternCtx.stroke();

                    // Add tiny dots at intersections for better alignment
                    patternCtx.fillStyle = 'rgba(0, 0, 0, 0.03)';
                    patternCtx.beginPath();
                    patternCtx.arc(0, 0, 0.8, 0, 2 * Math.PI);
                    patternCtx.arc(gridSize/2, gridSize/2, 0.5, 0, 2 * Math.PI);
                    patternCtx.fill();

                    // Create pattern and set as background
                    var pattern = ctx.createPattern(patternCanvas, 'repeat');
                    canvas.setBackgroundColor(pattern, function() {
                        canvas.renderAll();
                    });
                }

                function createFabricCanvasForCard(cardId, canvasId){
                    var card = document.getElementById(cardId);
                    if (!card) return null;
                    var svg = card.querySelector('svg');
                    var canvasEl = document.getElementById(canvasId);
                    if (!svg || !canvasEl) return null;

                    // Get SVG dimensions and shape dynamically
                    var dimensions = getSvgDimensions(svg);
                    var shape = detectSvgShape(svg);

                    // Update container dimensions and shape
                    updateCanvasContainerDimensions(cardId, dimensions);
                    updateCanvasAreaShape(cardId, shape);

                    // hide the original svg (we keep it for source reference)
                    svg.style.display = 'none';

                    var canvas = new fabric.Canvas(canvasId, {
                        selection: true,
                        preserveObjectStacking: true,
                        enableRetinaScaling: false, // Better performance
                        renderOnAddRemove: false, // Manual rendering for performance
                        skipOffscreen: true // Performance optimization
                    });

                    // Set dynamic canvas dimensions
                    canvas.setWidth(dimensions.width);
                    canvas.setHeight(dimensions.height);

                    // Update canvas element styles to match
                    canvasEl.style.width = dimensions.width + 'px';
                    canvasEl.style.height = dimensions.height + 'px';

                    // Add grid background
                    addGridBackground(canvas);

                    // parse only simple known elements: rect (background), image, text
                    // iterate in DOM order and add objects to canvas in same order so stacking preserved
                    Array.from(svg.childNodes).forEach(function(node){
                        if (node.nodeType !== 1) return; // element
                        var tag = node.tagName.toLowerCase();
                        if (tag === 'defs' || tag === 'title') return; // skip
                        if (tag === 'rect'){
                            var x = parseFloat(node.getAttribute('x') || 0);
                            var y = parseFloat(node.getAttribute('y') || 0);
                            var w = parseFloat(node.getAttribute('width') || canvas.width);
                            var h = parseFloat(node.getAttribute('height') || canvas.height);
                            var fill = node.getAttribute('fill') || '#ffffff';
                            var selectable = node.getAttribute('data-background-layer') ? false : false;
                            var rect = new fabric.Rect({ left: x, top: y, width: w, height: h, fill: fill, selectable: selectable, evented: false, originX: 'left', originY: 'top' });
                            canvas.add(rect);
                        } else if (tag === 'image'){
                            var href = getSvgHref(node);
                            var x = parseFloat(node.getAttribute('x') || 0);
                            var y = parseFloat(node.getAttribute('y') || 0);
                            var w = parseFloat(node.getAttribute('width') || canvas.width);
                            var h = parseFloat(node.getAttribute('height') || canvas.height);
                            var preserve = node.getAttribute('preserveAspectRatio') || 'xMidYMid slice';
                            var editableName = node.getAttribute('id') || node.getAttribute('data-editable-image') || null;
                            var isChangeable = node.hasAttribute('data-changeable');
                            var changeableId = node.getAttribute('data-changeable-id') || null;

                            // Skip if no valid href
                            if (!href || href.trim() === '') {
                                console.warn('Image element missing href attribute');
                                return;
                            }

                            try {
                                fabric.Image.fromURL(href, function(img){
                                    if (!img || !img.getElement()) {
                                        console.warn('Failed to load image:', href);
                                        return;
                                    }

                                    // Set initial position
                                    img.set({
                                        left: x,
                                        top: y,
                                        originX: 'left',
                                        originY: 'top',
                                        selectable: true, // Make images selectable
                                        evented: true,    // Make images respond to events
                                        hasControls: true, // Show resize handles
                                        hasBorders: true,  // Show selection borders
                                        lockUniScaling: false, // Allow free scaling
                                        centeredRotation: true
                                    });

                                    // Enhanced scaling logic with aspect ratio preservation
                                    var targetWidth = w;
                                    var targetHeight = h;
                                    var imgAspectRatio = img.width / img.height;
                                    var targetAspectRatio = targetWidth / targetHeight;

                                    // Handle percentage-based dimensions
                                    var widthAttr = node.getAttribute('width') || '';
                                    var heightAttr = node.getAttribute('height') || '';

                                    if (widthAttr.indexOf('%') !== -1 || widthAttr === '100%') {
                                        targetWidth = canvas.width;
                                    }
                                    if (heightAttr.indexOf('%') !== -1 || heightAttr === '100%') {
                                        targetHeight = canvas.height;
                                    }

                                    // Apply preserveAspectRatio logic
                                    var preserveMode = preserve.split(' ')[1] || 'slice';
                                    var alignMode = preserve.split(' ')[0] || 'xMidYMid';

                                    if (preserveMode === 'meet') {
                                        // Fit entire image within bounds
                                        if (imgAspectRatio > targetAspectRatio) {
                                            // Image is wider than target - fit by height
                                            img.scaleToHeight(targetHeight);
                                        } else {
                                            // Image is taller than target - fit by width
                                            img.scaleToWidth(targetWidth);
                                        }
                                    } else if (preserveMode === 'slice') {
                                        // Fill entire bounds, cropping if necessary
                                        if (imgAspectRatio > targetAspectRatio) {
                                            // Image is wider than target - fill by width
                                            img.scaleToWidth(targetWidth);
                                        } else {
                                            // Image is taller than target - fill by height
                                            img.scaleToHeight(targetHeight);
                                        }
                                    } else {
                                        // Default: stretch to fit
                                        img.scaleToWidth(targetWidth);
                                        img.scaleToHeight(targetHeight);
                                    }

                                    // Apply alignment based on preserveAspectRatio
                                    var scaledWidth = img.getScaledWidth();
                                    var scaledHeight = img.getScaledHeight();
                                    var offsetX = 0;
                                    var offsetY = 0;

                                    if (alignMode.includes('xMid')) {
                                        offsetX = (targetWidth - scaledWidth) / 2;
                                    } else if (alignMode.includes('xMax')) {
                                        offsetX = targetWidth - scaledWidth;
                                    }

                                    if (alignMode.includes('YMid')) {
                                        offsetY = (targetHeight - scaledHeight) / 2;
                                    } else if (alignMode.includes('YMax')) {
                                        offsetY = targetHeight - scaledHeight;
                                    }

                                    img.set({
                                        left: x + offsetX,
                                        top: y + offsetY
                                    });

                                    // Add quality and performance optimizations
                                    var imgElement = img.getElement();
                                    if (imgElement) {
                                        // Enable image smoothing for better quality
                                        imgElement.style.imageRendering = 'auto';
                                        // Add crossOrigin for CORS compliance
                                        imgElement.crossOrigin = 'anonymous';
                                    }

                                    // Set additional properties for better performance
                                    img.set({
                                        objectCaching: true,
                                        statefulCache: true,
                                        cacheKey: href // Use URL as cache key
                                    });

                                    // Custom properties for identification and changeability
                                    if (editableName) {
                                        img.editableImageName = editableName;
                                        // Force editable images to be selectable
                                        img.set({
                                            selectable: true,
                                            evented: true,
                                            hasControls: true,
                                            hasBorders: true,
                                            lockUniScaling: false,
                                            centeredRotation: true
                                        });
                                    }

                                    // Handle changeable images
                                    if (isChangeable) {
                                        img.isChangeableImage = true;
                                        img.changeableId = changeableId;
                                        img.set({
                                            selectable: true,
                                            evented: true,
                                            hasControls: true,
                                            hasBorders: true,
                                            hoverCursor: 'pointer',
                                            moveCursor: 'pointer'
                                        });

                                        // Add double-click handler for image replacement
                                        img.on('mousedblclick', function() {
                                            showImageReplacementDialog(img);
                                        });
                                    }

                                    // Layer management: send full-card images to back (but not editable images)
                                    var isFullCard = (x === 0 && y === 0) &&
                                        (widthAttr === '100%' || heightAttr === '100%' ||
                                         Math.abs(targetWidth - canvas.width) < 10 ||
                                         Math.abs(targetHeight - canvas.height) < 10);

                                    if (isFullCard && !editableName && !isChangeable) {
                                        img.sendToBack();
                                    }

                                    // Add to canvas and render
                                    canvas.add(img);
                                    canvas.requestRenderAll();

                                    // Log successful image load for debugging
                                    console.log('Image loaded successfully:', {
                                        src: href,
                                        position: { x: img.left, y: img.top },
                                        size: { width: scaledWidth, height: scaledHeight },
                                        preserveAspectRatio: preserve,
                                        selectable: img.selectable,
                                        evented: img.evented,
                                        editableImageName: img.editableImageName,
                                        isChangeableImage: img.isChangeableImage,
                                        changeableId: img.changeableId
                                    });

                                }, {
                                    crossOrigin: 'anonymous',
                                    // Add error handling options
                                    onError: function() {
                                        console.error('Image failed to load:', href);
                                        // Could add fallback placeholder image here
                                    }
                                });
                            } catch (e) {
                                console.error('Image processing error for:', href, e);
                            }

                        } else if (tag === 'text'){
                            var txt = node.textContent || '';
                            var x = parseFloat(node.getAttribute('x') || 0);
                            var y = parseFloat(node.getAttribute('y') || 0);
                            var fontSize = parseFloat(node.getAttribute('font-size') || 16);
                            var fill = node.getAttribute('fill') || '#000000';
                            var anchor = node.getAttribute('text-anchor') || 'start';
                            var align = anchor === 'middle' ? 'center' : (anchor === 'end' ? 'right' : 'left');
                            var letterSpacing = parseFloat(node.getAttribute('letter-spacing') || 0);

                            var itext = new fabric.IText(txt, {
                                left: x,
                                top: y,
                                fontSize: fontSize,
                                fill: fill,
                                originX: anchor === 'middle' ? 'center' : 'left',
                                originY: 'middle',
                                textAlign: align,
                                editable: true,
                                selectable: true,
                                evented: true
                            });
                            // store node-id mapping so we can sync back later if needed
                            var nodeName = node.getAttribute('data-text-node') || node.id || null;
                            if (nodeName) itext.textNodeName = nodeName;
                            canvas.add(itext);
                        } else if (tag === 'rect'){
                            var x = parseFloat(node.getAttribute('x') || 0);
                            var y = parseFloat(node.getAttribute('y') || 0);
                            var w = parseFloat(node.getAttribute('width') || canvas.width);
                            var h = parseFloat(node.getAttribute('height') || canvas.height);
                            var fill = node.getAttribute('fill') || '#ffffff';
                            var rx = parseFloat(node.getAttribute('rx') || 0); // border radius
                            var editableName = node.getAttribute('id') || node.getAttribute('data-editable-image') || null;
                            var isChangeable = node.hasAttribute('data-changeable');
                            var changeableId = node.getAttribute('data-changeable-id') || null;

                            // Check if this rect has a pattern fill (indicating it's an image placeholder)
                            var isPatternFill = fill && fill.startsWith('url(#');

                            if (isChangeable && isPatternFill) {
                                // Extract pattern ID from fill attribute
                                var patternId = fill.match(/url\(#([^)]+)\)/);
                                if (patternId && patternId[1]) {
                                    patternId = patternId[1];

                                    // Find the pattern definition in the SVG
                                    var svgRoot = node.ownerDocument.documentElement;
                                    var pattern = svgRoot.querySelector('#' + patternId);
                                    if (pattern) {
                                        // Look for image reference in the pattern
                                        var useElement = pattern.querySelector('use');
                                        if (useElement) {
                                            var imageHref = useElement.getAttributeNS('http://www.w3.org/1999/xlink', 'href') ||
                                                           useElement.getAttribute('xlink:href') ||
                                                           useElement.getAttribute('href');

                                            if (imageHref && imageHref.startsWith('#')) {
                                                // Find the actual image definition
                                                var imageDef = svgRoot.querySelector(imageHref);
                                                if (imageDef && imageDef.tagName === 'image') {
                                                    var actualImageHref = imageDef.getAttributeNS('http://www.w3.org/1999/xlink', 'href') ||
                                                                         imageDef.getAttribute('xlink:href') ||
                                                                         imageDef.getAttribute('href');

                                                    if (actualImageHref) {
                                                        try {
                                                            fabric.Image.fromURL(actualImageHref, function(img){
                                                                if (!img || !img.getElement()) {
                                                                    console.warn('Failed to load pattern image:', actualImageHref);
                                                                    return;
                                                                }

                                                                // Set initial position and size
                                                                img.set({
                                                                    left: x,
                                                                    top: y,
                                                                    originX: 'left',
                                                                    originY: 'top',
                                                                    selectable: true,
                                                                    evented: true,
                                                                    hasControls: true,
                                                                    hasBorders: true,
                                                                    lockUniScaling: false,
                                                                    centeredRotation: true
                                                                });

                                                                // Scale image to fit the rect dimensions
                                                                var imgAspectRatio = img.width / img.height;
                                                                var rectAspectRatio = w / h;

                                                                if (imgAspectRatio > rectAspectRatio) {
                                                                    // Image is wider - fit by width
                                                                    img.scaleToWidth(w);
                                                                } else {
                                                                    // Image is taller - fit by height
                                                                    img.scaleToHeight(h);
                                                                }

                                                                // Center the image within the rect
                                                                var scaledWidth = img.getScaledWidth();
                                                                var scaledHeight = img.getScaledHeight();
                                                                var offsetX = (w - scaledWidth) / 2;
                                                                var offsetY = (h - scaledHeight) / 2;

                                                                img.set({
                                                                    left: x + offsetX,
                                                                    top: y + offsetY
                                                                });

                                                                // Add quality and performance optimizations
                                                                var imgElement = img.getElement();
                                                                if (imgElement) {
                                                                    imgElement.style.imageRendering = 'auto';
                                                                    imgElement.crossOrigin = 'anonymous';
                                                                }

                                                                // Set additional properties for better performance
                                                                img.set({
                                                                    objectCaching: true,
                                                                    statefulCache: true,
                                                                    cacheKey: actualImageHref
                                                                });

                                                                // Custom properties for identification and changeability
                                                                if (editableName) {
                                                                    img.editableImageName = editableName;
                                                                }

                                                                // Handle changeable images
                                                                if (isChangeable) {
                                                                    img.isChangeableImage = true;
                                                                    img.changeableId = changeableId;
                                                                    img.set({
                                                                        selectable: true,
                                                                        evented: true,
                                                                        hasControls: true,
                                                                        hasBorders: true,
                                                                        hoverCursor: 'pointer',
                                                                        moveCursor: 'pointer'
                                                                    });

                                                                    // Add double-click handler for image replacement
                                                                    img.on('mousedblclick', function() {
                                                                        showImageReplacementDialog(img);
                                                                    });
                                                                }

                                                                // Add to canvas and render
                                                                canvas.add(img);
                                                                canvas.requestRenderAll();

                                                                // Log successful image load for debugging
                                                                console.log('Pattern-based image loaded successfully:', {
                                                                    src: actualImageHref,
                                                                    position: { x: img.left, y: img.top },
                                                                    size: { width: scaledWidth, height: scaledHeight },
                                                                    rect: { x: x, y: y, width: w, height: h },
                                                                    selectable: img.selectable,
                                                                    evented: img.evented,
                                                                    isChangeableImage: img.isChangeableImage,
                                                                    changeableId: img.changeableId
                                                                });

                                                            }, {
                                                                crossOrigin: 'anonymous',
                                                                onError: function() {
                                                                    console.error('Pattern image failed to load:', actualImageHref);
                                                                }
                                                            });
                                                        } catch (e) {
                                                            console.error('Pattern image processing error for:', actualImageHref, e);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            } else if (isChangeable && !isPatternFill) {
                                // Handle changeable rects that are not pattern-based (solid color placeholders)
                                var rect = new fabric.Rect({
                                    left: x,
                                    top: y,
                                    width: w,
                                    height: h,
                                    fill: fill,
                                    rx: rx,
                                    selectable: true,
                                    evented: true,
                                    hasControls: true,
                                    hasBorders: true,
                                    hoverCursor: 'pointer',
                                    moveCursor: 'pointer',
                                    originX: 'left',
                                    originY: 'top'
                                });

                                // Custom properties for changeability
                                rect.isChangeableImage = true;
                                rect.changeableId = changeableId;

                                // Add double-click handler for image replacement
                                rect.on('mousedblclick', function() {
                                    showImageReplacementDialog(rect);
                                });

                                canvas.add(rect);
                                console.log('Changeable rect added:', {
                                    position: { x: rect.left, y: rect.top },
                                    size: { width: w, height: h },
                                    fill: fill,
                                    isChangeableImage: rect.isChangeableImage
                                });
                            }
                            // for other tags, try to import their outerHTML as an image snapshot fallback
                            // skip for now
                        }
                    });

                    // ensure text objects are on top
                    canvas.getObjects().forEach(function(o){ if (o.type && (o.type.indexOf('text')!==-1 || o instanceof fabric.IText)) o.bringToFront(); });

                    // Force all images with editableImageName to be selectable
                    canvas.getObjects().forEach(function(o) {
                        if (o.type === 'image' && o.editableImageName) {
                            o.set({
                                selectable: true,
                                evented: true,
                                hasControls: true,
                                hasBorders: true
                            });
                            console.log('Forced image to be selectable:', o.editableImageName);
                        }
                    });

                    // Also force selectability after a short delay to ensure async images are loaded
                    setTimeout(function() {
                        canvas.getObjects().forEach(function(o) {
                            if ((o.type === 'image' || o.type === 'rect') && (o.editableImageName || o.isChangeableImage)) {
                                o.set({
                                    selectable: true,
                                    evented: true,
                                    hasControls: true,
                                    hasBorders: true,
                                    hoverCursor: 'pointer',
                                    moveCursor: 'pointer'
                                });
                                console.log('Delayed force object to be selectable:', o.type, o.editableImageName || o.changeableId, 'selectable:', o.selectable, 'evented:', o.evented);
                            }
                        });
                        canvas.requestRenderAll();
                    }, 1000);

                    canvas.requestRenderAll();

                        // Enhanced double-click to edit text with better UX
                        canvas.on('mouse:dblclick', function(e){
                            var target = e.target;
                            if (!target) return;
                            if (target.isType('i-text') || target.isType('textbox') || target instanceof fabric.IText) {
                                target.enterEditing();
                                target.selectAll();
                                // Focus the canvas for keyboard events
                                canvasEl.focus();
                            }
                        });

                        // Debug: Log mouse events
                        canvas.on('mouse:down', function(e) {
                            console.log('Canvas mouse down:', e.target ? e.target.type : 'canvas', e.target);
                            if (e.target && (e.target.type === 'image' || e.target.type === 'rect')) {
                                console.log('Object clicked - selectable:', e.target.selectable, 'evented:', e.target.evented, 'hasControls:', e.target.hasControls);
                            }
                        });

                        // Add canvas click handler for debugging
                        canvas.on('mouse:up', function(e) {
                            console.log('Canvas mouse up - active object:', canvas.getActiveObject());
                        });

                        // Add selection event handlers for debugging
                        canvas.on('selection:created', function(e) {
                            console.log('Selection created:', e.selected);
                        });

                        canvas.on('selection:updated', function(e) {
                            console.log('Selection updated:', e.selected);
                        });

                        canvas.on('selection:cleared', function() {
                            console.log('Selection cleared');
                        });

                        // Add keyboard shortcuts for better UX
                        canvas.on('key:down', function(e) {
                            var key = e.e.key;

                            // Delete selected objects with Delete or Backspace
                            if ((key === 'Delete' || key === 'Backspace') && canvas.getActiveObject()) {
                                var activeObject = canvas.getActiveObject();
                                if (activeObject && activeObject.selectable) {
                                    canvas.remove(activeObject);
                                    canvas.requestRenderAll();
                                    updateSelectionDisplay(canvas);
                                }
                                e.e.preventDefault();
                            }

                            // Escape to deselect
                            if (key === 'Escape') {
                                canvas.discardActiveObject();
                                canvas.requestRenderAll();
                                updateSelectionDisplay(canvas);
                                e.e.preventDefault();
                            }

                            // Ctrl+A to select all text objects
                            if (e.e.ctrlKey && key === 'a') {
                                var textObjects = canvas.getObjects().filter(function(obj) {
                                    return obj.isType('i-text') || obj.isType('textbox') || obj instanceof fabric.IText;
                                });
                                if (textObjects.length > 0) {
                                    var selection = new fabric.ActiveSelection(textObjects, { canvas: canvas });
                                    canvas.setActiveObject(selection);
                                    canvas.requestRenderAll();
                                    updateSelectionDisplay(canvas);
                                }
                                e.e.preventDefault();
                            }
                        });                    // Enhanced mouse wheel zoom with smooth animation
                    canvas.on('mouse:wheel', function(opt) {
                        var delta = opt.e.deltaY;
                        var zoom = canvas.getZoom();
                        var newZoom = zoom * Math.pow(0.999, delta);

                        // Apply zoom limits with smooth clamping
                        newZoom = Math.max(0.01, Math.min(20, newZoom));

                        // Smooth zoom animation
                        var zoomStep = (newZoom - zoom) / 10;
                        var stepCount = 0;

                        function animateZoom() {
                            stepCount++;
                            var currentZoom = zoom + (zoomStep * stepCount);

                            if (stepCount < 10) {
                                canvas.zoomToPoint({ x: opt.e.offsetX, y: opt.e.offsetY }, currentZoom);
                                updateZoomDisplay(currentZoom);
                                requestAnimationFrame(animateZoom);
                            } else {
                                canvas.zoomToPoint({ x: opt.e.offsetX, y: opt.e.offsetY }, newZoom);
                                updateZoomDisplay(newZoom);
                            }
                        }

                        animateZoom();
                        opt.e.preventDefault();
                        opt.e.stopPropagation();
                    });

                    return canvas;
                }

                // create canvases
                canvases.front = createFabricCanvasForCard('cardFront','fabricFront');
                canvases.back  = createFabricCanvasForCard('cardBack','fabricBack');

                // Initialize SVG Template Editor BEFORE the canvas hides the SVG
                document.addEventListener('DOMContentLoaded', function() {
                    // Initialize SvgTemplateEditor for SVG elements with auto-parser data
                    const svgElements = document.querySelectorAll('svg[data-svg-editor]');
                    svgElements.forEach(svgElement => {
                        try {
                            // Actually initialize the SVG template editor
                            console.log('Initializing SVG template editor for:', svgElement);
                            new SvgTemplateEditor(svgElement);
                        } catch (error) {
                            console.error('Failed to initialize SVG editor:', error);
                        }
                    });
                });

                // Resize canvases if custom SVGs are provided
                var frontSvg = document.querySelector('#cardFront svg');
                var backSvg = document.querySelector('#cardBack svg');

                if (canvases.front && frontSvg) {
                    var frontDimensions = getSvgDimensions(frontSvg);
                    if (frontDimensions.width !== 500 || frontDimensions.height !== 700) {
                        resizeCanvasToSvg(canvases.front, frontSvg, 'cardFront');
                    }
                }

                if (canvases.back && backSvg) {
                    var backDimensions = getSvgDimensions(backSvg);
                    if (backDimensions.width !== 500 || backDimensions.height !== 700) {
                        resizeCanvasToSvg(canvases.back, backSvg, 'cardBack');
                    }
                }

                // Add history tracking to canvases
                if (canvases.front) {
                    canvases.front.on('object:added', function() {
                        saveCanvasState(canvases.front, 'front');
                        updateLayersPanel(canvases.front);
                    });
                    canvases.front.on('object:removed', function() {
                        saveCanvasState(canvases.front, 'front');
                        updateLayersPanel(canvases.front);
                    });
                    canvases.front.on('object:modified', function() {
                        saveCanvasState(canvases.front, 'front');
                    });
                }

                if (canvases.back) {
                    canvases.back.on('object:added', function() {
                        saveCanvasState(canvases.back, 'back');
                        updateLayersPanel(canvases.back);
                    });
                    canvases.back.on('object:removed', function() {
                        saveCanvasState(canvases.back, 'back');
                        updateLayersPanel(canvases.back);
                    });
                    canvases.back.on('object:modified', function() {
                        saveCanvasState(canvases.back, 'back');
                    });
                }

                // wire view toggle buttons to set currentView and show/hide canvases
                var showFrontBtn = document.getElementById('showFront');
                var showBackBtn = document.getElementById('showBack');
                function showView(requestedView){
                    var hasBack = !!document.getElementById('cardBack');
                    var view = (requestedView === 'back' && !hasBack) ? 'front' : requestedView;

                    window.currentView = view;

                    document.querySelectorAll('.card').forEach(function(c){ c.classList.remove('active'); });

                    var activeCardId = view === 'front' ? 'cardFront' : 'cardBack';
                    var activeCard = document.getElementById(activeCardId);
                    if (activeCard) {
                        activeCard.classList.add('active');
                    }

                    var fabricFront = document.getElementById('fabricFront');
                    if (fabricFront) {
                        fabricFront.style.display = (view === 'front') ? 'block' : 'none';
                    }

                    var fabricBack = document.getElementById('fabricBack');
                    if (fabricBack) {
                        fabricBack.style.display = (view === 'back') ? 'block' : 'none';
                    }

                    return view;
                }
                if (showFrontBtn) showFrontBtn.addEventListener('click', function(){ showView('front'); });
                if (showBackBtn) showBackBtn.addEventListener('click', function(){ showView('back'); });
                // start showing front
                showView(window.currentView);

                // Ensure initial UI status (zoom, selection, dimensions) is visible and accurate
                setTimeout(function(){
                    try {
                        updateZoomDisplay(1);
                        var activeCanvas = canvases[window.currentView] || canvases.front || null;
                        updateSelectionDisplay(activeCanvas);
                        var statusDimensionsEl = document.getElementById('statusDimensions');
                        if (statusDimensionsEl && activeCanvas) {
                            var activeCardId = window.currentView === 'front' ? 'cardFront' : 'cardBack';
                            var activeSvg = document.querySelector('#' + activeCardId + ' svg');
                            var activeShape = activeSvg ? detectSvgShape(activeSvg) : 'rectangle';
                            statusDimensionsEl.textContent = 'Canvas: ' + Math.round(activeCanvas.width) + 'x' + Math.round(activeCanvas.height) + 'px (' + activeShape + ')';
                        }
                    } catch (e) { console.warn('Initial UI status init failed', e); }
                }, 80);

                // Zoom controls
                var zoomLevelEl = document.getElementById('zoomLevel');
                var statusZoomEl = document.getElementById('statusZoom');
                var statusSelectionEl = document.getElementById('statusSelection');
                var statusDimensionsEl = document.getElementById('statusDimensions');
                var zoomInBtn = document.getElementById('zoomIn');
                var zoomOutBtn = document.getElementById('zoomOut');
                var zoomFitBtn = document.getElementById('zoomFit');

                function updateZoomDisplay(zoom) {
                    if (typeof zoom !== 'number' || isNaN(zoom)) {
                        console.warn('Invalid zoom value:', zoom);
                        return;
                    }

                    var zoomPercent = Math.round(zoom * 100) + '%';
                    var zoomLevelEl = document.getElementById('zoomLevel');
                    var statusZoomEl = document.getElementById('statusZoom');

                    if (zoomLevelEl) {
                        zoomLevelEl.textContent = zoomPercent;
                        // Add visual feedback for zoom level
                        zoomLevelEl.style.color = zoom > 2 ? '#ef4444' : zoom < 0.5 ? '#f59e0b' : '#374151';
                    }
                    if (statusZoomEl) {
                        statusZoomEl.textContent = 'Zoom: ' + zoomPercent;
                    }
                }

                function updateSelectionDisplay(canvas) {
                    if (!canvas) return;

                    try {
                        var activeObject = canvas.getActiveObject();
                        var statusSelectionEl = document.getElementById('statusSelection');

                        if (!statusSelectionEl) return;

                        if (activeObject) {
                            var type = activeObject.type || 'Unknown';
                            var info = type.charAt(0).toUpperCase() + type.slice(1);

                            if (activeObject.text) {
                                var text = activeObject.text.substring(0, 25);
                                if (activeObject.text.length > 25) text += '...';
                                info += ' "' + text + '"';
                            } else if (type === 'image') {
                                info += ' (' + Math.round(activeObject.width * activeObject.scaleX) + '×' + Math.round(activeObject.height * activeObject.scaleY) + ')';
                            } else if (type === 'rect') {
                                info += ' (' + Math.round(activeObject.width) + '×' + Math.round(activeObject.height) + ')';
                            }

                            statusSelectionEl.textContent = 'Selected: ' + info;
                            statusSelectionEl.style.fontWeight = '600';
                        } else {
                            statusSelectionEl.textContent = 'Selected: None';
                            statusSelectionEl.style.fontWeight = 'normal';
                        }
                    } catch (error) {
                        console.error('Error updating selection display:', error);
                        var statusSelectionEl = document.getElementById('statusSelection');
                        if (statusSelectionEl) {
                            statusSelectionEl.textContent = 'Selected: Error';
                        }
                    }
                }

                function zoomCanvas(factor, animate = true) {
                    var canvas = canvases[window.currentView];
                    if (!canvas) {
                        console.warn('Canvas not available for zoom');
                        return;
                    }

                    var currentZoom = canvas.getZoom();
                    var newZoom = currentZoom * factor;

                    // Apply zoom limits
                    newZoom = Math.max(0.01, Math.min(20, newZoom));

                    if (animate) {
                        // Smooth zoom animation
                        var zoomStep = (newZoom - currentZoom) / 8;
                        var stepCount = 0;

                        function animateZoom() {
                            stepCount++;
                            var stepZoom = currentZoom + (zoomStep * stepCount);

                            if (stepCount < 8) {
                                canvas.setZoom(stepZoom);
                                updateZoomDisplay(stepZoom);
                                requestAnimationFrame(animateZoom);
                            } else {
                                canvas.setZoom(newZoom);
                                updateZoomDisplay(newZoom);
                            }
                        }

                        animateZoom();
                    } else {
                        canvas.setZoom(newZoom);
                        updateZoomDisplay(newZoom);
                    }
                }

                function zoomToFit() {
                    var canvas = canvases[window.currentView];
                    if (!canvas) {
                        console.warn('Canvas not available for zoom to fit');
                        return;
                    }

                    // Get the canvas container
                    var cardId = window.currentView === 'front' ? 'cardFront' : 'cardBack';
                    var card = document.getElementById(cardId);
                    var canvasContainer = card ? card.querySelector('.canvas') : null;

                    if (!canvasContainer) {
                        console.warn('Canvas container not found');
                        return;
                    }

                    // Get container dimensions
                    var containerRect = canvasContainer.getBoundingClientRect();

                    // Calculate scale to fit canvas in container
                    var scaleX = containerRect.width / canvas.width;
                    var scaleY = containerRect.height / canvas.height;
                    var scale = Math.min(scaleX, scaleY, 1); // Don't scale up beyond 100%

                    // Apply minimum and maximum zoom limits
                    scale = Math.max(0.1, Math.min(2, scale));

                    // Smooth animation to target zoom
                    var currentZoom = canvas.getZoom();
                    var zoomStep = (scale - currentZoom) / 10;
                    var stepCount = 0;

                    function animateToFit() {
                        stepCount++;
                        var stepZoom = currentZoom + (zoomStep * stepCount);

                        if (stepCount < 10) {
                            canvas.setZoom(stepZoom);
                            updateZoomDisplay(stepZoom);
                            requestAnimationFrame(animateToFit);
                        } else {
                            canvas.setZoom(scale);
                            canvas.setViewportTransform([scale, 0, 0, scale, 0, 0]);
                            updateZoomDisplay(scale);
                        }
                    }

                    animateToFit();
                }

                // Initialize status bar with dynamic dimensions and shape
                var currentCanvas = canvases[window.currentView];
                var currentCardId = window.currentView === 'front' ? 'cardFront' : 'cardBack';
                var currentSvg = document.querySelector('#' + currentCardId + ' svg');
                var currentShape = currentSvg ? detectSvgShape(currentSvg) : 'rectangle';

                if (currentCanvas && statusDimensionsEl) {
                    statusDimensionsEl.textContent = 'Canvas: ' + Math.round(currentCanvas.width) + 'x' + Math.round(currentCanvas.height) + 'px (' + currentShape + ')';
                } else if (statusDimensionsEl) {
                    statusDimensionsEl.textContent = 'Canvas: 500x700px (rectangle)'; // fallback
                }

                // Add UI control event listeners
                var canvasUndoBtn = document.getElementById('canvasUndo');
                var canvasRedoBtn = document.getElementById('canvasRedo');
                var showLayersBtn = document.getElementById('showLayers');
                var showStatsBtn = document.getElementById('showStats');
                var toggleLayersBtn = document.getElementById('toggleLayers');
                var layersPanel = document.getElementById('layersPanel');
                var canvasStats = document.getElementById('canvasStats');

                // Undo/Redo event listeners
                if (canvasUndoBtn) {
                    canvasUndoBtn.addEventListener('click', function() {
                        undoCanvas(window.currentView);
                    });
                }
                if (canvasRedoBtn) {
                    canvasRedoBtn.addEventListener('click', function() {
                        redoCanvas(window.currentView);
                    });
                }

                // Layers panel toggle
                if (showLayersBtn) {
                    showLayersBtn.addEventListener('click', function() {
                        if (layersPanel) {
                            layersPanel.classList.toggle('show');
                            updateLayersPanel(canvases[window.currentView]);
                        }
                    });
                }
                if (toggleLayersBtn) {
                    toggleLayersBtn.addEventListener('click', function() {
                        if (layersPanel) {
                            layersPanel.classList.remove('show');
                        }
                    });
                }

                // Stats panel toggle
                if (showStatsBtn) {
                    showStatsBtn.addEventListener('click', function() {
                        if (canvasStats) {
                            canvasStats.classList.toggle('show');
                            if (canvasStats.classList.contains('show')) {
                                startStatsMonitoring(canvases[window.currentView]);
                            } else {
                                if (statsUpdateInterval) {
                                    clearInterval(statsUpdateInterval);
                                }
                            }
                        }
                    });
                }

                // Update layers and stats when switching views
                var originalShowView = showView;
                showView = function(v) {
                    var activeView = originalShowView(v) || window.currentView || 'front';
                    var activeCanvas = canvases[activeView] || canvases.front || null;

                    if (layersPanel && layersPanel.classList.contains('show') && activeCanvas) {
                        updateLayersPanel(activeCanvas);
                    }

                    updateUndoRedoButtons(activeView);

                    if (statusDimensionsEl && activeCanvas) {
                        var hasBackCard = !!document.getElementById('cardBack');
                        var activeCardId = (activeView === 'back' && hasBackCard) ? 'cardBack' : 'cardFront';
                        var activeSvg = document.querySelector('#' + activeCardId + ' svg');
                        var activeShape = activeSvg ? detectSvgShape(activeSvg) : 'rectangle';

                        statusDimensionsEl.textContent = 'Canvas: ' + Math.round(activeCanvas.width) + 'x' + Math.round(activeCanvas.height) + 'px (' + activeShape + ')';
                    }
                };

                // Initialize undo/redo buttons for current view
                updateUndoRedoButtons(window.currentView);

                if (zoomInBtn) zoomInBtn.addEventListener('click', function() { zoomCanvas(1.2); });
                if (zoomOutBtn) zoomOutBtn.addEventListener('click', function() { zoomCanvas(0.8); });
                if (zoomFitBtn) zoomFitBtn.addEventListener('click', function() { zoomToFit(); });

                // Change Image button flow
                var changeBtn = document.getElementById('changeImageBtn');
                var fileInput = document.getElementById('backgroundFileInput');
                if (changeBtn && fileInput) {
                    changeBtn.addEventListener('click', function(){ fileInput.click(); });
                    fileInput.addEventListener('change', function(e){
                        var f = e.target.files && e.target.files[0];
                        if (!f) return;
                        var reader = new FileReader();
                        reader.onload = function(evt){
                            var dataUrl = evt.target.result;
                            applyBackgroundReplacement(dataUrl, window.currentView);
                        };
                        reader.readAsDataURL(f);
                    });
                }

                function applyBackgroundReplacement(dataUrl, view){
                    var canvas = canvases[view];
                    if (!canvas) {
                        showNotification('Canvas not ready for image replacement', 'error');
                        return;
                    }

                    try {
                        // Show loading state
                        showNotification('Replacing background image...', 'info');

                        // find candidate image object: prefer object with editableImageName === view OR svgId === 'backgroundImage'
                        var targetObj = null;
                        canvas.getObjects().forEach(function(o){
                            if (!o || o.type !== 'image') return;
                            if (o.editableImageName && (o.editableImageName === view || o.editableImageName === 'backgroundImage')) targetObj = o;
                            if (!targetObj && (o.editableImageName && o.editableImageName.indexOf('background')!==-1)) targetObj = o;
                            // fallback: an image that covers the full canvas
                            if (!targetObj && o.left === 0 && o.top === 0 && (Math.abs((o.width*o.scaleX) - canvas.width) < 4 || Math.abs((o.height*o.scaleY) - canvas.height) < 4)) targetObj = o;
                        });

                        if (!targetObj) {
                            // if no image object, create a new full-bleed image and send to back
                            fabric.Image.fromURL(dataUrl, function(img){
                                if (!img || !img.getElement()) {
                                    showNotification('Failed to load image', 'error');
                                    return;
                                }

                                // Enhanced image setup for background replacement
                                img.set({
                                    left: 0,
                                    top: 0,
                                    originX: 'left',
                                    originY: 'top',
                                    selectable: false,
                                    evented: false,
                                    objectCaching: true,
                                    statefulCache: true
                                });

                                // Scale to fill canvas while preserving aspect ratio
                                var canvasAspectRatio = canvas.width / canvas.height;
                                var imgAspectRatio = img.width / img.height;

                                if (imgAspectRatio > canvasAspectRatio) {
                                    // Image is wider - scale to height and center horizontally
                                    img.scaleToHeight(canvas.height);
                                    var scaledWidth = img.getScaledWidth();
                                    img.set({ left: (canvas.width - scaledWidth) / 2 });
                                } else {
                                    // Image is taller - scale to width and center vertically
                                    img.scaleToWidth(canvas.width);
                                    var scaledHeight = img.getScaledHeight();
                                    img.set({ top: (canvas.height - scaledHeight) / 2 });
                                }

                                // Quality optimization
                                var imgElement = img.getElement();
                                if (imgElement) {
                                    imgElement.style.imageRendering = 'auto';
                                    imgElement.crossOrigin = 'anonymous';
                                }

                                canvas.add(img);
                                img.sendToBack();

                                // Ensure texts remain on top
                                canvas.getObjects().forEach(function(o){
                                    if (o.type && (o.type.indexOf('text') !== -1 || o instanceof fabric.IText)) {
                                        o.bringToFront();
                                    }
                                });

                                canvas.requestRenderAll();
                                showNotification('Background image replaced successfully!', 'success');
                            }, {
                                crossOrigin: 'anonymous',
                                onError: function() {
                                    showNotification('Failed to load replacement image', 'error');
                                }
                            });
                            return;
                        }

                        // Replace existing image with enhanced logic
                        targetObj.setSrc(dataUrl, function(){
                            if (!targetObj || !targetObj.getElement()) {
                                showNotification('Failed to replace image', 'error');
                                return;
                            }

                            // Lock background properties
                            targetObj.set({
                                selectable: false,
                                evented: false,
                                objectCaching: true,
                                statefulCache: true
                            });

                            // Enhanced scaling for background images
                            var isBackgroundImage = targetObj.left === 0 && targetObj.top === 0;
                            if (isBackgroundImage) {
                                var canvasAspectRatio = canvas.width / canvas.height;
                                var imgAspectRatio = targetObj.width / targetObj.height;

                                if (imgAspectRatio > canvasAspectRatio) {
                                    // Image is wider - scale to height and center horizontally
                                    targetObj.scaleToHeight(canvas.height);
                                    var scaledWidth = targetObj.getScaledWidth();
                                    targetObj.set({ left: (canvas.width - scaledWidth) / 2 });
                                } else {
                                    // Image is taller - scale to width and center vertically
                                    targetObj.scaleToWidth(canvas.width);
                                    var scaledHeight = targetObj.getScaledHeight();
                                    targetObj.set({ top: (canvas.height - scaledHeight) / 2 });
                                }
                            }

                            // Quality optimization
                            var imgElement = targetObj.getElement();
                            if (imgElement) {
                                imgElement.style.imageRendering = 'auto';
                                imgElement.crossOrigin = 'anonymous';
                            }

                            // Ensure proper layering
                            targetObj.sendToBack();

                            // Keep text objects on top
                            canvas.getObjects().forEach(function(o){
                                if (o.type && (o.type.indexOf('text') !== -1 || o instanceof fabric.IText)) {
                                    o.bringToFront();
                                }
                            });

                            canvas.requestRenderAll();
                            showNotification('Background image replaced successfully!', 'success');

                            // Update canvas dimensions if needed
                            if (targetObj.width && targetObj.height) {
                                var cardId = view === 'front' ? 'cardFront' : 'cardBack';
                                var svg = document.querySelector('#' + cardId + ' svg');
                                if (svg) {
                                    svg.setAttribute('viewBox', '0 0 ' + targetObj.width + ' ' + targetObj.height);
                                    resizeCanvasToSvg(canvas, svg, cardId);
                                }
                            }
                        }, {
                            crossOrigin: 'anonymous',
                            onError: function() {
                                showNotification('Failed to replace image', 'error');
                            }
                        });
                    } catch (err) {
                        console.error('Replace image failed:', err);
                        showNotification('Failed to replace background image', 'error');
                    }
                }

                // Enhanced Save handler with better error handling and user feedback
                var saveBtn = document.querySelector('.save-btn');
                if (saveBtn) {
                    saveBtn.addEventListener('click', function(){
                        try {
                            // Show loading state
                            var originalText = saveBtn.textContent;
                            saveBtn.textContent = 'Saving...';
                            saveBtn.disabled = true;

                            var frontSvg = canvases.front ? canvases.front.toSVG() : '';
                            var backSvg  = canvases.back  ? canvases.back.toSVG()  : '';
                            var frontInput = document.getElementById('exportFrontSvg');
                            var backInput  = document.getElementById('exportBackSvg');

                            if (frontInput) frontInput.value = frontSvg;
                            if (backInput)  backInput.value  = backSvg;

                            // Validate SVG content
                            if (!frontSvg && !backSvg) {
                                throw new Error('No canvas content to save');
                            }

                            console.log('Front SVG length:', frontSvg.length);
                            console.log('Back SVG length:', backSvg.length);

                            // Create download links for preview
                            if (frontSvg) {
                                var frontDl = document.createElement('a');
                                frontDl.href = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(frontSvg);
                                frontDl.download = 'front-design.svg';
                                frontDl.style.display = 'none';
                                document.body.appendChild(frontDl);
                                frontDl.click();
                                frontDl.remove();
                            }

                            // Reset button state
                            saveBtn.textContent = originalText;
                            saveBtn.disabled = false;

                            // Show success message
                            showNotification('Design saved successfully!', 'success');

                        } catch (e) {
                            console.error('Export failed:', e);
                            saveBtn.textContent = originalText;
                            saveBtn.disabled = false;
                            showNotification('Failed to save design: ' + e.message, 'error');
                        }
                    });
                }

                // Helper function to show notifications
                function showNotification(message, type) {
                    // Remove existing notifications
                    var existingNotifications = document.querySelectorAll('.canvas-notification');
                    existingNotifications.forEach(function(notif) {
                        notif.remove();
                    });

                    // Create new notification
                    var notification = document.createElement('div');
                    notification.className = 'canvas-notification ' + (type || 'info');
                    notification.textContent = message;
                    notification.style.cssText = `
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: ${type === 'error' ? '#ef4444' : type === 'success' ? '#10b981' : '#3b82f6'};
                        color: white;
                        padding: 12px 20px;
                        border-radius: 8px;
                        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                        z-index: 10000;
                        font-weight: 500;
                        animation: slideIn 0.3s ease-out;
                    `;

                    document.body.appendChild(notification);

                    // Auto-remove after 3 seconds
                    setTimeout(function() {
                        notification.style.animation = 'slideOut 0.3s ease-in';
                        setTimeout(function() {
                            notification.remove();
                        }, 300);
                    }, 3000);
                }

                // Function to show image replacement dialog for changeable images
                function showImageReplacementDialog(fabricObject) {
                    // Create file input
                    const input = document.createElement('input');
                    input.type = 'file';
                    input.accept = 'image/*';
                    input.style.display = 'none';

                    input.addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        if (file) {
                            handleImageReplacement(file, fabricImage);
                        }
                        document.body.removeChild(input);
                    });

                    document.body.appendChild(input);
                    input.click();
                }

                // Function to handle image replacement
                function handleImageReplacement(file, fabricObject) {
                    const reader = new FileReader();

                    reader.onload = function(e) {
                        const imageUrl = e.target.result;

                        // Show loading state
                        showNotification('Replacing image...', 'info');

                        if (fabricObject.type === 'image') {
                            // Replace the image source
                            fabricObject.setSrc(imageUrl, function() {
                                // Ensure the image remains selectable and interactive
                                fabricObject.set({
                                    selectable: true,
                                    evented: true,
                                    hasControls: true,
                                    hasBorders: true,
                                    hoverCursor: 'pointer',
                                    moveCursor: 'pointer'
                                });

                                // Re-render the canvas
                                fabricObject.canvas.requestRenderAll();

                                // Trigger change event
                                const event = new CustomEvent('fabricImageReplaced', {
                                    detail: {
                                        fabricImage: fabricObject,
                                        imageUrl: imageUrl,
                                        file: file
                                    }
                                });
                                fabricObject.canvas.getElement().dispatchEvent(event);

                                showNotification('Image replaced successfully!', 'success');
                            });
                        } else if (fabricObject.type === 'rect') {
                            // For rect objects, we need to create a new image object to replace it
                            fabric.Image.fromURL(imageUrl, function(newImg) {
                                if (!newImg || !newImg.getElement()) {
                                    showNotification('Failed to load replacement image', 'error');
                                    return;
                                }

                                // Position the new image where the rect was
                                newImg.set({
                                    left: fabricObject.left,
                                    top: fabricObject.top,
                                    originX: fabricObject.originX,
                                    originY: fabricObject.originY,
                                    selectable: true,
                                    evented: true,
                                    hasControls: true,
                                    hasBorders: true,
                                    hoverCursor: 'pointer',
                                    moveCursor: 'pointer',
                                    lockUniScaling: false,
                                    centeredRotation: true
                                });

                                // Scale to fit the rect dimensions
                                var rectWidth = fabricObject.width * fabricObject.scaleX;
                                var rectHeight = fabricObject.height * fabricObject.scaleY;
                                var imgAspectRatio = newImg.width / newImg.height;
                                var rectAspectRatio = rectWidth / rectHeight;

                                if (imgAspectRatio > rectAspectRatio) {
                                    newImg.scaleToWidth(rectWidth);
                                } else {
                                    newImg.scaleToHeight(rectHeight);
                                }

                                // Center within the original rect bounds
                                var scaledWidth = newImg.getScaledWidth();
                                var scaledHeight = newImg.getScaledHeight();
                                var offsetX = (rectWidth - scaledWidth) / 2;
                                var offsetY = (rectHeight - scaledHeight) / 2;

                                newImg.set({
                                    left: fabricObject.left + offsetX,
                                    top: fabricObject.top + offsetY
                                });

                                // Set changeable properties
                                newImg.isChangeableImage = true;
                                newImg.changeableId = fabricObject.changeableId;

                                // Add double-click handler for further replacement
                                newImg.on('mousedblclick', function() {
                                    showImageReplacementDialog(newImg);
                                });

                                // Replace the rect with the image
                                var canvas = fabricObject.canvas;
                                canvas.remove(fabricObject);
                                canvas.add(newImg);
                                canvas.requestRenderAll();

                                // Trigger change event
                                const event = new CustomEvent('fabricImageReplaced', {
                                    detail: {
                                        fabricImage: newImg,
                                        imageUrl: imageUrl,
                                        file: file,
                                        replacedRect: fabricObject
                                    }
                                });
                                canvas.getElement().dispatchEvent(event);

                                showNotification('Image replaced successfully!', 'success');
                            }, {
                                crossOrigin: 'anonymous',
                                onError: function() {
                                    showNotification('Failed to load replacement image', 'error');
                                }
                            });
                        }
                    };

                    reader.readAsDataURL(file);
                }

                // Add notification CSS animations
                if (!document.getElementById('notification-styles')) {
                    var style = document.createElement('style');
                    style.id = 'notification-styles';
                    style.textContent = `
                        @keyframes slideIn {
                            from { transform: translateX(100%); opacity: 0; }
                            to { transform: translateX(0); opacity: 1; }
                        }
                        @keyframes slideOut {
                            from { transform: translateX(0); opacity: 1; }
                            to { transform: translateX(100%); opacity: 0; }
                        }
                    `;
                    document.head.appendChild(style);
                }

            }); // whenReady

        })();
    </script>
</body>
</html>
