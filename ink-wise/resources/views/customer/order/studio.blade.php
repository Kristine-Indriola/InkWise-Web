@include('customer.studio._head')
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

    // Parse size parameter (e.g., "4.5x6.25") and override canvas dimensions
    $selectedSize = request()->query('size');
    $parsedSize = null;
    if ($selectedSize && preg_match('/^(\d+(?:\.\d+)?)x(\d+(?:\.\d+)?)$/', $selectedSize, $matches)) {
        $parsedSize = [
            'width' => (float) $matches[1],
            'height' => (float) $matches[2],
            'unit' => 'in'
        ];
        // Convert inches to pixels (assuming 96 DPI)
        $canvasWidthAttr = (string) round($parsedSize['width'] * 96);
        $canvasHeightAttr = (string) round($parsedSize['height'] * 96);
        $canvasUnitAttr = 'px';
    }

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

    $readSvgFile = static function ($candidate) {
        if (!is_string($candidate) || trim($candidate) === '') {
            return null;
        }

        $pathPart = $candidate;
        $urlComponents = @parse_url($candidate);
        if (is_array($urlComponents) && !empty($urlComponents['path'])) {
            $pathPart = $urlComponents['path'];
        }

        $pathPart = urldecode((string) $pathPart);
        if (trim($pathPart) === '') {
            return null;
        }

        $variants = [];
        $normalized = str_replace('\\', '/', ltrim($pathPart, '/'));
        $variants[] = $normalized;
        $variants[] = preg_replace('#^storage/#i', '', $normalized) ?? $normalized;
        $variants[] = preg_replace('#^public/#i', '', $normalized) ?? $normalized;
        $variants[] = preg_replace('#^public/storage/#i', '', $normalized) ?? $normalized;
        $variants = array_values(array_unique(array_filter($variants, static fn ($value) => is_string($value) && $value !== '')));

        $disks = array_values(array_unique(array_filter([
            'invitation_templates',
            'public',
            config('filesystems.default'),
        ], static fn ($disk) => is_string($disk) && $disk !== '')));

        foreach ($variants as $variant) {
            $trimmed = ltrim($variant, '/');

            foreach ($disks as $disk) {
                if (!config("filesystems.disks.{$disk}")) {
                    continue;
                }

                try {
                    if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($trimmed)) {
                        $contents = \Illuminate\Support\Facades\Storage::disk($disk)->get($trimmed);
                        if (is_string($contents) && $contents !== '') {
                            return $contents;
                        }
                    }
                } catch (\Throwable $e) {
                    // continue to other disks
                }
            }

            $fileCandidates = [
                public_path($trimmed),
                public_path('storage/' . $trimmed),
                storage_path('app/public/' . $trimmed),
            ];

            foreach ($fileCandidates as $filePath) {
                if (!$filePath || !is_string($filePath)) {
                    continue;
                }

                if (is_file($filePath)) {
                    $contents = @file_get_contents($filePath);
                    if (is_string($contents) && $contents !== '') {
                        return $contents;
                    }
                }
            }
        }

        return null;
    };

    $resolveSvgDataUri = static function ($value) use ($resolveImage, $readSvgFile) {
        if (empty($value)) {
            return null;
        }

        $candidates = [];
        if (is_array($value)) {
            foreach (['data', 'data_uri', 'inline', 'path', 'url', 0] as $key) {
                if (!isset($value[$key])) {
                    continue;
                }
                $candidate = $value[$key];
                if (is_string($candidate) && $candidate !== '') {
                    $candidates[] = $candidate;
                }
            }
        } elseif (is_string($value)) {
            $candidates[] = $value;
        }

        $candidates = array_values(array_unique(array_filter($candidates, static fn ($candidate) => is_string($candidate) && trim($candidate) !== '')));

        foreach ($candidates as $candidate) {
            if (str_starts_with($candidate, 'data:image/svg+xml')) {
                return $candidate;
            }
        }

        foreach ($candidates as $candidate) {
            $resolved = $resolveImage($candidate, null);
            if ($resolved && $resolved !== $candidate) {
                $candidates[] = $resolved;
            }
        }

        $candidates = array_values(array_unique(array_filter($candidates, static fn ($candidate) => is_string($candidate) && trim($candidate) !== '')));

        foreach ($candidates as $candidate) {
            if (!str_contains($candidate, '.svg') && !str_starts_with($candidate, 'data:image/svg+xml')) {
                continue;
            }

            if (str_starts_with($candidate, 'data:image/svg+xml')) {
                return $candidate;
            }

            // Try to return the URL directly for SVG files
            if (str_contains($candidate, '.svg')) {
                $url = $resolveImage($candidate, null);
                if ($url && is_string($url) && str_starts_with($url, '/')) {
                    return $url;
                }
            }

            $contents = $readSvgFile($candidate);
            if (is_string($contents) && $contents !== '') {
                return 'data:image/svg+xml;base64,' . base64_encode($contents);
            }
        }

        return null;
    };

    $templateFront = $templateModel?->preview_front
        ?? $templateModel?->front_image
        ?? $templateModel?->preview
        ?? $templateModel?->image;

    $templateBack = $templateModel?->preview_back
        ?? $templateModel?->back_image;

    $frontSvg = $resolveSvgDataUri($templateModel?->svg_path ?? null);
    $backSvg = $resolveSvgDataUri($templateModel?->back_svg_path ?? null);

    $hasBackSide = false;
    if ($templateModel) {
        $hasBackSide = (bool) (
            ($templateModel->has_back_design ?? false)
            || !empty($templateBack)
            || !empty($templateModel->back_image)
            || !empty($backSvg)
        );
    }

    if (!$hasBackSide) {
        $productBack = data_get($product, 'images.back');
        if (!empty($productBack)) {
            $hasBackSide = true;
        }
    }

    $frontRasterCandidates = [
        $templateFront,
        $product?->images?->front,
        $product?->images?->preview,
        $product?->image,
    ];

    $backRasterCandidates = [
        $templateBack,
        $product?->images?->back,
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
    $backSource = $hasBackSide ? $pickFirst($backRasterCandidates) : null;

    $frontImage = is_string($frontSource) && str_starts_with($frontSource, 'data:')
        ? $frontSource
        : $resolveImage($frontSource, $defaultFront);

    $backImage = null;
    if ($hasBackSide) {
        $backImage = is_string($backSource) && str_starts_with($backSource, 'data:')
            ? $backSource
            : $resolveImage($backSource, $defaultBack);
        $backImage = $backImage ?: $defaultBack;
    }

    $frontImage = $frontImage ?: $defaultFront;

    $templateBootstrap = null;
    if ($templateModel) {
        $templateBootstrap = [
            'id' => $templateModel->id,
            'name' => $templateModel->name,
            'has_back_design' => $hasBackSide,
            'svg_path' => $templateModel->svg_path ? \App\Support\ImageResolver::url($templateModel->svg_path) : null,
            'back_svg_path' => $hasBackSide && $templateModel->back_svg_path ? \App\Support\ImageResolver::url($templateModel->back_svg_path) : null,
            'svg_source' => $templateModel->svg_path,
            'back_svg_source' => $hasBackSide ? $templateModel->back_svg_path : null,
            'preview' => $templateModel->preview ? \App\Support\ImageResolver::url($templateModel->preview) : null,
            'preview_front' => $templateModel->preview_front ? \App\Support\ImageResolver::url($templateModel->preview_front) : null,
            'preview_back' => $hasBackSide && $templateModel->preview_back ? \App\Support\ImageResolver::url($templateModel->preview_back) : null,
            'front_image' => $templateModel->front_image ? \App\Support\ImageResolver::url($templateModel->front_image) : null,
            'back_image' => $hasBackSide && $templateModel->back_image ? \App\Support\ImageResolver::url($templateModel->back_image) : null,
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
        <div class="topbar-status">
            <span class="topbar-status-dot" aria-hidden="true"></span>
            <span class="topbar-status-label">Saved</span>
        </div>
        <div class="topbar-history-controls" role="group" aria-label="History controls">
            <button type="button" class="topbar-icon-btn" aria-label="View history"><i class="fa-regular fa-clock"></i></button>
            <!-- Undo/Redo removed -->
        </div>
        <div id="inkwise-customer-studio-root" class="studio-react-root" aria-live="polite"></div>
    </div>
    <div class="topbar-actions">
        <button class="topbar-action-btn" type="button" onclick="window.location.href='{{ route('templates.wedding.invitations') }}'">Change Template</button>
        <button class="topbar-action-btn" type="button" data-action="preview">Preview</button>
        <button class="topbar-action-btn" type="button" id="save-template-btn" data-action="save-template">Save Template</button>
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
            <span>Colors</span>
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
                <!-- Loading state -->
                <div id="canvas-loading" class="canvas-loading">
                    <div class="loading-spinner"></div>
                    <p>Loading template...</p>
                </div>
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
                        data-has-back="{{ $hasBackSide ? 'true' : 'false' }}"
                        data-front-image="{{ $frontImage }}"
                        @if($hasBackSide && $backImage)
                        data-back-image="{{ $backImage }}"
                        @endif
                        @if($frontSvg)
                        data-front-svg="{{ $frontSvg }}"
                        @endif
                        @if($hasBackSide && $backSvg)
                        data-back-svg="{{ $backSvg }}"
                        @endif
                        style="background-image: url('{{ $frontImage }}');"
                        @if($canvasWidthAttr !== null) data-canvas-width="{{ $canvasWidthAttr }}" @endif
                        @if($canvasHeightAttr !== null) data-canvas-height="{{ $canvasHeightAttr }}" @endif
                        @if($canvasShapeAttr) data-canvas-shape="{{ $canvasShapeAttr }}" @endif
                        @if($canvasUnitAttr) data-canvas-unit="{{ $canvasUnitAttr }}" @endif
                    >
                        <svg
                            id="preview-svg"
                            width="100%"
                            height="100%"
                            @if($canvasWidthAttr && $canvasHeightAttr)
                                viewBox="0 0 {{ $canvasWidthAttr }} {{ $canvasHeightAttr }}"
                            @else
                                viewBox="0 0 433 559"
                            @endif
                            preserveAspectRatio="xMidYMid meet"
                        ></svg>
                    </div>
                </div>
                <div id="mini-toolbar" class="mini-toolbar" style="display: none;">
                    <button class="toolbar-btn" data-action="edit" title="Edit"><i class="fa-solid fa-edit"></i></button>
                    <button class="toolbar-btn" data-action="delete" title="Delete"><i class="fa-solid fa-trash"></i></button>
                    <button class="toolbar-btn" data-action="duplicate" title="Duplicate"><i class="fa-solid fa-copy"></i></button>
                    <button class="toolbar-btn" data-action="move" title="Move"><i class="fa-solid fa-arrows-alt"></i></button>
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
                <!-- Reset zoom button removed -->
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
        <!-- Floating toolbar removed -->
        <div class="preview-thumbs" role="tablist" aria-label="Card sides">
            <button type="button" class="preview-thumb active" data-card-thumb="front" aria-pressed="true">
                <div class="thumb-preview" @if($frontImage) style="background-image: url('{{ $frontImage }}');" @endif>
                    <span class="thumb-placeholder">Front</span>
                </div>
                <span class="thumb-label">Front</span>
            </button>
            @if($hasBackSide)
            <button type="button" class="preview-thumb" data-card-thumb="back" aria-pressed="false">
                <div class="thumb-preview" @if($backImage) style="background-image: url('{{ $backImage }}');" @endif>
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
        <!-- Text layout controls removed -->
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

<div id="background-modal" class="modal" data-section="background" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="background-modal-title">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="background-modal-title">Colors</h2>
            <div class="modal-header-actions">
                <button type="button" aria-label="Dock panel" disabled aria-disabled="true">
                    <i class="fa-solid fa-up-right-and-down-left-from-center"></i>
                </button>
                <button type="button" class="modal-close" data-modal-close aria-label="Close panel">
                    <i class="fa-solid fa-xmark modal-close-icon"></i>
                </button>
            </div>
        </div>
        <p class="modal-helper">Choose colors, textures, or patterns for your design.</p>
        <div class="color-palette">
            <button class="color-btn" style="background-color: #ff0000;" data-color="#ff0000" title="Red"></button>
            <button class="color-btn" style="background-color: #ff7f00;" data-color="#ff7f00" title="Orange"></button>
            <button class="color-btn" style="background-color: #ffff00;" data-color="#ffff00" title="Yellow"></button>
            <button class="color-btn" style="background-color: #00ff00;" data-color="#00ff00" title="Green"></button>
            <button class="color-btn" style="background-color: #0000ff;" data-color="#0000ff" title="Blue"></button>
            <button class="color-btn" style="background-color: #4b0082;" data-color="#4b0082" title="Indigo"></button>
            <button class="color-btn" style="background-color: #9400d3;" data-color="#9400d3" title="Violet"></button>
            <button class="color-btn" style="background-color: #ff69b4;" data-color="#ff69b4" title="Pink"></button>
            <button class="color-btn" style="background-color: #a52a2a;" data-color="#a52a2a" title="Brown"></button>
            <button class="color-btn" style="background-color: #808080;" data-color="#808080" title="Gray"></button>
            <button class="color-btn" style="background-color: #FFFFFF;" data-color="#FFFFFF" title="White"></button>
            <button class="color-btn" style="background-color: #000000;" data-color="#000000" title="Black"></button>
            <button class="color-btn" style="background-color: #FFF6E5;" data-color="#FFF6E5" title="Cream"></button>
            <button class="color-btn" style="background-color: #F5EFE8;" data-color="#F5EFE8" title="Beige"></button>
            <button class="color-btn" style="background-color: #FFFFF0;" data-color="#FFFFF0" title="Ivory"></button>
            <button class="color-btn" style="background-color: #D3D3D3;" data-color="#D3D3D3" title="Light Gray"></button>
            <button class="color-btn" style="background-color: #36454F;" data-color="#36454F" title="Charcoal"></button>
            <button class="color-btn" style="background-color: #001F3F;" data-color="#001F3F" title="Navy"></button>
            <button class="color-btn" style="background-color: #008080;" data-color="#008080" title="Teal"></button>
            <button class="color-btn" style="background-color: #40E0D0;" data-color="#40E0D0" title="Turquoise"></button>
            <button class="color-btn" style="background-color: #CDEFEA;" data-color="#CDEFEA" title="Mint"></button>
            <button class="color-btn" style="background-color: #9DC183;" data-color="#9DC183" title="Sage Green"></button>
            <button class="color-btn" style="background-color: #228B22;" data-color="#228B22" title="Forest Green"></button>
            <button class="color-btn" style="background-color: #800000;" data-color="#800000" title="Maroon"></button>
            <button class="color-btn" style="background-color: #800020;" data-color="#800020" title="Burgundy"></button>
            <button class="color-btn" style="background-color: #D4AF37;" data-color="#D4AF37" title="Gold"></button>
            <button class="color-btn" style="background-color: #F7E7CE;" data-color="#F7E7CE" title="Champagne"></button>
            <button class="color-btn" style="background-color: #FFDAB9;" data-color="#FFDAB9" title="Peach"></button>
            <button class="color-btn" style="background-color: #E6E6FA;" data-color="#E6E6FA" title="Lavender"></button>
            <button class="color-btn" style="background-color: #87CEEB;" data-color="#87CEEB" title="Sky Blue"></button>
            <button class="color-btn" style="background-color: #FF6F61;" data-color="#FF6F61" title="Coral"></button>
            <button class="color-btn" style="background-color: #FA8072;" data-color="#FA8072" title="Salmon"></button>
            <button class="color-btn" style="background-color: #E63946;" data-color="#E63946" title="Rose"></button>
            <button class="color-btn" style="background-color: #C97C8A;" data-color="#C97C8A" title="Dusty Rose"></button>
            <button class="color-btn" style="background-color: #B784A7;" data-color="#B784A7" title="Mauve"></button>
            <button class="color-btn" style="background-color: #8E4585;" data-color="#8E4585" title="Plum"></button>
            <button class="color-btn" style="background-color: #614051;" data-color="#614051" title="Eggplant"></button>
            <button class="color-btn" style="background-color: #CCCCFF;" data-color="#CCCCFF" title="Periwinkle"></button>
            <button class="color-btn" style="background-color: #B0E0E6;" data-color="#B0E0E6" title="Powder Blue"></button>
            <button class="color-btn" style="background-color: #4682B4;" data-color="#4682B4" title="Steel Blue"></button>
            <button class="color-btn" style="background-color: #1560BD;" data-color="#1560BD" title="Denim"></button>
            <button class="color-btn" style="background-color: #191970;" data-color="#191970" title="Midnight Blue"></button>
            <button class="color-btn" style="background-color: #808000;" data-color="#808000" title="Olive"></button>
            <button class="color-btn" style="background-color: #8A9A5B;" data-color="#8A9A5B" title="Moss Green"></button>
            <button class="color-btn" style="background-color: #9FE2BF;" data-color="#9FE2BF" title="Seafoam"></button>
            <button class="color-btn" style="background-color: #C2B280;" data-color="#C2B280" title="Sand"></button>
            <button class="color-btn" style="background-color: #483C32;" data-color="#483C32" title="Taupe"></button>
            <button class="color-btn" style="background-color: #6F4E37;" data-color="#6F4E37" title="Mocha"></button>
            <button class="color-btn" style="background-color: #708090;" data-color="#708090" title="Slate Gray"></button>
            <button class="color-btn" style="background-color: #BEBEBE;" data-color="#BEBEBE" title="Smoke"></button>
        </div>
        <div class="custom-color-section">
            <label for="custom-color-picker">Custom Color:</label>
            <input type="color" id="custom-color-picker" class="custom-color-input" value="#ffffff">
            <label for="opacity-slider">Opacity:</label>
            <input type="range" id="opacity-slider" class="opacity-slider" min="0" max="1" step="0.01" value="1">
            <span id="opacity-value">100%</span>
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
        <div class="table-controls">
            <div class="control-group">
                <label for="columns-count">Columns:</label>
                <button class="control-btn minus" type="button" data-action="decrease" data-target="columns" aria-label="Decrease columns">-</button>
                <span id="columns-count" aria-live="polite">3</span>
                <button class="control-btn plus" type="button" data-action="increase" data-target="columns" aria-label="Increase columns">+</button>
            </div>
            <div class="control-group">
                <label for="rows-count">Rows:</label>
                <button class="control-btn minus" type="button" data-action="decrease" data-target="rows" aria-label="Decrease rows">-</button>
                <span id="rows-count" aria-live="polite">3</span>
                <button class="control-btn plus" type="button" data-action="increase" data-target="rows" aria-label="Increase rows">+</button>
            </div>
            <button class="add-table-btn" type="button" id="add-table-btn">Add Table</button>
        </div>
    </div>
</div>

<div id="colors-modal" class="modal" data-section="colors" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="colors-modal-title">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="colors-modal-title">Color Palette</h2>
            <div class="modal-header-actions">
                <button type="button" aria-label="Dock panel" disabled aria-disabled="true">
                    <i class="fa-solid fa-up-right-and-down-left-from-center"></i>
                </button>
                <button type="button" class="modal-close" data-modal-close aria-label="Close panel">
                    <i class="fa-solid fa-xmark modal-close-icon"></i>
                </button>
            </div>
        </div>
        <p class="modal-helper">Choose colors for your design elements.</p>
        <div class="color-palette">
            <div class="color-grid">
                <button class="color-swatch" style="background-color: #000000;" data-color="#000000" title="Black"></button>
                <button class="color-swatch" style="background-color: #ffffff;" data-color="#ffffff" title="White"></button>
                <button class="color-swatch" style="background-color: #ff0000;" data-color="#ff0000" title="Red"></button>
                <button class="color-swatch" style="background-color: #00ff00;" data-color="#00ff00" title="Green"></button>
                <button class="color-swatch" style="background-color: #0000ff;" data-color="#0000ff" title="Blue"></button>
                <button class="color-swatch" style="background-color: #ffff00;" data-color="#ffff00" title="Yellow"></button>
                <button class="color-swatch" style="background-color: #ff00ff;" data-color="#ff00ff" title="Magenta"></button>
                <button class="color-swatch" style="background-color: #00ffff;" data-color="#00ffff" title="Cyan"></button>
                <button class="color-swatch" style="background-color: #800080;" data-color="#800080" title="Purple"></button>
                <button class="color-swatch" style="background-color: #ffa500;" data-color="#ffa500" title="Orange"></button>
                <button class="color-swatch" style="background-color: #a52a2a;" data-color="#a52a2a" title="Brown"></button>
                <button class="color-swatch" style="background-color: #808080;" data-color="#808080" title="Gray"></button>
            </div>
            <div class="color-picker-section">
                <label for="custom-color-picker">Custom Color:</label>
                <input type="color" id="custom-color-picker" value="#000000">
            </div>
        </div>
    </div>
</div>

@php
    $selectedSize = request()->query('size')
        ?? $product?->size
        ?? (is_array($product?->sizes ?? null) ? ($product->sizes[0] ?? null) : null)
        ?? (($templateModel?->width_inch && $templateModel?->height_inch) ? ($templateModel->width_inch . 'x' . $templateModel->height_inch) : null)
        ?? config('invitations.default_size', '5x7');

    $bootstrapPayload = [
        'csrfToken' => csrf_token(),
        'product' => $productBootstrap,
        'template' => $templateBootstrap,
        'assets' => [
            'front_image' => $frontImage,
            'back_image' => $hasBackSide ? $backImage : null,
            'default_front' => $defaultFront,
            'default_back' => $defaultBack,
            'brand_logo' => asset('images/logo.png'),
            'preview_images' => [
                'front' => $templateBootstrap['preview_front'] ?? null,
                'back' => $templateBootstrap['preview_back'] ?? null,
            ],
        ],
        'svg' => [
            'front' => $frontSvg,
            'back' => $backSvg,
        ],
        'selection' => [
            'size' => $selectedSize ?? null,
        ],
        'flags' => [
            'has_back' => $hasBackSide,
        ],
        'routes' => [
            'autosave' => route('order.design.autosave'),
            'saveTemplate' => route('order.design.save-template'),
            'review' => route('order.review'),
            'saveReview' => route('order.review.design'),
            'change_template' => route('templates.wedding.invitations'),
        ],
        'orderSummary' => $orderSummary ?? null,
    ];
@endphp

@include('customer.studio._bootstrap')

<script>
document.addEventListener('DOMContentLoaded', function() {
    const columnsCount = document.getElementById('columns-count');
    const rowsCount = document.getElementById('rows-count');
    const addTableBtn = document.getElementById('add-table-btn');
    const miniToolbar = document.getElementById('mini-toolbar');
    const previewSvg = document.getElementById('preview-svg');

    let columns = 3;
    let rows = 3;
    const minValue = 1;
    const maxValue = 10;

    function updateDisplay() {
        columnsCount.textContent = columns;
        rowsCount.textContent = rows;
    }

    function updateButtons() {
        document.querySelectorAll('.control-btn').forEach(btn => {
            const target = btn.dataset.target;
            const action = btn.dataset.action;
            const currentValue = target === 'columns' ? columns : rows;
            
            if (action === 'increase' && currentValue >= maxValue) {
                btn.disabled = true;
            } else if (action === 'decrease' && currentValue <= minValue) {
                btn.disabled = true;
            } else {
                btn.disabled = false;
            }
        });
    }

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('control-btn')) {
            const target = e.target.dataset.target;
            const action = e.target.dataset.action;

            if (target === 'columns') {
                if (action === 'increase' && columns < maxValue) columns++;
                if (action === 'decrease' && columns > minValue) columns--;
            } else if (target === 'rows') {
                if (action === 'increase' && rows < maxValue) rows++;
                if (action === 'decrease' && rows > minValue) rows--;
            }

            updateDisplay();
            updateButtons();
        }

        if (e.target.id === 'add-table-btn') {
            // For now, show an alert with table details
            // In a full implementation, this would add the table to the canvas
            alert(`Adding table with ${columns} columns and ${rows} rows to the canvas. (Note: Canvas integration requires React app updates)`);
            
            // Reset to default
            columns = 3;
            rows = 3;
            updateDisplay();
            updateButtons();
        }
    });

    // Mini toolbar functionality
    if (previewSvg) {
        previewSvg.addEventListener('click', function(e) {
            // Check if clicked on text or image element
            const target = e.target;
            if (target.tagName === 'text' || target.tagName === 'image' || target.closest('text') || target.closest('image')) {
                // Position the toolbar near the click
                const rect = previewSvg.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                miniToolbar.style.left = (x + 10) + 'px';
                miniToolbar.style.top = (y + 10) + 'px';
                miniToolbar.style.display = 'flex';
            } else {
                miniToolbar.style.display = 'none';
            }
        });
    }

    // Hide toolbar when clicking outside
    document.addEventListener('click', function(e) {
        if (!previewSvg.contains(e.target) && !miniToolbar.contains(e.target)) {
            miniToolbar.style.display = 'none';
        }
    });

    // Floating toolbar functionality
    const floatingToolbar = document.getElementById('floating-toolbar');
    if (floatingToolbar) {
        floatingToolbar.addEventListener('click', function(e) {
            if (e.target.classList.contains('toolbar-btn') || e.target.closest('.toolbar-btn')) {
                const btn = e.target.classList.contains('toolbar-btn') ? e.target : e.target.closest('.toolbar-btn');
                const nav = btn.dataset.nav;
                const modal = document.getElementById(nav + '-modal');
                if (modal) {
                    modal.style.display = 'flex';
                    modal.setAttribute('aria-hidden', 'false');
                }
            }
        });
    }

    // Modal close functionality
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-close') || e.target.classList.contains('modal-close-icon') || e.target.hasAttribute('data-modal-close')) {
            const modal = e.target.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
            }
        }
    });

    // Topbar action buttons functionality
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('topbar-action-btn') || e.target.closest('.topbar-action-btn')) {
            const btn = e.target.classList.contains('topbar-action-btn') ? e.target : e.target.closest('.topbar-action-btn');
            const action = btn.dataset.action;

            if (action === 'preview') {
                showPreview();
            } else if (action === 'proceed-review') {
                const destination = btn.dataset.destination;
                if (destination) {
                    window.location.href = destination;
                }
            }
        }
    });

    // Preview functionality
    function showPreview() {
        // Create or show preview overlay
        let previewOverlay = document.getElementById('preview-overlay');
        if (!previewOverlay) {
            const canvasWrapper = document.querySelector('.preview-canvas-wrapper');
            const cardBg = document.querySelector('.preview-card-bg');
            const previewSvg = document.getElementById('preview-svg');

            const canvasWidth = canvasWrapper ? canvasWrapper.dataset.canvasWidth : '433';
            const canvasHeight = canvasWrapper ? canvasWrapper.dataset.canvasHeight : '559';
            const bgImage = cardBg ? cardBg.style.backgroundImage : '';
            const svgContent = previewSvg ? previewSvg.innerHTML : '';

            previewOverlay = document.createElement('div');
            previewOverlay.id = 'preview-overlay';
            previewOverlay.className = 'preview-overlay-modal';
            previewOverlay.innerHTML = `
                <div class="preview-overlay-header">
                    <h2>Preview</h2>
                    <button type="button" class="preview-close-btn" aria-label="Close preview">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="preview-overlay-content">
                    <div class="preview-canvas-container">
                        <div class="preview-card-preview">
                            <div class="preview-card-bg" style="background-image: ${bgImage}; width: ${canvasWidth}px; height: ${canvasHeight}px;">
                                <svg width="100%" height="100%" viewBox="0 0 ${canvasWidth} ${canvasHeight}" preserveAspectRatio="xMidYMid meet">
                                    ${svgContent}
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(previewOverlay);

            // Add close functionality
            previewOverlay.querySelector('.preview-close-btn').addEventListener('click', function() {
                previewOverlay.remove();
            });

            // Close on background click
            previewOverlay.addEventListener('click', function(e) {
                if (e.target === previewOverlay) {
                    previewOverlay.remove();
                }
            });
        } else {
            previewOverlay.style.display = 'flex';
        }
    }

    // Initial setup
    updateDisplay();
    updateButtons();
});
</script>

</body>
</html>
