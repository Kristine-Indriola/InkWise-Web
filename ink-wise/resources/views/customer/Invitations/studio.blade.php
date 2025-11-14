<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $product->name ?? 'InkWise Studio' }} &mdash; InkWise</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/customer/studio.css') }}">
    <script src="{{ asset('js/customer/studio.js') }}" defer></script>
</head>
<body>
@php
    $defaultFront = asset('Customerimages/invite/wedding2.png');
    $defaultBack = asset('Customerimages/invite/wedding3.jpg');

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

    $templateFront = $product?->template?->preview_front
        ?? $product?->template?->front_image
        ?? $product?->template?->preview
        ?? $product?->template?->image;

    $templateBack = $product?->template?->preview_back
        ?? $product?->template?->back_image;

    $frontCandidates = [
        $product?->template?->svg_path,
        $templateFront,
        $product?->images?->front,
        $product?->images?->preview,
        $product?->image,
    ];

    $backCandidates = [
        $product?->template?->back_svg_path,
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

    $frontSource = $pickFirst($frontCandidates);
    $backSource = $pickFirst($backCandidates);

    $frontImage = is_string($frontSource) && str_starts_with($frontSource, 'data:')
        ? $frontSource
        : $resolveImage($frontSource, $defaultFront);

    $backImage = is_string($backSource) && str_starts_with($backSource, 'data:')
        ? $backSource
        : $resolveImage($backSource, $defaultBack);
@endphp
<header class="studio-topbar">
    <div class="topbar-left">
        <img src="{{ asset('images/logo.png') }}" alt="InkWise" class="topbar-logo">
        <div class="topbar-project">
            <span class="topbar-project-link">My Projects</span>
            <span class="topbar-divider">&ndash;</span>
            <span class="topbar-project-name">{{ $product->name ?? 'Save the Date Cards' }}</span>
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
    </div>
    <div class="topbar-actions">
        <button class="topbar-action-btn" type="button" onclick="window.location.href='{{ route('templates.wedding.invitations') }}'">Change Template</button>
        <button class="topbar-action-btn" type="button">Preview</button>
        <button class="topbar-action-btn primary" type="button" onclick="window.location.href='{{ route('order.review') }}'">Next</button>
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
                <div class="preview-canvas-wrapper preview-guides">
                    <div class="preview-card-bg" data-front-image="{{ $frontImage }}" data-back-image="{{ $backImage }}">
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
            <button type="button" class="preview-thumb" data-card-thumb="back" aria-pressed="false">
                <div class="thumb-preview">
                    <span class="thumb-placeholder">Back</span>
                </div>
                <span class="thumb-label">Back</span>
            </button>
        </div>
        <div class="quick-upload-section">
            <button type="button" id="quick-upload-button" class="quick-upload-button">
                <i class="fa-solid fa-cloud-arrow-up"></i>
                Quick Upload
            </button>
            <div class="quick-recent-uploads" id="quickRecentUploads">
                <!-- Quick recently uploaded images will be populated here -->
            </div>
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
        <div class="modal-placeholder">
            <p>Graphic libraries are being prepared. Stay tuned!</p>
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

</body>
</html>
