@include('customer.studio._head')
@php
    $defaultBack = asset('Customerimages/invite/wedding3.jpg');
    $templateModel = $template ?? null;
    $product = $product ?? null;

    $backImage = null;
    if ($templateModel) {
        $backImage = $templateModel->back_image ? \App\Support\ImageResolver::url($templateModel->back_image) : null;
        $backSvg = $templateModel->back_svg_path ? \App\Support\ImageResolver::url($templateModel->back_svg_path) : null;
    } else {
        $backImage = $defaultBack;
        $backSvg = null;
    }

    $bootstrapPayload = [
        'csrfToken' => csrf_token(),
        'product' => $product ? ['id' => $product->id, 'name' => $product->name] : null,
        'template' => $templateModel ? ['id' => $templateModel->id, 'name' => $templateModel->name] : null,
        'assets' => [
            'back_image' => $backImage,
            'preview_images' => ['back' => $templateModel?->preview_back ? \App\Support\ImageResolver::url($templateModel->preview_back) : null],
        ],
        'svg' => ['back' => $backSvg],
        'flags' => ['has_back' => (bool) ($backSvg || $backImage)],
        'routes' => [
            'autosave' => route('order.design.autosave'),
            'saveTemplate' => route('order.design.save-template'),
        ],
    ];
@endphp
<body class="studio-page">
<main class="studio-layout">
    <section class="studio-canvas-area">
        <div id="back-preview-root" class="preview-container" style="padding:20px;">
            <!-- back preview will be injected by back-preview.jsx -->
        </div>
    </section>
</main>

@include('customer.studio._bootstrap')

@vite('resources/js/customer/studio/back-preview.jsx')

</body>
</html>
