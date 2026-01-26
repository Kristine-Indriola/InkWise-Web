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

    // Force correct back SVG for this template if ID matches
    if ($templateModel && $templateModel->id == 132) {
        $backSvg = asset('storage/templates/back_svg/template_24445333-2952-4a3a-a51d-487c20a0dd5e.svg');
    }

    $hasBackSide = false;
    if ($templateModel) {
        $hasBackSide = (bool) (
            ($templateModel->has_back_design ?? false)
            || !empty($templateBack)
            || !empty($templateModel->back_image)
            || !empty($backSvg)
            || ($templateModel->id == 132) // Special case for template 132
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
        <button class="topbar-action-btn" type="button" id="preview-btn">Preview</button>
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
        <button class="sidenav-btn" type="button" data-nav="colors">
            <i class="fa-solid fa-palette"></i>
            <span>Colors</span>
        </button>
        <button class="sidenav-btn" type="button" data-nav="graphics">
            <i class="fa-solid fa-images"></i>
            <span>Graphics</span>
        </button>
        
    </nav>
    <section class="studio-canvas-area">
        <!-- React-based TemplateEditor mount point (renders Front and Back canvases) -->
        <div
            id="template-editor-root"
            data-has-back="{{ $hasBackSide ? 'true' : 'false' }}"
            data-front-canvas-width="{{ $canvasWidthAttr ?? '' }}"
            data-front-canvas-height="{{ $canvasHeightAttr ?? '' }}"
            data-front-canvas-unit="{{ $canvasUnitAttr ?? '' }}"
            data-back-canvas-width="{{ $canvasWidthAttr ?? '' }}"
            data-back-canvas-height="{{ $canvasHeightAttr ?? '' }}"
            data-back-canvas-unit="{{ $canvasUnitAttr ?? '' }}"
        ></div>
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
        <!-- Text fields content will be rendered by React -->
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
            <div class="upload-controls">
                <div class="side-toggle" role="tablist" aria-label="Upload side" style="margin-right:8px;display:inline-flex;gap:6px;">
                    <button type="button" id="upload-side-front" class="side-toggle-btn active" aria-pressed="true" data-side="front">Front</button>
                    <button type="button" id="upload-side-back" class="side-toggle-btn" aria-pressed="false" data-side="back">Back</button>
                </div>
                <label for="upload-side-select" class="sr-only">(legacy) Upload side</label>
                <select id="upload-side-select" aria-label="Upload side" style="display:none;">
                    <option value="front" selected>Front</option>
                    <option value="back">Back</option>
                </select>

                <button type="button" id="upload-button" class="upload-button">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    Upload Image
                </button>
                <input type="file" id="image-upload" accept="image/*" class="upload-input" style="display: none;" multiple>
            </div>

            <div id="uploadsDropZone" tabindex="0" aria-label="Upload drop zone" class="uploads-dropzone" style="border:2px dashed var(--accent);padding:12px;text-align:center;cursor:default;margin-top:12px;min-height:38px;">
                <!-- Drag & drop images here. Click has been disabled to avoid confusion. -->
            </div>
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

<div id="preview-modal" class="modal preview-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="preview-modal-title">
    <div class="modal-overlay" data-modal-close></div>
    <div class="modal-content preview-modal-content">
        <div class="modal-header">
            <h2 id="preview-modal-title">Preview Your Design</h2>
            <div class="modal-header-actions">
                <div class="preview-toggle-group" style="display:flex;gap:8px;align-items:center;margin-right:8px;">
                    <button type="button" id="preview-show-front" class="btn btn-sm" aria-pressed="true">Front</button>
                    @if($hasBackSide)
                    <button type="button" id="preview-show-back" class="btn btn-sm" aria-pressed="false">Back</button>
                    @endif
                </div>
                <button type="button" class="modal-close" data-modal-close aria-label="Close preview">
                    <i class="fa-solid fa-xmark modal-close-icon"></i>
                </button>
            </div>
        </div>
        <div class="preview-container">
            <div class="preview-card-wrapper">
                <div class="preview-card front-card active" id="preview-front-card">
                    <div class="preview-card-inner">
                        <div class="preview-card-bg" id="preview-front-bg"></div>
                        <svg id="preview-front-svg" class="preview-svg"></svg>
                    </div>
                </div>
                @if($hasBackSide)
                <div class="preview-card back-card" id="preview-back-card">
                    <div class="preview-card-inner">
                        <div class="preview-card-bg" id="preview-back-bg"></div>
                        <svg id="preview-back-svg" class="preview-svg"></svg>
                    </div>
                </div>
                @endif
            </div>
            {{-- Preview navigation removed: front/back toggle intentionally omitted --}}
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

<div id="graphics-modal" class="modal" data-section="graphics" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="graphics-modal-title">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="graphics-modal-title">Graphics Library</h2>
            <div class="modal-header-actions">
                <button type="button" aria-label="Dock panel" disabled aria-disabled="true">
                    <i class="fa-solid fa-up-right-and-down-left-from-center"></i>
                </button>
                <button type="button" class="modal-close" data-modal-close aria-label="Close panel">
                    <i class="fa-solid fa-xmark modal-close-icon"></i>
                </button>
            </div>
        </div>
        <p class="modal-helper">Browse and add graphics to your design.</p>
        <div class="graphics-panel">
            <div class="graphics-categories">
                <div class="graphics-categories-labels">
                    <button class="graphics-category-button" data-category="image" aria-expanded="false">
                        <i class="fa-solid fa-image"></i>
                        <span>Images</span>
                    </button>
                    <button class="graphics-category-button" data-category="icon" aria-expanded="false">
                        <i class="fa-solid fa-icons"></i>
                        <span>Icons</span>
                    </button>
                    <button class="graphics-category-button" data-category="shape" aria-expanded="false">
                        <i class="fa-solid fa-shapes"></i>
                        <span>Shapes</span>
                    </button>
                </div>
            </div>
            <div class="graphics-browser">
                <div class="graphics-search">
                    <form id="graphics-search-form">
                        <input type="search" id="graphics-search-input" placeholder="Search graphics..." aria-label="Search graphics">
                        <button type="submit" aria-label="Search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </form>
                </div>
                <div id="graphics-browser-samples" class="graphics-samples">
                    <div class="graphics-placeholder">
                        <i class="fa-solid fa-images"></i>
                        <p>Select a category to browse graphics</p>
                    </div>
                </div>
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
        // Include template_id explicitly so the client always has a direct value to use
        'template_id' => $templateBootstrap['id'] ?? $product?->template_id ?? null,
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
            'uploadImage' => route('order.design.upload-image'),
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

    // Modal and navigation functionality
    document.addEventListener('click', function(e) {
        // Handle sidenav buttons
        if (e.target.classList.contains('sidenav-btn') || e.target.closest('.sidenav-btn')) {
            const btn = e.target.classList.contains('sidenav-btn') ? e.target : e.target.closest('.sidenav-btn');
            const nav = btn.dataset.nav;
            const modal = document.getElementById(nav + '-modal');
            if (modal) {
                const isOpen = modal.style.display === 'flex' || modal.getAttribute('aria-hidden') === 'false';
                // Hide all modals
                document.querySelectorAll('.modal').forEach(m => {
                    m.style.display = 'none';
                    m.setAttribute('aria-hidden', 'true');
                });
                // Remove active from all nav buttons
                document.querySelectorAll('.sidenav-btn').forEach(b => b.classList.remove('active'));
                if (!isOpen) {
                    modal.style.display = 'flex';
                    modal.setAttribute('aria-hidden', 'false');
                    btn.classList.add('active');
                }
            }
        }

        // Handle modal close
        if (e.target.classList.contains('modal-close') || e.target.classList.contains('modal-close-icon') || e.target.hasAttribute('data-modal-close')) {
            const modal = e.target.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
                // Remove active from nav button
                const section = modal.dataset.section;
                if (section) {
                    const navBtn = document.querySelector(`.sidenav-btn[data-nav="${section}"]`);
                    if (navBtn) navBtn.classList.remove('active');
                }
            }
        }
    });

    // Preview functionality
    const previewBtn = document.getElementById('preview-btn');
    const previewModal = document.getElementById('preview-modal');
    const previewFrontCard = document.getElementById('preview-front-card');
    const previewBackCard = document.getElementById('preview-back-card');
    const previewFrontBg = document.getElementById('preview-front-bg');
    const previewBackBg = document.getElementById('preview-back-bg');
    const previewFrontSvg = document.getElementById('preview-front-svg');
    const previewBackSvg = document.getElementById('preview-back-svg');
    const previewNavBtns = document.querySelectorAll('.preview-nav-btn');

    if (previewBtn && previewModal) {
        previewBtn.addEventListener('click', function() {
            showPreview();
        });
    }

    function showPreview() {
        if (!previewModal) return;

        // If the React TemplateEditor is mounted, prefer copying the live editor SVGs
        try {
            const editorFront = document.querySelector('#template-editor-container-front svg');
            const editorBack = document.querySelector('#template-editor-container-back svg');
            if (editorFront) {
                // Clear and copy
                previewFrontSvg.innerHTML = '';
                Array.from(editorFront.childNodes).forEach(n => previewFrontSvg.appendChild(n.cloneNode(true)));
                // copy attributes
                Array.from(editorFront.attributes || []).forEach(attr => {
                    if (attr && attr.name !== 'id') previewFrontSvg.setAttribute(attr.name, attr.value);
                });
            }
            if (editorBack && previewBackSvg) {
                previewBackSvg.innerHTML = '';
                Array.from(editorBack.childNodes).forEach(n => previewBackSvg.appendChild(n.cloneNode(true)));
                Array.from(editorBack.attributes || []).forEach(attr => {
                    if (attr && attr.name !== 'id') previewBackSvg.setAttribute(attr.name, attr.value);
                });
            }
        } catch (e) {
            // fall back to existing canvas-based preview
        }

        // Get current canvas dimensions and content
        const canvasWrapper = document.querySelector('.preview-canvas-wrapper');
        const canvasBg = document.querySelector('.preview-card-bg');
        const canvasSvg = document.getElementById('preview-svg');

        if (canvasWrapper && canvasBg && canvasSvg) {
            // Copy dimensions
            const canvasWidth = canvasWrapper.dataset.canvasWidth;
            const canvasHeight = canvasWrapper.dataset.canvasHeight;
            const canvasShape = canvasWrapper.dataset.canvasShape;
            const canvasUnit = canvasWrapper.dataset.canvasUnit || 'px';

            // Set preview card dimensions
            if (canvasWidth && canvasHeight) {
                const width = parseFloat(canvasWidth);
                const height = parseFloat(canvasHeight);
                const aspectRatio = height / width;

                // Calculate display size (max 600px width, maintain aspect ratio)
                const maxWidth = 600;
                const displayWidth = Math.min(width, maxWidth);
                const displayHeight = displayWidth * aspectRatio;

                previewFrontCard.style.width = displayWidth + 'px';
                previewFrontCard.style.height = displayHeight + 'px';
                if (previewBackCard) {
                    previewBackCard.style.width = displayWidth + 'px';
                    previewBackCard.style.height = displayHeight + 'px';
                }

                // Set SVG viewBox
                previewFrontSvg.setAttribute('viewBox', `0 0 ${canvasWidth} ${canvasHeight}`);
                if (previewBackSvg) {
                    previewBackSvg.setAttribute('viewBox', `0 0 ${canvasWidth} ${canvasHeight}`);
                }
            }

            // Copy background images
            const frontImage = canvasBg.dataset.frontImage;
            const backImage = canvasBg.dataset.backImage;

            if (frontImage) {
                previewFrontBg.style.backgroundImage = `url('${frontImage}')`;
            }

            if (backImage && previewBackBg) {
                previewBackBg.style.backgroundImage = `url('${backImage}')`;
            }

            // Copy SVG content
            if (canvasSvg) {
                const frontSvgContent = canvasSvg.outerHTML;
                previewFrontSvg.innerHTML = canvasSvg.innerHTML;

                // Copy SVG attributes
                Array.from(canvasSvg.attributes).forEach(attr => {
                    if (attr.name !== 'id') {
                        previewFrontSvg.setAttribute(attr.name, attr.value);
                    }
                });
            }

            // Handle back side if exists
            const hasBack = canvasBg.dataset.hasBack === 'true';
            if (hasBack && previewBackCard && previewBackSvg) {
                const backSvgData = canvasBg.dataset.backSvg;
                if (backSvgData) {
                    if (backSvgData.startsWith('data:image/svg+xml;base64,')) {
                        // Handle data URI
                        try {
                            const base64Data = backSvgData.split(',')[1];
                            const svgContent = atob(base64Data);

                            // Parse the SVG content
                            const parser = new DOMParser();
                            const svgDoc = parser.parseFromString(svgContent, 'image/svg+xml');
                            const svgElement = svgDoc.querySelector('svg');

                            if (svgElement) {
                                // Copy the inner content of the SVG
                                previewBackSvg.innerHTML = svgElement.innerHTML;

                                // Set the viewBox to match the canvas dimensions
                                if (canvasWidth && canvasHeight) {
                                    previewBackSvg.setAttribute('viewBox', `0 0 ${canvasWidth} ${canvasHeight}`);
                                }

                                // Copy other important attributes, but don't override viewBox
                                ['width', 'height', 'preserveAspectRatio'].forEach(attr => {
                                    const value = svgElement.getAttribute(attr);
                                    if (value && attr !== 'viewBox') {
                                        previewBackSvg.setAttribute(attr, value);
                                    }
                                });
                            } else {
                                console.warn('Invalid SVG content for back side');
                                previewBackSvg.innerHTML = '';
                            }
                        } catch (error) {
                            console.error('Error decoding back SVG data URI:', error);
                            previewBackSvg.innerHTML = '';
                        }
                    } else if (backSvgData.startsWith('http') || backSvgData.startsWith('/')) {
                        // Handle URL - fetch the SVG content
                        fetch(backSvgData)
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Failed to load back SVG');
                                }
                                return response.text();
                            })
                            .then(svgContent => {
                                // Extract the SVG content
                                const parser = new DOMParser();
                                const svgDoc = parser.parseFromString(svgContent, 'image/svg+xml');
                                const svgElement = svgDoc.querySelector('svg');

                                if (svgElement) {
                                    // Copy the inner content of the SVG
                                    previewBackSvg.innerHTML = svgElement.innerHTML;

                                    // Set the viewBox to match the canvas dimensions
                                    if (canvasWidth && canvasHeight) {
                                        previewBackSvg.setAttribute('viewBox', `0 0 ${canvasWidth} ${canvasHeight}`);
                                    }

                                    // Copy other important attributes, but don't override viewBox
                                    ['width', 'height', 'preserveAspectRatio'].forEach(attr => {
                                        const value = svgElement.getAttribute(attr);
                                        if (value && attr !== 'viewBox') {
                                            previewBackSvg.setAttribute(attr, value);
                                        }
                                    });
                                } else {
                                    console.warn('Invalid SVG content for back side');
                                    previewBackSvg.innerHTML = '';
                                }
                            })
                            .catch(error => {
                                console.error('Error loading back SVG:', error);
                                previewBackSvg.innerHTML = '';
                            });
                    } else {
                        console.warn('Unsupported back SVG format:', backSvgData);
                        previewBackSvg.innerHTML = '';
                    }
                } else {
                    previewBackSvg.innerHTML = '';
                }
            }
        }

        // Show modal
        previewModal.style.display = 'flex';
        previewModal.setAttribute('aria-hidden', 'false');

        // Default to front side
        showPreviewSide('front');
    }

    function showPreviewSide(side) {
        if (side === 'front') {
            previewFrontCard.classList.add('active');
            if (previewBackCard) previewBackCard.classList.remove('active');
        } else if (side === 'back' && previewBackCard) {
            previewBackCard.classList.add('active');
            previewFrontCard.classList.remove('active');
        }

        // Update nav buttons
        previewNavBtns.forEach(btn => {
            if (btn.dataset.side === side) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }

    // Preview navigation
    if (previewNavBtns.length > 0) {
        previewNavBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const side = this.dataset.side;
                showPreviewSide(side);
            });
        });
    }

    // Initial setup
    updateDisplay();
    updateButtons();
});
// Preview button functionality
document.addEventListener('DOMContentLoaded', function() {
    const previewBtn = document.getElementById('preview-btn');
    const previewModal = document.getElementById('studio-preview-modal');
    const previewContent = document.getElementById('studio-preview-content');
    const previewClose = document.getElementById('studio-preview-close');

    function openPreview() {
        const wrapper = document.querySelector('.preview-canvas-wrapper');
        if (!wrapper || !previewModal) return;

        // Clear previous
        previewContent.innerHTML = '';

        // Clone the wrapper (deep) and scale it to fit
        const clone = wrapper.cloneNode(true);
        clone.style.transform = 'none';
        clone.style.maxWidth = '100%';
        clone.style.maxHeight = '80vh';
        clone.style.boxShadow = '0 10px 30px rgba(0,0,0,0.3)';

        // Remove any interactive attributes that might interfere
        clone.querySelectorAll('[data-modal-close],[data-action],[id]').forEach(el => el.removeAttribute('id'));

        previewContent.appendChild(clone);
        previewModal.style.display = 'flex';
        previewModal.setAttribute('aria-hidden', 'false');
    }

    function closePreview() {
        if (!previewModal) return;
        previewModal.style.display = 'none';
        previewModal.setAttribute('aria-hidden', 'true');
        previewContent.innerHTML = '';
    }

    previewBtn?.addEventListener('click', function(e) {
        e.preventDefault();
        openPreview();
    });

    previewClose?.addEventListener('click', function(e) {
        e.preventDefault();
        closePreview();
    });

    previewModal?.addEventListener('click', function(e) {
        if (e.target === previewModal) {
            closePreview();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closePreview();
    });
});
</script>

<!-- Preview modal markup -->
<div id="studio-preview-modal" class="studio-preview-modal" aria-hidden="true" style="display:none;position:fixed;inset:0;z-index:20000;align-items:center;justify-content:center;background:rgba(0,0,0,0.8)">
    <div class="studio-preview-inner" style="background:#fff;padding:18px;border-radius:10px;max-width:90vw;max-height:90vh;overflow:auto;position:relative;">
        <button id="studio-preview-close" aria-label="Close preview" style="position:absolute;top:8px;right:8px;border:none;background:transparent;font-size:20px;cursor:pointer">&times;</button>
        <div id="studio-preview-content" style="display:flex;align-items:center;justify-content:center;padding:8px"></div>
    </div>
</div>

</body>
</html>
