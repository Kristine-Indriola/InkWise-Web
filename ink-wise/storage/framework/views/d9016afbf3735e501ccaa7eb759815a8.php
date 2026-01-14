<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>Checkout - Inkwise</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo e(asset('css/customer/customer.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/customer/customertemplate.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/customer/template.css')); ?>">
    <script src="<?php echo e(asset('js/customer/customer.js')); ?>" defer></script>
    <script src="<?php echo e(asset('js/customer/template.js')); ?>" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.10.2/cdn.min.js" defer></script>
    <link rel="icon" type="image/png" href="<?php echo e(asset('adminimage/ink.png')); ?>">
    <style>
        :root {
            --page-gradient: radial-gradient(circle at 20% 20%, rgba(148, 163, 184, 0.12), transparent 35%),
                              radial-gradient(circle at 80% 0%, rgba(99, 102, 241, 0.08), transparent 32%),
                              #f7f9fc;
            --surface: #ffffff;
            --surface-muted: #f8fafc;
            --surface-strong: #0f172a;
            --divider: rgba(15, 23, 42, 0.06);
            --shadow-lg: 0 22px 70px rgba(15, 23, 42, 0.10);
            --shadow-md: 0 12px 40px rgba(15, 23, 42, 0.08);
            --text-strong: #0f172a;
            --text-default: #1f2937;
            --text-muted: #475569;
            --text-soft: #94a3b8;
            --accent: #0f172a;
            --accent-dark: #0b1220;
            --success: #16a34a;
            --warning: #f59e0b;
            --danger: #ef4444;
            --radius-lg: 18px;
            --radius-md: 12px;
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
            line-height: 1.6;
        }

        .glass-card {
            backdrop-filter: blur(14px);
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid rgba(15, 23, 42, 0.06);
            box-shadow: var(--shadow-lg);
        }

        .fade-border {
            border: 1px solid rgba(15, 23, 42, 0.06);
            box-shadow: var(--shadow-md);
        }

        a {
            color: var(--text-default);
            text-decoration: none;
            transition: color 0.2s ease, opacity 0.2s ease;
        }

        a:hover {
            color: var(--accent);
        }

        .page-wrapper {
            width: min(1200px, calc(100% - 48px));
            margin: 32px auto 40px;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 28px;
            align-items: start;
        }

        .card {
            background: var(--surface);
            border-radius: 26px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(15, 23, 42, 0.06);
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
            gap: 28px;
            padding: 32px;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 20px;
            font-weight: 600;
            color: var(--text-strong);
            margin: 0 0 6px 0;
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
            margin: 0 0 18px 0;
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
            font-weight: 600;
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
            padding: 12px 14px;
            font-family: inherit;
            transition: border 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
            background: #ffffff;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: rgba(79, 70, 229, 0.45);
            outline: none;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
            background: #f8fafc;
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        .option-card {
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.28);
            background: rgba(255, 255, 255, 0.96);
            padding: 16px 20px;
            display: flex;
            gap: 16px;
            align-items: flex-start;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
        }

        .option-card:hover {
            border-color: rgba(15, 23, 42, 0.22);
            box-shadow: 0 20px 52px rgba(15, 23, 42, 0.14);
        }

        .option-card.selected {
            border-color: #0f172a;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.04), rgba(15, 23, 42, 0.01));
            box-shadow: 0 22px 60px rgba(15, 23, 42, 0.16);
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
            padding: 24px;
            display: grid;
            gap: 20px;
            position: sticky;
            top: 24px;
            border-radius: 24px;
            border: 1px solid rgba(15, 23, 42, 0.06);
            box-shadow: var(--shadow-lg);
            background: rgba(255, 255, 255, 0.98);
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
            font-weight: 700;
            color: var(--text-strong);
            padding: 16px 0;
            border-top: 2px solid rgba(15, 23, 42, 0.08);
        }

        .summary-total span:last-child {
            color: var(--accent);
        }

        .place-order {
            width: 100%;
            padding: 14px 20px;
            border-radius: 999px;
            border: none;
            background: var(--accent);
            color: #ffffff;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.01em;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
        }

        .place-order:hover {
            background: var(--accent-dark);
            transform: translateY(-1px);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.18);
        }

        .place-order:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(238, 77, 45, 0.28), 0 12px 28px rgba(15, 23, 42, 0.18);
        }

        .place-order:active {
            transform: translateY(0);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
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

            .place-order,
            .btn-continue {
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
            border-radius: 999px;
            border: none;
            background: linear-gradient(135deg, var(--accent), #ef4444);
            background-size: 160% 160%;
            color: #ffffff;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.01em;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 24px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
        }

        .btn-continue:hover {
            background-position: 100% 0;
            transform: translateY(-1px);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.18);
        }

        .btn-continue:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(238, 77, 45, 0.28), 0 12px 28px rgba(15, 23, 42, 0.18);
        }

        .btn-continue:active {
            transform: translateY(0);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
            background-position: 0 100%;
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
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .btn-back:hover {
            background: #f8f9fa;
            border-color: #d1d5db;
        }

        .btn-back:focus-visible {
            outline: none;
            border-color: rgba(15, 23, 42, 0.26);
            box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.16), 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        .btn-back:active {
            transform: translateY(0);
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.06);
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
<?php
    // Provide navigation context required by the shared topbar
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

    $order = $order ?? null;
    $customerOrder = $order?->customerOrder;
    $orderItem = $order?->items->first();
    $subtotal = $order?->subtotal_amount ?? 0;
    $taxAmount = $order?->tax_amount ?? 0;
    $shippingFee = $order?->shipping_fee ?? 0;
    // Prefer persisted Order values when available (authoritative).
    if ($order) {
        $totalAmount = (float) ($order->grandTotalAmount() ?? 0);
        // Use model helper to compute total paid (includes applied payments, webhooks, etc.)
        $paidAmountDisplay = round((float) ($order->totalPaid() ?? 0), 2);
    } else {
        // Fall back to client-provided session summary or inline payment records
        $totalAmount = (float) (data_get($orderSummary, 'totalAmount') ?? 0);
        $paymentRecordsCollection = collect($paymentRecords ?? []);
        $calculatedPaid = $paidAmount ?? $paymentRecordsCollection
            ->filter(fn ($payment) => ($payment['status'] ?? null) === 'paid')
            ->sum(fn ($payment) => (float) ($payment['amount'] ?? 0));
        $paidAmountDisplay = round($calculatedPaid, 2);
    }

    // Calculate deposit and remaining amounts properly to avoid rounding issues
    $depositAmountDisplay = round($totalAmount * 0.5, 2);
    $remainingAmountDisplay = round($totalAmount - $depositAmountDisplay, 2);
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
    $taxRate = 0; // No tax
    $hasPendingPayment = ($paymongoMeta['status'] ?? null) === 'awaiting_next_action';
    $pendingPaymentUrl = $paymongoMeta['next_action_url'] ?? null;
    $paymentMode = $paymongoMeta['mode'] ?? 'half';
    $isFullyPaid = $balanceDueDisplay <= 0.01;
    $hasRemainingBalance = !$isFullyPaid;
    $formatPaymentDate = static function ($date) {
        try {
            return $date ? \Illuminate\Support\Carbon::parse($date)->format('M j, Y g:i A') : null;
        } catch (\Throwable $e) {
            return $date;
        }
    };
    $taxAmount = 0; // No tax
    // $totalAmount = $subtotal + $shippingFee + $taxAmount; // Use order's total_amount instead
    $checkoutQuantity = $orderItem?->quantity
        ?? ($orderSummary['quantity'] ?? null)
        ?? null;
?>

<?php if(session('status')): ?>
    <div class="status-banner" style="margin:16px auto; max-width:960px; padding:12px 18px; border-radius:16px; background:#ecfdf5; color:#047857; font-weight:600; text-align:center;">
        <?php echo e(session('status')); ?>

    </div>
<?php endif; ?>

<?php if($order && !$isFullyPaid && in_array($order->status, ['processing', 'in_production', 'confirmed'])): ?>
    <div class="status-banner" style="margin:16px auto; max-width:960px; padding:12px 18px; border-radius:16px; background:#fef3c7; color:#92400e; font-weight:600; text-align:center; border: 1px solid #f59e0b;">
        <div style="margin-bottom: 8px;">Your order has been confirmed! Please pay the remaining balance to complete your order.</div>
        <a href="<?php echo e(route('order.pay.remaining.balance', $order)); ?>" class="inline-block px-6 py-2 bg-[#ee4d2d] text-white rounded-lg hover:bg-[#d73211] transition-colors font-semibold">
            Pay Remaining Balance (₱<?php echo e(number_format($balanceDueDisplay, 2)); ?>)
        </a>
    </div>
<?php endif; ?>
    <?php echo $__env->make('partials.topbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="page-wrapper">
        <!-- Checkout Steps -->
        <div class="checkout-steps" style="grid-column: 1 / -1; margin-bottom: 24px;">
            <div class="steps-container" style="display: flex; align-items: center; justify-content: center; gap: 32px;">
                <div class="step active" style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div class="step-circle">1</div>
                    <span class="step-label">Order Summary</span>
                </div>
                <div class="step-line"></div>
                <div class="step" style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div class="step-circle">2</div>
                    <span class="step-label">Shipping</span>
                </div>
                <div class="step-line"></div>
                <div class="step" style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div class="step-circle">3</div>
                    <span class="step-label">Payment</span>
                </div>
            </div>
        </div>

        <section class="card checkout-form checkout-section active" id="summary-step">
            <header class="shipping-header">
                <h1 class="section-title">Order Summary</h1>
                <p class="section-subtitle">Review your selected items and totals before proceeding.</p>
            </header>

            <div class="summary-card" style="position: static; top: auto;">
                <div class="summary-items" id="summaryItems">
                    <?php $__empty_1 = true; $__currentLoopData = ($order?->items ?? collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="summary-item">
                            <span><?php echo e($item->product_name); ?> × <?php echo e($item->quantity); ?></span>
                            <strong>₱<?php echo e(number_format($item->subtotal, 2)); ?></strong>
                        </div>
                        <?php $__currentLoopData = $item->addons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $addon): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="summary-item" style="padding-left:16px; font-size:0.9rem;">
                                <span><?php echo e($addon->addon_name); ?></span>
                                <strong>₱<?php echo e(number_format(($addon->addon_price ?? 0) * $item->quantity, 2)); ?></strong>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="summary-item">
                            <span>No items in this order yet.</span>
                            <strong>—</strong>
                        </div>
                    <?php endif; ?>
                </div>
                <hr class="summary-divider">
                <div class="summary-item">
                    <span>Subtotal</span>
                    <strong id="subtotalAmount">₱<?php echo e(number_format($subtotal, 2)); ?></strong>
                </div>
                <div class="summary-item">
                    <span>Shipping</span>
                    <strong id="shippingAmount"><?php echo e($shippingFee > 0 ? '₱' . number_format($shippingFee, 2) : 'Free'); ?></strong>
                </div>
                <hr class="summary-divider">
                <div class="summary-item">
                    <span>Total paid via GCash</span>
                    <strong id="paidAmount">₱<?php echo e(number_format($paidAmountDisplay, 2)); ?></strong>
                </div>
                <?php if(!$isFullyPaid): ?>
                <div class="summary-item">
                    <span>Balance remaining</span>
                    <strong id="balanceAmount">₱<?php echo e(number_format($balanceDueDisplay, 2)); ?></strong>
                </div>
                <?php endif; ?>
                <div class="summary-total">
                    <span>Total due</span>
                    <span id="grandTotal">₱<?php echo e(number_format($totalAmount, 2)); ?></span>
                </div>
                <?php if($isFullyPaid): ?>
                <p class="note">Order fully paid. Recorded payments total ₱<?php echo e(number_format($paidAmountDisplay, 2)); ?>.</p>
                <?php else: ?>
                <p class="note">Recorded payments total ₱<?php echo e(number_format($paidAmountDisplay, 2)); ?>. Outstanding balance: ₱<?php echo e(number_format($balanceDueDisplay, 2)); ?>.</p>
                <?php endif; ?>
            </hr>

            <button type="button" class="btn-continue" id="continueToShipping" style="margin-top: 24px;">
                Continue to Shipping
            </button>
        </section>

        <section class="card checkout-form checkout-section" id="shipping">
            <header class="shipping-header">
                <h1 class="section-title">Fulfillment Information</h1>
                <p class="section-subtitle">Please provide your contact details for order pickup</p>
            </header>

            <div id="savedTemplatePreview" style="display:none; margin-bottom:12px;">
                <!-- Saved template preview will be injected here -->
            </div>

            
            <?php
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
            ?>

            <div class="fieldset info-row">
                <label>Full name
                    <input type="text" id="fullName" placeholder="Juan Dela Cruz" value="<?php echo e($shippingName); ?>">
                </label>
                <label>Email address
                    <input type="email" id="email" placeholder="juan@email.com" value="<?php echo e($shippingEmail); ?>">
                </label>
            </div>

            <div class="fieldset info-row">
                <label>Contact number
                    <input type="tel" id="phone" placeholder="09XX XXX XXXX" value="<?php echo e($shippingPhone); ?>">
                </label>
                <label>Quantity
                    <input type="number" id="checkoutQuantity" name="quantity" value="<?php echo e($checkoutQuantity); ?>" min="10" max="200">
                </label>
            </div>

            <label id="addressLabel">Address (optional)
                <input type="text" id="address" placeholder="House number, street, subdivision, barangay, city, province" value="<?php echo e($shippingAddress); ?>">
            </label>

            <div class="fieldset info-row" id="addressFields">
                <label>City / Municipality
                    <input type="text" id="city" placeholder="e.g. Quezon City" value="<?php echo e($shippingCity); ?>">
                </label>
                <label>Postal code
                    <input type="text" id="postalCode" placeholder="1100" value="<?php echo e($shippingPostal); ?>">
                </label>
            </div>

            <footer class="shipping-footer">
                <div class="shipping-footer__actions">
                    <a href="<?php echo e(route('customerprofile.addresses')); ?>" class="shipping-action-button shipping-action-button--primary">Update</a>
                    <a href="<?php echo e(route('customerprofile.addresses')); ?>" class="shipping-action-button">Add</a>
                </div>
            </footer>

            <input type="hidden" name="shippingOption" value="pickup">
            <div class="note" style="padding: 16px; background: #f0f9ff; border-radius: 8px; border: 1px solid #bae6fd; margin: 16px 0;">
                <p style="margin: 0; color: #0c4a6e; font-size: 14px; line-height: 1.6;">
                    <strong>📍 Pickup at Store:</strong> All orders are for pickup only at our store location. We'll notify you when your order is ready for collection.
                </p>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="button" class="btn-back" id="backToSummary">← Back to Summary</button>
                <button type="button" class="btn-continue" id="continueToPayment" style="flex: 1;">
                    Continue to Payment
                </button>
            </div>


        </section>

        <section class="card glass-card fade-border checkout-form checkout-section" id="payment">
            <header class="shipping-header">
                <h1 class="section-title">Payment Method</h1>
                <p class="section-subtitle" id="paymentSubtitle">Choose how you want to pay for your order.</p>
            </header>

            <div id="paymentAlert" class="payment-alert" style="display: none;"></div>

            <!-- Deposit Payment Options (50%) -->
            <div class="fieldset" id="depositPaymentOptions">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: var(--text-strong);">Deposit Payment (50%)</h3>

                <!-- GCash Deposit (50%) + COD (50%) -->
                <label class="option-card payment-option" data-payment-type="gcash-deposit-cod">
                    <input type="radio" name="paymentMethod" value="gcash-deposit-cod">
                    <div class="option-content">
                        <h3>50% GCash Deposit + Pay on Pickup</h3>
                        <p>Pay ₱<?php echo e(number_format($depositAmountDisplay, 2)); ?> deposit now via GCash, ₱<?php echo e(number_format($remainingAmountDisplay, 2)); ?> cash on delivery.</p>
                        <span class="option-tag">Required Deposit</span>
                    </div>
                </label>

                <!-- GCash Deposit (50%) + GCash Balance (50%) -->
                <label class="option-card payment-option" data-payment-type="gcash-split">
                    <input type="radio" name="paymentMethod" value="gcash-split">
                    <div class="option-content">
                        <h3>50% GCash Deposit + GCash Balance</h3>
                        <p>Pay ₱<?php echo e(number_format($depositAmountDisplay, 2)); ?> deposit now, ₱<?php echo e(number_format($remainingAmountDisplay, 2)); ?> via GCash after confirmation.</p>
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
                        <p>Pay ₱<?php echo e(number_format($totalAmount, 2)); ?> in full now via GCash. No remaining balance to pay later.</p>
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
                <button type="button" class="btn-back" id="backToShipping">
                    ← Back to Shipping
                </button>
                <button type="button" class="place-order" id="placeOrderBtn" style="flex: 1;">
                    Place Order
                </button>
            </div>
        </section>
    </div>

    <!-- payment section merged into shipping above -->

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Step navigation logic
            let currentStep = 1;
            const totalSteps = 3;
            const stepOrder = ['summary-step', 'shipping', 'payment'];

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

            // Render saved template preview (safe to call even if not saved)
            const renderSavedTemplatePreview = () => {
                try {
                    const container = document.getElementById('savedTemplatePreview');
                    if (!container) return;
                    // Prefer explicit saved template, then saved-template storage, then the final-step payload
                    const stored = window.savedCustomerTemplate
                        || (window.sessionStorage && JSON.parse(window.sessionStorage.getItem('inkwise-saved-template') || 'null'))
                        || (window.sessionStorage && JSON.parse(window.sessionStorage.getItem('inkwise-finalstep') || 'null'))
                        || null;
                    if (!stored) {
                        container.style.display = 'none';
                        container.innerHTML = '';
                        return;
                    }

                    let html = '<div style="display:flex; align-items:center; gap:12px;">';
                    // various possible preview keys: preview, previewImage, previewImages
                    const previewSrc = stored.preview || stored.previewImage || (Array.isArray(stored.previewImages) ? stored.previewImages[0] : null) || null;
                    if (previewSrc) {
                        html += `<img src="${previewSrc}" alt="Saved template" style="width:96px; height:96px; object-fit:cover; border-radius:8px; border:1px solid rgba(0,0,0,0.06);">`;
                    }
                    html += `<div style="flex:1;"><div style="font-weight:600;">Saved template</div><div style="font-size:13px; color:var(--text-muted);">${stored.name || ''}</div></div>`;
                    html += '</div>';

                    container.innerHTML = html;
                    container.style.display = 'block';
                } catch (err) {
                    console.debug('renderSavedTemplatePreview error', err);
                }
            };

            const showStep = (stepNumber) => {
                // Hide all sections
                document.querySelectorAll('.checkout-section').forEach(section => {
                    section.classList.remove('active');
                });

                // Show current step
                const currentSection = document.getElementById(stepOrder[stepNumber - 1]);
                if (currentSection) {
                    currentSection.classList.add('active');
                }

                currentStep = stepNumber;
                updateStepIndicator();

                if (stepNumber === 3) {
                    populateReviewInfo();
                }

                if (stepNumber === 2) {
                    // render preview of saved template (if any)
                    renderSavedTemplatePreview();
                }
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
                        const remaining = (paymentConfig.balance ?? Math.max(currentTotal - (recordedPaidAmount ?? 0), 0));
                        const depositAmount = Math.round(remaining * 100 / 2) / 100;
                        const remainingAmount = Math.max(remaining - depositAmount, 0);
                        paymentDetails = `
                            <div><strong>50% GCash Deposit + Cash on Delivery</strong></div>
                            <div>Deposit: ₱${depositAmount.toFixed(2)} (Pay now via GCash)</div>
                            <div>Remaining: ₱${remainingAmount.toFixed(2)} (Pay on delivery)</div>
                        `;
                    } else if (selectedPayment?.value === 'gcash-split') {
                        const remaining = (paymentConfig.balance ?? Math.max(currentTotal - (recordedPaidAmount ?? 0), 0));
                        const depositAmount = Math.round(remaining * 100 / 2) / 100;
                        const remainingAmount = Math.max(remaining - depositAmount, 0);
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
            const saveCustomerTemplate = async () => {
                try {
                    const stored = window.orderSummary || (window.sessionStorage && JSON.parse(window.sessionStorage.getItem('inkwise-finalstep') || 'null')) || null;
                    if (!stored || !stored.metadata || !stored.metadata.design) {
                        return { success: false, message: 'No design data available to save.' };
                    }

                    const payload = {
                        template_name: stored.templateName || ('My template ' + new Date().toISOString()),
                        design: stored.metadata.design,
                        preview_image: stored.previewImage || (Array.isArray(stored.previewImages) ? stored.previewImages[0] : null),
                        preview_images: stored.previewImages || [],
                    };

                    const localCsrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

                    const res = await fetch('<?php echo e(route('order.design.save-template')); ?>', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': localCsrf,
                        },
                        body: JSON.stringify(payload),
                    });

                    let data = null;
                    let textBody = null;
                    try { data = await res.json(); } catch (e) { try { textBody = await res.text(); } catch (e2) { textBody = null; } }

                    if (!res.ok) {
                        if (res.status === 419 || res.status === 401) {
                            return { success: false, message: 'Authentication or session expired. Please refresh the page and sign in.' };
                        }
                        const errMsg = (data && data.message) ? data.message : (textBody ? textBody : (`Status ${res.status}`));
                        return { success: false, message: 'Unable to save template: ' + errMsg };
                    }

                    // Persist saved template info for other pages/scripts
                    try {
                        const saved = { id: data.template_id ?? null, name: data.template_name ?? payload.template_name, preview: payload.preview_image };
                        window.sessionStorage.setItem('inkwise-saved-template', JSON.stringify(saved));
                        window.savedCustomerTemplate = saved;
                    } catch (e) {
                        // ignore storage errors
                    }

                    return { success: true, data };
                } catch (err) {
                    console.error('saveCustomerTemplate error', err);
                    return { success: false, message: err?.message ?? 'Unexpected error' };
                }
            };

            document.getElementById('continueToShipping')?.addEventListener('click', async () => {
                // Attempt to save customer's template before proceeding to shipping
                if (window && window.orderSummary) {
                    const result = await saveCustomerTemplate();
                    if (result.success) {
                        showPaymentMessage('success', 'Template saved to your account.');
                    } else {
                        // Non-fatal: allow user to continue but inform them
                        showPaymentMessage('error', result.message || 'Could not save template.');
                    }
                    // small delay so user sees the message, but continue flow immediately
                }

                showStep(2);
                // render saved template preview if available
                renderSavedTemplatePreview();
            });

            document.getElementById('backToSummary')?.addEventListener('click', () => {
                showStep(1);
            });

            document.getElementById('continueToPayment')?.addEventListener('click', () => {
                if (validateShippingStep()) {
                    clearPaymentMessage();
                    // Ensure any saved template is included in the final-step summary
                    try {
                        const saved = JSON.parse(window.sessionStorage.getItem('inkwise-saved-template') || 'null');
                        let summary = window.orderSummary || (window.sessionStorage && JSON.parse(window.sessionStorage.getItem('inkwise-finalstep') || 'null')) || {};
                        if (saved) {
                            summary = summary || {};
                            summary.template = summary.template || {};
                            summary.template.template_id = saved.id ?? summary.template.template_id ?? null;
                            summary.template.template_name = saved.name ?? summary.template.template_name ?? summary.template_name ?? (summary.template?.name ?? null);
                            // Use preview_image fields for compatibility with finalstep view
                            summary.previewImage = summary.previewImage || saved.preview || summary.previewImage || null;
                            if (!summary.previewImages || !Array.isArray(summary.previewImages) || summary.previewImages.length === 0) {
                                summary.previewImages = saved.preview ? [saved.preview] : (summary.previewImages || []);
                            } else if (saved.preview && !summary.previewImages.includes(saved.preview)) {
                                summary.previewImages.unshift(saved.preview);
                            }
                        }
                        try {
                            // Persist only a minimal summary to avoid writing large blobs to sessionStorage
                            const minSummary = {
                                productId: summary.productId ?? summary.product_id ?? null,
                                quantity: summary.quantity ?? null,
                                paymentMode: summary.paymentMode ?? summary.payment_mode ?? null,
                                totalAmount: summary.totalAmount ?? summary.total_amount ?? null,
                                shippingFee: summary.shippingFee ?? summary.shipping_fee ?? null,
                                order_id: summary.order_id ?? summary.orderId ?? null,
                            };
                            window.sessionStorage.setItem('inkwise-finalstep', JSON.stringify(minSummary));
                            window.orderSummary = Object.assign({}, window.orderSummary || {}, minSummary);
                        } catch (e) {
                            // ignore storage errors
                        }
                    } catch (err) {
                        // ignore parsing errors
                    }

                    showStep(3);
                }
            });

            document.getElementById('backToShipping')?.addEventListener('click', () => {
                clearPaymentMessage();
                showStep(2);
            });

            // Initialize step from hash (e.g., #payment or #review) or default to shipping
            const hash = (window.location.hash || '').replace('#', '').toLowerCase();
            let initialStep = 1;
            if (hash === 'shipping') {
                initialStep = 2;
            } else if (hash === 'payment' || hash === 'review') {
                initialStep = 3;
            }
            showStep(initialStep);

            // Ensure we have a client-side reference to the summary
            try {
                if (!window.orderSummary && window.sessionStorage) {
                    const stored = window.sessionStorage.getItem('inkwise-finalstep');
                    if (stored) window.orderSummary = JSON.parse(stored);
                }
            } catch (err) { /* ignore */ }

            // Wire quantity input to recalc totals
            const quantityInput = document.getElementById('checkoutQuantity');
            if (quantityInput) {
                quantityInput.addEventListener('input', () => {
                    // update in-memory orderSummary quantity if present
                    const parsed = Number.parseInt(quantityInput.value, 10);
                    if (!Number.isNaN(parsed) && parsed > 0) {
                        try {
                            if (window.orderSummary) {
                                window.orderSummary.quantity = parsed;
                                const minSummary = {
                                    productId: window.orderSummary.productId ?? window.orderSummary.product_id ?? null,
                                    quantity: window.orderSummary.quantity ?? null,
                                    paymentMode: window.orderSummary.paymentMode ?? window.orderSummary.payment_mode ?? null,
                                    totalAmount: window.orderSummary.totalAmount ?? window.orderSummary.total_amount ?? null,
                                    shippingFee: window.orderSummary.shippingFee ?? window.orderSummary.shipping_fee ?? null,
                                    order_id: window.orderSummary.order_id ?? window.orderSummary.orderId ?? null,
                                };
                                window.sessionStorage.setItem('inkwise-finalstep', JSON.stringify(minSummary));
                            }
                        } catch (err) { /* ignore */ }
                        recalcTotals();
                        // also refresh payment UI
                        const activePayment = document.querySelector('input[name="paymentMethod"]:checked');
                        if (activePayment) updatePaymentSummary(activePayment.value);
                    }
                });
            }

            const priceFormatter = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
            const subtotal = <?php echo json_encode($subtotal ?? 0, 15, 512) ?>;
            const baseShipping = <?php echo json_encode($shippingFee ?? 0, 15, 512) ?>;
            const taxRate = <?php echo json_encode($taxRate ?? 0, 15, 512) ?>;
            const orderTotalAmount = <?php echo json_encode($totalAmount ?? 0, 15, 512) ?>; // Use the order's total amount
            let recordedPaidAmount = <?php echo json_encode($paidAmountDisplay ?? 0, 15, 512) ?>;
            let currentShippingCost = Number(baseShipping ?? 0);
            let currentTax = Number((subtotal ?? 0) * (taxRate ?? 0));
            let currentTotal = Number(orderTotalAmount); // Use order total instead of calculation
            const paymentConfig = {
                createUrl: '<?php echo e(route('payment.gcash.create')); ?>',
                resumeUrl: <?php echo json_encode($pendingPaymentUrl ?? null, 15, 512) ?>,
                hasPending: <?php echo json_encode($hasPendingPayment ?? false, 15, 512) ?>,
                depositAmount: <?php echo json_encode($depositSuggested ?? 0, 15, 512) ?>,
                balance: <?php echo json_encode($balanceDueDisplay ?? 0, 15, 512) ?>,
                isFullyPaid: <?php echo json_encode($isFullyPaid ?? false, 15, 512) ?>,
                // Ensure we carry the authoritative order id from the server into client flows.
                orderId: <?php echo json_encode($order->id ?? null, 15, 512) ?>,
            };

            const shippingRadios = document.querySelectorAll('input[name="shippingOption"]');
            const paymentRadios = document.querySelectorAll('input[name="paymentMethod"]');
            const shippingAmountEl = document.getElementById('shippingAmount');
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



            // Update summary area when payment method changes
            const updateSummaryForPaymentMethod = (method) => {
                const paymentHelper = document.getElementById('paymentHelper');
                const paymentStatusPill = document.querySelector('.summary-status strong.status-pill');
                if (method === 'gcash') {
                    // Show only GCash button
                    if (paymentHelper) paymentHelper.innerHTML = "You&rsquo;ll be redirected to GCash to authorize this payment. Ensure your account is ready for ₱420.68.";
                } else if (method === 'cod') {
                    // Show only COD button
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
                if (!shippingAmountEl || !grandTotalEl) return;

                // Always base totals on the server-provided order total to avoid client-side drift.
                // `orderTotalAmount` is injected from the server and represents the authoritative total due.
                const total = Number(orderTotalAmount || 0);
                const balance = Math.max(total - (recordedPaidAmount ?? 0), 0);

                currentShippingCost = Number(baseShipping ?? 0);
                currentTax = 0; // No tax
                currentTotal = total;

                shippingAmountEl.textContent = currentShippingCost === 0 ? 'Free' : priceFormatter.format(currentShippingCost);
                grandTotalEl.textContent = priceFormatter.format(total);
                if (paidAmountEl) paidAmountEl.textContent = priceFormatter.format(recordedPaidAmount ?? 0);
                if (balanceAmountEl) balanceAmountEl.textContent = priceFormatter.format(balance);

                paymentConfig.balance = balance;
                // Deposit should be computed from the remaining balance (not the full order total)
                paymentConfig.depositAmount = balance <= 0 ? 0 : Math.min(Math.round(balance * 100 / 2) / 100, balance);
                paymentConfig.isFullyPaid = paymentConfig.balance <= 0.01;
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
                radio.addEventListener('change', (event) => {
                    highlightSelection(event.target);
                    if (currentStep === 3) {
                        populateReviewInfo();
                    }
                });
            });

            // Listen for payment method changes to show payment summary
            document.querySelectorAll('input[name="paymentMethod"]').forEach(r => {
                r.addEventListener('change', (e) => {
                    updatePaymentSummary(e.target.value);
                    highlightSelection(e.target);
                    if (currentStep === 3) {
                        populateReviewInfo();
                    }
                });
                if (r.checked) {
                    updatePaymentSummary(r.value);
                    highlightSelection(r);
                    if (currentStep === 3) {
                        populateReviewInfo();
                    }
                }
            });

            const updatePaymentSummary = (paymentType) => {
                const summaryDiv = document.getElementById('paymentSummary');
                const breakdownDiv = document.getElementById('paymentBreakdown');

                if (!summaryDiv || !breakdownDiv) return;

                let breakdownHTML = '';

                switch (paymentType) {
                    case 'gcash-deposit-cod': {
                        const remainingForCod = (paymentConfig.balance ?? Math.max(currentTotal - (recordedPaidAmount ?? 0), 0));
                        const depositCod = Math.round(remainingForCod * 100 / 2) / 100;
                        const remainingCod = Math.max(remainingForCod - depositCod, 0);
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
                    }

                    case 'gcash-split': {
                        const remainingForSplit = (paymentConfig.balance ?? Math.max(currentTotal - (recordedPaidAmount ?? 0), 0));
                        const depositSplit = Math.round(remainingForSplit * 100 / 2) / 100;
                        const remainingSplit = Math.max(remainingForSplit - depositSplit, 0);
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
                    }

                    case 'gcash-full': {
                        // Show full breakdown including subtotal, shipping, tax, total, recorded payments and effective additional charge
                        const subtotalVal = Number(subtotal || 0);
                        const shippingVal = Number(baseShipping || 0);
                        const taxVal = Number(currentTax || 0);
                        const totalOrder = Number(currentTotal || 0);
                        const alreadyPaid = Number(recordedPaidAmount || 0);
                        const remaining = Math.max(totalOrder - alreadyPaid, 0);
                        const chargeNow = Number(totalOrder);
                        const effectiveExtra = Math.max(chargeNow - alreadyPaid, 0);

                        summaryDiv.style.display = 'block';
                        breakdownHTML = `
                            <div class="payment-breakdown-item">
                                <span>Subtotal</span>
                                <span>₱${priceFormatter.format(subtotalVal).replace(/^\D+/, '') ? priceFormatter.format(subtotalVal) : '₱0.00'}</span>
                            </div>
                            <div class="payment-breakdown-item">
                                <span>Shipping</span>
                                <span>${shippingVal === 0 ? 'Free' : priceFormatter.format(shippingVal)}</span>
                            </div>
                            <div class="payment-breakdown-item">
                                <span>Tax</span>
                                <span>${priceFormatter.format(taxVal)}</span>
                            </div>
                            <div class="payment-breakdown-item">
                                <span>Total order</span>
                                <span class="payment-amount">${priceFormatter.format(totalOrder)}</span>
                            </div>
                            <div class="payment-breakdown-item">
                                <span>Recorded payments</span>
                                <span class="payment-amount later">${priceFormatter.format(alreadyPaid)}</span>
                            </div>
                            <div class="payment-breakdown-item">
                                <span>Remaining balance</span>
                                <span class="payment-amount later">${priceFormatter.format(remaining)}</span>
                            </div>
                            <div class="payment-breakdown-item">
                                <span>Charge now (Full)</span>
                                <span class="payment-amount now">${priceFormatter.format(chargeNow)}</span>
                            </div>
                            <div class="payment-breakdown-item">
                                <span class="payment-timing">Effective additional charge</span>
                                <span>${priceFormatter.format(effectiveExtra)}</span>
                            </div>
                            <div class="payment-breakdown-item">
                                <span class="payment-timing">Note</span>
                                <span>Selecting Full Payment charges the full order total now. Previously recorded payments will remain recorded; effective additional charge is shown above.</span>
                            </div>
                        `;
                        break;
                    }

                    default: {
                        summaryDiv.style.display = 'none';
                        return;
                    }
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

                // Build payload without any base/total price fields to ensure server uses
                // authoritative totals and remaining balance. Also include an explicit
                // flag so server-side handlers can ignore any base price if present.
                const metadata = paymentAmount ? { payment_amount: paymentAmount } : {};
                metadata.omit_base_price = true;

                const payload = {
                    quantity: readQuantityInput(),
                    paper_stock_id: document.querySelector('input[name="paper_stock_id"]')?.value ?? undefined,
                    addons: collectAddonSelections(),
                    metadata,
                    payment_method: selectedPaymentMethod,
                };

                try {
                    const res = await fetch('<?php echo e(route('order.finalstep.save')); ?>', {
                        method: 'POST',
                        credentials: 'same-origin',
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
                            // Persist a minimal returned summary into sessionStorage to avoid storing large blobs
                            const minSummary = {
                                productId: data.summary.productId ?? data.summary.product_id ?? null,
                                quantity: data.summary.quantity ?? null,
                                paymentMode: data.summary.paymentMode ?? data.summary.payment_mode ?? null,
                                totalAmount: data.summary.totalAmount ?? data.summary.total_amount ?? null,
                                shippingFee: data.summary.shippingFee ?? data.summary.shipping_fee ?? null,
                                order_id: data.summary.order_id ?? data.summary.orderId ?? null,
                            };
                            window.sessionStorage.setItem('inkwise-finalstep', JSON.stringify(minSummary));
                            window.sessionStorage.setItem('order_summary_payload', JSON.stringify(minSummary));

                            // Merge the minimal summary into window.orderSummary to make order_id available
                            window.orderSummary = window.orderSummary || {};
                            Object.assign(window.orderSummary, minSummary);
                        } catch (storageError) {
                            console.debug('Unable to cache minimal order summary in session storage.', storageError);
                        }
                    }

                    // Ensure we capture and expose the order id (returned by the server)
                    if (data.order_id) {
                        window.orderSummary = window.orderSummary || {};
                        window.orderSummary.order_id = data.order_id;
                        try {
                            window.sessionStorage.setItem('inkwise-finalstep', JSON.stringify(window.orderSummary));
                        } catch (storageError) {
                            // ignore storage errors
                        }
                    }

                    if (redirectOnSuccess) {
                        const redirectUrl = data.admin_redirect ?? (data.order_number ? `<?php echo e(url('/admin/ordersummary')); ?>/${data.order_number}` : null);
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

                const remaining = (paymentConfig.balance ?? Math.max(currentTotal - (recordedPaidAmount ?? 0), 0));
                if (paymentMethod === 'gcash-deposit-cod' || paymentMethod === 'gcash-split') {
                    amount = Math.round(remaining * 100 / 2) / 100; // Always 50% of remaining balance
                } else if (paymentMethod === 'gcash-full') {
                    // Charge the full order total when user selects Full Payment
                    amount = Number(currentTotal);
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
                    console.debug('Starting GCash payment fetch', {
                        paymentConfig,
                        paymentMethod,
                        amount,
                        mode,
                        contact,
                        recordedPaidAmount,
                        orderId: (window.orderSummary && (window.orderSummary.order_id || window.orderSummary.id)) || paymentConfig.orderId || null,
                        orderSummary: window.orderSummary || null,
                        payloadPreview: {
                            order_id: (window.orderSummary && (window.orderSummary.order_id || window.orderSummary.id)) || paymentConfig.orderId || null,
                            amount: amount,
                            mode: mode,
                        },
                    });

                    const response = await fetch(paymentConfig.createUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                                order_id: (window.orderSummary && (window.orderSummary.order_id || window.orderSummary.id)) || undefined,
                                name: contact.name,
                                email: contact.email,
                                phone: contact.phone,
                                mode,
                                // Ensure server charges the same amount shown in the UI
                                amount: amount,
                            }),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        console.debug('GCash create failed response', response.status, data);
                        // Handle conflict when server reports order already paid
                        if (response.status === 409 && data.message && /already fully paid/i.test(data.message)) {
                            // Update client-side payment state to reflect fully-paid order
                            paymentConfig.isFullyPaid = true;
                            paymentConfig.balance = 0;
                            try { recalcTotals(); } catch (e) { /* ignore if unavailable */ }
                            showPaymentMessage('info', data.message);
                            return { success: false, handledConflict: true, data };
                        }

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
                    const selectedPaymentMethod = document.querySelector('input[name="paymentMethod"]:checked')?.value;

                    // Recalculate the outstanding amount on click to avoid relying on a possibly stale
                    // `paymentConfig.isFullyPaid` value (which can be set by other flows). Use the
                    // authoritative balance value instead and prevent payments only when the
                    // remaining outstanding amount is essentially zero.
                    const remainingOutstanding = (paymentConfig.balance ?? Math.max(currentTotal - (recordedPaidAmount ?? 0), 0));
                    if (remainingOutstanding <= 0.01) {
                        // Update client-side payment state to reflect fully-paid order
                        paymentConfig.isFullyPaid = true;
                        paymentConfig.balance = 0;
                        try { recalcTotals(); } catch (e) { /* ignore if unavailable */ }
                        console.debug('Payment blocked: order fully paid', { remainingOutstanding, paymentConfig });
                        showPaymentMessage('info', 'This order is already fully paid.');
                        return;
                    }

                    let paymentAmount = 0;
                    let finalPaymentMethod = selectedPaymentMethod;

                    // Use the remainingOutstanding computed above (we already ensured it's > 0.01)
                    if (selectedPaymentMethod === 'gcash-deposit-cod') {
                        paymentAmount = Math.round(remainingOutstanding * 100 / 2) / 100; // 50% of remaining
                        finalPaymentMethod = 'gcash_deposit_cod';
                    } else if (selectedPaymentMethod === 'gcash-split') {
                        paymentAmount = Math.round(remainingOutstanding * 100 / 2) / 100; // 50% of remaining
                        finalPaymentMethod = 'gcash_split';
                    }

                    placeOrderBtn.disabled = true;
                    placeOrderBtn.textContent = 'Processing...';

                    try {
                        if (selectedPaymentMethod.startsWith('gcash')) {
                            console.debug('Placing order with GCash payment', { selectedPaymentMethod, finalPaymentMethod, paymentAmount, paymentConfig });
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

<?php if(!empty($orderSummary)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const summaryData = <?php echo \Illuminate\Support\Js::from($orderSummary); ?>;
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

            try {
                const minSummary = {
                    productId: summaryData.productId ?? summaryData.product_id ?? null,
                    quantity: summaryData.quantity ?? null,
                    paymentMode: summaryData.paymentMode ?? summaryData.payment_mode ?? null,
                    totalAmount: summaryData.totalAmount ?? summaryData.total_amount ?? null,
                    shippingFee: summaryData.shippingFee ?? summaryData.shipping_fee ?? null,
                    order_id: summaryData.order_id ?? summaryData.orderId ?? null,
                };
                window.sessionStorage.setItem('inkwise-finalstep', JSON.stringify(minSummary));
            } catch (e) {
                /* ignore */
            }
            console.log('Saved to sessionStorage:', summaryData);

            // expose for other scripts to reference and update dynamically
            window.orderSummary = summaryData;

            // Check payment mode and adjust payment options
            const paymentMode = summaryData.paymentMode || 'half';
            console.log('Using paymentMode for updatePaymentOptions:', paymentMode);
            updatePaymentOptions(paymentMode);
        });
    </script>
<?php endif; ?>

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
</html><?php /**PATH C:\Users\leanne\xampp\htdocs\InkWise-Web\ink-wise\resources\views/customer/orderflow/checkout.blade.php ENDPATH**/ ?>