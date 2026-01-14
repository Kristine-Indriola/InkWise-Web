@extends('layouts.staffapp')

@section('title', 'Manage Order Status')

@push('styles')
<style>
    .order-status-shell {
        max-width: 1024px;
        margin: 0 auto;
        padding: 32px 16px 72px;
    }

    .order-status-shell > * + * {
        margin-top: 24px;
    }

    .order-status-card,
    .status-progress-card,
    .status-form-card,
    .status-info-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 8px 16px rgba(15, 23, 42, 0.04);
    }

    .order-status-card__header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 20px;
    }

    .order-status-card__header h1 {
        font-size: 26px;
        font-weight: 600;
        margin: 0;
        color: #111827;
    }

    .order-status-card__subtitle {
        margin: 6px 0 0;
        color: #6b7280;
        font-size: 14px;
        max-width: 560px;
        line-height: 1.5;
    }

    .order-stage-chip {
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

    .order-stage-chip::before {
        content: '';
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: currentColor;
    }

    .order-stage-chip--pending {
        background: #eef2ff;
        color: #3730a3;
    }

    .order-stage-chip--processing {
        background: #ede9fe;
        color: #7c3aed;
    }

    .order-stage-chip--in-production {
        background: #fef3c7;
        color: #b45309;
    }

    .order-stage-chip--confirmed {
        background: #dcfce7;
        color: #15803d;
    }

    .order-stage-chip--to-receive {
        background: #f1f5f9;
        color: #0f172a;
    }

    .order-stage-chip--completed {
        background: #e0f2fe;
        color: #0369a1;
    }

    .order-stage-chip--cancelled {
        background: #fee2e2;
        color: #b91c1c;
    }

    .order-status-meta {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 14px;
    }

    .order-status-meta__item {
        padding: 14px 16px;
        border-radius: 10px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
    }

    .order-status-meta__label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6b7280;
        margin-bottom: 6px;
    }

    .order-status-meta__value {
        font-size: 16px;
        font-weight: 600;
        color: #111827;
        word-break: break-word;
    }

    .status-progress-card__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 24px;
    }

    .status-progress-card__header h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: #111827;
    }

    .status-progress-card__header p {
        margin: 6px 0 0;
        color: #6b7280;
        font-size: 13px;
        line-height: 1.5;
    }

    .status-progress-card__actions {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .status-tracker {
        list-style: none;
        display: flex;
        justify-content: space-between;
        gap: 0;
        padding: 0 4px;
        margin: 0;
    }

    .status-tracker__item {
        position: relative;
        flex: 1;
        text-align: center;
        padding: 0 12px;
    }

    .status-tracker__marker {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        border: 3px solid #d1d5db;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 16px;
        margin: 0 auto 12px;
        color: #6b7280;
        position: relative;
        z-index: 2;
        flex-shrink: 0;
    }

    .status-tracker__number {
        font-size: 16px;
    }

    .status-tracker__icon {
        font-size: 20px;
        line-height: 1;
    }

    .status-tracker__line {
        position: absolute;
        top: 27px;
        left: calc(50% + 28px);
        width: calc(100% - 56px);
        height: 3px;
        background: #e5e7eb;
        z-index: 1;
        border-radius: 999px;
    }

    .status-tracker__item:last-child .status-tracker__line {
        display: none;
    }

    .status-tracker__title {
        font-size: 15px;
        font-weight: 600;
        margin: 0 0 6px;
        color: #111827;
    }

    .status-tracker__content {
        margin-top: 12px;
    }

    .status-tracker__subtitle {
        margin: 0;
        font-size: 13px;
        color: #6b7280;
        line-height: 1.6;
    }

    .status-tracker__item--done .status-tracker__marker {
        background: #22c55e;
        border-color: #22c55e;
        color: #ffffff;
    }

    .status-tracker__item--done .status-tracker__line {
        background: #22c55e;
    }

    .status-tracker__item--current .status-tracker__marker {
        border-color: #6366f1;
        background: #eef2ff;
        color: #4338ca;
    }

    .status-tracker__item--current .status-tracker__line {
        background: linear-gradient(90deg, #6366f1 0%, #e5e7eb 100%);
    }

    .status-tracker__item--disabled .status-tracker__marker,
    .status-tracker__item--upcoming .status-tracker__marker {
        border-color: #d1d5db;
        background: #f9fafb;
        color: #9ca3af;
    }

    .status-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 16px;
        margin-top: 24px;
    }

    .status-info-card__title {
        font-size: 16px;
        font-weight: 600;
        color: #111827;
        margin: 0 0 16px;
    }

    .status-info-card dl {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px 16px;
        margin: 0;
    }

    .status-info-card dt {
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6b7280;
    }

    .status-info-card dd {
        font-size: 14px;
        font-weight: 500;
        color: #111827;
        margin: 0;
        word-break: break-word;
    }

    .status-info-card__text {
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid #e5e7eb;
        font-size: 14px;
        color: #374151;
        line-height: 1.5;
    }

    .status-info-card__empty {
        font-style: italic;
        color: #9ca3af;
    }

    .status-form-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 8px 16px rgba(15, 23, 42, 0.04);
    }

    .status-form {
        max-width: 600px;
    }

    .status-form label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #111827;
        margin-bottom: 8px;
    }

    .status-form select,
    .status-form input,
    .status-form textarea {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        background: #ffffff;
        transition: border-color 0.2s ease;
    }

    .status-form select:focus,
    .status-form input:focus,
    .status-form textarea:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .status-form .hint {
        font-size: 13px;
        color: #6b7280;
        margin-top: 4px;
    }

    .status-form .form-row {
        margin-bottom: 20px;
    }

    .status-form .form-row.is-split {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .status-form button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.2s ease;
        background: #6366f1;
        color: #ffffff;
    }

    .status-form button:hover {
        background: #4f46e5;
    }

    .status-form button:focus-visible {
        outline: 3px solid #6366f1;
        outline-offset: 2px;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 8px;
        background: #f3f4f6;
        color: #374151;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: background-color 0.2s ease;
    }

    .back-link:hover {
        background: #e5e7eb;
        color: #111827;
    }

    .status-alert {
        padding: 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        margin-top: 16px;
    }

    .status-alert--success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }

    .status-alert--danger {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fca5a5;
    }

    .error-text {
        font-size: 13px;
        color: #dc2626;
        margin-top: 4px;
    }
</style>
@endpush

@section('content')
@php
    $normalizeToArray = function ($value) {
        if (is_array($value)) {
            return $value;
        }

        if ($value instanceof \JsonSerializable) {
            return (array) $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    };

    $metadata = $normalizeToArray($metadata ?? []);
    $statusLabels = $statusOptions ?? [
        'pending' => 'Order Received',
        'processing' => 'Processing',
        'in_production' => 'In Progress',
        'confirmed' => 'To Ship',
        'to_receive' => 'To Receive',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];
    $currentStatus = old('status', data_get($order, 'status', 'pending'));
    $flowIndex = array_search($currentStatus, $statusFlow ?? ['pending', 'in_production', 'confirmed', 'to_receive', 'completed'], true);
    $trackingNumber = old('tracking_number', $metadata['tracking_number'] ?? '');
    $statusNote = old('internal_note', $metadata['status_note'] ?? '');
    $previousUrl = url()->previous();
    if ($previousUrl === url()->current()) {
        $previousUrl = route('staff.order_list.show', $order->id);
    }
    $currentChipModifier = str_replace('_', '-', $currentStatus);
    $currentStatusLabel = $statusLabels[$currentStatus] ?? ucfirst(str_replace('_', ' ', $currentStatus));
    $nextStatusKey = $flowIndex !== false && $flowIndex < count($statusFlow ?? ['pending', 'in_production', 'confirmed', 'to_receive', 'completed']) - 1 ? ($statusFlow ?? ['pending', 'in_production', 'confirmed', 'to_receive', 'completed'])[$flowIndex + 1] : null;
    $nextStatusLabel = $nextStatusKey ? ($statusLabels[$nextStatusKey] ?? ucfirst(str_replace('_', ' ', $nextStatusKey))) : null;
    $formatDateTime = function ($value) {
        try {
            $carbon = $value ? \Illuminate\Support\Carbon::parse($value) : null;
            return $carbon ? $carbon->format('M j, Y g:i A') : null;
        } catch (\Throwable $e) {
            return null;
        }
    };
    $orderId = data_get($order, 'id');
    $orderNumber = data_get($order, 'order_number', $orderId ? '#' . $orderId : 'Order');
    $customerName = data_get($order, 'customer.full_name')
        ?? trim((data_get($order, 'customer.first_name') ?? '') . ' ' . (data_get($order, 'customer.last_name') ?? ''))
        ?? 'Guest customer';
    $placedDateDisplay = $formatDateTime(data_get($order, 'order_date'));
    $lastUpdatedDisplay = $formatDateTime(data_get($order, 'updated_at'));
@endphp

<main class="order-status-shell">
    <a href="{{ route('staff.orders.summary', $order->id) }}" class="back-link">
        <i class="fi fi-rr-arrow-left"></i>
        Back to Order Details
    </a>

    <section class="order-status-card">
        <header class="order-status-card__header">
            <div>
                <h1>Manage Order Status</h1>
                <p class="order-status-card__subtitle">Update the status, tracking information, and notes for order {{ $orderNumber }}</p>
            </div>
            <span class="order-stage-chip order-stage-chip--{{ $currentChipModifier }}">
                {{ $currentStatusLabel }}
            </span>
        </header>

        <div class="order-status-meta">
            <div class="order-status-meta__item">
                <span class="order-status-meta__label">Customer</span>
                <span class="order-status-meta__value">{{ $customerName }}</span>
            </div>
            <div class="order-status-meta__item">
                <span class="order-status-meta__label">Order Date</span>
                <span class="order-status-meta__value">{{ $placedDateDisplay ?: 'Unknown' }}</span>
            </div>
            <div class="order-status-meta__item">
                <span class="order-status-meta__label">Last Updated</span>
                <span class="order-status-meta__value">{{ $lastUpdatedDisplay ?: 'Never' }}</span>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="status-alert status-alert--success" role="alert" aria-live="polite">
            <i class="fi fi-rr-check"></i>
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="status-alert status-alert--danger" role="alert" aria-live="polite">
            <i class="fi fi-rr-exclamation-triangle"></i>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($currentStatus === 'cancelled')
        <div class="status-alert status-alert--danger" role="alert">
            <i class="fi fi-rr-exclamation-triangle"></i>
            <span>This order has been cancelled and cannot be modified.</span>
        </div>
    @endif

    <section class="status-progress-card">
        <header class="status-progress-card__header">
            <div>
                <h2>Order Progress</h2>
                <p>Current status and workflow progression</p>
            </div>
        </header>
        <ol class="status-tracker" aria-label="Order progress">
            @php
                $statusFlow = $statusFlow ?? ['pending', 'in_production', 'confirmed', 'to_receive', 'completed'];
            @endphp
            @foreach($statusFlow as $index => $statusKey)
                @php
                    $statusLabel = $statusLabels[$statusKey] ?? ucfirst(str_replace('_', ' ', $statusKey));
                    $isDone = $flowIndex !== false && $index < $flowIndex;
                    $isCurrent = $flowIndex !== false && $index === $flowIndex;
                    $isUpcoming = $flowIndex !== false && $index > $flowIndex;
                    $itemClasses = [];
                    if ($isDone) $itemClasses[] = 'status-tracker__item--done';
                    if ($isCurrent) $itemClasses[] = 'status-tracker__item--current';
                    if ($isUpcoming) $itemClasses[] = 'status-tracker__item--upcoming';
                @endphp
                <li class="status-tracker__item {{ implode(' ', $itemClasses) }}">
                    <div class="status-tracker__marker" aria-current="{{ $isCurrent ? 'step' : 'false' }}">
                        @if($isDone)
                            <i class="fi fi-rr-check"></i>
                        @else
                            <span class="status-tracker__number">{{ $index + 1 }}</span>
                        @endif
                    </div>
                    <div class="status-tracker__line" aria-hidden="true"></div>
                    <h3 class="status-tracker__title">{{ $statusLabel }}</h3>
                    @if($isCurrent && $nextStatusLabel)
                        <div class="status-tracker__content">
                            <p class="status-tracker__subtitle">Next: {{ $nextStatusLabel }}</p>
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>
    </section>

    @if($currentStatus !== 'cancelled')
    <form method="POST" action="{{ route('staff.orders.status.update', $order) }}" class="status-form-card status-form">
        @csrf
        @method('PUT')

        <div class="form-row">
            <label for="status">Order Status</label>
            <select name="status" id="status" required>
                @foreach($statusLabels as $value => $label)
                    <option value="{{ $value }}" {{ old('status', $currentStatus) === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            <p class="hint">Update the current status of this order</p>
        </div>

        <div class="form-row">
            <button type="submit">
                <i class="fi fi-rr-check"></i>
                Update Order Status
            </button>
        </div>
    </form>
    @endif
</main>
@endsection
