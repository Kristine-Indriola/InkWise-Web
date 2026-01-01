<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Order Summary — InkWise</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap');
        @import url('https://fonts.cdnfonts.com/css/edwardian-script-itc');
        :root { color-scheme: light; }
        body { font-family: 'Poppins', system-ui, -apple-system, sans-serif; }
        .glass-card { backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.82); }
        .fade-border { border: 1px solid rgba(15, 23, 42, 0.08); box-shadow: 0 20px 60px rgba(15, 23, 42, 0.12); }
    </style>
</head>
<body class="bg-slate-50 text-slate-900"
    data-envelope-total="{{ $envelopeTotalCalc ?? 0 }}"
    data-giveaway-total="{{ $giveawayTotalCalc ?? 0 }}"
    data-shipping="{{ $shipping ?? 0 }}"
    data-tax="{{ $tax ?? 0 }}">
@php
    $resolveRoute = static function (string $name, string $fallbackPath) {
        try {
            return route($name);
        } catch (\Throwable $exception) {
            return url($fallbackPath);
        }
    };

    $summaryUrl = $resolveRoute('order.summary', '/order/summary');
    $summaryJsonUrl = $resolveRoute('order.summary.json', '/order/summary.json');
    $summaryClearUrl = $resolveRoute('order.summary.clear', '/order/summary');
    $envelopeClearUrl = $resolveRoute('order.envelope.clear', '/order/envelope');
    $giveawayClearUrl = $resolveRoute('order.giveaways.clear', '/order/giveaways');
    $finalStepUrl = $resolveRoute('order.finalstep', '/order/finalstep');
    $envelopeUrl = $resolveRoute('order.envelope', '/order/envelope');
    $giveawaysUrl = $resolveRoute('order.giveaways', '/order/giveaways');
    $checkoutUrl = $resolveRoute('customer.checkout', '/checkout');
    $checkoutPaymentUrl = $checkoutUrl . '#payment';
    $placeholderImage = asset('images/placeholder.png');

    // Topbar navigation context
    $resolvedInvitationType = $invitationType
        ?? (request()->routeIs('templates.corporate.*') ? 'Corporate'
            : (request()->routeIs('templates.baptism.*') ? 'Baptism'
                : (request()->routeIs('templates.birthday.*') ? 'Birthday'
                    : 'Wedding')));

    $eventRoutes = [
        'wedding' => [
            'label' => 'Wedding',
            'invitations' => route('templates.wedding.invitations'),
            'giveaways' => route('templates.wedding.giveaways'),
        ],
        'corporate' => [
            'label' => 'Corporate',
            'invitations' => route('templates.corporate.invitations'),
            'giveaways' => route('templates.corporate.giveaways'),
        ],
        'baptism' => [
            'label' => 'Baptism',
            'invitations' => route('templates.baptism.invitations'),
            'giveaways' => route('templates.baptism.giveaways'),
        ],
        'birthday' => [
            'label' => 'Birthday',
            'invitations' => route('templates.birthday.invitations'),
            'giveaways' => route('templates.birthday.giveaways'),
        ],
    ];

    $currentEventKey = strtolower($resolvedInvitationType);
    if (! array_key_exists($currentEventKey, $eventRoutes)) {
        $currentEventKey = 'wedding';
    }
    $currentEventRoutes = $eventRoutes[$currentEventKey];

    $navLinks = [];
    foreach ($eventRoutes as $key => $config) {
        $navLinks[] = [
            'key' => $key,
            'label' => $config['label'],
            'route' => $config['invitations'],
            'isActive' => $key === $currentEventKey,
        ];
    }

    $favoritesEnabled = \Illuminate\Support\Facades\Route::has('customer.favorites');
    $cartRoute = \Illuminate\Support\Facades\Route::has('customer.cart')
        ? route('customer.cart')
        : '/order/addtocart';
    $searchValue = request('query', '');

    $hasEnvelope = (bool) data_get($orderSummary, 'hasEnvelope', !empty(data_get($orderSummary, 'envelope')));
    $hasGiveaway = (bool) data_get($orderSummary, 'hasGiveaway', !empty(data_get($orderSummary, 'giveaway')));

    $formatMoney = static fn ($amount) => '₱' . number_format((float) ($amount ?? 0), 2);
    $invitationSubtotal = (float) data_get($orderSummary, 'subtotalAmount', 0);
    $extras = (array) data_get($orderSummary, 'extras', []);
    $envelopeTotal = (float) ($extras['envelope'] ?? 0);
    $giveawayTotal = (float) ($extras['giveaway'] ?? 0);
    $paperExtras = (float) ($extras['paper'] ?? 0);
    $addonsExtra = (float) ($extras['addons'] ?? 0);
    $shipping = (float) data_get($orderSummary, 'shippingFee', 0);
    $tax = (float) data_get($orderSummary, 'taxAmount', 0);

    $extractQty = static function ($line) {
        return (int) (data_get($line, 'quantity') ?? data_get($line, 'qty') ?? 0);
    };

    $extractTotal = static function ($line) {
        return (float) (
            data_get($line, 'total')
            ?? data_get($line, 'totalAmount')
            ?? data_get($line, 'total_amount')
            ?? data_get($line, 'total_price')
            ?? data_get($line, 'price')
            ?? 0
        );
    };

    $extractPreview = static function ($line) {
        $candidates = [
            data_get($line, 'preview'),
            data_get($line, 'previewImage'),
            data_get($line, 'invitationImage'),
            data_get($line, 'previewImages.0'),
            data_get($line, 'preview_images.0'),
            data_get($line, 'preview_url'),
            data_get($line, 'previewUrl'),
            data_get($line, 'image'),
            data_get($line, 'image_url'),
            data_get($line, 'imageUrl'),
            data_get($line, 'images.0'),
        ];
        foreach ($candidates as $c) {
            if ($c) {
                return $c;
            }
        }
        return null;
    };

    $invitationItems = collect(data_get($orderSummary, 'items', []))->filter(fn ($item) => is_array($item));
    if ($invitationItems->isEmpty() && !empty($orderSummary)) {
        $invitationItems = collect([
            [
                'name' => data_get($orderSummary, 'productName', 'Custom invitation'),
                'quantity' => data_get($orderSummary, 'quantity', 0),
                'unitPrice' => data_get($orderSummary, 'unitPrice') ?? data_get($orderSummary, 'unit_price') ?? data_get($orderSummary, 'paperStockPrice'),
                'paperStockName' => data_get($orderSummary, 'paperStockName') ?? data_get($orderSummary, 'paperStock.name'),
                'paperStockPrice' => data_get($orderSummary, 'paperStockPrice') ?? data_get($orderSummary, 'paperStock.price'),
                'paperStockId' => data_get($orderSummary, 'paperStockId') ?? data_get($orderSummary, 'paperStock.id'),
                'addons' => data_get($orderSummary, 'addons', []),
                'addonItems' => data_get($orderSummary, 'addonItems', data_get($orderSummary, 'addons', [])),
                'total' => $invitationSubtotal + $paperExtras + $addonsExtra,
                'preview' => $extractPreview($orderSummary),
                'previewImages' => data_get($orderSummary, 'previewImages', []),
                'estimated_date' => data_get($orderSummary, 'estimated_date') ?? data_get($orderSummary, 'dateNeeded'),
                'estimated_date_label' => data_get($orderSummary, 'dateNeededLabel') ?? data_get($orderSummary, 'estimated_date_label'),
                'is_preorder' => data_get($orderSummary, 'metadata.final_step.is_preorder') ?? false,
                'metadata' => data_get($orderSummary, 'metadata', []),
            ],
        ]);
    }

    $envelopeItems = collect(data_get($orderSummary, 'envelopes', []))->filter(fn ($item) => is_array($item));
    if ($envelopeItems->isEmpty()) {
        $rawEnvelope = data_get($orderSummary, 'envelope');
        if ($hasEnvelope && is_array($rawEnvelope)) {
            if (function_exists('array_is_list') && array_is_list($rawEnvelope) && !empty($rawEnvelope) && is_array($rawEnvelope[0])) {
                $envelopeItems = collect($rawEnvelope)->filter(fn ($item) => is_array($item));
            } else {
                $envelopeItems = collect([$rawEnvelope]);
            }
        }
    }

    $giveawayItems = collect(data_get($orderSummary, 'giveaways', []))->filter(fn ($item) => is_array($item));
    if ($giveawayItems->isEmpty()) {
        $rawGiveaway = data_get($orderSummary, 'giveaway');
        if ($hasGiveaway && is_array($rawGiveaway)) {
            if (function_exists('array_is_list') && array_is_list($rawGiveaway) && !empty($rawGiveaway) && is_array($rawGiveaway[0])) {
                $giveawayItems = collect($rawGiveaway)->filter(fn ($item) => is_array($item));
            } else {
                $giveawayItems = collect([$rawGiveaway]);
            }
        }
    }

    $computeInvitationTotal = static function ($item) use ($extractTotal, $extractQty) {
        $rawTotal = $extractTotal($item);
        if ($rawTotal > 0) {
            return $rawTotal;
        }

        $qty = $extractQty($item);
        $unit = data_get($item, 'unitPrice')
            ?? data_get($item, 'unit_price')
            ?? data_get($item, 'price')
            ?? data_get($item, 'paperStockPrice')
            ?? data_get($item, 'paper_stock_price')
            ?? 0;

        return max(0, $qty * (float) $unit);
    };

    $invitationTotalCalc = $invitationItems->sum(fn ($item) => $computeInvitationTotal($item));
    if ($invitationTotalCalc <= 0) {
        $invitationTotalCalc = $invitationSubtotal + $paperExtras + $addonsExtra;
    }

    $envelopeTotalCalc = $envelopeItems->sum(fn ($item) => $extractTotal($item));
    if ($envelopeTotalCalc <= 0) {
        $envelopeTotalCalc = $envelopeTotal;
    }

    $giveawayTotalCalc = $giveawayItems->sum(fn ($item) => $extractTotal($item));
    if ($giveawayTotalCalc <= 0) {
        $giveawayTotalCalc = $giveawayTotal;
    }

    $grandTotal = $invitationTotalCalc + $envelopeTotalCalc + $giveawayTotalCalc + $shipping + $tax;
@endphp

    @include('partials.topbar')

    <script>
        // Sync sessionStorage data (supports legacy and new keys) to server session on page load
        (function() {
            try {
                const candidateKeys = ['inkwise-finalstep', 'order_summary_payload', 'inkwise-addtocart'];
                let sessionData = null;
                let activeKey = null;

                for (const key of candidateKeys) {
                    const value = sessionStorage.getItem(key);
                    if (value) {
                        sessionData = value;
                        activeKey = key;
                        break;
                    }
                }

                if (sessionData) {
                    const summary = JSON.parse(sessionData);
                    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                    // Keep a canonical copy for downstream pages
                    if (activeKey !== 'order_summary_payload') {
                        sessionStorage.setItem('order_summary_payload', sessionData);
                    }
                    
                    // Sync to server
                    fetch('{{ route("order.summary.sync") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf || ''
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ summary: summary })
                    }).then(response => {
                        if (response.ok) {
                            // Reload page to show synced data
                            if (!window.location.href.includes('synced=1')) {
                                window.location.href = window.location.href + (window.location.href.includes('?') ? '&' : '?') + 'synced=1';
                            }
                        }
                    }).catch(err => console.warn('Failed to sync session data:', err));
                }
            } catch (err) {
                console.warn('Error syncing session data:', err);
            }
        })();
    </script>

    <main class="max-w-6xl mx-auto px-4 lg:px-6 pt-28 pb-16">
        <header class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-10">
            <div>
                <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Order</p>
                <h1 class="text-3xl font-semibold text-slate-900">Order Summary</h1>
                <p class="text-slate-600 mt-1">Review your selections before checkout. You can still edit any item.</p>
            </div>
            <div class="flex gap-3">
                     <a href="{{ $finalStepUrl }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:border-slate-300">Edit design</a>
                     <a id="checkout-top"
                         href="{{ $checkoutPaymentUrl }}"
                         class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-slate-900/10 hover:bg-slate-800">Proceed to checkout</a>
            </div>
        </header>

        @if(empty($orderSummary))
            <section class="glass-card fade-border rounded-3xl p-10 text-center">
                <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-900/90 text-white shadow-lg shadow-slate-900/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-9 4h12M5 9h14M4 6h16M4 18h16" />
                    </svg>
                </div>
                <h2 class="text-2xl font-semibold text-slate-900">No order selections found</h2>
                <p class="mt-2 text-slate-600">Start again from the final step to configure your invitation, envelopes, or giveaways.</p>
                <div class="mt-6 flex flex-col sm:flex-row sm:justify-center gap-3">
                    <a href="{{ $finalStepUrl }}" class="rounded-full bg-slate-900 px-5 py-2.5 text-white font-medium shadow-lg shadow-slate-900/15 hover:bg-slate-800">Return to final step</a>
                    <a href="{{ $giveawaysUrl }}" class="rounded-full border border-slate-200 px-5 py-2.5 text-slate-800 font-medium hover:border-slate-300">Browse giveaways</a>
                </div>
            </section>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <section class="lg:col-span-2 space-y-6">
                    {{-- Invitations: show each selection as its own card --}}
                    @forelse($invitationItems as $invitation)
                        @php
                            $invName = data_get($invitation, 'name')
                                ?? data_get($invitation, 'productName')
                                ?? data_get($invitation, 'product_name')
                                ?? data_get($orderSummary, 'productName')
                                ?? 'Invitation selection';
                            $invQty = $extractQty($invitation);
                            $invPreview = $extractPreview($invitation);
                            $invPreviewGallery = collect(array_merge(
                                (array) data_get($invitation, 'previewImages', []),
                                (array) data_get($invitation, 'preview_images', []),
                                (array) data_get($invitation, 'images', []),
                                [
                                    data_get($invitation, 'backImage'),
                                    data_get($invitation, 'back_image'),
                                    data_get($invitation, 'previewImages.1'),
                                    data_get($invitation, 'preview_images.1'),
                                ],
                            ))->filter()->unique()->values();
                            if ($invPreview && $invPreviewGallery->isEmpty()) {
                                $invPreviewGallery = collect([$invPreview]);
                            }
                            $invUnitPrice = (float) (
                                data_get($invitation, 'unitPrice')
                                ?? data_get($invitation, 'unit_price')
                                ?? data_get($invitation, 'price')
                                ?? data_get($invitation, 'paperStockPrice')
                                ?? data_get($invitation, 'paper_stock_price')
                                ?? data_get($orderSummary, 'unitPrice')
                                ?? data_get($orderSummary, 'paperStockPrice')
                                ?? 0
                            );
                            $invMeta = (array) data_get($invitation, 'metadata', []);
                            $invIsPreorder = (bool) (
                                data_get($invMeta, 'is_preorder')
                                ?? data_get($invMeta, 'is_pre_order')
                                ?? data_get($invMeta, 'preorder')
                                ?? data_get($invitation, 'is_preorder')
                                ?? data_get($invitation, 'is_pre_order')
                                ?? data_get($orderSummary, 'metadata.final_step.is_preorder')
                                ?? data_get($orderSummary, 'metadata.final_step.is_pre_order')
                                ?? data_get($orderSummary, 'metadata.final_step.preorder')
                                ?? data_get($orderSummary, 'is_preorder')
                                ?? data_get($orderSummary, 'is_pre_order')
                                ?? data_get($orderSummary, 'preorder')
                            );
                            // Enhanced estimated delivery date label extraction
                            $invPickupLabel = data_get($invitation, 'estimated_date_label')
                                ?? data_get($invitation, 'estimatedDateLabel')
                                ?? data_get($invitation, 'dateNeededLabel')
                                ?? data_get($orderSummary, 'dateNeededLabel')
                                ?? data_get($orderSummary, 'estimated_date_label')
                                ?? data_get($orderSummary, 'estimatedDateLabel')
                                ?? data_get($orderSummary, 'metadata.final_step.estimated_date_label')
                                ?? data_get($orderSummary, 'metadata.final_step.estimatedDateLabel')
                                ?? data_get($invMeta, 'estimated_date_label')
                                ?? data_get($invMeta, 'estimatedDateLabel');
                            
                            // If no formatted label, try to format the date value
                            if (!$invPickupLabel) {
                                $invPickupDate = data_get($invitation, 'estimated_date')
                                    ?? data_get($invitation, 'dateNeeded')
                                    ?? data_get($orderSummary, 'estimated_date')
                                    ?? data_get($orderSummary, 'dateNeeded')
                                    ?? data_get($orderSummary, 'metadata.final_step.estimated_date');
                                if ($invPickupDate) {
                                    try {
                                        $invPickupLabel = \Carbon\Carbon::parse($invPickupDate)->format('F j, Y');
                                    } catch (\Throwable $e) {
                                        $invPickupLabel = $invPickupDate;
                                    }
                                }
                            }
                            
                            $invPaper = data_get($invitation, 'paperStockName') 
                                ?? data_get($invitation, 'paper_stock_name')
                                ?? data_get($orderSummary, 'paperStockName')
                                ?? data_get($orderSummary, 'paperStock.name');
                            $invPaperPrice = data_get($invitation, 'paperStockPrice') 
                                ?? data_get($invitation, 'paper_stock_price')
                                ?? data_get($orderSummary, 'paperStockPrice')
                                ?? data_get($orderSummary, 'paperStock.price');
                            $invPreorderPaper = data_get($invMeta, 'preorder_paper')
                                ?? data_get($invMeta, 'preorderPaper')
                                ?? data_get($invMeta, 'preorder_paper_name')
                                ?? data_get($invitation, 'preorder_paper')
                                ?? data_get($invitation, 'preorderPaper')
                                ?? data_get($orderSummary, 'metadata.final_step.preorder_paper')
                                ?? data_get($orderSummary, 'metadata.final_step.preorder_paper_name')
                                ?? data_get($orderSummary, 'metadata.final_step.preorderPaper')
                                ?? null;
                            
                            // Enhanced add-ons extraction
                            $invAddonsList = data_get($invitation, 'addonItems', data_get($invitation, 'addons', []));
                            if (empty($invAddonsList)) {
                                $invAddonsList = data_get($orderSummary, 'addonItems', data_get($orderSummary, 'addons', []));
                            }
                            $invAddons = collect($invAddonsList)
                                ->map(function ($addon) {
                                    if (is_array($addon)) {
                                        return data_get($addon, 'name') ?? data_get($addon, 'id');
                                    }
                                    return $addon;
                                })
                                ->filter()
                                ->implode(', ');
                            
                            $invTotal = $computeInvitationTotal($invitation) ?: ($invitationSubtotal + $paperExtras + $addonsExtra);
                        @endphp
                        <article class="glass-card fade-border rounded-3xl p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-4">
                                        <div class="h-24 w-24 overflow-hidden rounded-2xl bg-slate-100 shadow-inner">
                                        <img src="{{ $invPreview ?: $placeholderImage }}"
                                             alt="Invitation preview"
                                             class="h-full w-full object-cover js-preview-trigger"
                                             data-preview-images='@json($invPreviewGallery->values())'>
                                        </div>
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Invitation</p>
                                        <h2 class="text-lg font-semibold text-slate-900">{{ $invName }}</h2>
                                        @if($invIsPreorder)
                                            <p class="mt-1 inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                                                Pre-order
                                                @if($invPickupLabel)
                                                    <span class="text-amber-700 font-normal">· Est. pickup {{ $invPickupLabel }}</span>
                                                @endif
                                            </p>
                                        @elseif($invPickupLabel)
                                            <p class="mt-1 text-xs text-slate-600">Est. pickup {{ $invPickupLabel }}</p>
                                        @endif
                                        <label class="text-slate-600 text-sm flex items-center gap-2">Quantity:
                                              <input type="number"
                                                  value="{{ max(10, $invQty) }}"
                                                  min="10"
                                                  step="1"
                                                  inputmode="numeric"
                                                  pattern="[0-9]*"
                                                  class="js-inv-qty w-20 rounded-lg border border-slate-300 px-2 py-1 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-300"
                                                  data-index="{{ $loop->index }}"
                                                  data-unit="{{ $invUnitPrice }}">
                                        </label>
                                        @if($invPaper)
                                            <p class="text-slate-600 text-sm">Paper: {{ $invPaper }} @if($invPaperPrice) ({{ $formatMoney($invPaperPrice) }}) @endif</p>
                                        @endif
                                        @if($invIsPreorder && ($invPreorderPaper || $invPaper))
                                            <p class="text-amber-700 text-sm">Pre-order paper: {{ $invPreorderPaper ?? $invPaper }}</p>
                                        @endif
                                        @if($invAddons)
                                            <p class="text-slate-600 text-sm">Add-ons: {{ $invAddons }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right">
                                    <a href="{{ $finalStepUrl }}" class="inline-flex text-sm text-slate-700 hover:text-slate-900">Edit invitation</a>
                                    <p class="text-sm text-slate-500">Item total</p>
                                    <p class="text-xl font-semibold text-slate-900 js-inv-item-total" data-index="{{ $loop->index }}">{{ $formatMoney($invTotal) }}</p>
                                    <form method="POST" action="{{ $summaryClearUrl }}" class="mt-2">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-rose-600 hover:text-rose-700">Remove invitation(s)</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @empty
                        <article class="glass-card fade-border rounded-3xl p-6">
                            <p class="text-slate-700 text-sm">No invitations added yet.</p>
                        </article>
                    @endforelse

                    {{-- Envelopes: each selected envelope rendered separately --}}
                    <article class="glass-card fade-border rounded-3xl p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Envelopes</p>
                                @if($envelopeItems->isNotEmpty())
                                    <div class="space-y-4">
                                        @foreach($envelopeItems as $env)
                                            @php
                                                $envName = data_get($env, 'name', 'Envelope selection');
                                                $envQty = $extractQty($env);
                                                $envSize = data_get($env, 'size');
                                                $envColor = data_get($env, 'color');
                                                $envTotal = $extractTotal($env) ?: 0;
                                                $envPreview = $extractPreview($env);
                                                $envGallery = collect(data_get($env, 'images', []))->filter()->values();
                                                if ($envPreview && $envGallery->isEmpty()) {
                                                    $envGallery = collect([$envPreview]);
                                                }
                                                $envUnitPrice = $envQty > 0 && $envTotal > 0
                                                    ? $envTotal / $envQty
                                                    : (data_get($env, 'unit_price') ?? data_get($env, 'price') ?? 0);
                                                $envId = data_get($env, 'product_id') ?? data_get($env, 'id');
                                                $envPickupLabel = data_get($env, 'estimated_date_label')
                                                    ?? data_get($env, 'estimatedDateLabel')
                                                    ?? data_get($orderSummary, 'dateNeededLabel')
                                                    ?? data_get($orderSummary, 'estimated_date_label')
                                                    ?? data_get($orderSummary, 'estimatedDateLabel')
                                                    ?? data_get($orderSummary, 'metadata.final_step.estimated_date_label');
                                                if (!$envPickupLabel) {
                                                    $envPickupDate = data_get($env, 'estimated_date')
                                                        ?? data_get($env, 'dateNeeded')
                                                        ?? data_get($orderSummary, 'estimated_date')
                                                        ?? data_get($orderSummary, 'dateNeeded')
                                                        ?? data_get($orderSummary, 'metadata.final_step.estimated_date');
                                                    if ($envPickupDate) {
                                                        try {
                                                            $envPickupLabel = \Carbon\Carbon::parse($envPickupDate)->format('F j, Y');
                                                        } catch (\Throwable $e) {
                                                            $envPickupLabel = $envPickupDate;
                                                        }
                                                    }
                                                }
                                            @endphp
                                            <div class="flex gap-4 rounded-2xl border border-slate-200 bg-white/70 p-4 shadow-sm">
                                                <div class="h-20 w-20 overflow-hidden rounded-xl bg-slate-100 shadow-inner flex-shrink-0">
                                                    <img src="{{ $envPreview ?: $placeholderImage }}"
                                                         alt="Envelope preview"
                                                         class="h-full w-full object-cover js-preview-trigger"
                                                         data-preview-images='@json($envGallery->values())'>
                                                </div>
                                                <div class="flex-1">
                                                    <h3 class="text-base font-semibold text-slate-900">{{ $envName }}</h3>
                                                    @if($envPickupLabel)
                                                        <p class="mt-1 text-xs text-slate-600">Est. pickup {{ $envPickupLabel }}</p>
                                                    @endif
                                                    <label class="text-slate-600 text-sm flex items-center gap-2">Quantity:
                                                        <input type="number"
                                                            value="{{ max(10, $envQty) }}"
                                                            min="10"
                                                            step="1"
                                                            inputmode="numeric"
                                                            pattern="[0-9]*"
                                                            class="js-env-qty w-20 rounded-lg border border-slate-300 px-2 py-1 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-300"
                                                            data-index="{{ $loop->index }}"
                                                            data-unit="{{ (float) $envUnitPrice }}">
                                                    </label>
                                                    @if($envSize)
                                                        <p class="text-slate-600 text-sm">Size: {{ $envSize }}</p>
                                                    @endif
                                                    @if($envColor)
                                                        <p class="text-slate-600 text-sm">Color: {{ $envColor }}</p>
                                                    @endif
                                                    <p class="text-sm font-semibold text-slate-900 mt-2 js-env-item-total" data-index="{{ $loop->index }}">{{ $formatMoney($envTotal ?: $envelopeTotal) }}</p>
                                                    @if($envId)
                                                        <form method="POST" action="{{ $envelopeClearUrl }}" class="mt-2">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="product_id" value="{{ $envId }}">
                                                            <button type="submit" class="text-sm text-rose-600 hover:text-rose-700">Remove envelope</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-slate-700 text-sm">No envelope added yet.</p>
                                @endif
                            </div>
                            <div class="text-right">
                                <a href="{{ $envelopeUrl }}" class="text-sm text-slate-700 hover:text-slate-900">Edit envelopes</a>
                                <p class="mt-3 text-sm text-slate-500">Total</p>
                                <p class="text-xl font-semibold text-slate-900">{{ $formatMoney($envelopeTotalCalc) }}</p>
                            </div>
                        </div>
                    </article>

                    {{-- Giveaways: each selected giveaway rendered separately --}}
                    <article class="glass-card fade-border rounded-3xl p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Giveaways</p>
                                @if($giveawayItems->isNotEmpty())
                                    <div class="space-y-4">
                                        @foreach($giveawayItems as $give)
                                            @php
                                                $giveName = data_get($give, 'name', 'Giveaway selection');
                                                $giveQty = $extractQty($give);
                                                $giveMaterial = data_get($give, 'material');
                                                $giveTotal = $extractTotal($give) ?: 0;
                                                $givePreview = $extractPreview($give);
                                                $giveGallery = collect(data_get($give, 'images', []))->filter()->values();
                                                if ($givePreview && $giveGallery->isEmpty()) {
                                                    $giveGallery = collect([$givePreview]);
                                                }
                                                $giveUnitPrice = $giveQty > 0 && $giveTotal > 0
                                                    ? $giveTotal / $giveQty
                                                    : (data_get($give, 'unit_price') ?? data_get($give, 'price') ?? 0);
                                                $giveId = data_get($give, 'product_id') ?? data_get($give, 'id');
                                                $giveIsPreorder = (bool) (
                                                    data_get($give, 'is_preorder')
                                                    ?? data_get($give, 'metadata.is_preorder')
                                                    ?? (data_get($give, 'stock_qty') === 0)
                                                );
                                                $givePickupLabel = data_get($give, 'estimated_date_label')
                                                    ?? data_get($give, 'estimatedDateLabel')
                                                    ?? data_get($orderSummary, 'dateNeededLabel')
                                                    ?? data_get($orderSummary, 'estimated_date_label')
                                                    ?? data_get($orderSummary, 'estimatedDateLabel')
                                                    ?? data_get($orderSummary, 'metadata.final_step.estimated_date_label');
                                                if (!$givePickupLabel) {
                                                    $givePickupDate = data_get($give, 'estimated_date')
                                                        ?? data_get($give, 'dateNeeded')
                                                        ?? data_get($orderSummary, 'estimated_date')
                                                        ?? data_get($orderSummary, 'dateNeeded')
                                                        ?? data_get($orderSummary, 'metadata.final_step.estimated_date');
                                                    if ($givePickupDate) {
                                                        try {
                                                            $givePickupLabel = \Carbon\Carbon::parse($givePickupDate)->format('F j, Y');
                                                        } catch (\Throwable $e) {
                                                            $givePickupLabel = $givePickupDate;
                                                        }
                                                    }
                                                }
                                            @endphp
                                            <div class="flex gap-4 rounded-2xl border border-slate-200 bg-white/70 p-4 shadow-sm">
                                                <div class="h-20 w-20 overflow-hidden rounded-xl bg-slate-100 shadow-inner flex-shrink-0">
                                                    <img src="{{ $givePreview ?: $placeholderImage }}"
                                                         alt="Giveaway preview"
                                                         class="h-full w-full object-cover js-preview-trigger"
                                                         data-preview-images='@json($giveGallery->values())'>
                                                </div>
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <h3 class="text-base font-semibold text-slate-900">{{ $giveName }}</h3>
                                                        @if($giveIsPreorder)
                                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">Pre-order</span>
                                                        @endif
                                                    </div>
                                                    @if($giveIsPreorder)
                                                        <div class="mb-2 inline-flex items-center gap-2 rounded-lg bg-amber-50 px-3 py-1.5 ring-1 ring-amber-200">
                                                            <span class="text-amber-800 text-xs font-semibold uppercase tracking-wide">Pre-order</span>
                                                            @if($givePickupLabel)
                                                                <span class="text-amber-900 text-xs font-medium">· Est. pickup {{ $givePickupLabel }}</span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                    <label class="text-slate-600 text-sm flex items-center gap-2">Quantity:
                                                        <input type="number"
                                                            value="{{ max(10, $giveQty) }}"
                                                            min="10"
                                                            step="1"
                                                            inputmode="numeric"
                                                            pattern="[0-9]*"
                                                            class="js-give-qty w-20 rounded-lg border border-slate-300 px-2 py-1 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-300"
                                                            data-index="{{ $loop->index }}"
                                                            data-unit="{{ (float) $giveUnitPrice }}">
                                                    </label>
                                                    @if($giveMaterial)
                                                        @if($giveIsPreorder)
                                                            <p class="text-amber-900 text-sm">Pre-order material: {{ $giveMaterial }}</p>
                                                        @else
                                                            <p class="text-slate-600 text-sm">Material: {{ $giveMaterial }}</p>
                                                        @endif
                                                    @endif
                                                    <p class="text-sm font-semibold text-slate-900 mt-2 js-give-item-total" data-index="{{ $loop->index }}">{{ $formatMoney($giveTotal ?: $giveawayTotal) }}</p>
                                                    @if($giveId)
                                                        <form method="POST" action="{{ $giveawayClearUrl }}" class="mt-2">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="product_id" value="{{ $giveId }}">
                                                            <button type="submit" class="text-sm text-rose-600 hover:text-rose-700">Remove giveaway</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-slate-700 text-sm">No giveaway added yet.</p>
                                @endif
                            </div>
                            <div class="text-right">
                                <div class="flex flex-col items-end gap-2">
                                    <a href="{{ $giveawaysUrl }}" class="text-sm text-slate-700 hover:text-slate-900">Edit giveaways</a>
                                    @if($hasGiveaway)
                                        <form method="POST" action="{{ $giveawayClearUrl }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm text-rose-600 hover:text-rose-700">Remove giveaways</button>
                                        </form>
                                    @endif
                                </div>
                                <p class="mt-3 text-sm text-slate-500">Total</p>
                                <p class="text-xl font-semibold text-slate-900">{{ $formatMoney($giveawayTotalCalc) }}</p>
                            </div>
                        </div>
                    </article>
                </section>

                <aside class="space-y-4">
                    <div class="glass-card fade-border rounded-3xl p-6">
                        <h2 class="text-lg font-semibold text-slate-900">Payment summary</h2>
                        <dl class="mt-4 space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                    <dt class="text-slate-600">Invitation</dt>
                                    <dd class="font-medium text-slate-900" id="summary-inv-total">{{ $formatMoney($invitationTotalCalc) }}</dd>
                                </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-slate-600">Envelope</dt>
                                    <dd class="font-medium text-slate-900" id="summary-env-total">{{ $formatMoney($envelopeTotalCalc) }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-slate-600">Giveaways</dt>
                                    <dd class="font-medium text-slate-900" id="summary-give-total">{{ $formatMoney($giveawayTotalCalc) }}</dd>
                            </div>
                            @if($paperExtras > 0)
                                <div class="flex items-center justify-between">
                                    <dt class="text-slate-600">Paper options</dt>
                                    <dd class="font-medium text-slate-900">{{ $formatMoney($paperExtras) }}</dd>
                                </div>
                            @endif
                            @if($addonsExtra > 0)
                                <div class="flex items-center justify-between">
                                    <dt class="text-slate-600">Add-ons</dt>
                                    <dd class="font-medium text-slate-900">{{ $formatMoney($addonsExtra) }}</dd>
                                </div>
                            @endif
                            <div class="flex items-center justify-between">
                                <dt class="text-slate-600">Shipping</dt>
                                <dd class="font-medium text-slate-900">{{ $formatMoney($shipping) }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-slate-600">Tax</dt>
                                <dd class="font-medium text-slate-900">{{ $formatMoney($tax) }}</dd>
                            </div>
                            <div class="mt-4 flex items-center justify-between text-base font-semibold">
                                <dt class="text-slate-900">Total due</dt>
                                <dd class="text-slate-900" id="summary-grand-total">{{ $formatMoney($grandTotal) }}</dd>
                            </div>
                        </dl>
                                <a id="checkout-summary"
                                    href="{{ $checkoutPaymentUrl }}"
                                    class="mt-6 inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-900/10 hover:bg-slate-800">Checkout now</a>
                                <p id="qty-warning" class="mt-3 text-sm text-rose-600 hidden">Minimum quantity per item is 10.</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-600">
                        <p class="font-semibold text-slate-900">Need to start over?</p>
                        <p class="mt-1">Clearing your order removes every selection from this summary.</p>
                        <form method="POST" action="{{ $summaryClearUrl }}" class="mt-3">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-rose-600 hover:text-rose-700 font-medium">Clear entire order</button>
                        </form>
                    </div>
                </aside>
            </div>
        @endif
    </main>

    @if(!empty($orderSummary))
        <script>
            // Keep the JSON endpoint usable for other pages; store summary snapshot for reuse.
            try {
                window.sessionStorage.setItem('inkwise-finalstep', JSON.stringify(@json($orderSummary)));
            } catch (e) {
                console.warn('Unable to cache order summary', e);
            }

            // Lightweight preview modal for invitation fronts/backs
            (() => {
                const triggers = document.querySelectorAll('.js-preview-trigger');
                if (!triggers.length) return;

                const overlay = document.createElement('div');
                overlay.className = 'fixed inset-0 z-[90] hidden flex items-center justify-center bg-black/70 px-4';

                const frame = document.createElement('div');
                frame.className = 'relative max-w-3xl w-full max-h-[90vh] bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col';

                const img = document.createElement('img');
                img.className = 'w-full h-full object-contain bg-slate-50 flex-1';

                const closeBtn = document.createElement('button');
                closeBtn.type = 'button';
                closeBtn.textContent = '×';
                closeBtn.className = 'absolute top-3 right-3 h-10 w-10 rounded-full bg-white/90 text-slate-800 text-2xl leading-none shadow-md border border-slate-200';

                const pager = document.createElement('div');
                pager.className = 'absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-2';

                const prevBtn = document.createElement('button');
                prevBtn.type = 'button';
                prevBtn.textContent = '‹';
                prevBtn.className = 'h-9 w-9 rounded-full bg-white/90 text-slate-800 shadow-md border border-slate-200';

                const nextBtn = document.createElement('button');
                nextBtn.type = 'button';
                nextBtn.textContent = '›';
                nextBtn.className = 'h-9 w-9 rounded-full bg-white/90 text-slate-800 shadow-md border border-slate-200';

                pager.append(prevBtn, nextBtn);
                frame.append(closeBtn, img, pager);
                overlay.append(frame);
                document.body.appendChild(overlay);

                let gallery = [];
                let index = 0;

                const render = () => {
                    if (!gallery.length) return;
                    img.src = gallery[index];
                };

                const open = (images) => {
                    gallery = images.filter(Boolean);
                    index = 0;
                    if (!gallery.length) return;
                    render();
                    overlay.classList.remove('hidden');
                    overlay.classList.add('flex');
                };

                const close = () => {
                    overlay.classList.add('hidden');
                    gallery = [];
                };

                prevBtn.addEventListener('click', () => {
                    if (!gallery.length) return;
                    index = (index - 1 + gallery.length) % gallery.length;
                    render();
                });

                nextBtn.addEventListener('click', () => {
                    if (!gallery.length) return;
                    index = (index + 1) % gallery.length;
                    render();
                });

                closeBtn.addEventListener('click', close);
                overlay.addEventListener('click', (e) => {
                    if (e.target === overlay) close();
                });

                document.addEventListener('keydown', (e) => {
                    if (overlay.classList.contains('hidden')) return;
                    if (e.key === 'Escape') close();
                    if (e.key === 'ArrowRight') nextBtn.click();
                    if (e.key === 'ArrowLeft') prevBtn.click();
                });

                triggers.forEach((trigger) => {
                    trigger.addEventListener('click', () => {
                        try {
                            const images = JSON.parse(trigger.dataset.previewImages || '[]');
                            if (Array.isArray(images) && images.length) {
                                open(images);
                            }
                        } catch (err) {
                            console.warn('Unable to open preview modal', err);
                        }
                    });
                });
            })();

            // Recalculate invitation totals when quantities change (client-side convenience)
            (() => {
                const qtyInputs = document.querySelectorAll('.js-inv-qty');
                if (!qtyInputs.length) return;

                const formatMoney = (value) => new Intl.NumberFormat('en-PH', {
                    style: 'currency',
                    currency: 'PHP',
                }).format(Number(value) || 0);

                const envInputs = document.querySelectorAll('.js-env-qty');
                const giveInputs = document.querySelectorAll('.js-give-qty');
                const shipping = Number(document.body.dataset.shipping || 0);
                const tax = Number(document.body.dataset.tax || 0);

                const checkoutTop = document.getElementById('checkout-top');
                const checkoutSummary = document.getElementById('checkout-summary');
                const qtyWarning = document.getElementById('qty-warning');

                const summaryInv = document.getElementById('summary-inv-total');
                const summaryEnv = document.getElementById('summary-env-total');
                const summaryGive = document.getElementById('summary-give-total');
                const summaryGrand = document.getElementById('summary-grand-total');

                let checkoutBlocked = false;

                const clampQty = (input, enforceMin = true) => {
                    const rawNumber = Number(input.value);
                    let qty = Number.isFinite(rawNumber) ? Math.floor(rawNumber) : 0;
                    if (qty < 0) qty = 0;
                    if (enforceMin && qty < 10) {
                        qty = 10;
                    }
                    input.value = enforceMin ? qty : (qty > 0 ? qty : '');
                    return qty;
                };

                const setCheckoutEnabled = (enabled) => {
                    checkoutBlocked = !enabled;

                    const targets = [checkoutTop, checkoutSummary];
                    targets.forEach((btn) => {
                        if (!btn) return;
                        if (enabled) {
                            btn.classList.remove('pointer-events-none', 'opacity-60', 'cursor-not-allowed');
                            btn.removeAttribute('aria-disabled');
                        } else {
                            btn.classList.add('pointer-events-none', 'opacity-60', 'cursor-not-allowed');
                            btn.setAttribute('aria-disabled', 'true');
                        }
                    });

                    if (qtyWarning) {
                        qtyWarning.classList.toggle('hidden', enabled);
                    }
                };

                const recalc = (enforceMin = true) => {
                    let invitationTotal = 0;
                    let envelopeTotal = 0;
                    let giveawayTotal = 0;
                    let hasInvalidQty = false;

                    qtyInputs.forEach((input) => {
                        const unit = Number(input.dataset.unit || 0);
                        const raw = Number(input.value);
                        if (!Number.isFinite(raw) || raw < 10) {
                            hasInvalidQty = true;
                        }
                        const qty = clampQty(input, enforceMin);
                        const total = unit * qty;
                        invitationTotal += total;

                        const idx = input.dataset.index;
                        const itemTotalEl = document.querySelector(`.js-inv-item-total[data-index="${idx}"]`);
                        if (itemTotalEl) {
                            itemTotalEl.textContent = formatMoney(total);
                        }
                    });

                    envInputs.forEach((input) => {
                        const unit = Number(input.dataset.unit || 0);
                        const raw = Number(input.value);
                        if (!Number.isFinite(raw) || raw < 10) {
                            hasInvalidQty = true;
                        }
                        const qty = clampQty(input, enforceMin);
                        const total = unit * qty;
                        envelopeTotal += total;

                        const idx = input.dataset.index;
                        const itemTotalEl = document.querySelector(`.js-env-item-total[data-index="${idx}"]`);
                        if (itemTotalEl) {
                            itemTotalEl.textContent = formatMoney(total);
                        }
                    });

                    giveInputs.forEach((input) => {
                        const unit = Number(input.dataset.unit || 0);
                        const raw = Number(input.value);
                        if (!Number.isFinite(raw) || raw < 10) {
                            hasInvalidQty = true;
                        }
                        const qty = clampQty(input, enforceMin);
                        const total = unit * qty;
                        giveawayTotal += total;

                        const idx = input.dataset.index;
                        const itemTotalEl = document.querySelector(`.js-give-item-total[data-index="${idx}"]`);
                        if (itemTotalEl) {
                            itemTotalEl.textContent = formatMoney(total);
                        }
                    });

                    if (summaryInv) summaryInv.textContent = formatMoney(invitationTotal);
                    if (summaryEnv) summaryEnv.textContent = formatMoney(envelopeTotal);
                    if (summaryGive) summaryGive.textContent = formatMoney(giveawayTotal);

                    const grand = invitationTotal + envelopeTotal + giveawayTotal + shipping + tax;
                    if (summaryGrand) {
                        summaryGrand.textContent = formatMoney(grand);
                    }

                    setCheckoutEnabled(!hasInvalidQty);
                };

                qtyInputs.forEach((input) => {
                    input.addEventListener('input', () => recalc(false));
                    input.addEventListener('change', () => recalc(true));
                    input.addEventListener('blur', () => recalc(true));
                });

                envInputs.forEach((input) => {
                    input.addEventListener('input', () => recalc(false));
                    input.addEventListener('change', () => recalc(true));
                    input.addEventListener('blur', () => recalc(true));
                });

                giveInputs.forEach((input) => {
                    input.addEventListener('input', () => recalc(false));
                    input.addEventListener('change', () => recalc(true));
                    input.addEventListener('blur', () => recalc(true));
                });

                recalc(true);

                const guardCheckout = (event) => {
                    if (!checkoutBlocked) return;
                    event.preventDefault();
                    setCheckoutEnabled(false);
                    const warningTarget = qtyWarning || summaryInv;
                    if (warningTarget) {
                        warningTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                };

                if (checkoutTop) checkoutTop.addEventListener('click', guardCheckout);
                if (checkoutSummary) checkoutSummary.addEventListener('click', guardCheckout);
            })();
        </script>
    @endif
</body>
</html>
