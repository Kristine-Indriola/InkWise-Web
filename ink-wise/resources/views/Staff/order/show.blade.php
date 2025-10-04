@extends('layouts.staffapp')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@flaticon/flaticon-uicons/css/all/all.css">
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff-css/order-list.css') }}">
@endpush

@section('title', 'Order Details')

@section('content')
<main class="materials-page admin-page-shell staff-order-list-page" role="main">
    <header class="page-header">
        <div>
            <h1 class="page-title">Order Details</h1>
            <p class="page-subtitle">Detailed information for order #{{ $order->id }}</p>
        </div>
        <div class="page-header__quick-actions">
            <a href="{{ route('staff.order_list.index') }}" class="pill-link is-active" aria-label="Back to order list">
                <i class="fi fi-rr-arrow-left"></i>&nbsp;Back to List
            </a>
        </div>
    </header>

    @if(session('success'))
        <div class="alert staff-order-list-alert" role="alert" aria-live="polite">
            ✅ {{ session('success') }}
        </div>
    @endif

    <div class="alert alert-info" role="alert">
        <i class="fi fi-rr-info"></i>
        <span>This is sample order data for demonstration purposes.</span>
    </div>

    <section class="order-details" aria-label="Order details">
        <div class="details-wrapper">
            <div class="details-card">
                <h3 class="details-title">Order Information</h3>
                <div class="details-grid">
                    <div class="detail-item">
                        <span class="detail-label">Order ID</span>
                        <span class="detail-value">#{{ $order->id }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Customer</span>
                        <span class="detail-value">{{ $order->customer->name ?? 'Unknown Customer' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Items</span>
                        <span class="detail-value">{{ $order->items ?? 'Various items' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Quantity</span>
                        <span class="detail-value">{{ $order->items_count ?? 1 }} item(s)</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Total Amount</span>
                        <span class="detail-value">₱{{ number_format($order->total ?? 0, 2) }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status</span>
                        <span class="detail-value">
                            <span class="status-label {{ $order->status ?? 'pending' }}">{{ ucfirst($order->status ?? 'pending') }}</span>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Order Date</span>
                        <span class="detail-value">{{ ($order->created_at ?? now())->format('M d, Y H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const alertBanner = document.querySelector('.staff-order-list-alert');
            if (alertBanner) {
                setTimeout(function () {
                    alertBanner.classList.add('is-dismissing');
                    setTimeout(() => alertBanner.remove(), 600);
                }, 4000);
            }
        });
    </script>
@endpush