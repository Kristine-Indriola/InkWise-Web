@extends('layouts.staffapp')

@section('title', 'Manage Payment Transaction')

@push('styles')
<style>
    .payment-shell {
        max-width: 1024px;
        margin: 0 auto;
        padding: 32px 16px 72px;
    }

    .payment-shell > * + * {
        margin-top: 24px;
    }

    @media (max-width: 600px) {
        .payment-shell {
            padding: 24px 12px 64px;
        }
    }

    .payment-card,
    .transaction-history-card,
    .payment-form-card,
    .payment-info-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 8px 16px rgba(15, 23, 42, 0.04);
    }

    .payment-card__header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 20px;
    }

    @media (max-width: 720px) {
        .payment-card__header {
            flex-direction: column;
        }
    }

    .payment-card__header h1 {
        font-size: 26px;
        font-weight: 600;
        margin: 0;
        color: #111827;
    }

    .payment-card__subtitle {
        margin: 6px 0 0;
        color: #6b7280;
        font-size: 14px;
        max-width: 560px;
        line-height: 1.5;
    }

    .payment-status-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 9999px;
        font-weight: 600;
        font-size: 13px;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .payment-status-chip::before {
        content: '';
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: currentColor;
    }

    .payment-status-chip--pending {
        background: #eef2ff;
        color: #3730a3;
    }

    .payment-status-chip--paid {
        background: #dcfce7;
        color: #15803d;
    }

    .payment-status-chip--partial {
        background: #ede9fe;
        color: #5b21b6;
    }

    .payment-status-chip--failed {
        background: #fee2e2;
        color: #b91c1c;
    }

    .payment-status-chip--refunded {
        background: #fef3c7;
        color: #b45309;
    }

    .payment-meta {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 14px;
    }

    @media (max-width: 600px) {
        .payment-meta {
            grid-template-columns: 1fr;
        }
    }

    .payment-meta__item {
        padding: 14px 16px;
        border-radius: 10px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
    }

    .payment-meta__label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6b7280;
        margin-bottom: 6px;
    }

    .payment-meta__value {
        font-size: 16px;
        font-weight: 600;
        color: #111827;
        word-break: break-word;
    }

    .transaction-history-card__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 24px;
    }

    @media (max-width: 720px) {
        .transaction-history-card__header {
            flex-direction: column;
            align-items: flex-start;
        }
    }

    .transaction-history-card__header h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: #111827;
    }

    .transaction-history-card__header p {
        margin: 6px 0 0;
        color: #6b7280;
        font-size: 13px;
        line-height: 1.5;
    }

    .transaction-list {
        display: grid;
        gap: 16px;
    }

    .transaction-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #f9fafb;
    }

    @media (max-width: 600px) {
        .transaction-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }
    }

    .transaction-item__details {
        flex: 1;
    }

    .transaction-item__title {
        font-size: 16px;
        font-weight: 600;
        color: #111827;
        margin: 0 0 4px;
    }

    .transaction-item__meta {
        font-size: 13px;
        color: #6b7280;
        margin: 0;
    }

    .transaction-item__amount {
        font-size: 18px;
        font-weight: 700;
        color: #111827;
        text-align: right;
    }

    @media (max-width: 600px) {
        .transaction-item__amount {
            text-align: left;
        }
    }

    .payment-info-grid {
        display: grid;
        gap: 16px;
    }

    @media (min-width: 900px) {
        .payment-info-grid {
            grid-template-columns: 2fr 1fr;
        }
    }

    .payment-info-card__title {
        margin: 0 0 12px;
        font-size: 16px;
        font-weight: 600;
        color: #111827;
    }

    .payment-info-card dl {
        margin: 0;
        display: grid;
        gap: 12px;
    }

    .payment-info-card dt {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6b7280;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .payment-info-card dd {
        margin: 0;
        font-size: 14px;
        font-weight: 600;
        color: #111827;
        word-break: break-word;
    }

    .payment-info-card__text {
        margin: 0;
        color: #374151;
        font-size: 14px;
        line-height: 1.6;
    }

    .payment-info-card__empty {
        color: #9ca3af;
        font-size: 14px;
        line-height: 1.6;
        margin: 0;
    }

    .payment-form-card {
        padding: 24px;
    }

    .payment-form {
        display: grid;
        gap: 20px;
    }

    .payment-form label {
        font-weight: 600;
        color: #111827;
        display: block;
        margin-bottom: 6px;
    }

    .payment-form select,
    .payment-form input,
    .payment-form textarea {
        width: 100%;
        padding: 10px 12px;
        border-radius: 8px;
        border: 1px solid #d1d5db;
        background: #f9fafb;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .payment-form select:focus,
    .payment-form input:focus,
    .payment-form textarea:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        background: #ffffff;
    }

    .payment-form .hint {
        font-size: 13px;
        color: #6b7280;
        margin-top: 4px;
        display: block;
    }

    .payment-form .form-row {
        display: grid;
        gap: 16px;
    }

    .payment-form .form-row.is-split {
        gap: 16px;
    }

    @media (min-width: 700px) {
        .payment-form .form-row.is-split {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    .payment-form button {
        justify-self: flex-start;
        padding: 10px 24px;
        background: #4f46e5;
        color: #ffffff;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s ease;
    }

    .payment-form button:hover {
        background: #4338ca;
    }

    .payment-form button:focus-visible {
        outline: 2px solid #6366f1;
        outline-offset: 3px;
    }

    @media (max-width: 600px) {
        .payment-form button {
            width: 100%;
            justify-self: stretch;
        }
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #4f46e5;
        font-weight: 600;
        text-decoration: none;
    }

    .back-link:hover {
        text-decoration: underline;
    }

    .payment-alert {
        padding: 14px 18px;
        border-radius: 10px;
        font-size: 14px;
        border: 1px solid transparent;
    }

    .payment-alert--success {
        background: #ecfdf5;
        border-color: #34d399;
        color: #047857;
    }

    .payment-alert--danger {
        background: #fef2f2;
        border-color: #f87171;
        color: #b91c1c;
    }

    .error-text {
        color: #b91c1c;
        font-size: 13px;
        margin-top: 6px;
    }
</style>
@endpush

@section('content')
@php
    $normalizeToArray = function ($value) {
        if ($value instanceof \Illuminate\Support\Collection) {
            $value = $value->toArray();
        } elseif ($value instanceof \Illuminate\Contracts\Support\Arrayable) {
            $value = $value->toArray();
        } elseif ($value instanceof \JsonSerializable) {
            $value = $value->jsonSerialize();
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        return is_array($value) ? $value : [];
    };

    $metadata = $normalizeToArray($metadata ?? []);
    $paymentStatusOptions = [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'partial' => 'Partial',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
    ];
    $currentPaymentStatus = old('payment_status', data_get($order, 'payment_status', 'pending'));
    $trackingNumber = old('tracking_number', $metadata['tracking_number'] ?? '');
    $paymentNote = old('payment_note', $metadata['payment_note'] ?? '');
    $orderId = $order instanceof \Illuminate\Database\Eloquent\Model ? $order->getKey() : data_get($order, 'id');
    if (!$orderId) {
        $orderId = data_get($order, 'order_id');
    }
    $previousUrl = url()->previous();
    if ($previousUrl === url()->current()) {
        $previousUrl = $orderId ? route('staff.orders.summary', ['id' => $orderId]) : route('staff.order_list.index');
    }
    $currentPaymentChipModifier = str_replace('_', '-', $currentPaymentStatus);
    $currentPaymentStatusLabel = $paymentStatusOptions[$currentPaymentStatus] ?? ucfirst(str_replace('_', ' ', $currentPaymentStatus));
    $formatDateTime = function ($value) {
        try {
            if ($value instanceof \Illuminate\Support\Carbon) {
                return $value->format('M j, Y g:i A');
            }
            if ($value) {
                return \Illuminate\Support\Carbon::parse($value)->format('M j, Y g:i A');
            }
        } catch (\Throwable $e) {
            return null;
        }
        return null;
    };
    $orderNumber = data_get($order, 'order_number', $orderId ? '#' . $orderId : 'Order');
    $customerName = data_get($order, 'customer.full_name')
        ?? data_get($order, 'customer.name')
        ?? 'Guest customer';
    $placedDateDisplay = $formatDateTime(data_get($order, 'order_date'));
    $lastUpdatedDisplay = $formatDateTime(data_get($order, 'updated_at'));
    $paymentRecords = collect($paymentRecords ?? []);
    $currencyCode = data_get($paymentSnapshot ?? [], 'currency', 'PHP');
    $currencySymbol = $currencyCode === 'PHP' ? '₱' : ($currencyCode . ' ');
    $formatCurrencyAmount = function ($value) use ($currencySymbol) {
        $numeric = is_numeric($value) ? (float) $value : 0.0;
        return $currencySymbol . number_format($numeric, 2);
    };
    $totalAmount = data_get($paymentSnapshot ?? [], 'grand_total', $order->grandTotalAmount());
    $financialMetadata = data_get($metadata, 'financial', []);
    $paidAmountOverrideRaw = $financialMetadata['total_paid_override'] ?? null;
    $balanceDueOverrideRaw = $financialMetadata['balance_due_override'] ?? null;
    $paidAmountOverride = is_numeric($paidAmountOverrideRaw) ? (float) $paidAmountOverrideRaw : null;
    $balanceDueOverride = is_numeric($balanceDueOverrideRaw) ? (float) $balanceDueOverrideRaw : null;
    $paidAmount = $paidAmountOverride ?? data_get($paymentSnapshot ?? [], 'total_paid', $order->totalPaid());
    $balanceDue = $balanceDueOverride ?? data_get($paymentSnapshot ?? [], 'balance_due', $order->balanceDue());
    $latestPaymentDisplay = $formatDateTime(data_get($paymentSnapshot ?? [], 'latest_payment_at'));
    $primaryPaymentMethod = data_get($paymentRecords->first(), 'method') ?? data_get($order, 'payment_method');
    $primaryPaymentProvider = data_get($paymentRecords->first(), 'provider');
    $paymentCount = $paymentRecords->count();
@endphp

<main class="payment-shell">
    <a href="{{ $previousUrl }}" class="back-link">
        <span aria-hidden="true">&larr;</span>
        Back to order
    </a>

    @if(session('success'))
        <div class="payment-alert payment-alert--success" role="status" aria-live="polite">
            <strong>{{ session('success') }}</strong>
        </div>
    @endif

    @if(session('error'))
        <div class="payment-alert payment-alert--danger" role="alert" aria-live="assertive">
            <strong>{{ session('error') }}</strong>
        </div>
    @endif

    @if($errors->any())
        <div class="payment-alert payment-alert--danger" role="alert" aria-live="assertive">
            <strong>Unable to update payment details.</strong>
            <ul style="margin: 8px 0 0; padding-left: 18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="payment-card">
        <header class="payment-card__header">
            <div>
                <h1>Payment details</h1>
                <p class="payment-card__subtitle">
                    View payment status and transaction history for this order
                </p>
            </div>
            <span class="payment-status-chip payment-status-chip--{{ $currentPaymentChipModifier }}">
                {{ $paymentStatusOptions[$currentPaymentStatus] ?? ucfirst(str_replace('_', ' ', $currentPaymentStatus)) }}
            </span>
        </header>
        <div class="payment-meta">
            <div class="payment-meta__item">
                <span class="payment-meta__label">Order number</span>
                <span class="payment-meta__value">{{ $orderNumber }}</span>
            </div>
            <div class="payment-meta__item">
                <span class="payment-meta__label">Customer</span>
                <span class="payment-meta__value">{{ $customerName }}</span>
            </div>
            <div class="payment-meta__item">
                <span class="payment-meta__label">Total amount</span>
                <span class="payment-meta__value">{{ $formatCurrencyAmount($totalAmount) }}</span>
            </div>
            <div class="payment-meta__item">
                <span class="payment-meta__label">Amount paid</span>
                <span class="payment-meta__value">{{ $formatCurrencyAmount($paidAmount) }}</span>
            </div>
            <div class="payment-meta__item">
                <span class="payment-meta__label">Balance due</span>
                <span class="payment-meta__value">{{ $formatCurrencyAmount($balanceDue) }}</span>
            </div>
            <div class="payment-meta__item">
                <span class="payment-meta__label">Payment status</span>
                <span class="payment-meta__value">
                    {{ $paymentStatusOptions[$currentPaymentStatus] ?? ucfirst(str_replace('_', ' ', $currentPaymentStatus)) }}
                </span>
            </div>
        @if($primaryPaymentMethod)
            <div class="payment-meta__item">
                <span class="payment-meta__label">Payment method</span>
                <span class="payment-meta__value">{{ mb_strtoupper($primaryPaymentMethod) }}</span>
            </div>
        @endif
        @if($primaryPaymentProvider)
            <div class="payment-meta__item">
                <span class="payment-meta__label">Payment provider</span>
                <span class="payment-meta__value">{{ ucfirst($primaryPaymentProvider) }}</span>
            </div>
        @endif
        @if($latestPaymentDisplay)
            <div class="payment-meta__item">
                <span class="payment-meta__label">Latest payment</span>
                <span class="payment-meta__value">{{ $latestPaymentDisplay }}</span>
            </div>
        @endif
        </div>
    </section>

    <section class="transaction-history-card">
        <header class="transaction-history-card__header">
            <div>
                <h2>Transaction history</h2>
                <p>View all payment transactions for this order</p>
            </div>
        </header>
        <div class="transaction-list">
            @forelse($paymentRecords as $payment)
                @php
                    $method = data_get($payment, 'method');
                    $provider = data_get($payment, 'provider');
                    $status = strtolower((string) data_get($payment, 'status', 'pending'));
                    $statusModifier = str_replace('_', '-', $status);
                    $reference = data_get($payment, 'reference');
                    $recordedBy = data_get($payment, 'recorded_by.name');
                    $notes = data_get($payment, 'notes');
                    $origin = data_get($payment, 'origin');
                    $timestamp = data_get($payment, 'recorded_at') ?? data_get($payment, 'created_at');
                    $timestampDisplay = $formatDateTime($timestamp) ?? 'Date unavailable';
                    $metaPieces = collect([
                        $timestampDisplay,
                        $recordedBy ? 'Recorded by ' . $recordedBy : null,
                        $reference ? 'Ref: ' . $reference : null,
                        $origin === 'metadata' ? 'Imported record' : null,
                    ])->filter()->implode(' · ');
                @endphp
                <div class="transaction-item">
                    <div class="transaction-item__details">
                        <p class="transaction-item__title">
                            {{ $method ? ucfirst($method) : 'Payment' }}@if($provider) via {{ ucfirst($provider) }}@endif
                        </p>
                        @if($metaPieces !== '')
                            <p class="transaction-item__meta">{{ $metaPieces }}</p>
                        @endif
                        @if($notes)
                            <p class="transaction-item__meta">Note: {{ $notes }}</p>
                        @endif
                    </div>
                    <div class="transaction-item__amount">
                        {{ $formatCurrencyAmount(data_get($payment, 'amount', 0)) }}
                        <div>
                            <span class="payment-status-chip payment-status-chip--{{ $statusModifier }}" style="margin-top: 6px; display: inline-flex;">{{ ucfirst($status) }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <p class="payment-info-card__empty">No payment transactions recorded yet.</p>
            @endforelse
        </div>
    </section>

    <section class="payment-info-grid">
        <article class="payment-info-card">
            <h2 class="payment-info-card__title">Payment details</h2>
            <dl>
                <dt>Order number</dt>
                <dd>{{ $orderNumber }}</dd>
                <dt>Customer</dt>
                <dd>{{ $customerName }}</dd>
                <dt>Order date</dt>
                <dd>{{ $placedDateDisplay ?? 'Not available' }}</dd>
                <dt>Total amount</dt>
                <dd>{{ $formatCurrencyAmount($totalAmount) }}</dd>
                <dt>Amount paid</dt>
                <dd>{{ $formatCurrencyAmount($paidAmount) }}</dd>
                <dt>Balance due</dt>
                <dd>{{ $formatCurrencyAmount($balanceDue) }}</dd>
                <dt>Payment status</dt>
                <dd>{{ $paymentStatusOptions[$currentPaymentStatus] ?? ucfirst(str_replace('_', ' ', $currentPaymentStatus)) }}</dd>
                <dt>Last updated</dt>
                <dd>{{ $lastUpdatedDisplay ?? 'Not available' }}</dd>
                @if($paymentNote)
                <dt>Payment note</dt>
                <dd>{{ $paymentNote }}</dd>
                @endif
            </dl>
        </article>

        <article class="payment-info-card">
            <h2 class="payment-info-card__title">Payment summary</h2>
            <div class="payment-info-card__text">
                @if($balanceDue > 0)
                    <p>This order has an outstanding balance of <strong>{{ $formatCurrencyAmount($balanceDue) }}</strong>.</p>
                    @if($currentPaymentStatus === 'pending')
                        <p>Payment is still pending. Update the status once payment is confirmed.</p>
                    @endif
                @else
                    <p>This order is fully paid. All amounts have been received.</p>
                @endif

                @if($paymentCount > 0)
                    <p><strong>{{ $paymentCount }}</strong> payment transaction{{ $paymentCount > 1 ? 's' : '' }} recorded.</p>
                @else
                    <p>No payment transactions have been recorded yet.</p>
                @endif
            </div>
        </article>
    </section>
    <section class="payment-form-card">
        <div class="payment-info-card__text">
            <p>Payment details are read-only for staff accounts. Contact an administrator if a payment update is required.</p>
        </div>
    </section>
</main>
@endsection

@push('scripts')
<script>
    (function () {
        const shouldSync = Boolean(@json(session()->has('success')));
        if (!shouldSync) {
            return;
        }

        const payload = {
            orderId: @json($orderId),
            orderNumber: @json($orderNumber),
            paymentStatus: @json($currentPaymentStatus),
            paymentStatusLabel: @json($currentPaymentStatusLabel),
            consumedBy: []
        };

        payload.timestamp = Date.now();

        try {
            localStorage.setItem('inkwiseOrderPaymentUpdate', JSON.stringify(payload));
        } catch (error) {
            console.warn('Unable to persist order payment update for table sync.', error);
        }
    })();
</script>
@endpush
