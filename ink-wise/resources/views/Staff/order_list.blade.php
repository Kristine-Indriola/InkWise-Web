@extends('layouts.staffapp')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@flaticon/flaticon-uicons/css/all/all.css">
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff-css/order-list.css') }}">
@endpush

@section('title', 'Order List')

@section('content')
@php
    $ordersCollection = $orders instanceof \Illuminate\Support\Collection ? $orders : collect($orders ?? []);
    $statusFilter = request('status');

    $pendingCount = $ordersCollection->filter(fn($order) => ($order->status ?? 'pending') === 'pending')->count();
    $completedCount = $ordersCollection->filter(fn($order) => ($order->status ?? 'pending') === 'completed')->count();
    $cancelledCount = $ordersCollection->filter(fn($order) => ($order->status ?? 'pending') === 'cancelled')->count();
@endphp

<main class="materials-page admin-page-shell staff-order-list-page" role="main">
    <header class="page-header">
        <div>
            <h1 class="page-title">Order List</h1>
            <p class="page-subtitle">Track and manage customer orders.</p>
        </div>
    </header>

    @if(session('success'))
        <div class="alert staff-order-list-alert" role="alert" aria-live="polite">
            ✅ {{ session('success') }}
        </div>
    @endif

    <section class="summary-grid" aria-label="Order summary">
        <a href="{{ route('staff.order_list.index', ['status' => 'all']) }}" class="summary-card {{ in_array($statusFilter, [null, 'all'], true) ? 'is-active' : '' }}" aria-label="View all orders">
            <div class="summary-card-header">
                <span class="summary-card-label">Total Orders</span>
                <span class="summary-card-chip accent">All</span>
            </div>
            <span class="summary-card-value">{{ number_format($ordersCollection->count()) }}</span>
            <span class="summary-card-meta">Overall orders</span>
        </a>
        <a href="{{ route('staff.order_list.index', ['status' => 'pending']) }}" class="summary-card summary-card--pending {{ $statusFilter === 'pending' ? 'is-active' : '' }}" aria-label="Filter pending orders">
            <div class="summary-card-header">
                <span class="summary-card-label">Pending</span>
                <span class="summary-card-chip warning">Action Needed</span>
            </div>
            <span class="summary-card-value">{{ number_format($pendingCount) }}</span>
            <span class="summary-card-meta">Awaiting processing</span>
        </a>
        <a href="{{ route('staff.order_list.index', ['status' => 'completed']) }}" class="summary-card summary-card--completed {{ $statusFilter === 'completed' ? 'is-active' : '' }}" aria-label="Filter completed orders">
            <div class="summary-card-header">
                <span class="summary-card-label">Completed</span>
                <span class="summary-card-chip success">Fulfilled</span>
            </div>
            <span class="summary-card-value">{{ number_format($completedCount) }}</span>
            <span class="summary-card-meta">Successfully delivered</span>
        </a>
        <a href="{{ route('staff.order_list.index', ['status' => 'cancelled']) }}" class="summary-card summary-card--cancelled {{ $statusFilter === 'cancelled' ? 'is-active' : '' }}" aria-label="Filter cancelled orders">
            <div class="summary-card-header">
                <span class="summary-card-label">Cancelled</span>
                <span class="summary-card-chip danger">Terminated</span>
            </div>
            <span class="summary-card-value">{{ number_format($cancelledCount) }}</span>
            <span class="summary-card-meta">Order cancellations</span>
        </a>
    </section>

    <section class="order-list-table" aria-label="Orders list">
        <div class="table-wrapper">
            <table class="table" role="grid">
                <thead>
                    <tr>
                        <th scope="col">Order ID</th>
                        <th scope="col">Customer</th>
                        <th scope="col">Items</th>
                        <th scope="col">Total (₱)</th>
                        <th scope="col">Status</th>
                        <th scope="col">Date</th>
                        <th scope="col" class="actions-col text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ordersCollection as $order)
                        @php
                            $status = $order->status ?? 'pending';
                            $statusClass = match($status) {
                                'pending' => 'pending',
                                'completed' => 'completed',
                                'cancelled' => 'cancelled',
                                default => 'pending'
                            };
                            $statusLabel = ucfirst($status);
                        @endphp
                        <tr>
                            <td>#{{ str_pad($order->id ?? 123, 5, '0', STR_PAD_LEFT) }}</td>
                            <td class="customer-name">{{ $order->customer->name ?? 'Unknown Customer' }}</td>
                            <td>{{ $order->items_count ?? 1 }} item(s)</td>
                            <td>₱{{ number_format($order->total ?? 0, 2) }}</td>
                            <td class="status-col">
                                <span class="status-label {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td>{{ ($order->created_at ?? now())->format('M d, Y') }}</td>
                            <td class="actions-col text-center">
                                <div class="order-actions">
                                    <a href="{{ route('staff.order_list.show', $order->id ?? 123) }}" class="btn btn-sm btn-primary" title="View order">
                                        <i class="fi fi-rr-eye"></i>
                                    </a>
                                    @if($status === 'pending')
                                        <form action="{{ route('staff.order_list.update', $order->id ?? 123) }}" method="POST" class="inline-form">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" class="btn btn-sm btn-success" title="Mark as completed">
                                                <i class="fi fi-rr-check"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="table-empty">No orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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
