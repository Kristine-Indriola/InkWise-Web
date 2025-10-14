<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Checkout - Inkwise</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/customer/customer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customer/customertemplates.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customer/template.css') }}">
    <script src="{{ asset('js/customer/customer.js') }}" defer></script>
    <script src="{{ asset('js/customer/customertemplate.js') }}" defer></script>
    <script src="{{ asset('js/customer/template.js') }}" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.10.2/cdn.min.js" defer></script>
    <link rel="icon" type="image/png" href="{{ asset('adminimage/ink.png') }}">
    <style>
        :root {
            --page-gradient: radial-gradient(circle at top, #e1f5fe 0%, #f8fbff 45%, #ffffff 100%);
            --surface: #ffffff;
            --surface-muted: #f3f6fb;
            --surface-strong: #0f172a;
            --divider: rgba(15, 23, 42, 0.08);
            --shadow-lg: 0 22px 48px rgba(15, 23, 42, 0.10);
            --shadow-md: 0 18px 32px rgba(15, 23, 42, 0.08);
            --text-strong: #0f172a;
            --text-default: #1f2937;
            --text-muted: #6b7280;
            --text-soft: #94a3b8;
            --accent: #a6b7ff;
            --accent-dark: #8f9dff;
            --success: #10b981;
            --warning: #f97316;
            --danger: #dc2626;
            --radius-lg: 20px;
            --radius-md: 14px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            background: var(--page-gradient);
            color: var(--text-default);
            padding: clamp(24px, 4vw, 48px) 0;
        }

        a {
            color: var(--accent);
            text-decoration: none;
        }

        a:hover {
            color: var(--accent);
        }

        .page-wrapper {
            width: min(1120px, calc(100% - clamp(32px, 6vw, 80px)));
            margin: calc(clamp(24px, 4vw, 48px) + 72px) auto 0;
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) 320px;
            gap: clamp(18px, 2.6vw, 32px);
            align-items: flex-start;
        }

        .card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(15, 23, 42, 0.04);
            overflow: hidden;
            transform: translateZ(0);
        }

        /* Ensure the Shipping information card is tall enough on desktop to avoid layout collapse */
        .card.checkout-form#shipping {
            min-height: 520px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .checkout-form {
            display: flex;
            flex-direction: column;
            gap: clamp(18px, 2vw, 26px);
            padding: clamp(22px, 2.4vw, 30px);
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: clamp(20px, 2.4vw, 24px);
            font-weight: 600;
            color: var(--text-strong);
            margin: 0;
        }

        .section-title span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(166, 183, 255, 0.24);
            color: #5f6ad9;
            font-weight: 600;
        }

        .section-subtitle {
            margin: 6px 0 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        .fieldset {
            display: grid;
            gap: 16px;
        }

        .info-row {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        label {
            font-weight: 500;
            font-size: 14px;
            color: var(--text-strong);
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="number"],
        textarea,
        select {
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            padding: 12px 16px;
            font-family: inherit;
            transition: border 0.2s ease, box-shadow 0.2s ease;
            background: #ffffff;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: rgba(166, 183, 255, 0.6);
            outline: none;
            box-shadow: 0 0 0 3px rgba(166, 183, 255, 0.18);
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        .option-card {
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.22);
            background: rgba(248, 250, 255, 0.82);
            padding: 18px 20px;
            display: flex;
            gap: 16px;
            align-items: flex-start;
            cursor: pointer;
            transition: border 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }

        .option-card:hover {
            border-color: rgba(166, 183, 255, 0.6);
            box-shadow: 0 20px 36px rgba(166, 183, 255, 0.22);
            transform: translateY(-2px);
            background: linear-gradient(135deg, rgba(166, 183, 255, 0.20), rgba(166, 183, 255, 0.08));
        }

        .option-card.selected {
            border-color: rgba(166, 183, 255, 0.8);
            box-shadow: 0 24px 44px rgba(166, 183, 255, 0.26);
            background: linear-gradient(135deg, rgba(166, 183, 255, 0.24), rgba(166, 183, 255, 0.10));
        }

        .option-card input[type="radio"] {
            margin-top: 4px;
            width: 18px;
            height: 18px;
            accent-color: var(--accent);
        }

        .option-content {
            flex: 1;
        }

        .option-content h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .option-content p {
            margin: 6px 0 0;
            font-size: 13px;
            color: var(--text-muted);
        }

        .option-tag {
            display: inline-block;
            margin-top: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            background: rgba(166, 183, 255, 0.24);
            color: #5f6ad9;
        }

        .summary-card {
            padding: clamp(22px, 2.2vw, 28px);
            display: grid;
            gap: 18px;
            position: sticky;
            top: clamp(22px, 3vw, 36px);
        }

        .summary-header {
            margin: 0;
            font-size: clamp(20px, 2.4vw, 24px);
            font-weight: 600;
            color: var(--text-strong);
        }

        .summary-items {
            display: grid;
            gap: 16px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 14px;
        }

        .summary-item strong {
            font-size: 14px;
            color: var(--text-strong);
        }

        .summary-divider {
            border: none;
            border-top: 1px solid rgba(148, 163, 184, 0.2);
            margin: 0;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: clamp(18px, 2vw, 22px);
            font-weight: 600;
            color: var(--text-strong);
        }

        .summary-total span:last-child {
            color: var(--accent-dark);
        }

        .place-order {
            width: 100%;
            padding: 16px 18px;
            border-radius: 16px;
            border: none;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            letter-spacing: 0.35px;
            box-shadow: 0 26px 50px rgba(166, 183, 255, 0.30);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .place-order:hover {
            transform: translateY(-2px);
            box-shadow: 0 32px 58px rgba(166, 183, 255, 0.34);
        }

        .shipping-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            row-gap: 12px;
            margin-bottom: 12px;
            padding-bottom: 4px;
        }

        .shipping-header__copy {
            flex: 1 1 260px;
            min-width: 240px;
        }

        .shipping-footer {
            margin-top: auto;
            padding-top: 10px;
            border-top: 1px solid rgba(148, 163, 184, 0.18);
        }

        .shipping-footer__actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
            padding-top: 10px;
        }

        .shipping-action-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 500;
            border-radius: 10px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: #ffffff;
            color: #1f2937;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
            white-space: nowrap;
        }

        .shipping-action-button:hover {
            transform: translateY(-1px);
            border-color: rgba(15, 23, 42, 0.18);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
        }

        .shipping-action-button--primary {
            background: #eef2ff;
            border-color: rgba(79, 70, 229, 0.35);
            color: #4338ca;
        }

        .shipping-action-button--primary:hover {
            border-color: rgba(79, 70, 229, 0.55);
            box-shadow: 0 10px 22px rgba(79, 70, 229, 0.18);
        }

        .note {
            font-size: 12px;
            color: var(--text-soft);
            margin: 0;
            text-align: center;
        }

        .note a {
            color: var(--accent);
            transition: color 0.2s ease;
        }

        .note a:hover {
            color: #94a7ff;
            text-decoration: none;
        }

        .payment-alert {
            display: none;
            margin-top: 12px;
            border-radius: 14px;
            padding: 12px 16px;
            font-size: 13px;
            font-weight: 500;
        }

        .payment-alert.info {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .payment-alert.success {
            background: #ecfdf5;
            color: #047857;
        }

        .payment-alert.error {
            background: #fee2e2;
            color: #b91c1c;
        }

        .summary-payments {
            border-top: 1px dashed rgba(148, 163, 184, 0.25);
            padding-top: 14px;
            display: grid;
            gap: 10px;
        }

        .summary-payments h3 {
            margin: 0;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .summary-payments ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 8px;
        }

        .summary-payments li {
            display: flex;
            flex-direction: column;
            gap: 2px;
            font-size: 12px;
            color: var(--text-muted);
        }

        .summary-payments li .payment-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: var(--text-strong);
        }

        .summary-payments li .payment-meta {
            font-size: 11px;
            color: var(--text-soft);
        }

        @media (max-width: 1200px) {
            .page-wrapper {
                grid-template-columns: 1fr;
                width: calc(100% - clamp(24px, 6vw, 64px));
            }

            .summary-card {
                position: static;
                box-shadow: var(--shadow-md);
                max-width: 520px;
                margin-inline: auto;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: calc(20px + 64px) 0 40px;
            }

            .global-nav .max-w-7xl {
                min-height: 64px;
            }

            #mainNav {
                width: 100%;
                justify-content: center;
            }

            .page-wrapper {
                width: calc(100% - 22px);
                gap: 20px;
                margin: calc(clamp(24px, 7vw, 48px) + 64px) auto 0;
            }

            .card {
                border-radius: 20px;
            }

            .checkout-form,
            .summary-card {
                padding: 22px;
            }

            .shipping-footer__actions {
                width: 100%;
                justify-content: flex-start;
            }

            .place-order {
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
@php
    $order = $order ?? null;
    $customerOrder = $order?->customerOrder;
    $orderItem = $order?->items->first();
    $subtotal = $order?->subtotal_amount ?? 0;
    $taxAmount = $order?->tax_amount ?? 0;
    $shippingFee = $order?->shipping_fee ?? 0;
    $totalAmount = $order?->total_amount ?? 0;
    $paymentRecordsCollection = collect($paymentRecords ?? []);
    $calculatedPaid = $paidAmount ?? $paymentRecordsCollection
        ->filter(fn ($payment) => ($payment['status'] ?? null) === 'paid')
        ->sum(fn ($payment) => (float) ($payment['amount'] ?? 0));
    $paidAmountDisplay = round($calculatedPaid, 2);
    $depositSuggested = $depositAmount ?? ($totalAmount ? round($totalAmount / 2, 2) : 0);
    $balanceDueDisplay = $balanceDue ?? max($totalAmount - $paidAmountDisplay, 0);
    $currentUser = Auth::user();
    $authCustomer = $currentUser?->customer;
    $shippingName = $customerOrder->name ?? trim(($authCustomer?->first_name ?? '') . ' ' . ($authCustomer?->last_name ?? '')) ?: 'Sample Customer';
    $shippingEmail = $customerOrder->email ?? ($currentUser?->email ?? 'sample.customer@example.com');
    $shippingPhone = $customerOrder->phone ?? ($authCustomer?->contact_number ?? '09171234567');
    $shippingCompany = $customerOrder->company ?? '';
    $shippingAddress = $customerOrder->address ?? '';
    $shippingCity = $customerOrder->city ?? '';
    $shippingPostal = $customerOrder->postal_code ?? '';
    $taxRate = $subtotal > 0 ? round($taxAmount / $subtotal, 4) : 0.12;
    $expressShippingFee = $shippingFee > 0 ? $shippingFee + 180 : 180;
    $hasPendingPayment = ($paymongoMeta['status'] ?? null) === 'awaiting_next_action';
    $pendingPaymentUrl = $paymongoMeta['next_action_url'] ?? null;
    $paymentMode = $paymongoMeta['mode'] ?? 'half';
    $isFullyPaid = $balanceDueDisplay <= 0.01;
    $formatPaymentDate = static function ($date) {
        try {
            return $date ? \Illuminate\Support\Carbon::parse($date)->format('M j, Y g:i A') : null;
        } catch (\Throwable $e) {
            return $date;
        }
    };
    $taxAmount = $subtotal * $taxRate;
    $totalAmount = $subtotal + $shippingFee + $taxAmount;
    $checkoutQuantity = $orderItem?->quantity
        ?? ($orderSummary['quantity'] ?? null)
        ?? null;
@endphp

@if(session('status'))
    <div class="status-banner" style="margin:16px auto; max-width:960px; padding:12px 18px; border-radius:16px; background:#ecfdf5; color:#047857; font-weight:600; text-align:center;">
        {{ session('status') }}
    </div>
@endif
    <header class="global-nav shadow animate-fade-in-down bg-white w-full">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-wrap items-center justify-between h-16">
            <div class="flex items-center animate-bounce-slow flex-shrink-0">
                <span class="text-5xl font-bold logo-i" style="font-family: Edwardian Script ITC; color:#06b6d4;">I</span>
                <span class="text-2xl font-bold" style="font-family: 'Playfair Display', serif; color: #0891b2;">nkwise</span>
            </div>

            <nav id="mainNav" class="flex flex-wrap gap-4 md:gap-0 md:space-x-6" role="navigation">
                <a href="{{ route('customer.dashboard') }}" class="text-gray-700 hover:text-[#a6b7ff]">Home</a>
                <a href="#shipping" class="text-gray-700 hover:text-[#a6b7ff]">Shipping</a>
                <a href="#payment" class="text-gray-700 hover:text-[#a6b7ff]">Payment</a>
                <a href="#summary" class="text-gray-700 hover:text-[#a6b7ff]">Summary</a>
            </nav>

            <div class="flex items-center space-x-4 relative min-w-0">
                <form action="{{ url('/search') }}" method="GET" class="hidden md:flex">
                    <input type="text" name="query" placeholder="Search..." class="border rounded-lg px-3 py-1 text-sm focus:outline-none focus:ring focus:ring-[#a6b7ff]">
                </form>

                @guest
                    <a href="{{ route('customer.login') }}" id="openLogin" class="text-white px-5 py-2 font-semibold animate-ocean rounded-full" style="font-family: 'Seasons', serif;">
                        Sign in
                    </a>
                @endguest

                @auth
                    <div class="relative min-w-0 group">
                        <button id="userDropdownBtn" class="flex items-center space-x-2 text-gray-700 hover:text-gray-900">
                            <span>{{ Auth::user()->customer?->first_name ?? Auth::user()->email ?? 'Customer' }}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>

                        <div id="userDropdownMenu" class="absolute right-0 mt-2 w-48 bg-white rounded shadow-lg opacity-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto transition-opacity duration-200 z-50 hidden group-hover:block">
                            <a href="{{ route('customer.profile.index') }}" class="block px-4 py-2 text-gray-700 hover:bg-[#e7ecff] transition-colors">My Account</a>
                            <div class="block px-4 py-2 text-gray-700 hover:bg-[#e7ecff] cursor-pointer transition-colors">My Purchase</div>
                            <div class="block px-4 py-2 text-gray-700 hover:bg-[#e7ecff] cursor-pointer transition-colors">My Favorites</div>
                            <form method="POST" action="{{ route('customer.logout') }}">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-[#e7ecff] transition-colors">Logout</button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </header>

    <div class="page-wrapper">
        <section class="card checkout-form" id="shipping">
            <header class="shipping-header">
                <div class="shipping-header__copy">
                    <h1 class="section-title"><span>1</span>Shipping information</h1>
                    <p class="section-subtitle">Confirm your delivery address and preferred shipping option.</p>
                </div>
            </header>

            {{-- If user has saved addresses, auto-fill shipping inputs from the first one but keep fields editable --}}
            @php
                $savedAddresses = collect();
                try {
                    if (auth()->check()) {
                        $savedAddresses = \App\Models\Address::where('user_id', auth()->id())->get();
                        if ($savedAddresses->isNotEmpty()) {
                            $firstAddr = $savedAddresses->first();
                            // Only override if blank
                            $shippingName = $firstAddr->full_name ?: ($shippingName ?? '');
                            $shippingPhone = $firstAddr->phone ?: ($shippingPhone ?? '');
                            $shippingAddress = $firstAddr->street ?: ($shippingAddress ?? '');
                            $shippingCity = $firstAddr->city ?: ($shippingCity ?? '');
                            $shippingPostal = $firstAddr->postal_code ?: ($shippingPostal ?? '');
                        }
                    }
                } catch (\Throwable $e) { /* ignore */ }
            @endphp

            <div class="fieldset info-row">
                <label>Full name
                    <input type="text" id="fullName" placeholder="Juan Dela Cruz" value="{{ $shippingName }}">
                </label>
                <label>Email address
                    <input type="email" id="email" placeholder="juan@email.com" value="{{ $shippingEmail }}">
                </label>
            </div>

            <div class="fieldset info-row">
                <label>Contact number
                    <input type="tel" id="phone" placeholder="09XX XXX XXXX" value="{{ $shippingPhone }}">
                </label>
            </div>

            <label>Delivery address
                <input type="text" id="address" placeholder="House number, street, subdivision, barangay, city, province" value="{{ $shippingAddress }}">
            </label>

            <div class="fieldset info-row">
                <label>City / Municipality
                    <input type="text" id="city" placeholder="e.g. Quezon City" value="{{ $shippingCity }}">
                </label>
                <label>Postal code
                    <input type="text" id="postalCode" placeholder="1100" value="{{ $shippingPostal }}">
                </label>
            </div>

            <footer class="shipping-footer">
                <div class="shipping-footer__actions">
                    <a href="{{ route('customer.profile.addresses') }}" class="shipping-action-button shipping-action-button--primary">Update</a>
                    <a href="{{ route('customer.profile.addresses') }}" class="shipping-action-button">Add</a>
                </div>
            </footer>

            <div class="fieldset" id="shippingOptions">
                <h2 class="section-title" style="font-size:18px; margin-bottom:4px;"><span>2</span>Shipping options</h2>
                <p class="section-subtitle" style="margin-bottom:10px;">Choose the delivery speed that works best for you.</p>

                <label class="option-card" data-option="standard">
                    <input type="radio" name="shippingOption" value="standard" data-cost="{{ $shippingFee }}" checked>
                    <div class="option-content">
                        <h3>Standard shipping (3-5 business days)</h3>
                        <p>Free delivery nationwide for orders above ₱3,000.</p>
                        <span class="option-tag">{{ $shippingFee > 0 ? '+ ₱' . number_format($shippingFee, 2) : 'Free' }}</span>
                    </div>
                </label>

                <label class="option-card" data-option="express">
                    <input type="radio" name="shippingOption" value="express" data-cost="{{ $expressShippingFee }}">
                    <div class="option-content">
                        <h3>Express shipping (1-2 business days)</h3>
                        <p>Priority dispatch with real-time tracking updates.</p>
                        <span class="option-tag">+ ₱{{ number_format(max($expressShippingFee - $shippingFee, 0), 2) }}</span>
                    </div>
                </label>
            </div>

            {{-- Payment method moved here to merge with Shipping into one container --}}
            <div class="fieldset" id="paymentOptions" style="margin-top:18px;">
                <h2 class="section-title" style="font-size:18px; margin-bottom:6px;"><span>3</span>Payment method</h2>
                <p class="section-subtitle" style="margin-bottom:10px;">Pick a payment method and provide the necessary details.</p>

                <label class="option-card" data-payment="gcash">
                    <input type="radio" name="paymentMethod" value="gcash" data-label="GCash" checked>
                    <div class="option-content">
                        <h3>GCash</h3>
                        <p>Pay instantly using your GCash account. You’ll receive a confirmation within minutes.</p>
                        <span class="option-tag">Recommended</span>
                    </div>
                </label>

                <label class="option-card" data-payment="cod">
                    <input type="radio" name="paymentMethod" value="cod" data-label="Cash on Delivery">
                    <div class="option-content">
                        <h3>Cash on Delivery (COD)</h3>
                        <p>Pay with cash once your order arrives. Available nationwide for orders under ₱10,000.</p>
                        <span class="option-tag">No additional fee</span>
                    </div>
                </label>
                <p class="section-subtitle" style="margin:0;">We’ll send instructions for your selected payment method after you place the order.</p>
            </div>

            {{-- No dynamic select behavior: fields are prefilled server-side from first saved address (if any) and remain editable --}}
            <input type="hidden" id="checkoutQuantity" name="quantity" value="{{ $checkoutQuantity }}">
        </section>

        <aside class="card summary-card" id="summary">
            <h2 class="summary-header">Order summary</h2>
            <div class="summary-items" id="summaryItems">
                @forelse(($order?->items ?? collect()) as $item)
                    <div class="summary-item">
                        <span>{{ $item->product_name }} × {{ $item->quantity }}</span>
                        <strong>₱{{ number_format($item->subtotal, 2) }}</strong>
                    </div>
                    @foreach($item->addons as $addon)
                        <div class="summary-item" style="padding-left:16px; font-size:0.9rem;">
                            <span>{{ $addon->addon_name }}</span>
                            <strong>₱{{ number_format(($addon->addon_price ?? 0) * $item->quantity, 2) }}</strong>
                        </div>
                    @endforeach
                @empty
                    <div class="summary-item">
                        <span>No items in this order yet.</span>
                        <strong>—</strong>
                    </div>
                @endforelse
            </div>
            <hr class="summary-divider">
            <div class="summary-item">
                <span>Subtotal</span>
                <strong id="subtotalAmount">₱{{ number_format($subtotal, 2) }}</strong>
            </div>
            <div class="summary-item">
                <span>Shipping</span>
                <strong id="shippingAmount">{{ $shippingFee > 0 ? '₱' . number_format($shippingFee, 2) : 'Free' }}</strong>
            </div>
            <div class="summary-item">
                <span>Tax</span>
                <strong id="taxAmount">₱{{ number_format($taxAmount, 2) }}</strong>
            </div>
            <hr class="summary-divider">
            <div class="summary-item">
                <span>Total paid via GCash</span>
                <strong id="paidAmount">₱{{ number_format($paidAmountDisplay, 2) }}</strong>
            </div>
            <div class="summary-item">
                <span>Balance remaining</span>
                <strong id="balanceAmount">₱{{ number_format($balanceDueDisplay, 2) }}</strong>
            </div>
            <div class="summary-total">
                <span>Total due</span>
                <span id="grandTotal">₱{{ number_format($totalAmount, 2) }}</span>
            </div>
            <button type="button" id="payWithGCash" class="place-order" @if($isFullyPaid) disabled @endif>
                {{ $isFullyPaid ? 'Order fully paid' : 'Pay ' . '₱' . number_format($depositSuggested > 0 ? $depositSuggested : $balanceDueDisplay, 2) . ' via GCash' }}
            </button>
            <button type="button" id="markAsPaid" class="place-order" style="background: linear-gradient(135deg, #10b981, #059669); margin-top: 8px;" @if($isFullyPaid) disabled @endif>
                {{ $isFullyPaid ? 'Order fully paid' : 'Mark as Paid' }}
            </button>
            <div id="paymentAlert" class="payment-alert"></div>
            {{-- 'Mark as fully paid' button removed per UI update: payment completion should be handled via the payment flow and recorded payments. --}}
            {{-- Cancel Order button removed per UI update --}}
            <p class="note">Recorded payments total ₱{{ number_format($paidAmountDisplay, 2) }}. Outstanding balance: ₱{{ number_format($balanceDueDisplay, 2) }}.</p>
        </aside>
    </div>

    <!-- payment section merged into shipping above -->

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const priceFormatter = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
            const subtotal = @json($subtotal ?? 0);
            const baseShipping = @json($shippingFee ?? 0);
            const taxRate = @json($taxRate ?? 0);
            let recordedPaidAmount = @json($paidAmountDisplay ?? 0);
            let currentShippingCost = Number(baseShipping ?? 0);
            let currentTax = Number((subtotal ?? 0) * (taxRate ?? 0));
            let currentTotal = Number((subtotal ?? 0) + currentShippingCost + currentTax);
            const paymentConfig = {
                createUrl: '{{ route('payment.gcash.create') }}',
                resumeUrl: @json($pendingPaymentUrl ?? null),
                hasPending: @json($hasPendingPayment ?? false),
                depositAmount: @json($depositSuggested ?? 0),
                balance: @json($balanceDueDisplay ?? 0),
                isFullyPaid: @json($isFullyPaid ?? false),
            };

            const shippingRadios = document.querySelectorAll('input[name="shippingOption"]');
            const paymentRadios = document.querySelectorAll('input[name="paymentMethod"]');
            const shippingAmountEl = document.getElementById('shippingAmount');
            const taxAmountEl = document.getElementById('taxAmount');
            const grandTotalEl = document.getElementById('grandTotal');
            const paidAmountEl = document.getElementById('paidAmount');
            const balanceAmountEl = document.getElementById('balanceAmount');
            const payButton = document.getElementById('payWithGCash');
            // create COD button (hidden by default)
            let codButton = document.getElementById('payWithCOD');
            if (!codButton) {
                codButton = document.createElement('button');
                codButton.type = 'button';
                codButton.id = 'payWithCOD';
                codButton.className = 'place-order';
                codButton.style.display = 'none';
                codButton.textContent = 'Pay on delivery (COD)';
                payButton.parentElement.insertBefore(codButton, payButton.nextSibling);
            }
            const markAsPaidButton = document.getElementById('markAsPaid');
            const paymentAlert = document.getElementById('paymentAlert');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

            const showPaymentMessage = (type, message) => {
                if (!paymentAlert) return;
                paymentAlert.classList.remove('info', 'success', 'error');
                paymentAlert.classList.add(type);
                paymentAlert.innerHTML = message;
                paymentAlert.style.display = 'block';
            };

            const clearPaymentMessage = () => {
                if (!paymentAlert) return;
                paymentAlert.classList.remove('info', 'success', 'error');
                paymentAlert.style.display = 'none';
                paymentAlert.textContent = '';
            };

            const updatePayButton = () => {
                if (!payButton) return;
                if (paymentConfig.isFullyPaid) {
                    payButton.textContent = 'Order fully paid';
                    payButton.disabled = true;
                    codButton.style.display = 'none';
                    payButton.style.display = 'inline-block';
                    return;
                }
                const amountLabel = paymentConfig.depositAmount > 0 ? paymentConfig.depositAmount : paymentConfig.balance;
                payButton.textContent = `Pay ${priceFormatter.format(amountLabel)} via GCash`;
                payButton.disabled = false;
                payButton.style.display = 'inline-block';
                codButton.style.display = 'none';
            };

            const updateMarkAsPaidButton = () => {
                if (!markAsPaidButton) return;
                if (paymentConfig.isFullyPaid) {
                    markAsPaidButton.textContent = 'Order fully paid';
                    markAsPaidButton.disabled = true;
                    return;
                }
                markAsPaidButton.textContent = 'Mark as Paid';
                markAsPaidButton.disabled = false;
            };

            // Update summary area when payment method changes
            const updateSummaryForPaymentMethod = (method) => {
                const paymentHelper = document.getElementById('paymentHelper');
                const paymentStatusPill = document.querySelector('.summary-status strong.status-pill');
                if (method === 'gcash') {
                    // Show only GCash button
                    payButton.style.display = 'inline-block';
                    codButton.style.display = 'none';
                    updatePayButton();
                    if (paymentHelper) paymentHelper.innerHTML = "You&rsquo;ll be redirected to GCash to authorize this payment. Ensure your account is ready for ₱420.68.";
                } else if (method === 'cod') {
                    // Show only COD button
                    payButton.style.display = 'none';
                    codButton.style.display = 'inline-block';
                    if (paymentHelper) paymentHelper.innerHTML = "You must pay upon delivery.";
                }
            };

            const highlightSelection = (target) => {
                if (!target) return;
                const isShipping = target.name === 'shippingOption';
                const groupSelector = isShipping ? '#shippingOptions .option-card' : '#paymentOptions .option-card';
                document.querySelectorAll(groupSelector).forEach((card) => card.classList.remove('selected'));
                target.closest('.option-card')?.classList.add('selected');
            };

            const recalcTotals = () => {
                if (!shippingAmountEl || !taxAmountEl || !grandTotalEl) return;
                const selectedShipping = document.querySelector('input[name="shippingOption"]:checked');
                const shippingCost = Number(selectedShipping?.dataset.cost ?? baseShipping ?? 0);
                const tax = (subtotal ?? 0) * (taxRate ?? 0);
                const total = (subtotal ?? 0) + shippingCost + tax;
                const balance = Math.max(total - (recordedPaidAmount ?? 0), 0);

                currentShippingCost = shippingCost;
                currentTax = tax;
                currentTotal = total;

                shippingAmountEl.textContent = shippingCost === 0 ? 'Free' : priceFormatter.format(shippingCost);
                taxAmountEl.textContent = priceFormatter.format(tax);
                grandTotalEl.textContent = priceFormatter.format(total);
                if (paidAmountEl) paidAmountEl.textContent = priceFormatter.format(recordedPaidAmount ?? 0);
                if (balanceAmountEl) balanceAmountEl.textContent = priceFormatter.format(balance);

                paymentConfig.balance = balance;
                paymentConfig.depositAmount = balance <= 0 ? 0 : Math.min(Number((total / 2).toFixed(2)), balance);
                paymentConfig.isFullyPaid = paymentConfig.balance <= 0.01;

                updatePayButton();
                updateMarkAsPaidButton();
            };

            const applyPaymentLocally = (amount, options = {}) => {
                if (!Number.isFinite(amount) || amount <= 0) return;
                recordedPaidAmount = Number((recordedPaidAmount + amount).toFixed(2));
                recalcTotals();

                if (options.message) {
                    showPaymentMessage('success', options.message);
                }
            };

            shippingRadios.forEach((radio) => {
                if (radio.checked) highlightSelection(radio);
                radio.addEventListener('change', (event) => {
                    highlightSelection(event.target);
                    recalcTotals();
                });
            });

            paymentRadios.forEach((radio) => {
                if (radio.checked) highlightSelection(radio);
                radio.addEventListener('change', (event) => highlightSelection(event.target));
            });

            // Listen for payment method changes to adjust summary UI
            document.querySelectorAll('input[name="paymentMethod"]').forEach(r => {
                r.addEventListener('change', (e) => {
                    updateSummaryForPaymentMethod(e.target.value);
                });
                if (r.checked) updateSummaryForPaymentMethod(r.value);
            });

            const readQuantityInput = () => {
                const quantityInput = document.querySelector('input[name="quantity"]');
                if (!quantityInput) return undefined;

                const parsed = Number.parseInt(quantityInput.value, 10);
                return Number.isNaN(parsed) || parsed <= 0 ? undefined : parsed;
            };

            const collectAddonSelections = () => Array.from(document.querySelectorAll('input[name="addons[]"]:checked')).map((input) => input.value);

            // Helper to persist final-step data without forcing a redirect
            const persistFinalStepSelections = async (selectedPaymentMethod, options = {}) => {
                const { paymentAmount = null, redirectOnSuccess = false } = options;

                if (!csrfToken) {
                    const error = new Error('Missing security token. Please refresh the page and try again.');
                    showPaymentMessage('error', error.message);
                    return { success: false, handledRedirect: false, error };
                }

                clearPaymentMessage();

                const payload = {
                    quantity: readQuantityInput(),
                    paper_stock_id: document.querySelector('input[name="paper_stock_id"]')?.value ?? undefined,
                    addons: collectAddonSelections(),
                    metadata: paymentAmount ? { payment_amount: paymentAmount } : {},
                    payment_method: selectedPaymentMethod,
                };

                try {
                    const res = await fetch('{{ route('order.finalstep.save') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await res.json();

                    if (!res.ok) {
                        throw new Error(data.message || 'Unable to save order selections.');
                    }

                    if (data.summary) {
                        try {
                            window.sessionStorage.setItem('inkwise-finalstep', JSON.stringify(data.summary));
                        } catch (storageError) {
                            console.debug('Unable to cache order summary in session storage.', storageError);
                        }
                    }

                    if (redirectOnSuccess) {
                        const redirectUrl = data.admin_redirect ?? (data.order_number ? `{{ url('/admin/ordersummary') }}/${data.order_number}` : null);
                        if (redirectUrl) {
                            window.location.href = redirectUrl;
                            return { success: true, handledRedirect: true, data };
                        }
                    }

                    return { success: true, handledRedirect: false, data };
                } catch (err) {
                    console.error(err);
                    showPaymentMessage('error', err.message ?? 'Unable to save order selections.');
                    return { success: false, handledRedirect: false, error: err };
                }
            };

            const determineGcashMode = () => {
                const balance = Number(paymentConfig.balance ?? 0);
                const deposit = Number(paymentConfig.depositAmount ?? 0);

                if (balance <= 0.01) {
                    return 'full';
                }

                if (deposit > 0 && deposit + 0.01 < balance) {
                    return 'half';
                }

                return 'full';
            };

            const collectContactDetails = () => ({
                name: document.getElementById('fullName')?.value?.trim() || undefined,
                email: document.getElementById('email')?.value?.trim() || undefined,
                phone: document.getElementById('phone')?.value?.trim() || undefined,
            });

            const startGCashPayment = async () => {
                if (!paymentConfig.createUrl) {
                    showPaymentMessage('error', 'GCash payment endpoint is not configured.');
                    return { success: false };
                }

                const amount = Number(paymentConfig.depositAmount > 0 ? paymentConfig.depositAmount : paymentConfig.balance);
                if (!Number.isFinite(amount) || amount <= 0) {
                    showPaymentMessage('error', 'There is no outstanding balance to charge.');
                    return { success: false };
                }

                const mode = determineGcashMode();
                const contact = collectContactDetails();

                try {
                    const response = await fetch(paymentConfig.createUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            name: contact.name,
                            email: contact.email,
                            phone: contact.phone,
                            mode,
                        }),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Unable to start the GCash payment.');
                    }

                    if (data.status) {
                        paymentConfig.hasPending = data.status === 'awaiting_next_action';
                    }
                    if (typeof data.pending === 'boolean') {
                        paymentConfig.hasPending = data.pending;
                    }
                    if (data.redirect_url) {
                        paymentConfig.resumeUrl = data.redirect_url;
                    }

                    showPaymentMessage('info', 'Redirecting you to GCash to complete the payment...');
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                        return { success: true, redirected: true, data };
                    }

                    showPaymentMessage('success', data.message ?? 'GCash payment initialized.');
                    return { success: true, redirected: false, data };
                } catch (err) {
                    console.error(err);
                    showPaymentMessage('error', err.message ?? 'Unable to start the GCash payment.');
                    return { success: false, error: err };
                }
            };

            if (payButton) {
                payButton.addEventListener('click', async () => {
                    if (paymentConfig.isFullyPaid) {
                        showPaymentMessage('info', 'This order is already fully paid.');
                        return;
                    }

                    const amountLabel = Number(paymentConfig.depositAmount > 0 ? paymentConfig.depositAmount : paymentConfig.balance);
                    payButton.disabled = true;

                    try {
                        const result = await persistFinalStepSelections('gcash', { paymentAmount: amountLabel });
                        if (!result?.success || result?.handledRedirect) {
                            payButton.disabled = false;
                            return;
                        }

                        const paymentResult = await startGCashPayment();
                        if (!paymentResult?.success || paymentResult.redirected !== true) {
                            payButton.disabled = false;
                        }
                    } catch (err) {
                        console.error(err);
                        payButton.disabled = false;
                    }
                });
            }

            if (markAsPaidButton) {
                markAsPaidButton.addEventListener('click', () => {
                    if (paymentConfig.isFullyPaid) {
                        showPaymentMessage('info', 'This order is already fully paid.');
                        return;
                    }

                    window.location.href = '{{ route("customer.my_purchase.toship") }}';
                });
            }

            if (codButton) {
                codButton.addEventListener('click', async () => {
                    const result = await persistFinalStepSelections('cod', { redirectOnSuccess: true });
                    if (result?.success && !result.handledRedirect) {
                        window.location.href = '{{ route("customer.my_purchase.toship") }}';
                    }
                });
            }

            recalcTotals();

            if (paymentConfig.hasPending && paymentConfig.resumeUrl) {
                showPaymentMessage('info', `A GCash payment is waiting for completion. <a href="${paymentConfig.resumeUrl}" target="_blank" rel="noopener">Resume payment</a>.`);
            }

            const userDropdownBtn = document.getElementById('userDropdownBtn');
            const userDropdownMenu = document.getElementById('userDropdownMenu');
            if (userDropdownBtn && userDropdownMenu) {
                const hideMenu = () => userDropdownMenu.classList.add('hidden', 'opacity-0', 'pointer-events-none');

                userDropdownBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    const isHidden = userDropdownMenu.classList.contains('hidden');
                    if (isHidden) {
                        userDropdownMenu.classList.remove('hidden', 'opacity-0', 'pointer-events-none');
                    } else {
                        hideMenu();
                    }
                });

                document.addEventListener('click', (event) => {
                    if (!userDropdownMenu.contains(event.target) && !userDropdownBtn.contains(event.target)) {
                        hideMenu();
                    }
                });
            }
        });
    </script>

@if(!empty($orderSummary))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const summaryData = {!! \Illuminate\Support\Js::from($orderSummary) !!};
            window.sessionStorage.setItem('inkwise-finalstep', JSON.stringify(summaryData));
        });
    </script>
@endif
</body>
</html>
