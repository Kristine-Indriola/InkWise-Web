@extends('layouts.admin')

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

    @media (max-width: 600px) {
        .order-status-shell {
            padding: 24px 12px 64px;
        }
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

    @media (max-width: 720px) {
        .order-status-card__header {
            flex-direction: column;
        }
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

    .order-stage-chip--completed {
        background: #e0f2fe;
        color: #0369a1;
    }

    .order-stage-chip--cancelled {
        background: #fee2e2;
        color: #b91c1c;
    }

    .order-stage-chip--draft,
    .order-stage-chip--new-order {
        background: #f3f4f6;
        color: #1f2937;
    }

    .order-status-meta {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 14px;
    }

    @media (max-width: 600px) {
        .order-status-meta {
            grid-template-columns: 1fr;
        }
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

    @media (max-width: 720px) {
        .status-progress-card__header {
            flex-direction: column;
            align-items: flex-start;
        }

        .status-progress-card__actions {
            margin-top: 12px;
        }
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

    @media (max-width: 720px) {
        .status-tracker {
            flex-direction: column;
            gap: 16px;
            padding: 0;
        }

        .status-tracker__item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            text-align: left;
            padding: 0;
        }
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

    @media (max-width: 720px) {
        .status-tracker__marker {
            margin: 0;
        }
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

    @media (max-width: 720px) {
        .status-tracker__line {
            display: none;
        }
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

    @media (max-width: 720px) {
        .status-tracker__content {
            margin-top: 0;
        }
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
        background: #f3f4f6;
        border-color: #d1d5db;
        color: #9ca3af;
    }

    .status-info-grid {
        display: grid;
        gap: 16px;
    }

    @media (min-width: 900px) {
        .status-info-grid {
            grid-template-columns: 2fr 1fr;
        }
    }

    .status-info-card__title {
        margin: 0 0 12px;
        font-size: 16px;
        font-weight: 600;
        color: #111827;
    }

    .status-info-card dl {
        margin: 0;
        display: grid;
        gap: 12px;
    }

    .status-info-card dt {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6b7280;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .status-info-card dd {
        margin: 0;
        font-size: 14px;
        font-weight: 600;
        color: #111827;
        word-break: break-word;
    }

    .status-info-card__text {
        margin: 0;
        color: #374151;
        font-size: 14px;
        line-height: 1.6;
    }

    .status-info-card__empty {
        color: #9ca3af;
        font-size: 14px;
        line-height: 1.6;
        margin: 0;
    }

    .status-form-card {
        padding: 24px;
    }


    .rating-summary {
        display: grid;
        gap: 12px;
    }

    .rating-summary__stars {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #d1d5db;
        font-weight: 600;
        font-size: 14px;
    }

    .rating-summary__stars span {
        display: inline-flex;
        color: inherit;
    }

    .rating-summary__stars span.is-filled {
        color: #f59e0b;
    }

    .rating-summary__stars svg {
        width: 22px;
        height: 22px;
    }

    .rating-summary__stars strong {
        color: #111827;
    }

    .rating-summary__timestamp {
        color: #6b7280;
        font-size: 12px;
    }

    .rating-summary__comment {
        margin: 0;
        font-size: 14px;
        line-height: 1.6;
        color: #1f2937;
        background: #f9fafb;
        border-radius: 10px;
        padding: 12px 14px;
        border: 1px solid #e5e7eb;
    }

    .rating-summary__tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .rating-summary__tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 999px;
        background: #eef2ff;
        color: #3730a3;
        font-size: 12px;
        font-weight: 600;
    }

    .rating-summary__media {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .rating-summary__media a {
        display: inline-flex;
    }

    .rating-summary__media img {
        width: 64px;
        height: 64px;
        object-fit: cover;
        border-radius: 12px;
        box-shadow: 0 6px 12px -10px rgba(15, 23, 42, 0.45);
        border: 1px solid #e5e7eb;
    }
    .status-form {
        display: grid;
        gap: 20px;
    }

    .status-form label {
        font-weight: 600;
        color: #111827;
        display: block;
        margin-bottom: 6px;
    }

    .status-form select,
    .status-form input,
    .status-form textarea {
        width: 100%;
        padding: 10px 12px;
        border-radius: 8px;
        border: 1px solid #d1d5db;
        background: #f9fafb;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .status-form select:focus,
    .status-form input:focus,
    .status-form textarea:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        background: #ffffff;
    }

    .status-form .hint {
        font-size: 13px;
        color: #6b7280;
        margin-top: 4px;
        display: block;
    }

    .status-form .form-row {
        display: grid;
        gap: 16px;
    }

    .status-form .form-row.is-split {
        gap: 16px;
    }

    @media (min-width: 700px) {
        .status-form .form-row.is-split {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    .status-form button {
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

    .status-form button:hover {
        background: #4338ca;
    }

    .status-form button:focus-visible {
        outline: 2px solid #6366f1;
        outline-offset: 3px;
    }

    @media (max-width: 600px) {
        .status-form button {
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

    .status-alert {
        padding: 14px 18px;
        border-radius: 10px;
        font-size: 14px;
        border: 1px solid transparent;
    }

    .status-alert--success {
        background: #ecfdf5;
        border-color: #34d399;
        color: #047857;
    }

    .status-alert--danger {
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
    $statusFlow = array_values(array_filter(
        $normalizeToArray($statusFlow ?? []),
        function ($value) {
            return is_string($value) && $value !== '';
        }
    ));

    // Ensure "in_production" appears before "processing" for ordering-sensitive UI elements.
    $processingIndex = array_search('processing', $statusFlow, true);
    $productionIndex = array_search('in_production', $statusFlow, true);

    if ($processingIndex !== false && $productionIndex !== false && $productionIndex > $processingIndex) {
        $statusFlow[$processingIndex] = 'in_production';
        $statusFlow[$productionIndex] = 'processing';
    }

    $statusOptions = $normalizeToArray($statusOptions ?? []);

    if (!empty($statusFlow) && !empty($statusOptions)) {
        $orderedOptions = [];
        foreach ($statusFlow as $statusKey) {
            if (array_key_exists($statusKey, $statusOptions)) {
                $orderedOptions[$statusKey] = $statusOptions[$statusKey];
            }
        }
        foreach ($statusOptions as $key => $label) {
            if (!array_key_exists($key, $orderedOptions)) {
                $orderedOptions[$key] = $label;
            }
        }
        $statusOptions = $orderedOptions;
    }

    $statusLabels = $statusOptions;
    $formatStatusLabel = static function ($statusKey) use ($statusLabels) {
        $label = $statusLabels[$statusKey] ?? null;

        if (is_string($label) && $label !== '') {
            return $label;
        }

        return \Illuminate\Support\Str::title(str_replace('_', ' ', (string) $statusKey));
    };

    $currentStatus = old('status', data_get($order, 'status', 'pending'));
    $flowIndex = array_search($currentStatus, $statusFlow, true);
    $trackingNumber = old('tracking_number', $metadata['tracking_number'] ?? '');
    $statusNote = old('internal_note', $metadata['status_note'] ?? '');
    $previousUrl = url()->previous();
    if ($previousUrl === url()->current()) {
        $previousUrl = route('admin.orders.index');
    }
    $currentChipModifier = str_replace('_', '-', $currentStatus);
    $currentStatusLabel = $formatStatusLabel($currentStatus);
    $nextStatusKey = $flowIndex !== false && $flowIndex < count($statusFlow) - 1 ? $statusFlow[$flowIndex + 1] : null;
    $nextStatusLabel = $nextStatusKey ? $formatStatusLabel($nextStatusKey) : null;
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
    $orderId = data_get($order, 'id');
    $orderNumber = data_get($order, 'order_number', $orderId ? '#' . $orderId : 'Order');
    $customer = data_get($order, 'customer') ?: data_get($order, 'customerOrder.customer');
    $customerName = trim((string) (data_get($customer, 'full_name')
        ?? trim((data_get($customer, 'first_name') ?? '') . ' ' . (data_get($customer, 'last_name') ?? ''))))
        ?: (data_get($customer, 'name') ?? 'Guest customer');
    $placedDateDisplay = $formatDateTime(data_get($order, 'order_date'));
    $lastUpdatedDisplay = $formatDateTime(data_get($order, 'updated_at'));
    $ratingRecord = data_get($order, 'rating');
    $ratingValue = null;
    $ratingValueDisplay = null;
    $ratingReview = '';
    $ratingTags = [];
    $ratingMedia = [];
    $ratingSubmittedDisplay = null;

    if ($ratingRecord) {
        $ratingRaw = data_get($ratingRecord, 'rating');
        if (is_numeric($ratingRaw)) {
            $ratingValue = max(0, min(5, (float) $ratingRaw));
            $ratingValueDisplay = rtrim(rtrim(number_format($ratingValue, 1), '0'), '.');
        }

        $ratingReview = trim((string) data_get($ratingRecord, 'review', ''));
        $ratingMedia = collect(data_get($ratingRecord, 'photos', []))
            ->filter(fn ($url) => is_string($url) && trim($url) !== '')
            ->values()
            ->all();
        $ratingSubmittedDisplay = $formatDateTime(
            data_get($ratingRecord, 'submitted_at', data_get($ratingRecord, 'created_at'))
        );
    } elseif (!empty($metadata)) {
        $ratingRaw = $metadata['rating'] ?? null;
        if (is_numeric($ratingRaw)) {
            $ratingValue = max(0, min(5, (float) $ratingRaw));
            $ratingValueDisplay = rtrim(rtrim(number_format($ratingValue, 1), '0'), '.');
        }

        $ratingReview = trim((string) ($metadata['review'] ?? ''));
        $ratingTags = array_values(array_filter(array_map(function ($tag) {
            return trim(is_string($tag) ? $tag : (string) $tag);
        }, $normalizeToArray($metadata['review_tags'] ?? [])), function ($tag) {
            return $tag !== '';
        }));
        $ratingMedia = array_values(array_filter(array_map(function ($url) {
            return trim(is_string($url) ? $url : (string) $url);
        }, $normalizeToArray($metadata['review_media'] ?? [])), function ($url) {
            return $url !== '';
        }));
        $ratingSubmittedDisplay = $formatDateTime($metadata['reviewed_at'] ?? ($metadata['rating_submitted_at'] ?? null));
    }
@endphp

<main class="order-status-shell">
    <a href="{{ $previousUrl }}" class="back-link">
        <span aria-hidden="true">&larr;</span>
        Back to order
    </a>

    <section class="order-status-card">
        <header class="order-status-card__header">
            <div>
                <h1>Manage order status</h1>
                <p class="order-status-card__subtitle">

                </p>
            </div>
            <span class="order-stage-chip order-stage-chip--{{ $currentChipModifier }}">
                {{ $formatStatusLabel($currentStatus) }}
            </span>
        </header>
        <div class="order-status-meta">
            <div class="order-status-meta__item">
                <span class="order-status-meta__label">Order number</span>
                <span class="order-status-meta__value">{{ $orderNumber }}</span>
            </div>
            <div class="order-status-meta__item">
                <span class="order-status-meta__label">Customer</span>
                <span class="order-status-meta__value">{{ $customerName }}</span>
            </div>
            <div class="order-status-meta__item">
                <span class="order-status-meta__label">Placed on</span>
                <span class="order-status-meta__value">{{ $placedDateDisplay ?? 'Not available' }}</span>
            </div>
            <div class="order-status-meta__item">
                <span class="order-status-meta__label">Current stage</span>
                <span class="order-status-meta__value">
                    {{ $formatStatusLabel($currentStatus) }}
                </span>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="status-alert status-alert--success" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="status-alert status-alert--danger" role="alert">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($currentStatus === 'cancelled')
        <div class="status-alert status-alert--danger" role="alert">
            <strong>Order Cancelled:</strong> This order has been cancelled by the customer and cannot be updated. Status changes are locked for cancelled orders.
        </div>
    @endif

    <section class="status-progress-card">
        <header class="status-progress-card__header">
            <div>
                <h2>Progress tracker</h2>
            </div>
            <div class="status-progress-card__actions">
                <span class="order-stage-chip order-stage-chip--{{ $currentChipModifier }}">
                    {{ $formatStatusLabel($currentStatus) }}
                </span>
            </div>
        </header>
        <ol class="status-tracker" aria-label="Order progress">
            @foreach($statusFlow as $index => $statusKey)
                @php
                    $stateClass = 'status-tracker__item--upcoming';
                    if ($currentStatus === 'cancelled') {
                        $stateClass = 'status-tracker__item--disabled';
                    } elseif ($flowIndex !== false) {
                        if ($index < $flowIndex) {
                            $stateClass = 'status-tracker__item--done';
                        } elseif ($index === $flowIndex) {
                            $stateClass = 'status-tracker__item--current';
                        }
                    }
                @endphp
                <li class="status-tracker__item {{ $stateClass }}">
                    <div class="status-tracker__marker">
                        @if($stateClass === 'status-tracker__item--done')
                            <span class="status-tracker__icon">✓</span>
                        @else
                            <span class="status-tracker__number">{{ $index + 1 }}</span>
                        @endif
                    </div>
                    @if(!$loop->last)
                        <span class="status-tracker__line" aria-hidden="true"></span>
                    @endif
                    <div class="status-tracker__content">
                        <p class="status-tracker__title">
                            {{ $formatStatusLabel($statusKey) }}
                        </p>
                        <p class="status-tracker__subtitle">
                            @switch($statusKey)
                                @case('draft')
                                    Order submitted and awaiting admin review.
                                    @break
                                @case('pending')
                                    Order received.
                                    @break 
                                @case('processing') 
                                    Team is preparing assets before full production starts.
                                    @break
                                @case('in_production')
                                    Team is preparing the invitation or giveaway items.
                                    @break
                                @case('confirmed')
                                    Items are packed and ready for customer pickup at store.
                                    @break
                                @case('completed')
                                    Order picked up and marked as finished.
                                    @break
                                @default
                                    Status update in progress.
                            @endswitch
                        </p>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>

    @if($currentStatus !== 'cancelled')
    <form method="POST" action="{{ route('admin.orders.status.update', $order) }}" class="status-form-card status-form">
        @csrf
        @method('PUT')

        <div class="form-row">
            <div>
                <label for="status">Order status</label>
                <select id="status" name="status" required>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" {{ $currentStatus === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <span class="hint">Choose the stage that matches the order&apos;s progress.</span>
            </div>
        </div>


        <button type="submit">Save status update</button>
    </form>
    @else
    <div class="status-form-card" style="text-align: center; padding: 40px 24px;">
        <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">🚫</div>
        <h3 style="margin: 0 0 8px; color: #111827; font-size: 18px; font-weight: 600;">Status Updates Disabled</h3>
        <p style="margin: 0; color: #6b7280; font-size: 14px; line-height: 1.6;">
            This order has been cancelled by the customer. Status updates are not allowed for cancelled orders.
        </p>
    </div>
    @endif

    
    <section class="status-info-grid">
        <article class="status-info-card">
            <h2 class="status-info-card__title">Order details</h2>
            <dl>
                <dt>Order number</dt>
                <dd>{{ $orderNumber }}</dd>
                <dt>Customer</dt>
                <dd>{{ $customerName }}</dd>
                <dt>Placed on</dt>
                <dd>{{ $placedDateDisplay ?? 'Not available' }}</dd>
                <dt>Last updated</dt>
                <dd>{{ $lastUpdatedDisplay ?? 'Not available' }}</dd>
                @if($statusNote)
                <dt>Internal note</dt>
                <dd>{{ $statusNote }}</dd>
                @endif
            </dl>
        </article>
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
            status: @json($currentStatus),
            statusLabel: @json($currentStatusLabel),
            consumedBy: []
        };

        payload.timestamp = Date.now();

        try {
            localStorage.setItem('inkwiseOrderStatusUpdate', JSON.stringify(payload));
        } catch (error) {
            console.warn('Unable to persist order status update for table sync.', error);
        }
    })();
</script>
@endpush
