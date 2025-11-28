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
            --page-gradient: linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%);
            --surface: #ffffff;
            --surface-muted: #f8f9fa;
            --surface-strong: #1a1a1a;
            --divider: rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 4px 20px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 2px 12px rgba(0, 0, 0, 0.06);
            --text-strong: #1a1a1a;
            --text-default: #333333;
            --text-muted: #666666;
            --text-soft: #999999;
            --accent: #ee4d2d;
            --accent-dark: #d73211;
            --success: #00bfa5;
            --warning: #ffbf00;
            --danger: #ee4d2d;
            --radius-lg: 8px;
            --radius-md: 6px;
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
            line-height: 1.5;
        }

        a {
            color: var(--accent);
            text-decoration: none;
        }

        a:hover {
            color: var(--accent);
        }

        .page-wrapper {
            width: min(1200px, calc(100% - 40px));
            margin: 20px auto;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 24px;
            align-items: start;
        }

        .card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(0, 0, 0, 0.04);
            overflow: hidden;
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
            gap: 32px;
            padding: 32px;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 20px;
            font-weight: 600;
            color: var(--text-strong);
            margin: 0 0 8px 0;
        }

        .section-title span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--accent);
            color: #ffffff;
            font-weight: 600;
            font-size: 14px;
        }

        .section-subtitle {
            margin: 0 0 20px 0;
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
            border-radius: var(--radius-md);
            border: 1px solid #e0e0e0;
            background: #ffffff;
            padding: 16px 20px;
            display: flex;
            gap: 16px;
            align-items: flex-start;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .option-card:hover {
            border-color: var(--accent);
            box-shadow: 0 2px 8px rgba(238, 77, 45, 0.15);
        }

        .option-card.selected {
            border-color: var(--accent);
            background: rgba(238, 77, 45, 0.02);
            box-shadow: 0 2px 8px rgba(238, 77, 45, 0.15);
        }        .option-card input[type="radio"] {
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
            padding: 24px;
            display: grid;
            gap: 20px;
            position: sticky;
            top: 20px;
        }

        .summary-header {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .summary-items {
            display: grid;
            gap: 12px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 14px;
            color: var(--text-muted);
        }

        .summary-item strong {
            font-size: 14px;
            color: var(--text-strong);
            font-weight: 500;
        }

        .summary-divider {
            border: none;
            border-top: 1px solid #e0e0e0;
            margin: 16px 0;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 20px;
            font-weight: 600;
            color: var(--text-strong);
            padding: 16px 0;
            border-top: 2px solid #e0e0e0;
        }

        .summary-total span:last-child {
            color: var(--accent);
        }

        .place-order {
            width: 100%;
            padding: 14px 20px;
            border-radius: var(--radius-md);
            border: none;
            background: var(--accent);
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .place-order:hover {
            background: var(--accent-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(238, 77, 45, 0.3);
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

        /* Step-by-step checkout process */
        .step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e0e0e0;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .step-label {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .step-line {
            width: 60px;
            height: 2px;
            background: #e0e0e0;
            transition: background 0.3s ease;
        }

        .step.active .step-circle {
            background: var(--accent);
            color: white;
        }

        .step.active .step-label {
            color: var(--text-strong);
        }

        .step.completed .step-circle {
            background: var(--success);
            color: white;
        }

        .step.completed .step-label {
            color: var(--text-strong);
        }

        .step.completed + .step-line {
            background: var(--success);
        }

        .checkout-section {
            display: none;
        }

        .checkout-section.active {
            display: block;
        }
            display: block;
        }

        .btn-continue {
            width: 100%;
            padding: 14px 20px;
            border-radius: var(--radius-md);
            border: none;
            background: var(--accent);
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 24px;
        }

        .btn-continue:hover {
            background: var(--accent-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(238, 77, 45, 0.3);
        }

        .btn-back {
            width: 100%;
            padding: 12px 20px;
            border-radius: var(--radius-md);
            border: 1px solid #e0e0e0;
            background: #ffffff;
            color: var(--text-default);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 12px;
        }

        .btn-back:hover {
            background: #f8f9fa;
            border-color: #d1d5db;
        }

        /* GCash payment options styling */
        #gcashOptions .option-card,
        #remainingBalanceOptions .option-card {
            margin-bottom: 12px;
        }

        #gcashOptions .option-card:last-child,
        #remainingBalanceOptions .option-card:last-child {
            margin-bottom: 0;
        }

        /* Payment summary styling */
        .payment-summary {
            margin-top: 20px;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: var(--radius-md);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .summary-box h4 {
            margin: 0 0 16px 0;
            font-size: 16px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .payment-breakdown-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .payment-breakdown-item:last-child {
            border-bottom: none;
            font-weight: 600;
            color: var(--text-strong);
        }

        .payment-timing {
            font-size: 12px;
            color: var(--text-muted);
            font-style: italic;
        }

        .payment-amount {
            font-weight: 600;
        }

        .payment-amount.now {
            color: var(--accent);
        }

        .payment-amount.later {
            color: var(--text-muted);
        }

        /* Enhanced option cards for payment methods */
        .payment-option.selected {
            border-color: var(--accent);
            background: rgba(238, 77, 45, 0.02);
            box-shadow: 0 2px 8px rgba(238, 77, 45, 0.15);
        }

        .payment-option .option-content h3 {
            font-size: 15px;
            margin-bottom: 4px;
        }

        .payment-option .option-content p {
            font-size: 13px;
            margin: 0 0 8px 0;
            line-height: 1.4;
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

@if($order && !$isFullyPaid && in_array($order->status, ['processing', 'in_production', 'confirmed']))
    <div class="status-banner" style="margin:16px auto; max-width:960px; padding:12px 18px; border-radius:16px; background:#fef3c7; color:#92400e; font-weight:600; text-align:center; border: 1px solid #f59e0b;">
        <div style="margin-bottom: 8px;">Your order has been confirmed! Please pay the remaining balance to complete your order.</div>
        <a href="{{ route('order.pay.remaining.balance', $order) }}" class="inline-block px-6 py-2 bg-[#ee4d2d] text-white rounded-lg hover:bg-[#d73211] transition-colors font-semibold">
            Pay Remaining Balance (₱{{ number_format($balanceDueDisplay, 2) }})
        </a>
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
                            <a href="{{ route('customerprofile.index') }}" class="block px-4 py-2 text-gray-700 hover:bg-[#e7ecff] transition-colors">My Account</a>
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
        <!-- Checkout Steps -->
        <div class="checkout-steps" style="grid-column: 1 / -1; margin-bottom: 24px;">
            <div class="steps-container" style="display: flex; align-items: center; justify-content: center; gap: 32px;">
                <div class="step active" style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div class="step-circle">1</div>
                    <span class="step-label">Shipping</span>
                </div>
                <div class="step-line"></div>
                <div class="step" style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div class="step-circle">2</div>
                    <span class="step-label">Payment</span>
                </div>
                <div class="step-line"></div>
                <div class="step" style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div class="step-circle">3</div>
                    <span class="step-label">Review</span>
                </div>
            </div>
        </div>

        <section class="card checkout-form checkout-section active" id="shipping">
            <header class="shipping-header">
                <h1 class="section-title">Fulfillment Information</h1>
                <p class="section-subtitle">Please provide your contact details for order pickup</p>
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

            <label id="addressLabel">Address (optional)
                <input type="text" id="address" placeholder="House number, street, subdivision, barangay, city, province" value="{{ $shippingAddress }}">
            </label>

            <div class="fieldset info-row" id="addressFields">
                <label>City / Municipality
                    <input type="text" id="city" placeholder="e.g. Quezon City" value="{{ $shippingCity }}">
                </label>
                <label>Postal code
                    <input type="text" id="postalCode" placeholder="1100" value="{{ $shippingPostal }}">
                </label>
            </div>

            <footer class="shipping-footer">
                <div class="shipping-footer__actions">
                    <a href="{{ route('customerprofile.addresses') }}" class="shipping-action-button shipping-action-button--primary">Update</a>
                    <a href="{{ route('customerprofile.addresses') }}" class="shipping-action-button">Add</a>
                </div>
            </footer>

            <input type="hidden" name="shippingOption" value="pickup">
            <div class="note" style="padding: 16px; background: #f0f9ff; border-radius: 8px; border: 1px solid #bae6fd; margin: 16px 0;">
                <p style="margin: 0; color: #0c4a6e; font-size: 14px; line-height: 1.6;">
                    <strong>📍 Pickup at Store:</strong> All orders are for pickup only at our store location. We'll notify you when your order is ready for collection.
                </p>
            </div>

            <button type="button" class="btn-continue" id="continueToPayment">
                Continue to Payment
            </button>

            {{-- No dynamic select behavior: fields are prefilled server-side from first saved address (if any) and remain editable --}}
            <input type="hidden" id="checkoutQuantity" name="quantity" value="{{ $checkoutQuantity }}">
        </section>

        <section class="card checkout-form checkout-section" id="payment">
            <header class="shipping-header">
                <h1 class="section-title">Payment Method</h1>
                <p class="section-subtitle" id="paymentSubtitle">Choose how you want to pay for your order.</p>
            </header>

            <!-- Deposit Payment Options (50%) -->
            <div class="fieldset" id="depositPaymentOptions">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: var(--text-strong);">Deposit Payment (50%)</h3>

                <!-- GCash Deposit (50%) + COD (50%) -->
                <label class="option-card payment-option" data-payment-type="gcash-deposit-cod">
                    <input type="radio" name="paymentMethod" value="gcash-deposit-cod">
                    <div class="option-content">
                        <h3>50% GCash Deposit + Cash on Delivery</h3>
                        <p>Pay ₱{{ number_format($totalAmount / 2, 2) }} deposit now via GCash, ₱{{ number_format($totalAmount / 2, 2) }} cash on delivery.</p>
                        <span class="option-tag">Required Deposit</span>
                    </div>
                </label>

                <!-- GCash Deposit (50%) + GCash Balance (50%) -->
                <label class="option-card payment-option" data-payment-type="gcash-split">
                    <input type="radio" name="paymentMethod" value="gcash-split">
                    <div class="option-content">
                        <h3>50% GCash Deposit + GCash Balance</h3>
                        <p>Pay ₱{{ number_format($totalAmount / 2, 2) }} deposit now, ₱{{ number_format($totalAmount / 2, 2) }} via GCash after confirmation.</p>
                        <span class="option-tag">All Digital</span>
                    </div>
                </label>
            </div>

            <!-- Full Payment Options (100%) -->
            <div class="fieldset" id="fullPaymentOptions" style="display: none;">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: var(--text-strong);">Full Payment (100%)</h3>

                <!-- GCash Full Payment -->
                <label class="option-card payment-option" data-payment-type="gcash-full">
                    <input type="radio" name="paymentMethod" value="gcash-full">
                    <div class="option-content">
                        <h3>Full GCash Payment</h3>
                        <p>Pay ₱{{ number_format($totalAmount, 2) }} in full now via GCash. No remaining balance to pay later.</p>
                        <span class="option-tag" style="background: #10b981;">Pay in Full</span>
                    </div>
                </label>
            </div>

            <!-- Payment Summary -->
            <div class="payment-summary" id="paymentSummary" style="display: none;">
                <div class="summary-box">
                    <h4>Payment Summary</h4>
                    <div id="paymentBreakdown"></div>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="button" class="btn-back" id="backToShipping">
                    ← Back to Shipping
                </button>
                <button type="button" class="btn-continue" id="continueToReview" style="flex: 1;">
                    Continue to Review
                </button>
            </div>
        </section>

        <section class="card checkout-form checkout-section" id="review">
            <header class="shipping-header">
                <h1 class="section-title">Review Your Order</h1>
                <p class="section-subtitle">Please review your order details before placing your order</p>
            </header>

            <div class="fieldset">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: var(--text-strong);">Fulfillment Details</h3>
                <div style="background: #f8f9fa; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                    <div id="reviewShippingInfo" style="font-size: 14px; color: var(--text-default);">
                        <!-- Fulfillment info will be populated by JavaScript -->
                    </div>
                </div>
            </div>

            <div class="fieldset">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: var(--text-strong);">Payment Method</h3>
                <div style="background: #f8f9fa; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                    <div id="reviewPaymentInfo" style="font-size: 14px; color: var(--text-default);">
                        <!-- Payment info will be populated by JavaScript -->
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="button" class="btn-back" id="backToPayment">
                    ← Back to Payment
                </button>
                <button type="button" class="place-order" id="placeOrderBtn" style="flex: 1;">
                    Place Order
                </button>
            </div>
        </section>

        <aside class="card summary-card" id="summary">
            <h2 class="summary-header">Order Summary</h2>
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
            <div id="paymentAlert" class="payment-alert"></div>
            <p class="note">Recorded payments total ₱{{ number_format($paidAmountDisplay, 2) }}. Outstanding balance: ₱{{ number_format($balanceDueDisplay, 2) }}.</p>
        </aside>
    </div>

    <!-- payment section merged into shipping above -->

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Step navigation logic
            let currentStep = 1;
            const totalSteps = 3;

            const updateStepIndicator = () => {
                // Update step circles and labels
                document.querySelectorAll('.step').forEach((step, index) => {
                    const stepNumber = index + 1;
                    step.classList.remove('active', 'completed');

                    if (stepNumber === currentStep) {
                        step.classList.add('active');
                    } else if (stepNumber < currentStep) {
                        step.classList.add('completed');
                    }
                });

                // Update step lines
                document.querySelectorAll('.step-line').forEach((line, index) => {
                    if (index + 1 < currentStep) {
                        line.style.background = 'var(--success)';
                    } else {
                        line.style.background = '#e0e0e0';
                    }
                });
            };

            const showStep = (stepNumber) => {
                // Hide all sections
                document.querySelectorAll('.checkout-section').forEach(section => {
                    section.classList.remove('active');
                });

                // Show current step
                const currentSection = document.getElementById(['shipping', 'payment', 'review'][stepNumber - 1]);
                if (currentSection) {
                    currentSection.classList.add('active');
                }

                currentStep = stepNumber;
                updateStepIndicator();
            };

            const validateShippingStep = () => {
                const fullName = document.getElementById('fullName')?.value?.trim();
                const email = document.getElementById('email')?.value?.trim();
                const phone = document.getElementById('phone')?.value?.trim();
                const selectedShipping = document.querySelector('input[name="shippingOption"]:checked');

                if (!fullName || !email || !phone) {
                    alert('Please fill in your name, email, and phone number.');
                    return false;
                }

                // Basic email validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    alert('Please enter a valid email address.');
                    return false;
                }

                // Address fields are optional for pickup
                return true;
            };

            const populateReviewInfo = () => {
                // Populate shipping info
                const fullName = document.getElementById('fullName')?.value?.trim() || '';
                const email = document.getElementById('email')?.value?.trim() || '';
                const phone = document.getElementById('phone')?.value?.trim() || '';
                const address = document.getElementById('address')?.value?.trim() || '';
                const city = document.getElementById('city')?.value?.trim() || '';
                const postalCode = document.getElementById('postalCode')?.value?.trim() || '';

                const shippingInfo = document.getElementById('reviewShippingInfo');
                if (shippingInfo) {
                    const fulfillmentDetails = `
                        <div><strong>Pickup at Store</strong></div>
                        <div>Ready for pickup at our store location</div>
                        <div>${phone}</div>
                        <div>${email}</div>
                    `;

                    shippingInfo.innerHTML = `
                        <div><strong>${fullName}</strong></div>
                        ${fulfillmentDetails}
                    `;
                }

                // Populate payment info
                const selectedPayment = document.querySelector('input[name="paymentMethod"]:checked');
                const paymentInfo = document.getElementById('reviewPaymentInfo');
                if (paymentInfo) {
                    let paymentDetails = '';

                    if (selectedPayment?.value === 'gcash-deposit-cod') {
                        const depositAmount = currentTotal / 2;
                        const remainingAmount = currentTotal / 2;
                        paymentDetails = `
                            <div><strong>50% GCash Deposit + Cash on Delivery</strong></div>
                            <div>Deposit: ₱${depositAmount.toFixed(2)} (Pay now via GCash)</div>
                            <div>Remaining: ₱${remainingAmount.toFixed(2)} (Pay on delivery)</div>
                        `;
                    } else if (selectedPayment?.value === 'gcash-split') {
                        const depositAmount = currentTotal / 2;
                        const remainingAmount = currentTotal / 2;
                        paymentDetails = `
                            <div><strong>50% GCash Deposit + GCash Balance</strong></div>
                            <div>Deposit: ₱${depositAmount.toFixed(2)} (Pay now via GCash)</div>
                            <div>Remaining: ₱${remainingAmount.toFixed(2)} (Pay later via GCash)</div>
                        `;
                    }

                    paymentInfo.innerHTML = paymentDetails;
                }
            };

            // Step navigation event listeners
            document.getElementById('continueToPayment')?.addEventListener('click', () => {
                if (validateShippingStep()) {
                    showStep(2);
                }
            });

            document.getElementById('backToShipping')?.addEventListener('click', () => {
                showStep(1);
            });

            document.getElementById('continueToReview')?.addEventListener('click', () => {
                populateReviewInfo();
                showStep(3);
            });

            document.getElementById('backToPayment')?.addEventListener('click', () => {
                showStep(2);
            });

            // Initialize first step
            showStep(1);

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
                // No longer needed - payment happens in review step
            };

            const updateMarkAsPaidButton = () => {
                // No longer needed - payment happens in review step
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

            const toggleAddressFields = (shippingMethod) => {
                const addressLabel = document.getElementById('addressLabel');
                const addressFields = document.getElementById('addressFields');

                if (shippingMethod === 'pickup') {
                    // Hide address fields for pickup
                    if (addressLabel) addressLabel.style.display = 'none';
                    if (addressFields) addressFields.style.display = 'none';
                } else {
                    // Show address fields for delivery
                    if (addressLabel) addressLabel.style.display = 'block';
                    if (addressFields) addressFields.style.display = 'grid';
                }
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

                // No need to update buttons anymore
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
                if (radio.checked) {
                    highlightSelection(radio);
                    toggleAddressFields(radio.value);
                }
                radio.addEventListener('change', (event) => {
                    highlightSelection(event.target);
                    toggleAddressFields(event.target.value);
                    recalcTotals();
                });
            });

            paymentRadios.forEach((radio) => {
                if (radio.checked) highlightSelection(radio);
                radio.addEventListener('change', (event) => highlightSelection(event.target));
            });

            // Listen for payment method changes to show payment summary
            document.querySelectorAll('input[name="paymentMethod"]').forEach(r => {
                r.addEventListener('change', (e) => {
                    updatePaymentSummary(e.target.value);
                    highlightSelection(e.target);
                });
                if (r.checked) {
                    updatePaymentSummary(r.value);
                    highlightSelection(r);
                }
            });

            const updatePaymentSummary = (paymentType) => {
                const summaryDiv = document.getElementById('paymentSummary');
                const breakdownDiv = document.getElementById('paymentBreakdown');

                if (!summaryDiv || !breakdownDiv) return;

                let breakdownHTML = '';

                switch (paymentType) {
                    case 'gcash-deposit-cod':
                        const depositCod = currentTotal / 2;
                        const remainingCod = currentTotal / 2;
                        summaryDiv.style.display = 'block';
                        breakdownHTML = `
                            <div class="payment-breakdown-item">
                                <span>GCash Deposit (50%)</span>
                                <span class="payment-amount now">₱${depositCod.toFixed(2)}</span>
                            </div>
                            <div class="payment-breakdown-item">
                                <span>Cash on Delivery (50%)</span>
                                <span class="payment-amount later">₱${remainingCod.toFixed(2)}</span>
                            </div>
                            <div class="payment-breakdown-item">
                                <span class="payment-timing">Deposit now, balance on delivery</span>
                                <span></span>
                            </div>
                        `;
                        break;

                    case 'gcash-split':
                        const depositSplit = currentTotal / 2;
                        const remainingSplit = currentTotal / 2;
                        summaryDiv.style.display = 'block';
                        breakdownHTML = `
                            <div class="payment-breakdown-item">
                                <span>GCash Deposit (50%)</span>
                                <span class="payment-amount now">₱${depositSplit.toFixed(2)}</span>
                            </div>
                            <div class="payment-breakdown-item">
                                <span>GCash Balance (50%)</span>
                                <span class="payment-amount later">₱${remainingSplit.toFixed(2)}</span>
                            </div>
                            <div class="payment-breakdown-item">
                                <span class="payment-timing">Deposit now, balance after confirmation</span>
                                <span></span>
                            </div>
                        `;
                        break;

                    default:
                        summaryDiv.style.display = 'none';
                        return;
                }

                breakdownDiv.innerHTML = breakdownHTML;
            };

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
                const selected = document.querySelector('input[name="paymentMethod"]:checked');
                const paymentMethod = selected?.value;

                console.log('determineGcashMode: selected element:', selected, 'value:', paymentMethod);

                if (paymentMethod === 'gcash-deposit-cod' || paymentMethod === 'gcash-split') {
                    return 'half';
                }

                if (paymentMethod === 'gcash-full') {
                    return 'full';
                }

                // Fallback
                return 'half';
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

                const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked')?.value;
                let amount = 0;

                if (paymentMethod === 'gcash-deposit-cod' || paymentMethod === 'gcash-split') {
                    amount = currentTotal / 2; // Always 50% deposit
                } else if (paymentMethod === 'gcash-full') {
                    amount = currentTotal; // Full payment
                } else {
                    amount = Number(paymentConfig.depositAmount > 0 ? paymentConfig.depositAmount : paymentConfig.balance);
                }

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

            const placeOrderBtn = document.getElementById('placeOrderBtn');

            if (placeOrderBtn) {
                placeOrderBtn.addEventListener('click', async () => {
                    if (paymentConfig.isFullyPaid) {
                        showPaymentMessage('info', 'This order is already fully paid.');
                        return;
                    }

                    const selectedPaymentMethod = document.querySelector('input[name="paymentMethod"]:checked')?.value;

                    if (!selectedPaymentMethod) {
                        showPaymentMessage('error', 'Please select a payment method.');
                        return;
                    }

                    let paymentAmount = 0;
                    let finalPaymentMethod = selectedPaymentMethod;

                    if (selectedPaymentMethod === 'gcash-deposit-cod') {
                        paymentAmount = currentTotal / 2; // Always 50% deposit
                        finalPaymentMethod = 'gcash_deposit_cod';
                    } else if (selectedPaymentMethod === 'gcash-split') {
                        paymentAmount = currentTotal / 2; // Always 50% deposit
                        finalPaymentMethod = 'gcash_split';
                    }

                    placeOrderBtn.disabled = true;
                    placeOrderBtn.textContent = 'Processing...';

                    try {
                        if (selectedPaymentMethod.startsWith('gcash')) {
                            const result = await persistFinalStepSelections(finalPaymentMethod, { paymentAmount });
                            if (!result?.success || result?.handledRedirect) {
                                placeOrderBtn.disabled = false;
                                placeOrderBtn.textContent = 'Place Order';
                                return;
                            }

                            const paymentResult = await startGCashPayment();
                            if (!paymentResult?.success || paymentResult.redirected !== true) {
                                placeOrderBtn.disabled = false;
                                placeOrderBtn.textContent = 'Place Order';
                            }
                        }
                    } catch (err) {
                        console.error(err);
                        placeOrderBtn.disabled = false;
                        placeOrderBtn.textContent = 'Place Order';
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

            // Initialize address fields visibility based on selected shipping method
            const initialShippingMethod = document.querySelector('input[name="shippingOption"]:checked');
            if (initialShippingMethod) {
                toggleAddressFields(initialShippingMethod.value);
            }

            // Initialize payment options visibility
            const initialPaymentMethod = document.querySelector('input[name="paymentMethod"]:checked');
            if (initialPaymentMethod && initialPaymentMethod.value === 'gcash') {
                document.getElementById('gcashOptions').style.display = 'block';
            }

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
            console.log('OrderSummary from PHP:', summaryData);

            // Preserve existing paymentMode from sessionStorage if it exists
            const existingStored = window.sessionStorage.getItem('inkwise-finalstep');
            console.log('Existing sessionStorage:', existingStored);
            let existingPaymentMode = null;
            if (existingStored) {
                try {
                    const existing = JSON.parse(existingStored);
                    existingPaymentMode = existing.paymentMode;
                    console.log('Existing paymentMode:', existingPaymentMode);
                } catch (err) {
                    console.warn('Failed to parse existing sessionStorage:', err);
                }
            }

            // Merge with existing paymentMode if present
            if (existingPaymentMode) {
                summaryData.paymentMode = existingPaymentMode;
                console.log('Merged paymentMode into summaryData:', summaryData.paymentMode);
            }

            window.sessionStorage.setItem('inkwise-finalstep', JSON.stringify(summaryData));
            console.log('Saved to sessionStorage:', summaryData);

            // Check payment mode and adjust payment options
            const paymentMode = summaryData.paymentMode || 'half';
            console.log('Using paymentMode for updatePaymentOptions:', paymentMode);
            updatePaymentOptions(paymentMode);
        });
    </script>
@endif

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Also check sessionStorage directly in case orderSummary is not set
    try {
        const stored = window.sessionStorage.getItem('inkwise-finalstep');
        console.log('Fallback: checking sessionStorage:', stored);
        if (stored) {
            const summary = JSON.parse(stored);
            const paymentMode = summary.paymentMode || 'half';
            console.log('Fallback: using paymentMode:', paymentMode);
            updatePaymentOptions(paymentMode);
        } else {
            console.log('Fallback: no sessionStorage found');
        }
    } catch (err) {
        console.warn('Failed to check payment mode from sessionStorage:', err);
    }
});

function updatePaymentOptions(mode) {
    const subtitle = document.getElementById('paymentSubtitle');
    const depositOptions = document.getElementById('depositPaymentOptions');
    const fullOptions = document.getElementById('fullPaymentOptions');

    // Always show both payment options
    subtitle.textContent = 'Choose how you want to pay for your order.';
    depositOptions.style.display = 'block';
    fullOptions.style.display = 'block';

    // Pre-select based on mode
    if (mode === 'full') {
        const fullPaymentRadio = document.querySelector('input[name="paymentMethod"][value="gcash-full"]');
        if (fullPaymentRadio) {
            fullPaymentRadio.checked = true;
        }
    } else {
        const firstDepositRadio = document.querySelector('input[name="paymentMethod"][value="gcash-deposit-cod"]');
        if (firstDepositRadio) {
            firstDepositRadio.checked = true;
        }
    }
}
</script>
</body>
</html>
