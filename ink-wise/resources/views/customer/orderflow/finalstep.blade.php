<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Finalize Order — InkWise</title>
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
	<style>
		@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Seasons&display=swap');
		@import url('https://fonts.cdnfonts.com/css/edwardian-script-itc');
	</style>
	<script src="https://cdn.tailwindcss.com"></script>
	<link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-bold-rounded/css/uicons-bold-rounded.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
	<link rel="stylesheet" href="{{ asset('css/customer/orderflow-finalstep.css') }}">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
	<style>
		.flatpickr-calendar {
			background: white;
			box-shadow: 0 10px 25px rgba(0,0,0,0.1);
		}
		.flatpickr-day {
			color: black !important;
		}
		.flatpickr-day:hover {
			background: #e0f2fe;
		}
		.flatpickr-day.selected {
			background: #4f46e5;
			color: white !important;
		}
		.flatpickr-months .flatpickr-month {
			color: black;
		}
		.flatpickr-weekday {
			color: #6b7280;
		}

		.nav-icon-button {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 2.75rem;
			height: 2.75rem;
			border-radius: 9999px;
			border: 1px solid #dbeafe;
			background-color: rgba(255, 255, 255, 0.92);
			color: #f472b6;
			transition: transform 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
			box-shadow: 0 12px 24px rgba(166, 183, 255, 0.25);
		}

		.nav-icon-button:hover {
			transform: translateY(-1px);
			background-color: #fdf2ff;
		}

		.nav-icon-button:focus-visible {
			outline: 2px solid #a6b7ff;
			outline-offset: 2px;
		}

		.nav-icon-button[aria-disabled="true"] {
			opacity: 0.6;
			pointer-events: none;
		}

		.nav-icon-button i {
			font-size: 1.15rem;
		}

		@media (max-width: 1024px) {
			.nav-icon-button {
				box-shadow: none;
			}
		}
	</style>
	<script src="{{ asset('js/customer/orderflow-finalstep.js') }}" defer></script>
	<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body class="finalstep-body bg-white" data-product-id="{{ $product->id ?? '' }}">
@php
	$resolvedInvitationTypeNavbar = 'Wedding';
	$eventRoutesNavbar = [
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

	$currentEventKeyNavbar = strtolower($resolvedInvitationTypeNavbar);
	if (! array_key_exists($currentEventKeyNavbar, $eventRoutesNavbar)) {
		$currentEventKeyNavbar = 'wedding';
	}
	$currentEventRoutesNavbar = $eventRoutesNavbar[$currentEventKeyNavbar];

	$navLinksNavbar = [
		[
			'label' => 'Home',
			'route' => route('dashboard'),
			'isActive' => request()->routeIs('dashboard'),
		],
		[
			'label' => 'Invitations',
			'route' => $currentEventRoutesNavbar['invitations'],
			'isActive' => request()->routeIs('templates.' . $currentEventKeyNavbar . '.invitations'),
		],
		[
			'label' => 'Giveaways',
			'route' => $currentEventRoutesNavbar['giveaways'],
			'isActive' => request()->routeIs('templates.' . $currentEventKeyNavbar . '.giveaways'),
		],
	];

	$categoryLinksNavbar = [];
	foreach ($eventRoutesNavbar as $key => $config) {
		$categoryLinksNavbar[] = [
			'key' => $key,
			'label' => $config['label'],
			'route' => $config['invitations'],
			'isActive' => $key === $currentEventKeyNavbar,
		];
	}

	$favoritesEnabledNavbar = \Illuminate\Support\Facades\Route::has('customer.favorites');
	$cartRouteNavbar = \Illuminate\Support\Facades\Route::has('customer.cart')
		? route('customer.cart')
		: '/order/addtocart';
	$searchValueNavbar = request('query', '');
@endphp

<header class="fixed top-0 z-40 w-full border-b border-[#c7d2fe] bg-white/95 backdrop-blur shadow-sm">
	<div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
		<a href="{{ route('dashboard') }}" class="group flex items-center gap-2" aria-label="Inkwise home">
			<span class="text-4xl font-bold text-[#a6b7ff] transition-transform duration-200 group-hover:-translate-y-0.5" style="font-family: Edwardian Script ITC;">I</span>
			<span class="text-2xl font-bold text-gray-900" style="font-family: 'Playfair Display', serif;">nkwise</span>
		</a>

		<div class="flex items-center gap-3 lg:gap-6">
			<button id="navToggle" aria-controls="mobileNavPanel" aria-expanded="false" type="button"
			        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#c7d2fe] text-[#4f46e5] transition hover:bg-[#eef2ff] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#a6b7ff] lg:hidden">
				<span class="sr-only">Toggle navigation menu</span>
				<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
					<path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round" stroke-linejoin="round" />
				</svg>
			</button>

			<nav id="primaryNav" class="hidden lg:flex">
				<ul class="flex items-center gap-4 text-sm font-semibold text-gray-700">
					@foreach ($navLinksNavbar as $link)
						<li>
							<a href="{{ $link['route'] }}"
							   class="inline-flex items-center gap-2 rounded-full px-4 py-2 transition {{ $link['isActive'] ? 'bg-[#eef2ff] text-[#4338ca] shadow-sm' : 'hover:text-[#4338ca]' }}"
							   aria-current="{{ $link['isActive'] ? 'page' : 'false' }}">
								{{ $link['label'] }}
							</a>
						</li>
					@endforeach
					<li class="relative">
						<button id="categoryToggle" type="button" aria-haspopup="true" aria-expanded="false"
						        class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-gray-700 transition hover:border-[#c7d2fe] hover:text-[#4338ca]">
							{{ $eventRoutesNavbar[$currentEventKeyNavbar]['label'] ?? 'Wedding' }}
							<svg class="h-4 w-4 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
								<path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
						</button>
						<div id="categoryMenu" class="absolute left-0 right-auto mt-2 hidden w-56 rounded-2xl border border-[#dbeafe] bg-white/95 backdrop-blur shadow-2xl lg:right-0">
							<ul>
								@foreach ($categoryLinksNavbar as $category)
									<li>
										<a href="{{ $category['route'] }}"
										   class="flex items-center justify-between px-5 py-2.5 text-sm text-gray-700 transition hover:bg-[#eef2ff] {{ $category['isActive'] ? 'font-semibold text-[#4338ca]' : '' }}">
											<span>{{ $category['label'] }}</span>
											@if($category['isActive'])
												<span class="text-xs text-[#4338ca]">Current</span>
											@endif
										</a>
									</li>
								@endforeach
							</ul>
						</div>
					</li>
				</ul>
			</nav>

			<div class="flex items-center gap-3">
				<form action="{{ url('/search') }}" method="GET" class="hidden lg:flex">
					<label for="desktop-invitation-search" class="sr-only">Search templates</label>
					<input id="desktop-invitation-search" type="text" name="query" value="{{ $searchValueNavbar }}" placeholder="Search templates..."
					       class="w-52 rounded-full border border-[#dbeafe] px-4 py-2 text-sm placeholder:text-gray-400 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-[#a6b7ff]">
				</form>

				<div class="hidden items-center gap-2 lg:flex">
					<a href="{{ $favoritesEnabledNavbar ? route('customer.favorites') : '#' }}"
					   class="nav-icon-button"
					   aria-label="My favorites"
					   title="My favorites"
					   @unless($favoritesEnabledNavbar) aria-disabled="true" @endunless>
						<i class="fi fi-br-comment-heart" aria-hidden="true"></i>
					</a>
					<a href="{{ $cartRouteNavbar }}"
					   class="nav-icon-button"
					   aria-label="My cart"
					   title="My cart">
						<i class="bi bi-bag-heart-fill" aria-hidden="true"></i>
					</a>
				</div>

				@guest
					<a href="{{ route('customer.login') }}"
					   class="hidden items-center rounded-full bg-gradient-to-r from-[#6366f1] via-[#7c83ff] to-[#a6b7ff] px-5 py-2 font-semibold text-white shadow focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#a6b7ff] sm:inline-flex"
					   style="font-family: 'Seasons', serif;">
						Sign in
					</a>
				@endguest

				@auth
					<div class="relative">
						<button id="userDropdownBtn" type="button" aria-expanded="false"
						        class="inline-flex items-center gap-2 rounded-full bg-[#4f46e5] px-3 py-2 text-sm text-white transition hover:bg-[#4338ca] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#a6b7ff]">
							<span>{{ Auth::user()->customer?->first_name ?? Auth::user()->email }}</span>
							<svg class="h-3.5 w-3.5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
								<path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
						</button>
						<div id="userDropdownMenu" class="absolute right-0 mt-3 hidden w-48 rounded-2xl border border-[#dbeafe] bg-white shadow-xl">
							<a href="{{ route('customer.dashboard') }}" class="block px-4 py-2.5 text-sm text-gray-700 transition hover:bg-[#eef2ff]">Profile</a>
							<form action="{{ route('customer.logout') }}" method="POST">
								@csrf
								<button type="submit" class="block w-full px-4 py-2.5 text-left text-sm text-gray-700 transition hover:bg-[#eef2ff]">Logout</button>
							</form>
						</div>
					</div>
				@endauth
			</div>
		</div>
	</div>
	<div id="mobileNavPanel" class="mx-auto hidden w-full max-w-7xl border-t border-[#e0e7ff] bg-white px-4 pb-6 pt-4 shadow-inner lg:hidden">
		<div class="space-y-5">
			<form action="{{ url('/search') }}" method="GET">
				<label for="mobile-invitation-search" class="sr-only">Search templates</label>
				<input id="mobile-invitation-search" type="text" name="query" value="{{ $searchValueNavbar }}" placeholder="Search templates..."
				       class="w-full rounded-full border border-[#dbeafe] px-4 py-2 text-sm placeholder:text-gray-400 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-[#a6b7ff]">
			</form>

			<div class="flex items-center gap-3">
				<a href="{{ $favoritesEnabledNavbar ? route('customer.favorites') : '#' }}"
				   class="nav-icon-button"
				   aria-label="My favorites"
				   title="My favorites"
				   @unless($favoritesEnabledNavbar) aria-disabled="true" @endunless>
					<i class="fi fi-br-comment-heart" aria-hidden="true"></i>
				</a>
				<a href="{{ $cartRouteNavbar }}" class="nav-icon-button" aria-label="My cart" title="My cart">
					<i class="bi bi-bag-heart-fill" aria-hidden="true"></i>
				</a>
			</div>

			<ul class="space-y-2 text-sm font-semibold text-gray-700">
				@foreach ($navLinksNavbar as $link)
					<li>
						<a href="{{ $link['route'] }}"
						   class="flex items-center justify-between rounded-lg px-4 py-2 {{ $link['isActive'] ? 'bg-[#eef2ff] text-[#4338ca]' : 'hover:bg-[#eef2ff] hover:text-[#4338ca]' }}"
						   aria-current="{{ $link['isActive'] ? 'page' : 'false' }}">
							<span>{{ $link['label'] }}</span>
							@if($link['isActive'])
								<i class="bi bi-dot text-2xl"></i>
							@endif
						</a>
					</li>
				@endforeach
				<li>
					<button id="mobileCategoryToggle" type="button" aria-expanded="false"
					        class="flex w-full items-center justify-between rounded-lg border border-[#dbeafe] px-4 py-2 text-gray-700">
						{{ $eventRoutesNavbar[$currentEventKeyNavbar]['label'] ?? 'Wedding' }}
						<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
							<path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
					</button>
					<div id="mobileCategoryMenu" class="mt-2 hidden rounded-xl border border-[#dbeafe] bg-white shadow-lg">
						@foreach ($categoryLinksNavbar as $category)
							<a href="{{ $category['route'] }}" class="flex items-center justify-between px-4 py-2 text-sm text-gray-700 transition hover:bg-[#eef2ff] {{ $category['isActive'] ? 'font-semibold text-[#4338ca]' : '' }}">
								<span>{{ $category['label'] }}</span>
								@if($category['isActive'])
									<i class="bi bi-check-lg"></i>
								@endif
							</a>
						@endforeach
					</div>
				</li>
			</ul>

			@guest
				<a href="{{ route('customer.login') }}" class="inline-flex w-full items-center justify-center rounded-full bg-gradient-to-r from-[#6366f1] via-[#7c83ff] to-[#a6b7ff] px-5 py-2 font-semibold text-white">
					Sign in
				</a>
			@endguest

			@auth
				<div class="rounded-2xl border border-[#dbeafe] px-4 py-3 text-sm text-gray-700">
					<p class="font-semibold text-gray-900">{{ Auth::user()->customer?->first_name ?? Auth::user()->email }}</p>
					<div class="mt-3 flex flex-col gap-2">
						<a href="{{ route('customer.dashboard') }}" class="rounded-lg border border-transparent px-3 py-2 text-left transition hover:border-[#dbeafe]">Profile</a>
						<form action="{{ route('customer.logout') }}" method="POST">
							@csrf
							<button type="submit" class="w-full rounded-lg px-3 py-2 text-left transition hover:bg-[#eef2ff]">Logout</button>
						</form>
					</div>
				</div>
			@endauth
		</div>
	</div>
</header>
@php
	// Minimal, robust view setup for finalstep — resolve product/template if provided
	$product = $product ?? null;
	$proof = $proof ?? null;

	try {
		$req = request();
		if (!$product) {
			if ($req->has('product_id')) {
				try {
					$product = \App\Models\Product::with(['uploads', 'product_images', 'template', 'paperStocks', 'addons'])->find($req->get('product_id'));
				} catch (\Throwable $_e) {
					try {
						$row = \Illuminate\Support\Facades\DB::table('products')->where('id', $req->get('product_id'))->first();
						if ($row) $product = $row;
					} catch (\Throwable $_e) {
						// ignore
					}
				}
			}

			if (!$product && $req->has('template_id')) {
				try {
					$templateRef = \App\Models\Template::with(['preview_front', 'preview_back'])->find($req->get('template_id')) ?? ($templateRef ?? null);
				} catch (\Throwable $_e) {
					try {
						$tpl = \Illuminate\Support\Facades\DB::table('templates')->where('id', $req->get('template_id'))->first();
						if ($tpl) $templateRef = $tpl;
					} catch (\Throwable $_e) {
						// ignore
					}
				}
			}
		}
	} catch (\Throwable $e) {
		// swallow errors in view, proceed with fallbacks
	}

	$uploads = $product->uploads ?? collect();
	$images = $product->product_images ?? $product->images ?? optional($proof)->images ?? null;
	$templateRef = $product->template ?? ($templateRef ?? optional($proof)->template ?? null);

	$frontImage = $finalArtwork['front'] ?? ($finalArtworkFront ?? null);
	$backImage = $finalArtwork['back'] ?? ($finalArtworkBack ?? null);

	$resolveImage = function ($candidate) {
		if (!$candidate) return null;
		if (preg_match('/^(https?:)?\/\//i', $candidate) || str_starts_with($candidate, '/')) return $candidate;
		try { return \Illuminate\Support\Facades\Storage::url($candidate); } catch (\Throwable $e) { return null; }
	};

	if (!$frontImage && $images) {
		$frontImage = $resolveImage($images->final_front ?? $images->front ?? $images->preview ?? null);
	}
	if (!$backImage && $images) {
		$backImage = $resolveImage($images->final_back ?? $images->back ?? null);
	}
	if (!$frontImage && isset($templateRef)) {
		$frontImage = $resolveImage($templateRef->preview_front ?? $templateRef->front_image ?? $templateRef->preview ?? $templateRef->image ?? null);
	}
	if (!$backImage && isset($templateRef)) {
		$backImage = $resolveImage($templateRef->preview_back ?? $templateRef->back_image ?? null);
	}

	if (!$frontImage && $uploads->isNotEmpty()) {
		$first = $uploads->firstWhere(fn($upload) => str_starts_with($upload->mime_type ?? '', 'image/'));
		if ($first) {
			$frontImage = asset('storage/uploads/products/' . ($product->id ?? 'generic') . '/' . ($first->filename ?? ''));
		}
	}
	if (!$backImage && $uploads->count() > 1) {
		$second = $uploads->skip(1)->firstWhere(fn($upload) => str_starts_with($upload->mime_type ?? '', 'image/'));
		if ($second) {
			$backImage = asset('storage/uploads/products/' . ($product->id ?? 'generic') . '/' . ($second->filename ?? ''));
		}
	}

	$fallbackImage = asset('images/placeholder.png');
	$frontImage = $frontImage ?? $fallbackImage;
	$backImage = $backImage ?? $frontImage;

	$selectedQuantity = optional(optional($order)->items->first())->quantity ?? null;

	$quantityOptions = collect($quantityOptions ?? [])->map(function ($option) {
		return (object) $option;
	});

	if ($quantityOptions->isEmpty()) {
		$quantityOptions = collect(range(1, 20))->map(function ($i) {
			$value = $i * 10;
			return (object) [
				'label' => number_format($value),
				'value' => $value,
				'price' => round($value * max(6 - floor($i / 4), 3), 2),
			];
		});
	}

	$quantityValues = $quantityOptions->pluck('value')->filter(fn($value) => $value !== null);
	if ($quantityValues->isNotEmpty()) {
		$minQuantity = (int) $quantityValues->min();
		$maxQuantity = (int) $quantityValues->max();
	} else {
		$minQuantity = null;
		$maxQuantity = null;
	}

	// Show a simple minimum-order note instead of the previous bulk-range wording
	$effectiveMin = $minQuantity ?? 20;
	$quantityNote = 'Select a quantities. Minimum order is ' . number_format($effectiveMin);

	$paperStocks = collect($paperStocks ?? [])->map(function ($stock, $i) use ($fallbackImage) {
		$stock = (object) $stock;
		$stock->id = $stock->id ?? ('paper_' . $i);
		$stock->name = $stock->name ?? ($stock->label ?? 'Paper Stock');
		$stock->price = isset($stock->price) ? $stock->price : null;
		$stock->image = $stock->image ?? $fallbackImage;
		return $stock;
	})->values();

	$initialPaperSelection = $paperStocks->first(fn ($stock) => $stock->selected ?? false);
	$initialPaperStockId = $initialPaperSelection->id ?? '';
	$initialPaperStockPrice = $initialPaperSelection->price ?? 0;

	$addonGroups = collect($addonGroups ?? [])->map(function ($group, $i) use ($fallbackImage) {
		$group = (object) $group;
		$group->type = $group->type ?? ('additional_' . $i);
		$group->label = $group->label ?? \Illuminate\Support\Str::headline($group->type);
		$group->items = collect($group->items ?? [])->map(function ($item, $j) use ($fallbackImage, $group) {
			$item = (object) $item;
			$item->id = $item->id ?? ($group->type . '_' . $j);
			$item->name = $item->name ?? 'Add-on';
			$item->price = isset($item->price) ? $item->price : null;
			$item->image = $item->image ?? $fallbackImage;
			$item->type = $item->type ?? $group->type;
			return $item;
		})->all();
		return $group;
	})->values();

	$estimatedDeliveryDate = isset($estimatedDeliveryDate)
		? $estimatedDeliveryDate
		: \Carbon\Carbon::now()->addWeekdays(5)->format('F j, Y');
	$resolvedProductName = $product->name ?? optional($templateRef)->name ?? 'Custom Invitation';
// Resolve envelope route safely - prioritize customer-facing route, then fallback to admin endpoints
	try {
		$envelopeUrl = route('order.envelope');
	} catch (\Throwable $_ePrimary) {
		try {
			$envelopeUrl = route('products.create.envelope');
		} catch (\Throwable $_eAdmin) {
			try {
				$envelopeUrl = route('create.envelope');
			} catch (\Throwable $_eAlt) {
				// final fallback to the customer envelope URL path
				$envelopeUrl = url('/order/envelope');
			}
		}
	}

	try {
		$giveawaysUrl = route('order.giveaways');
	} catch (\Throwable $_eGiveaways) {
		$giveawaysUrl = url('/order/giveaways');
	}

	try {
		$reviewUrl = route('order.review');
	} catch (\Throwable $_eReview) {
		$reviewUrl = url('/order/review');
	}

	try {
		$finalStepSaveUrl = route('order.finalstep.save');
	} catch (\Throwable $_eSave) {
		$finalStepSaveUrl = url('/order/finalstep/save');
	}

	// Define date variables for pickup date input
	$estimatedDeliveryMinDate = \Carbon\Carbon::now()->addDay()->format('Y-m-d');
	$estimatedDeliveryMaxDate = \Carbon\Carbon::now()->addMonths(3)->format('Y-m-d');
	$estimatedDeliveryDateFormatted = \Carbon\Carbon::now()->addWeekdays(5)->format('Y-m-d');

	// Define pricing variables
	$bulkOrders = collect([
		(object)['min_qty' => 1, 'max_qty' => 49, 'price_per_unit' => 6.00],
		(object)['min_qty' => 50, 'max_qty' => 99, 'price_per_unit' => 5.50],
		(object)['min_qty' => 100, 'max_qty' => 199, 'price_per_unit' => 5.00],
		(object)['min_qty' => 200, 'max_qty' => null, 'price_per_unit' => 4.50],
	]);
	$basePrice = 6.00;
	$minQty = 10;
@endphp
@php
	$processingDays = $estimatedDeliveryDays ?? ($processingDays ?? null) ?? 7;
@endphp
<main class="finalstep-shell" data-storage-key="inkwise-finalstep" data-envelope-url="{{ $envelopeUrl }}" data-cart-url="{{ route('order.addtocart') }}" data-save-url="{{ $finalStepSaveUrl }}" data-fallback-samples="false" data-product-id="{{ $product->id ?? '' }}" data-product-name="{{ $resolvedProductName }}" data-processing-days="{{ $processingDays }}">
	<header class="finalstep-header">
		<div class="finalstep-header__content">
			<a href="{{ $reviewUrl }}" class="finalstep-header__back" aria-label="Back to review">
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
				Back to review
			</a>
			<h1>Finalize your order</h1>
			<p>Review your artwork, choose finishing touches, and confirm the final details before checkout.</p>
		</div>
	</header>

	<div class="finalstep-layout">
		<section class="finalstep-preview" data-product-name="{{ $resolvedProductName }}">
			<div class="finalstep-card preview-card">
				<header class="preview-card__header">
					<span class="preview-card__badge">Artwork preview</span>
					<div class="preview-toggle" role="group" aria-label="Preview sides">
						<button type="button" class="active" data-face="front" aria-pressed="true">Front</button>
						<button type="button" data-face="back" aria-pressed="false">Back</button>
					</div>
				</header>
				<div class="card-flip">
					<div class="inner">
						<div class="card-face front">
							<img src="{{ $frontImage }}" alt="Front of your design">
						</div>
						<div class="card-face back">
							<img src="{{ $backImage }}" alt="Back of your design">
						</div>
					</div>
				</div>
				<ul class="preview-meta">
					<li><span class="meta-label">Product</span><span class="meta-value">{{ $resolvedProductName }}</span></li>
					@if(isset($product->size))
						<li><span class="meta-label">Size</span><span class="meta-value">{{ $product->size }}</span></li>
					@endif
					@if(isset($product->paper_stock))
						<li><span class="meta-label">Paper stock</span><span class="meta-value">{{ $product->paper_stock }}</span></li>
					@endif
				</ul>
			</div>
		</section>

		<section class="finalstep-panel">
			<div class="finalstep-card form-card">
				<header class="form-card__header">
					<h2>Order details</h2>
					<p>Pick quantities, paper stocks, and optional finishes. We’ll calculate totals automatically.</p>
				</header>
				<form id="finalOrderForm" class="finalstep-form" novalidate>
					<div class="form-fixed-section">
						<div class="form-group">
							<label for="quantityInput">Quantity</label>
							<div class="quantity-price-row">
								<input type="number" id="quantityInput" name="quantity" value="{{ $selectedQuantity ?? $minQty }}" min="{{ $minQty }}" {{ $maxQty ? 'max="' . $maxQty . '"' : '' }} required>
								<div class="price-display">
									<span class="meta-label">Total:</span>
									<span id="priceDisplay" class="meta-value">₱0.00</span>
								</div>
							</div>
							<div id="quantityError" class="error-message" style="display: none;">Quantity must be at least {{ $minQty }}</div>
							<p class="bulk-note">{{ $quantityNote }}</p>
						</div>
					</div>

					<div class="form-scroll-section">
						<div class="form-group paper-stocks-group">
							<label>Paper stock</label>
							<p class="selection-hint">Choose your preferred stock.</p>
							<p id="paperStockAvailable" class="stock-available" style="display:none;">Available: <span id="paperStockAvailableCount">0</span></p>
							<div id="stockError" class="error-message" style="display:none; margin-top:6px;"></div>
							<div class="feature-grid small">
								@forelse($paperStocks as $stock)
									<button
										type="button"
										class="feature-card selectable-card paper-stock-card"
										data-id="{{ $stock->id }}"
										data-price="{{ $stock->price ?? 0 }}"
										data-available="{{ $stock->available ?? $stock->quantity ?? $stock->stock ?? 0 }}"
										aria-pressed="{{ $stock->selected ? 'true' : 'false' }}"
									>
										<div class="feature-card-media">
											<img src="{{ $stock->image }}" alt="{{ $stock->name }}">
										</div>
										<div class="feature-card-info">
											<span class="feature-card-title">{{ $stock->name }}</span>
											<span class="feature-card-price">
												@if($stock->price !== null)
													₱{{ number_format($stock->price, 2) }}
												@else
													On request
												@endif
											</span>
										</div>
									</button>
								@empty
									<p class="muted">No paper stocks available for this product.</p>
								@endforelse
							</div>
							<input type="hidden" name="paper_stock_id" id="paperStockId" value="{{ $initialPaperStockId }}">
							<input type="hidden" name="paper_stock_price" id="paperStockPrice" value="{{ $initialPaperStockPrice ?? 0 }}">
						</div>

						<div class="form-group addons-group">
							<label>Size</label>
							@forelse($addonGroups as $group)
								<div class="addon-section" data-addon-type="{{ $group->type }}">
									<h4 class="addon-title">{{ trim(str_ireplace('Additional', '', $group->label)) }}</h4>
									<div class="feature-grid small addon-grid" data-addon-type="{{ $group->type }}">
										@forelse($group->items as $addon)
											<button
												type="button"
												class="feature-card selectable-card addon-card"
												data-id="{{ $addon->id }}"
												data-price="{{ $addon->price ?? 0 }}"
												data-type="{{ $addon->type }}"
												aria-pressed="{{ $addon->selected ? 'true' : 'false' }}"
											>
												<div class="feature-card-media">
													<img src="{{ $addon->image }}" alt="{{ $addon->name }}">
												</div>
												<div class="feature-card-info">
													<span class="feature-card-title">{{ $addon->name }}</span>
													<span class="feature-card-price">
														@if($addon->price !== null)
															₱{{ number_format($addon->price, 2) }}
														@else
															On request
														@endif
													</span>
												</div>
											</button>
										@empty
											<p class="muted">No add-ons available for this category.</p>
										@endforelse
									</div>
								</div>
							@empty
								<p class="muted">No add-ons available for this product.</p>
							@endforelse
						</div>
					</div>

					<div class="form-fixed-section form-fixed-section--bottom">
						<div class="order-meta">
							<div class="order-total">
								<span class="meta-label">Total</span>
								<span class="meta-value" data-order-total>₱0.00</span>
							</div>
						</div>

						<div class="delivery-info">
							<div class="delivery-group">
								<label for="estimatedDate" class="meta-label">Estimated day</label>
								<div class="delivery-inputs">
									<div class="delivery-row">
										<input type="date" id="estimatedDate" name="estimated_date" value="{{ $estimatedDeliveryDateFormatted }}" required>
										<span id="estimatedDateFinalLabel" class="final-date-label" aria-hidden="true"></span>
									</div>
									<p id="estimatedDateHint" class="date-hint">Choose an estimated date at least 10 days from today, up to 1 month ahead. Past dates are not allowed.</p>
									<div id="estimatedDateError" class="date-error" style="display: none;" aria-live="polite"></div>
									<p id="estimatedArrival" class="arrival-label" style="display:none;" aria-live="polite"></p>
								</div>
							</div>
						</div>

						<div class="action-buttons">
							<button type="button" id="addToCartBtn" class="primary-action" data-envelope-url="{{ $envelopeUrl }}" data-cart-url="{{ route('order.addtocart') }}">Add to cart</button>
							<a id="continueToCheckout" href="{{ $envelopeUrl }}" class="btn btn-secondary">Continue to checkout</a>
							<div id="paperSelectError" class="error-message" style="display:none; margin-left:8px;">Please select a paper type to continue.</div>
						</div>
					</div>
				</form>
			</div>
		</section>
	</div>

	<div id="finalStepToast" class="finalstep-toast" role="status" aria-live="polite" hidden></div>
</main>
@if(!empty($orderSummary))
	<script>
		document.addEventListener('DOMContentLoaded', () => {
			const summaryData = {!! \Illuminate\Support\Js::from($orderSummary) !!};
			window.sessionStorage.setItem('inkwise-finalstep', JSON.stringify(summaryData));
		});
	</script>
@endif
<script>
	document.addEventListener('DOMContentLoaded', () => {
		const dropdownControllers = [];
		const navToggle = document.getElementById('navToggle');
		const mobilePanel = document.getElementById('mobileNavPanel');

		const registerDropdown = ({ triggerId, menuId }) => {
			const trigger = document.getElementById(triggerId);
			const menu = document.getElementById(menuId);
			if (!trigger || !menu) return null;

			const indicator = trigger.querySelector('svg');

			const open = () => {
				menu.classList.remove('hidden');
				trigger.setAttribute('aria-expanded', 'true');
				indicator?.classList.add('rotate-180');
			};

			const close = () => {
				menu.classList.add('hidden');
				trigger.setAttribute('aria-expanded', 'false');
				indicator?.classList.remove('rotate-180');
			};

			trigger.addEventListener('click', (event) => {
				event.stopPropagation();
				const willOpen = menu.classList.contains('hidden');
				closeAllDropdowns();
				if (willOpen) open();
			});

			return { trigger, menu, close };
		};

		const closeAllDropdowns = () => dropdownControllers.forEach((controller) => controller.close());

		[
			{ triggerId: 'categoryToggle', menuId: 'categoryMenu' },
			{ triggerId: 'mobileCategoryToggle', menuId: 'mobileCategoryMenu' },
			{ triggerId: 'userDropdownBtn', menuId: 'userDropdownMenu' },
		].forEach((config) => {
			const controller = registerDropdown(config);
			if (controller) dropdownControllers.push(controller);
		});

		document.addEventListener('click', (event) => {
			dropdownControllers.forEach((controller) => {
				if (controller.menu.classList.contains('hidden')) return;
				if (controller.menu.contains(event.target) || controller.trigger.contains(event.target)) return;
				controller.close();
			});
		});

		document.addEventListener('keydown', (event) => {
			if (event.key !== 'Escape') return;
			closeAllDropdowns();
			if (mobilePanel && !mobilePanel.classList.contains('hidden')) {
				mobilePanel.classList.add('hidden');
				navToggle?.setAttribute('aria-expanded', 'false');
			}
		});

		navToggle?.addEventListener('click', () => {
			const isOpen = navToggle.getAttribute('aria-expanded') === 'true';
			navToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
			mobilePanel?.classList.toggle('hidden', isOpen);
			if (!isOpen) closeAllDropdowns();
		});

		const storageKey = 'inkwise-finalstep';
		const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
		const navIcons = Array.from(document.querySelectorAll('.nav-icon-button'));

		const serverHasOrder = async () => {
			try {
				const response = await fetch('/order/summary.json', { method: 'GET', headers: { Accept: 'application/json' }, credentials: 'same-origin' });
				return response.ok;
			} catch (_error) {
				return false;
			}
		};

		const createOrderFromSummary = async (summary) => {
			if (!summary) return false;
			const productId = summary.productId ?? summary.product_id;
			if (!productId) return false;
			const quantity = Number(summary.quantity ?? 10);
			try {
				const response = await fetch('/order/cart/items', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						Accept: 'application/json',
						...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
					},
					credentials: 'same-origin',
					body: JSON.stringify({ product_id: Number(productId), quantity }),
				});
				return response.ok;
			} catch (_error) {
				return false;
			}
		};

		navIcons.forEach((icon) => {
			if (icon.getAttribute('aria-disabled') === 'true') {
				icon.setAttribute('data-was-aria-disabled', 'true');
				icon.removeAttribute('aria-disabled');
				icon.style.pointerEvents = 'auto';
				icon.setAttribute('tabindex', '0');
				icon.setAttribute('role', 'button');
				icon.addEventListener('keydown', (event) => {
					if (event.key === 'Enter' || event.key === ' ') {
						event.preventDefault();
						icon.click();
					}
				});
			}

			icon.addEventListener('click', async (event) => {
				event.preventDefault();
				try {
					if (await serverHasOrder()) {
						window.location.href = '/order/addtocart';
						return;
					}

					let summary = null;
					try {
						const raw = window.sessionStorage.getItem(storageKey);
						summary = raw ? JSON.parse(raw) : null;
					} catch (_error) {
						summary = null;
					}

					if (summary && (summary.productId || summary.product_id)) {
						const created = await createOrderFromSummary(summary);
						if (created) {
							window.location.href = '/order/addtocart';
							return;
						}
					}

					const href = icon.getAttribute('href');
					if (href && href !== '#') {
						window.location.href = href;
						return;
					}

					window.location.href = '/order/addtocart';
				} catch (_error) {
					window.location.href = '/order/addtocart';
				}
			});
		});
	});
</script>
<script>
	document.addEventListener('DOMContentLoaded', () => {
		const quantityInput = document.getElementById('quantityInput');
		const priceDisplay = document.getElementById('priceDisplay');
		const quantityError = document.getElementById('quantityError');
		const bulkOrders = {!! $bulkOrders->toJson() !!};
		const basePrice = {{ $basePrice }};
		const minQty = {{ $minQty }};

		function calculatePrice(quantity) {
			if (!quantity || quantity < minQty) return 0;

			let unitPrice = basePrice;
			for (const tier of bulkOrders) {
				const min = tier.min_qty || 0;
				const max = tier.max_qty || Infinity;
				if (quantity >= min && quantity <= max && tier.price_per_unit) {
					unitPrice = parseFloat(tier.price_per_unit);
					break;
				}
			}
			return Math.round(quantity * unitPrice * 100) / 100;
		}

		function updatePrice() {
			const quantity = parseInt(quantityInput.value) || 0;
			const price = calculatePrice(quantity);
			priceDisplay.textContent = '₱' + price.toFixed(2);
			if (quantity < minQty) {
				quantityError.style.display = 'block';
			} else {
				quantityError.style.display = 'none';
			}
		}

		quantityInput.addEventListener('input', updatePrice);
		// Initial update
		updatePrice();
	});
</script>

<!-- Pre-order Confirmation Modal -->
<div id="preOrderModal" class="modal" style="display: none;" role="dialog" aria-labelledby="preOrderTitle" aria-describedby="preOrderMessage" aria-hidden="true">
	<div class="modal-backdrop" tabindex="-1"></div>
	<div class="modal-content">
		<h2 id="preOrderTitle" class="modal-title">Pre-order Confirmation</h2>
		<p id="preOrderMessage" class="modal-message">Pre-order: 15 days estimated delivery. Proceed with the order?</p>
		<div class="modal-actions">
			<button id="preOrderConfirm" class="primary-action" type="button">Confirm</button>
			<button id="preOrderCancel" class="secondary-action" type="button">Cancel</button>
		</div>
	</div>
</div>

</body>
</html>
