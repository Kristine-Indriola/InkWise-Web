@include('customer.studio._head')
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
-
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

    $frontSvgPath = $templateModel?->svg_path ?? null;
    $backSvgPath = $templateModel?->back_svg_path ?? null;

    $frontSvg = $resolveSvgDataUri($frontSvgPath);
    if (!$frontSvg) {
        $frontSvgUrl = $resolveImage($frontSvgPath, null);
        if (is_string($frontSvgUrl) && preg_match('/\.svg($|\?)/i', $frontSvgUrl)) {
            $frontSvg = $frontSvgUrl;
        }
    }

    $backSvg = $resolveSvgDataUri($backSvgPath);
    if (!$backSvg) {
        $backSvgUrl = $resolveImage($backSvgPath, null);
        if (is_string($backSvgUrl) && preg_match('/\.svg($|\?)/i', $backSvgUrl)) {
            $backSvg = $backSvgUrl;
        }
    }

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
        'size' => $product->size ?? null,
    ] : null;

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
            'change_template' => route('templates.wedding.invitations'),
        ],
    ];
@endphp
<body class="studio-page">
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
        <span class="topbar-status-label">Preview</span>
        <div class="topbar-history-controls" role="group" aria-label="History controls">
            <button type="button" class="topbar-icon-btn" aria-label="View history"><i class="fa-regular fa-clock"></i></button>
        </div>
        <div id="inkwise-customer-studio-root" class="studio-react-root" aria-live="polite"></div>
    </div>
    <div class="topbar-actions">
        <button class="topbar-action-btn" type="button" onclick="window.location.href='{{ route('templates.wedding.invitations') }}'">Change Template</button>
        <button class="topbar-action-btn" type="button">Preview</button>
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
            </div>
            <div class="canvas-container">
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
                <button type="button" class="canvas-control-btn icon" data-zoom-reset aria-label="Reset zoom to 100%"><i class="fa-solid fa-rotate-right"></i></button>
                <button type="button" class="canvas-control-btn icon" aria-label="Canvas settings"><i class="fa-solid fa-gear"></i></button>
                <select class="canvas-zoom-select" id="canvas-zoom-select" aria-label="Zoom level">
                    <option value="0.25">25%</option>
                    <option value="0.5">50%</option>
                    <option value="0.75">75%</option>
                    <option value="1" selected>100%</option>
                    <option value="1.25">125%</option>
                    <option value="1.5">150%</option>
                    <option value="2">200%</option>
                </select>
            </div>
        </div>
    </section>
</main>

@include('customer.studio._bootstrap')

</body>
</html>
