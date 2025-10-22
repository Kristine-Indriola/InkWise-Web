@extends('layouts.staffapp')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@flaticon/flaticon-uicons/css/all/all.css">
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff-css/assigned-orders.css') }}">
@endpush

@section('content')
@php
    $searchTerm = $searchValue ?? request('search');
    $ordersCollection = method_exists($orders, 'getCollection') ? $orders->getCollection() : collect($orders);
    $statusOptions = [
        'pending' => 'Pending',
        'in_production' => 'In Production',
        'confirmed' => 'Confirmed',
        'to_receive' => 'To Receive',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];
@endphp

<main class="materials-page admin-page-shell staff-assigned-page" role="main">
    <header class="page-header">
        <div>
            <h1 class="page-title">Assigned Orders</h1>
            <p class="page-subtitle">Monitor orders in your queue and keep customers updated.</p>
        </div>
        <div class="page-header__quick-actions">
            <a href="{{ route('staff.dashboard') }}" class="pill-link" aria-label="Back to dashboard">
                <i class="fi fi-rr-house-chimney"></i>&nbsp;Dashboard
            </a>
            <a href="{{ route('staff.messages.index') }}" class="pill-link" aria-label="Open messages">
                <i class="fi fi-rr-comments"></i>&nbsp;Messages
            </a>
        </div>
    </header>

    @if(session('success'))
        <div class="alert staff-alert" role="alert" aria-live="polite">
            ✅ {{ session('success') }}
        </div>
    @endif

    <section class="materials-toolbar staff-assigned-toolbar" aria-label="Filter assigned orders">
        <form method="GET" action="{{ route('staff.assigned.orders') }}" class="staff-search-form">
            <div class="search-input">
                <span class="search-icon"><i class="fi fi-rr-search"></i></span>
                <input type="text" name="search" value="{{ $searchTerm }}" placeholder="Search by order #, customer, or product" class="form-control" aria-label="Search assigned orders">
            </div>
            <div class="staff-toolbar-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fi fi-rr-search"></i>
                    <span>Search</span>
                </button>
                @if(!empty($searchTerm))
                    <a href="{{ route('staff.assigned.orders') }}" class="btn btn-secondary" aria-label="Clear search">Clear</a>
                @endif
            </div>
        </form>
    </section>

    <section class="staff-table-section" aria-label="Assigned orders list">
        <div class="table-wrapper">
            <table class="table staff-orders-table" role="grid">
                <thead>
                    <tr>
                        <th scope="col">Order #</th>
                        <th scope="col">Customer</th>
                        <th scope="col">Product</th>
                        <th scope="col">Deadline</th>
                        <th scope="col" class="status-col">Status</th>
                        <th scope="col" class="actions-col text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        @php
                            $firstItem = $order->items->first();
                            $productName = $firstItem->product_name
                                ?? optional($firstItem->product)->name
                                ?? '—';

                            $dateNeeded = $order->date_needed
                                ? \Illuminate\Support\Carbon::parse($order->date_needed)
                                : null;
                            $orderDate = $order->order_date
                                ? \Illuminate\Support\Carbon::parse($order->order_date)
                                : null;
                            $displayDate = $dateNeeded ?? $orderDate;

                            $statusMeta = [
                                'pending' => ['label' => 'Pending', 'class' => 'status-pending'],
                                'confirmed' => ['label' => 'Confirmed', 'class' => 'status-confirmed'],
                                'to_receive' => ['label' => 'To Receive', 'class' => 'status-confirmed'],
                                'in_production' => ['label' => 'In Production', 'class' => 'status-production'],
                                'completed' => ['label' => 'Completed', 'class' => 'status-completed'],
                                'cancelled' => ['label' => 'Cancelled', 'class' => 'status-cancelled'],
                            ][$order->status] ?? [
                                'label' => ucfirst(str_replace('_', ' ', $order->status)),
                                'class' => 'status-default',
                            ];
                        @endphp
                        <tr>
                            <td data-title="Order #">
                                <span class="staff-order-number">#{{ $order->order_number }}</span>
                            </td>
                            <td data-title="Customer">{{ $order->customer->name ?? '—' }}</td>
                            <td data-title="Product" class="staff-product-name">{{ $productName }}</td>
                            <td data-title="Deadline">
                                <span class="staff-deadline-chip">{{ $displayDate ? $displayDate->format('M d, Y') : '—' }}</span>
                            </td>
                            <td data-title="Status" class="status-col">
                                <span class="staff-status-chip {{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span>
                            </td>
                            <td data-title="Actions" class="actions-col">
                                <div class="staff-table-actions">
                                    <form action="{{ route('staff.orders.updateStatus', $order) }}" method="POST" class="staff-status-form">
                                        @csrf
                                        <label class="visually-hidden" for="status-{{ $order->id }}">Update status for order {{ $order->order_number }}</label>
                                        <select id="status-{{ $order->id }}" name="status" class="staff-status-select">
                                            @foreach($statusOptions as $value => $label)
                                                <option value="{{ $value }}" {{ $order->status === $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-primary btn-sm">Update</button>
                                    </form>

                                    @if(!in_array($order->status, ['confirmed', 'to_receive', 'completed'], true))
                                        <form action="{{ route('staff.orders.confirm', $order) }}" method="POST" class="staff-confirm-form">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm">Confirm</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="table-empty">No assigned orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($orders, 'hasPages') && $orders->hasPages())
            <div class="staff-pagination">
                {{ $orders->onEachSide(1)->links() }}
            </div>
        @endif
    </section>
</main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const alertBanner = document.querySelector('.staff-alert');
            if (alertBanner) {
                setTimeout(function () {
                    alertBanner.classList.add('is-dismissing');
                    setTimeout(() => alertBanner.remove(), 600);
                }, 4000);
            }

            document.querySelectorAll('.staff-status-select').forEach(function (select) {
                select.addEventListener('change', function () {
                    const row = select.closest('tr');
                    if (row) {
                        row.classList.add('row-highlight');
                        setTimeout(() => row.classList.remove('row-highlight'), 1200);
                    }
                });
            });
        });
    </script>
@endpush
