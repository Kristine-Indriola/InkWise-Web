<!doctype html>
<html lang="en" class="h-full">
<head>
    @php
        $rawDesign = $template->design;
        if (is_string($rawDesign)) {
            $designPayload = json_decode($rawDesign ?: '[]', true) ?? [];
        } elseif (is_array($rawDesign)) {
            $designPayload = $rawDesign;
        } else {
            $designPayload = [];
        }
        $pageCount = isset($designPayload['pages']) && is_array($designPayload['pages'])
            ? count($designPayload['pages'])
            : 1;
    @endphp

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $template->name ? $template->name . ' · Template Builder' : 'InkWise Template Builder' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap">

    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-regular-rounded/css/uicons-regular-rounded.css">
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-solid-rounded/css/uicons-solid-rounded.css">
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-solid-straight/css/uicons-solid-straight.css">
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-brands/css/uicons-brands.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://kit.fontawesome.com/eb0420111f.js" crossorigin="anonymous"></script>

    <style>
        :root {
            color-scheme: light;
        }

        body {
            margin: 0;
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, 'Helvetica Neue', sans-serif;
            background: #f8fafc;
            color: #0f172a;
        }

        .builder-loading-shell {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 3rem 1.5rem;
        }

        .builder-loading-card {
            display: grid;
            place-items: center;
            gap: 0.75rem;
            text-align: center;
            max-width: 420px;
            width: min(420px, 90vw);
            padding: 2.5rem 2rem;
            border-radius: 1.25rem;
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: #ffffff;
            box-shadow: 0 18px 45px -18px rgba(15, 23, 42, 0.35);
        }

        .builder-loading-card h1 {
            font-size: 1.25rem;
            margin: 0;
            font-weight: 600;
        }

        .builder-loading-card p {
            margin: 0;
            font-size: 0.9rem;
            color: #475569;
        }

        .builder-loading-spinner {
            width: 44px;
            height: 44px;
            border-radius: 999px;
            border: 3px solid rgba(59, 130, 246, 0.25);
            border-top-color: #2563eb;
            animation: builder-spin 1s linear infinite;
        }

        @keyframes builder-spin {
            to { transform: rotate(360deg); }
        }
    </style>

    @if (app()->environment('local'))
        @viteReactRefresh
    @endif

    @vite(['resources/js/staff/template-editor/main.jsx'])
</head>
<body class="h-full bg-slate-100 text-slate-900 antialiased">
    <div
        id="template-builder-app"
        class="h-full min-h-screen"
        data-template-id="{{ $template->id }}"
        data-template-name="{{ $template->name ?? 'Untitled template' }}"
        data-template-slug="{{ $template->slug ?? '' }}"
        data-page-count="{{ $pageCount }}"
    >
        <div class="builder-loading-shell">
            <div class="builder-loading-card" role="status" aria-live="polite">
                <span class="builder-loading-spinner" aria-hidden="true"></span>
                <h1>Loading InkWise template builder…</h1>
                <p>Preparing fonts, brand assets, and guide overlays. This only takes a moment.</p>
            </div>
        </div>
    </div>

    <noscript>
        <div style="padding:24px;background:#fee2e2;color:#991b1b;font-weight:600;">
            JavaScript is required to use the InkWise template builder. Please enable JavaScript and reload this page.
        </div>
    </noscript>

    <script type="application/json" id="inkwise-builder-bootstrap">
        {!! json_encode([
            'csrfToken' => csrf_token(),
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'category' => $designPayload['category'] ?? null,
                'design' => $designPayload,
                'status' => $template->status ?? null,
                'slug' => $template->slug ?? null,
                'updated_at' => optional($template->updated_at)->toIso8601String(),
            ],
            'routes' => [
                'index' => route('staff.templates.index'),
                'create' => route('staff.templates.create'),
                'update' => route('staff.templates.update', $template->id),
                'saveTemplate' => route('staff.templates.saveTemplate', $template->id),
                'saveCanvas' => route('staff.templates.saveCanvas', $template->id),
                'saveSvg' => route('staff.templates.saveSvg', $template->id),
                'uploadPreview' => route('staff.templates.uploadPreview', $template->id),
                'saveVersion' => route('staff.templates.saveVersion', $template->id),
                'loadDesign' => route('staff.templates.loadDesign', $template->id),
                'searchAssets' => route('staff.templates.searchAssets', $template->id),
                'autosave' => route('staff.templates.autosave', $template->id),
                'figmaAnalyze' => route('staff.templates.figma.analyze'),
                'figmaPreview' => route('staff.templates.figma.preview'),
                'figmaImport' => route('staff.templates.figma.import'),
            ],
            'flags' => [
                'betaMockupPreview' => (bool) config('services.inkwise.enable_mockup_preview', false),
                'enableFilters' => true,
            ],
            'user' => [
                'id' => auth()->id(),
                'name' => optional(auth()->user())->name,
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
</body>
</html>
