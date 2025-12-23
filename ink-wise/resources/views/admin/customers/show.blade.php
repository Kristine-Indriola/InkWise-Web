@extends('layouts.admin')

@section('title', 'Customer Details')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-css/customer.css') }}">
    <style>
        .customer-detail-page {
            padding: 2rem;
            background: #f8fafc;
            min-height: 100vh;
        }
        .customer-header {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            border-radius: 12px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.12);
            display: flex;
            align-items: center;
            gap: 2rem;
            position: relative;
            overflow: hidden;
        }
        .customer-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 400px;
            height: 100%;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.1) 0%, transparent 100%);
            border-radius: 50%;
            transform: translateX(30%);
        }
        .customer-avatar-large {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            object-fit: cover;
            border: 3px solid rgba(14, 165, 233, 0.3);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            position: relative;
            z-index: 1;
        }
        .customer-info {
            position: relative;
            z-index: 1;
            flex: 1;
        }
        .customer-info h1 {
            margin: 0 0 0.75rem 0;
            font-size: 1.875rem;
            color: #ffffff;
            font-weight: 600;
            letter-spacing: -0.025em;
        }
        .customer-meta {
            display: flex;
            gap: 1.5rem;
            color: rgba(226, 232, 240, 0.9);
            margin-top: 0.75rem;
            flex-wrap: wrap;
            font-size: 0.875rem;
        }
        .customer-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0;
        }
        .customer-meta i {
            color: #0ea5e9;
            font-size: 0.875rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: #ffffff;
            border-radius: 10px;
            padding: 1.75rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
            border-top: 3px solid #e2e8f0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        .stat-card:hover {
            box-shadow: 0 8px 16px rgba(15, 23, 42, 0.1);
            transform: translateY(-4px);
            border-top-color: #0ea5e9;
        }
        .stat-card h3 {
            font-size: 0.75rem;
            color: #64748b;
            margin: 0 0 0.75rem 0;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.1em;
        }
        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .stat-card.success {
            border-top-color: #10b981;
        }
        .stat-card.success .value {
            color: #059669;
        }
        .stat-card.warning {
            border-top-color: #f59e0b;
        }
        .stat-card.warning .value {
            color: #d97706;
        }
        .stat-card.danger {
            border-top-color: #ef4444;
        }
        .stat-card.danger .value {
            color: #dc2626;
        }
        .stat-card.info {
            border-top-color: #0ea5e9;
        }
        .stat-card.info .value {
            color: #0284c7;
        }
        .section-card {
            background: #ffffff;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
            border: 1px solid #e2e8f0;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.75rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f5f9;
        }
        .section-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.625rem;
            letter-spacing: -0.025em;
        }
        .section-title i {
            color: #0ea5e9;
            font-size: 1.125rem;
        }
        .order-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .order-item {
            padding: 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
            border-radius: 6px;
            margin-bottom: 0.5rem;
        }
        .order-item:hover {
            background: #f8fafc;
            transform: translateX(4px);
            border-left: 3px solid #0ea5e9;
            padding-left: calc(1.25rem - 3px);
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .order-number {
            font-weight: 700;
            color: #0f172a;
            font-size: 1rem;
            letter-spacing: -0.025em;
        }
        .order-date {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.25rem;
            font-weight: 500;
        }
        .order-amount {
            font-weight: 700;
            color: #0f172a;
            font-size: 1.05rem;
        }
        .order-status {
            display: inline-block;
            padding: 0.375rem 0.875rem;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            margin-top: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .status-completed {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .status-pending {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .status-processing, .status-in_production, .status-confirmed {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        .status-to_receive {
            background: #e0e7ff;
            color: #4338ca;
            border: 1px solid #c7d2fe;
        }
        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .rating-item {
            padding: 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 0.5rem;
            transition: all 0.2s ease;
            border-radius: 6px;
            background: #fefefe;
        }
        .rating-item:hover {
            background: #fffbeb;
            border-left: 3px solid #fbbf24;
            padding-left: calc(1.25rem - 3px);
        }
        .rating-item:last-child {
            border-bottom: none;
        }
        .rating-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.625rem;
        }
        .rating-stars {
            color: #f59e0b;
            font-size: 1.125rem;
            letter-spacing: 0.125rem;
        }
        .rating-date {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 600;
        }
        .rating-comment {
            color: #475569;
            margin-top: 0.5rem;
            font-style: italic;
            line-height: 1.7;
            font-size: 0.9rem;
        }
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #0f172a;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.15);
            letter-spacing: 0.025em;
        }
        .back-button:hover {
            background: #1e293b;
            transform: translateX(-2px);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2);
            color: white;
        }
        .empty-state {
            text-align: center;
            padding: 3.5rem;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.2;
            color: #cbd5e1;
        }
        .empty-state p {
            font-size: 0.9rem;
            margin: 0;
            color: #94a3b8;
            font-weight: 500;
        }
        .order-items-count {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 500;
        }
    </style>
@endpush

@section('content')
<main class="admin-page-shell customer-detail-page" role="main">
    <a href="{{ route('admin.customers.index') }}" class="back-button">
        <i class="fi fi-rr-arrow-left"></i>
        Back to Customers
    </a>

    <div class="customer-header">
        @php
            $profile = $customer;
            $avatar = $profile && $profile->photo
                ? \App\Support\ImageResolver::url($profile->photo)
                : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&size=120&background=4f46e5&color=fff&bold=true';
            $fullName = collect([
                $profile->first_name ?? null,
                $profile->middle_name ?? null,
                $profile->last_name ?? null,
            ])->filter()->implode(' ');
        @endphp
        <img src="{{ $avatar }}" 
             alt="{{ $fullName ?: $user->name }}" 
             class="customer-avatar-large">
        <div class="customer-info">
            <h1>{{ $fullName ?: $user->name }}</h1>
            <div class="customer-meta">
                <span><i class="fi fi-rr-envelope"></i> {{ $user->email }}</span>
                @if($customer && $customer->contact_number)
                    <span><i class="fi fi-rr-phone-call"></i> {{ $customer->contact_number }}</span>
                @endif
                <span><i class="fi fi-rr-calendar"></i> Joined {{ $user->created_at->format('M d, Y') }}</span>
            </div>
            @if($user->address)
                @php
                    $address = $user->address;
                    $addressParts = collect([
                        $address->street ?? null,
                        $address->barangay ?? null,
                        $address->city ?? null,
                        $address->province ?? null,
                    ])->filter()->implode(', ');
                @endphp
                @if($addressParts)
                    <div class="customer-meta" style="margin-top: 0.5rem;">
                        <span><i class="fi fi-rr-marker"></i> {{ $addressParts }}</span>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Orders</h3>
            <div class="value">{{ $totalOrders }}</div>
        </div>
        <div class="stat-card success">
            <h3>Completed</h3>
            <div class="value">{{ $completedOrders }}</div>
        </div>
        <div class="stat-card warning">
            <h3>Pending</h3>
            <div class="value">{{ $pendingOrders }}</div>
        </div>
        <div class="stat-card info">
            <h3>Total Spent</h3>
            <div class="value">₱{{ number_format($totalSpent, 2) }}</div>
        </div>
        <div class="stat-card" style="border-left-color: #fbbf24;">
            <h3>Average Rating</h3>
            <div class="value">{{ number_format($averageRating, 1) }} <span style="font-size: 1.5rem; color: #fbbf24;">⭐</span></div>
        </div>
    </div>

    <div class="section-card">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fi fi-rr-shopping-cart"></i> Purchase History
            </h2>
        </div>
        @if($orders->count() > 0)
            <ul class="order-list">
                @foreach($orders as $order)
                    <li class="order-item">
                        <div>
                            <div class="order-number">#{{ $order->order_number }}</div>
                            <div class="order-date">
                                {{ $order->created_at->format('M d, Y g:i A') }}
                            </div>
                            <div class="order-items-count">
                                {{ $order->items->count() }} {{ Str::plural('item', $order->items->count()) }}
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div class="order-amount">₱{{ number_format($order->total_amount, 2) }}</div>
                            <span class="order-status status-{{ $order->status }}">
                                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                            </span>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="empty-state">
                <i class="fi fi-rr-shopping-cart"></i>
                <p>No orders yet</p>
            </div>
        @endif
    </div>

    <div class="section-card">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fi fi-rr-star"></i> Ratings & Reviews
            </h2>
        </div>
        @if($ratings->count() > 0)
            <div>
                @foreach($ratings as $rating)
                    <div class="rating-item">
                        <div class="rating-header">
                            <div class="rating-stars">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= $rating->rating)
                                        ⭐
                                    @else
                                        ☆
                                    @endif
                                @endfor
                            </div>
                            <div class="rating-date">
                                {{ $rating->created_at->format('M d, Y') }}
                            </div>
                        </div>
                        @if($rating->review)
                            <div class="rating-comment">"{{ $rating->review }}"</div>
                        @endif
                        <div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.5rem;">
                            Order #{{ $rating->order->order_number ?? 'N/A' }}
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <i class="fi fi-rr-star"></i>
                <p>No ratings yet</p>
            </div>
        @endif
    </div>
</main>
@endsection
