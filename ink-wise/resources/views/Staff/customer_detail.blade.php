@extends('layouts.staffapp')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@flaticon/flaticon-uicons/css/all/all.css">
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff-css/customer-profiles.css') }}">
    <style>
        .customer-detail-page {
            padding: 2rem;
        }
        .customer-header {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        .customer-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #f3f4f6;
        }
        .customer-info h1 {
            margin: 0 0 0.5rem 0;
            font-size: 1.75rem;
            color: #111827;
        }
        .customer-meta {
            display: flex;
            gap: 1.5rem;
            color: #6b7280;
            margin-top: 0.5rem;
        }
        .customer-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid #4f46e5;
        }
        .stat-card h3 {
            font-size: 0.875rem;
            color: #6b7280;
            margin: 0 0 0.5rem 0;
            text-transform: uppercase;
            font-weight: 600;
        }
        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: #111827;
        }
        .section-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f3f4f6;
        }
        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }
        .order-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .order-item {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.2s;
        }
        .order-item:hover {
            background: #f9fafb;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .order-number {
            font-weight: 600;
            color: #4f46e5;
        }
        .order-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        .status-processing, .status-in_production {
            background: #dbeafe;
            color: #1e40af;
        }
        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }
        .rating-item {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
        }
        .rating-item:last-child {
            border-bottom: none;
        }
        .rating-stars {
            color: #fbbf24;
            font-size: 1.25rem;
        }
        .rating-comment {
            color: #6b7280;
            margin-top: 0.5rem;
            font-style: italic;
        }
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #f3f4f6;
            color: #374151;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s;
            margin-bottom: 1.5rem;
        }
        .back-button:hover {
            background: #e5e7eb;
            transform: translateX(-4px);
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #9ca3af;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
@endpush

@section('title', 'Customer Details')

@section('content')
<main class="materials-page admin-page-shell customer-detail-page" role="main">
    <a href="{{ route('staff.customer_profile') }}" class="back-button">
        <i class="fi fi-rr-arrow-left"></i>
        Back to Customers
    </a>

    <div class="customer-header">
        <img src="{{ $user->profile_picture ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&size=100&background=4f46e5&color=fff' }}" 
             alt="{{ $user->name }}" 
             class="customer-avatar">
        <div class="customer-info">
            <h1>{{ $user->name }}</h1>
            <div class="customer-meta">
                <span><i class="fi fi-rr-envelope"></i> {{ $user->email }}</span>
                @if($customer && $customer->contact_number)
                    <span><i class="fi fi-rr-phone-call"></i> {{ $customer->contact_number }}</span>
                @endif
                <span><i class="fi fi-rr-calendar"></i> Joined {{ $user->created_at->format('M d, Y') }}</span>
            </div>
            @if($customer && $customer->address)
                <div class="customer-meta" style="margin-top: 0.5rem;">
                    <span><i class="fi fi-rr-marker"></i> {{ $customer->address }}</span>
                </div>
            @endif
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Orders</h3>
            <div class="value">{{ $totalOrders }}</div>
        </div>
        <div class="stat-card" style="border-left-color: #10b981;">
            <h3>Completed Orders</h3>
            <div class="value">{{ $completedOrders }}</div>
        </div>
        <div class="stat-card" style="border-left-color: #f59e0b;">
            <h3>Total Spent</h3>
            <div class="value">₱{{ number_format($totalSpent, 2) }}</div>
        </div>
        <div class="stat-card" style="border-left-color: #fbbf24;">
            <h3>Average Rating</h3>
            <div class="value">{{ number_format($averageRating, 1) }} <span style="font-size: 1.25rem;">⭐</span></div>
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
                            <div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem;">
                                {{ $order->created_at->format('M d, Y g:i A') }}
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-weight: 600; color: #111827;">₱{{ number_format($order->total_amount, 2) }}</div>
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
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div>
                                <div class="rating-stars">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= $rating->rating)
                                            ⭐
                                        @else
                                            ☆
                                        @endif
                                    @endfor
                                </div>
                                @if($rating->review)
                                    <div class="rating-comment">"{{ $rating->review }}"</div>
                                @endif
                            </div>
                            <div style="font-size: 0.875rem; color: #6b7280;">
                                {{ $rating->created_at->format('M d, Y') }}
                            </div>
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
