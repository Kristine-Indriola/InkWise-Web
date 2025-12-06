<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $product->name ?? optional($template)->name ?? 'InkWise Studio' }} &mdash; InkWise</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-regular-rounded/css/uicons-regular-rounded.css">
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-solid-rounded/css/uicons-solid-rounded.css">
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-solid-straight/css/uicons-solid-straight.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@flaticon/flaticon-uicons/css/all/all.css">
    @if(app()->environment('local'))
        @viteReactRefresh
    @endif
    @vite([
        'resources/css/customer/studio.css',
        'resources/js/customer/studio/main.jsx',
    ])
</head>
<body>
@php
    $defaultFront = asset('Customerimages/invite/wedding2.png');
    $defaultBack = asset('Customerimages/invite/wedding3.jpg');

    $templateModel = $template ?? $product?->template;

    $normalizeToArray = static function ($value) {
        if (is_array($value)) {
            return $value;
        }

        if ($value instanceof \Illuminate\Contracts\Support\Arrayable) {
            return $value->toArray();
        }

        if ($value instanceof \JsonSerializable) {
            $serialized = $value->jsonSerialize();
            if (is_array($serialized)) {
                return $serialized;
            }

            if ($serialized instanceof \Illuminate\Contracts\Support\Arrayable) {
                return $serialized->toArray();
            }

            return (array) $serialized;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    };

    $formatDimension = static function ($value) {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            $numeric = (float) $value;
            if ((int) $numeric == $numeric) {
                return (string) ((int) $numeric);
            }

            return rtrim(rtrim(number_format($numeric, 6, '.', ''), '0'), '.');
        }

        return null;
    };

    $templateMetadata = $templateModel ? $normalizeToArray($templateModel->metadata ?? []) : [];
    $rawCanvasSettings = [];
    if (!empty($templateMetadata)) {
        $rawCanvasSettings = $templateMetadata['canvas'] ?? ($templateMetadata['builder_canvas'] ?? []);
    }

    $canvasSettings = $normalizeToArray($rawCanvasSettings);
    $canvasWidth = isset($canvasSettings['width']) ? (float) $canvasSettings['width'] : null;
    $canvasHeight = isset($canvasSettings['height']) ? (float) $canvasSettings['height'] : null;

    if ($canvasWidth !== null && $canvasWidth <= 0) {
        $canvasWidth = null;
    }

    if ($canvasHeight !== null && $canvasHeight <= 0) {
        $canvasHeight = null;
    }

    $canvasShape = $canvasSettings['shape'] ?? null;
    if (!is_string($canvasShape) || trim($canvasShape) === '') {
        $canvasShape = null;
    }

    $templateCanvas = ($canvasWidth && $canvasHeight)
        ? [
            'width' => $canvasWidth,
            'height' => $canvasHeight,
            'shape' => $canvasShape,
            'unit' => $canvasSettings['unit'] ?? 'px',
        ]
        : null;

    $canvasWidthAttr = $templateCanvas ? $formatDimension($templateCanvas['width']) : null;
    $canvasHeightAttr = $templateCanvas ? $formatDimension($templateCanvas['height']) : null;
    $canvasShapeAttr = $templateCanvas && $templateCanvas['shape'] ? $templateCanvas['shape'] : null;
    $canvasUnitAttr = $templateCanvas ? ($templateCanvas['unit'] ?? 'px') : null;

    $resolveImage = static function ($path, $fallback) {
        if (!$path) {
            return $fallback;
        }

        try {
            return \App\Support\ImageResolver::url($path);
        } catch (\Throwable $e) {
            return $fallback;
        }
    };

    $templateFront = $templateModel?->preview_front
        ?? $templateModel?->front_image
        ?? $templateModel?->preview
        ?? $templateModel?->image;

    $templateBack = $templateModel?->preview_back
        ?? $templateModel?->back_image;

    $frontSvg = $resolveImage($templateModel?->svg_path ?? null, null);
    $backSvg = $resolveImage($templateModel?->back_svg_path ?? null, null);

    $frontRasterCandidates = [
        $templateFront,
        $product?->images?->front,
        $product?->images?->preview,
        $product?->image,
    ];

    $backRasterCandidates = [
        $templateBack,
        $product?->images?->back,
        $product?->image,
    ];

    $pickFirst = static function (array $candidates) {
        foreach ($candidates as $candidate) {
            if (is_array($candidate)) {
                foreach ($candidate as $value) {
                    if (!empty($value)) {
                        return $value;
                    }
                }
                continue;
            }

            if (!empty($candidate)) {
                return $candidate;
            }
        }

        return null;
    };

    $frontSource = $pickFirst($frontRasterCandidates);
    $backSource = $pickFirst($backRasterCandidates);

    $frontImage = is_string($frontSource) && str_starts_with($frontSource, 'data:')
        ? $frontSource
        : $resolveImage($frontSource, $defaultFront);

    $backImage = is_string($backSource) && str_starts_with($backSource, 'data:')
        ? $backSource
        : $resolveImage($backSource, $defaultBack);

    $frontImage = $frontImage ?: $defaultFront;
    $backImage = $backImage ?: ($frontImage ?: $defaultBack);

    $templateBootstrap = null;
    if ($templateModel) {
        $templateBootstrap = [
            'id' => $templateModel->id,
            'name' => $templateModel->name,
            'svg_path' => $templateModel->svg_path ? \App\Support\ImageResolver::url($templateModel->svg_path) : null,
            'svg_source' => $templateModel->svg_path,
            'preview' => $templateModel->preview ? \App\Support\ImageResolver::url($templateModel->preview) : null,
            'front_image' => $templateModel->front_image ? \App\Support\ImageResolver::url($templateModel->front_image) : null,
            'back_image' => $templateModel->back_image ? \App\Support\ImageResolver::url($templateModel->back_image) : null,
            'updated_at' => optional($templateModel->updated_at)->toIso8601String(),
            'canvas' => $templateCanvas,
        ];
    }

    $productBootstrap = $product ? [
        'id' => $product->id,
        'name' => $product->name,
        'slug' => $product->slug ?? null,
        'event_type' => $product->event_type ?? null,
        'product_type' => $product->product_type ?? null,
    ] : null;
@endphp
<header class="studio-topbar">
    <div class="topbar-left">
        <img src="{{ asset('images/logo.png') }}" alt="InkWise" class="topbar-logo">
        <div class="topbar-project">
            <span class="topbar-project-link">My Projects</span>
            <span class="topbar-divider">&ndash;</span>
            <span class="topbar-project-name">{{ $product->name ?? $templateModel?->name ?? 'Save the Date Cards' }}</span>
        </div>
    </div>
    <div class="topbar-center">
        <span class="topbar-status-dot" aria-hidden="true"></span>
        <span class="topbar-status-label">Saved</span>
        <div class="topbar-history-controls" role="group" aria-label="History controls">
            <button type="button" class="topbar-icon-btn" aria-label="View history"><i class="fa-regular fa-clock"></i></button>
            <button type="button" class="topbar-icon-btn" aria-label="Undo"><i class="fa-solid fa-rotate-left"></i></button>
            <button type="button" class="topbar-icon-btn" aria-label="Redo"><i class="fa-solid fa-rotate-right"></i></button>
        </div>
        <div id="inkwise-customer-studio-root" class="studio-react-root" aria-live="polite"></div>
    </div>
    <div class="topbar-actions">
        <button class="topbar-action-btn" type="button" onclick="window.location.href='{{ route('templates.wedding.invitations') }}'">Change Template</button>
        <button class="topbar-action-btn" type="button">Preview</button>
        <button
            class="topbar-action-btn primary"
            type="button"
            data-action="proceed-review"
            data-destination="{{ route('order.review') }}"
        >Next</button>
    </div>
</header>
<main class="studio-layout">
    <nav class="studio-sidenav" aria-label="Editor sections">
        <button class="sidenav-btn active" type="button" data-nav="text">
            <span class="sidenav-icon-text">T</span>
            <span>Text</span>
        </button>
        <button class="sidenav-btn" type="button" data-nav="uploads">
            <i class="fa-solid fa-cloud-arrow-up"></i>
            <span>Uploads</span>
        </button>
        <button class="sidenav-btn" type="button" data-nav="graphics">
            <i class="fa-solid fa-images"></i>
            <span>Graphics</span>
        </button>
        <button class="sidenav-btn" type="button" data-nav="background">
            <i class="fa-solid fa-brush"></i>
            <span>Background</span>
        </button>
        <button class="sidenav-btn" type="button" data-nav="template">
            <i class="fa-regular fa-square"></i>
            <span>Template</span>
        </button>
        <button class="sidenav-btn" type="button" data-nav="color">
            <i class="fa-solid fa-fill-drip"></i>
            <span>Template color</span>
        </button>
            <!-- Product options removed per request -->
            <!-- QR-codes removed per request -->
        <button class="sidenav-btn" type="button" data-nav="tables">
            <i class="fa-solid fa-table"></i>
            <span>Tables</span>
        </button>
    </nav>
    <section class="studio-canvas-area">
        <div class="canvas-workspace">
            <div class="canvas-pill-group">
                <div class="pill-with-tooltip">
                    <button type="button" class="canvas-pill safety" id="safety-pill" aria-describedby="safety-tooltip" aria-expanded="false">
                        Safety Area
                    </button>
                    <div id="safety-tooltip" class="tooltip" role="tooltip" aria-hidden="true">
                        Fit all essential elements, like text and logos, inside this area to ensure content prints completely.
                    </div>
                </div>
                <div class="pill-with-tooltip">
                    <button type="button" class="canvas-pill bleed" id="bleed-pill" aria-describedby="bleed-tooltip" aria-expanded="false">
                        Bleed
                    </button>
                    <div id="bleed-tooltip" class="tooltip" role="tooltip" aria-hidden="true">
                        Extend background to this edge to
                        ensure full coverage and avoid blank
                        borders during printing.
                    </div>
                </div>
            </div>
            <div class="canvas-stage">
                <div class="canvas-measure canvas-measure-vertical" aria-hidden="true">
                    <span class="measure-cap"></span>
                    <span class="measure-line"></span>
                    <span class="measure-value">5.59in</span>
                    <span class="measure-line"></span>
                    <span class="measure-cap"></span>
                </div>
                <div
                    class="preview-canvas-wrapper preview-guides"
                    @if($canvasWidthAttr !== null) data-canvas-width="{{ $canvasWidthAttr }}" @endif
                    @if($canvasHeightAttr !== null) data-canvas-height="{{ $canvasHeightAttr }}" @endif
                    @if($canvasShapeAttr) data-canvas-shape="{{ $canvasShapeAttr }}" @endif
                    @if($canvasUnitAttr) data-canvas-unit="{{ $canvasUnitAttr }}" @endif
                >
                    <div
                        class="preview-card-bg"
                        data-front-image="{{ $frontImage }}"
                        data-back-image="{{ $backImage }}"
                        data-front-svg="{{ $frontSvg }}"
                        data-back-svg="{{ $backSvg }}"
                        style="background-image: url('{{ $frontImage }}');"
                        @if($canvasWidthAttr !== null) data-canvas-width="{{ $canvasWidthAttr }}" @endif
                        @if($canvasHeightAttr !== null) data-canvas-height="{{ $canvasHeightAttr }}" @endif
                        @if($canvasShapeAttr) data-canvas-shape="{{ $canvasShapeAttr }}" @endif
                        @if($canvasUnitAttr) data-canvas-unit="{{ $canvasUnitAttr }}" @endif
                    >
                        <svg id="preview-svg" width="100%" height="100%" viewBox="0 0 433 559" preserveAspectRatio="xMidYMid meet"></svg>
                    </div>
                </div>
                <div class="canvas-measure canvas-measure-horizontal" aria-hidden="true">
                    <span class="measure-cap"></span>
                    <span class="measure-line"></span>
                    <span class="measure-value">4.33in</span>
                    <span class="measure-line"></span>
                    <span class="measure-cap"></span>
                </div>
            </div>
            <div class="canvas-controls" role="group" aria-label="Canvas zoom controls">
                <button type="button" class="canvas-control-btn icon" data-zoom-step="down" aria-label="Zoom out"><i class="fa-solid fa-minus"></i></button>
                <span class="canvas-zoom-value" id="canvas-zoom-display">100%</span>
                <button type="button" class="canvas-control-btn icon" data-zoom-step="up" aria-label="Zoom in"><i class="fa-solid fa-plus"></i></button>
                <button type="button" class="canvas-control-btn icon" data-zoom-reset aria-label="Reset zoom to 100%"><i class="fa-solid fa-rotate-right"></i></button>
                <button type="button" class="canvas-control-btn icon" aria-label="Canvas settings"><i class="fa-solid fa-gear"></i></button>
                <select class="canvas-zoom-select" id="canvas-zoom-select" aria-label="Zoom level">
                    <option value="3">300%</option>
                    <option value="2">200%</option>
                    <option value="1.5">150%</option>
                    <option value="1" selected>100%</option>
                    <option value="0.75">75%</option>
                    <option value="0.5">50%</option>
                    <option value="0.25">25%</option>
                </select>
            </div>
        </div>
        <div class="preview-thumbs" role="tablist" aria-label="Card sides">
            <button type="button" class="preview-thumb active" data-card-thumb="front" aria-pressed="true">
                <div class="thumb-preview">
                    <span class="thumb-placeholder">Front</span>
                </div>
                <span class="thumb-label">Front</span>
            </button>
            @if($backSource)
            <button type="button" class="preview-thumb" data-card-thumb="back" aria-pressed="false">
                <div class="thumb-preview">
                    <span class="thumb-placeholder">Back</span>
                </div>
                <span class="thumb-label">Back</span>
            </button>
            @endif
        </div>
    </section>
</main>

<!-- Modals -->
<div id="text-modal" class="modal" data-section="text" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="text-modal-title">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="text-modal-title">Text</h2>
            <div class="modal-header-actions">
                <button type="button" aria-label="Dock panel" disabled aria-disabled="true">
                    <i class="fa-solid fa-up-right-and-down-left-from-center"></i>
                </button>
                <button type="button" class="modal-close" data-modal-close aria-label="Close panel">
                    <i class="fa-solid fa-xmark modal-close-icon"></i>
                </button>
            </div>
        </div>
        <p class="modal-helper">Edit your text below, or click on the field you'd like to edit directly on your design.</p>
        <div class="text-field-list" id="textFieldList">
            <!-- fields are generated dynamically from the SVG (or default placeholders shown only when SVG has no text nodes) -->
        </div>
        <button class="add-field-btn" type="button" data-add-text-field>New Text Field</button>
    </div>
</div>

<div id="uploads-modal" class="modal" data-section="uploads" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="uploads-modal-title">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="uploads-modal-title">Uploads</h2>
            <div class="modal-header-actions">
                <button type="button" aria-label="Dock panel" disabled aria-disabled="true">
                    <i class="fa-solid fa-up-right-and-down-left-from-center"></i>
                </button>
                <button type="button" class="modal-close" data-modal-close aria-label="Close panel">
                    <i class="fa-solid fa-xmark modal-close-icon"></i>
                </button>
            </div>
        </div>
        <p class="modal-helper">Upload photos and illustrations to personalize your invitation.</p>
        <div class="upload-section">
            <button type="button" id="upload-button" class="upload-button">
                <i class="fa-solid fa-cloud-arrow-up"></i>
                Upload Image
            </button>
            <input type="file" id="image-upload" accept="image/*" class="upload-input" style="display: none;">
        </div>
        <div class="recently-uploaded-section">
            <h3 class="section-title">Recently Uploaded</h3>
            <div class="recent-uploads-grid" id="recentUploadsGrid">
                <!-- Recently uploaded images will be populated here -->
                <div class="no-recent-uploads">
                    <p>No recent uploads found. Upload some images above to see them here.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="graphics-modal" class="modal" data-section="graphics" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="graphics-modal-title">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="graphics-modal-title">Graphics</h2>
            <div class="modal-header-actions">
                <button type="button" aria-label="Dock panel" disabled aria-disabled="true">
                    <i class="fa-solid fa-up-right-and-down-left-from-center"></i>
                </button>
                <button type="button" class="modal-close" data-modal-close aria-label="Close panel">
                    <i class="fa-solid fa-xmark modal-close-icon"></i>
                </button>
            </div>
        </div>
        <p class="modal-helper">Decorate your card with curated illustrations and icons.</p>
        <div class="graphics-panel">
            <div class="graphics-categories-labels">
                <div class="category-row" data-category-row="shapes">
                    <span class="category-label">Shapes</span>
                    <button type="button" class="graphics-category-button" data-category="shapes" aria-expanded="false"><i class="fi fi-br-angle-small-right"></i></button>
                </div>
                <div class="category-row" data-category-row="image">
                    <span class="category-label">Image</span>
                    <button type="button" class="graphics-category-button" data-category="image" aria-expanded="false"><i class="fi fi-br-angle-small-right"></i></button>
                </div>
                <div class="category-row" data-category-row="icons">
                    <span class="category-label">Icons</span>
                    <button type="button" class="graphics-category-button" data-category="icons" aria-expanded="false"><i class="fi fi-br-angle-small-right"></i></button>
                </div>
                <div class="category-row" data-category-row="illustrations">
                    <span class="category-label">Illustrations</span>
                    <button type="button" class="graphics-category-button" data-category="illustrations" aria-expanded="false"><i class="fi fi-br-angle-small-right"></i></button>
                </div>
                <div class="category-row" data-category-row="patterns">
                    <span class="category-label">Patterns</span>
                    <button type="button" class="graphics-category-button" data-category="patterns" aria-expanded="false"><i class="fi fi-br-angle-small-right"></i></button>
                </div>
            </div>
            <div class="graphics-browser" id="graphics-browser">
                <form class="graphics-search is-hidden" id="graphics-search-form" role="search">
                    <label for="graphics-search-input" class="visually-hidden">Search graphics</label>
                    <div class="graphics-search-field">
                        <i class="fi fi-rr-search graphics-search-icon" aria-hidden="true"></i>
                        <input type="search" id="graphics-search-input" name="graphics-search" autocomplete="off" placeholder="Search graphics" />
                    </div>
                    <button type="submit" id="graphics-search-submit">Search</button>
                </form>
                <div class="graphics-samples" id="graphics-browser-samples" aria-live="polite"></div>
            </div>
        </div>
    </div>
</div>

<div id="product-modal" class="modal" data-section="product" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="product-modal-title">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="product-modal-title">Product options</h2>
            <div class="modal-header-actions">
                <button type="button" aria-label="Dock panel" disabled aria-disabled="true">
                    <i class="fa-solid fa-up-right-and-down-left-from-center"></i>
                </button>
                <button type="button" class="modal-close" data-modal-close aria-label="Close panel">
                    <i class="fa-solid fa-xmark modal-close-icon"></i>
                </button>
            </div>
        </div>
        <p class="modal-helper">Review stock, paper, and finishing options for your print.</p>
        <div class="modal-placeholder">
            <p>Product configuration lives here.</p>
        </div>
    </div>
</div>

<div id="template-modal" class="modal" data-section="template" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="template-modal-title">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="template-modal-title">Template</h2>
            <div class="modal-header-actions">
                <button type="button" aria-label="Dock panel" disabled aria-disabled="true">
                    <i class="fa-solid fa-up-right-and-down-left-from-center"></i>
                </button>
                <button type="button" class="modal-close" data-modal-close aria-label="Close panel">
                    <i class="fa-solid fa-xmark modal-close-icon"></i>
                </button>
            </div>
        </div>
        <p class="modal-helper">Swap between coordinated template styles without starting over.</p>
        <div class="modal-placeholder">
            <p>Template variations will be available soon.</p>
        </div>
    </div>
</div>

<div id="background-modal" class="modal" data-section="background" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="background-modal-title">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="background-modal-title">Background</h2>
            <div class="modal-header-actions">
                <button type="button" aria-label="Dock panel" disabled aria-disabled="true">
                    <i class="fa-solid fa-up-right-and-down-left-from-center"></i>
                </button>
                <button type="button" class="modal-close" data-modal-close aria-label="Close panel">
                    <i class="fa-solid fa-xmark modal-close-icon"></i>
                </button>
            </div>
        </div>
        <p class="modal-helper">Swap in textures, colors, or patterns to update the canvas background.</p>
        <div class="modal-placeholder">
            <p>Background presets will appear here.</p>
        </div>
    </div>
</div>

<div id="color-modal" class="modal" data-section="color" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="color-modal-title">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="color-modal-title">Template color</h2>
            <div class="modal-header-actions">
                <button type="button" aria-label="Dock panel" disabled aria-disabled="true">
                    <i class="fa-solid fa-up-right-and-down-left-from-center"></i>
                </button>
                <button type="button" class="modal-close" data-modal-close aria-label="Close panel">
                    <i class="fa-solid fa-xmark modal-close-icon"></i>
                </button>
            </div>
        </div>
        <p class="modal-helper">Apply brand colors and foil finishes to your design.</p>
        <div class="modal-placeholder">
            <p>Color themes are on the way.</p>
        </div>
    </div>
</div>

<div id="qr-modal" class="modal" data-section="qr" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="qr-modal-title">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="qr-modal-title">QR-codes</h2>
            <div class="modal-header-actions">
                <button type="button" aria-label="Dock panel" disabled aria-disabled="true">
                    <i class="fa-solid fa-up-right-and-down-left-from-center"></i>
                </button>
                <button type="button" class="modal-close" data-modal-close aria-label="Close panel">
                    <i class="fa-solid fa-xmark modal-close-icon"></i>
                </button>
            </div>
        </div>
        <p class="modal-helper">Add scannable codes to link guests to RSVP forms or schedules.</p>
        <div class="modal-placeholder">
            <p>QR tools are coming soon.</p>
        </div>
    </div>
</div>

<div id="tables-modal" class="modal" data-section="tables" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="tables-modal-title">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="tables-modal-title">Tables</h2>
            <div class="modal-header-actions">
                <button type="button" aria-label="Dock panel" disabled aria-disabled="true">
                    <i class="fa-solid fa-up-right-and-down-left-from-center"></i>
                </button>
                <button type="button" class="modal-close" data-modal-close aria-label="Close panel">
                    <i class="fa-solid fa-xmark modal-close-icon"></i>
                </button>
            </div>
        </div>
        <p class="modal-helper">Manage seating charts, table numbers, and place cards.</p>
        <div class="modal-placeholder">
            <p>Table planning tools will show here soon.</p>
        </div>
    </div>
</div>

<script type="application/json" id="inkwise-customer-studio-bootstrap">
    {!! json_encode([
        'product' => $productBootstrap,
        'template' => $templateBootstrap,
        'routes' => [
            'autosave' => route('order.design.autosave'),
            'review' => route('order.review'),
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>

</body>
</html>
