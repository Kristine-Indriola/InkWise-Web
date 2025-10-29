@extends('layouts.admin')

@section('title', 'Payment Transactions')

@push('styles')
<style>
    .payments-shell {
        max-width: 1200px;
        margin: 0 auto;
        padding: 32px 16px 72px;
    }

    .payments-shell > * + * {
        margin-top: 24px;
    }

    @media (max-width: 600px) {
        .payments-shell {
            padding: 24px 12px 64px;
        }
    }

    .payments-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 24px;
    }

    @media (max-width: 720px) {
        .payments-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }

    .payments-header h1 {
        font-size: 28px;
        font-weight: 700;
        margin: 0;
        color: #111827;
    }

    .payments-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .stat-card__icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .stat-card__icon--total {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
    }

    .stat-card__icon--paid {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .stat-card__icon--pending {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
    }

    .stat-card__icon--overdue {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

    .stat-card__content h3 {
        font-size: 24px;
        font-weight: 700;
        margin: 0 0 4px;
        color: #111827;
    }

    .stat-card__content p {
        font-size: 14px;
        color: #6b7280;
        margin: 0;
    }

    .payments-table-container {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
    }

    .payments-table {
        width: 100%;
        border-collapse: collapse;
    }

    .payments-table th {
        background: #f9fafb;
        padding: 16px;
        text-align: left;
        font-weight: 600;
        font-size: 14px;
        color: #374151;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
    }

    .payments-table td {
        padding: 16px;
        border-bottom: 1px solid #f3f4f6;
        font-size: 14px;
        color: #374151;
    }

    .payments-table tbody tr:hover {
        background: #f9fafb;
    }

    .order-number {
        font-weight: 600;
        color: #111827;
        text-decoration: none;
    }

    .order-number:hover {
        text-decoration: underline;
    }

    .customer-name {
        font-weight: 500;
        color: #374151;
    }

    .payment-amount {
        font-weight: 600;
        font-size: 16px;
        color: #111827;
    }

    .payment-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .payment-status--paid {
        background: #dcfce7;
        color: #166534;
    }

    .payment-status--pending {
        background: #fef3c7;
        color: #92400e;
    }

    .payment-status--failed {
        background: #fee2e2;
        color: #991b1b;
    }

    .payment-status--refunded {
        background: #e0f2fe;
        color: #0c4a6e;
    }

    .actions-cell {
        text-align: right;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-outline {
        background: transparent;
        border-color: #d1d5db;
        color: #374151;
    }

    .btn-outline:hover {
        background: #f9fafb;
        border-color: #9ca3af;
    }

    .btn-sm {
        padding: 6px 10px;
        font-size: 12px;
    }

    .btn-icon {
        width: 36px;
        height: 36px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .pagination-container {
        display: flex;
        justify-content: center;
        margin-top: 24px;
    }

    .pagination {
        display: flex;
        gap: 4px;
        align-items: center;
    }

    .pagination a,
    .pagination span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        border: 1px solid #d1d5db;
        background: white;
        color: #374151;
        transition: all 0.2s ease;
    }

    .pagination .active {
        background: #3b82f6;
        border-color: #3b82f6;
        color: white;
    }

    .pagination a:hover:not(.active) {
        background: #f3f4f6;
        border-color: #9ca3af;
    }

    .empty-state {
        text-align: center;
        padding: 48px 24px;
        color: #6b7280;
    }

    .empty-state h3 {
        font-size: 18px;
        font-weight: 600;
        margin: 0 0 8px;
        color: #374151;
    }

    .empty-state p {
        font-size: 14px;
        margin: 0;
    }

    @media (max-width: 768px) {
        .payments-table {
            font-size: 12px;
        }

        .payments-table th,
        .payments-table td {
            padding: 12px 8px;
        }

        .stat-card {
            padding: 16px;
        }

        .stat-card__icon {
            width: 40px;
            height: 40px;
            font-size: 16px;
        }

        .stat-card__content h3 {
            font-size: 20px;
        }
    }
</style>
@endpush

@section('content')
@php
    // Calculate payment statistics
    $totalOrders = $orders->total();
    $paidOrders = $orders->where('payment_status', 'paid')->count();
    $pendingOrders = $orders->where('payment_status', 'pending')->count();
    $failedOrders = $orders->where('payment_status', 'failed')->count();

    // Calculate total amounts
    $totalAmount = $orders->sum('total_amount');
    $paidAmount = $orders->where('payment_status', 'paid')->sum('total_amount');
    $pendingAmount = $orders->where('payment_status', 'pending')->sum('total_amount');
@endphp

<main class="payments-shell">
    <header class="payments-header">
        <h1>Payment Transactions</h1>
    </header>

    <section class="payments-stats">
        <div class="stat-card">
            <div class="stat-card__icon stat-card__icon--total">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-card__content">
                <h3>{{ number_format($totalOrders) }}</h3>
                <p>Total Orders</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card__icon stat-card__icon--paid">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-card__content">
                <h3>{{ number_format($paidOrders) }}</h3>
                <p>Paid Orders</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card__icon stat-card__icon--pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-card__content">
                <h3>{{ number_format($pendingOrders) }}</h3>
                <p>Pending Payments</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card__icon stat-card__icon--overdue">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-card__content">
                <h3>{{ number_format($failedOrders) }}</h3>
                <p>Failed Payments</p>
            </div>
        </div>
    </section>

    <section class="payments-table-container">
        @if($orders->count() > 0)
            <table class="payments-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Order Date</th>
                        <th>Total Amount</th>
                        <th>Paid Amount</th>
                        <th>Balance</th>
                        <th>Payment Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                        @php
                            $paidAmount = $order->totalPaid();
                            $balanceDue = $order->balanceDue();
                            $paymentStatusClass = 'payment-status--' . strtolower($order->payment_status ?? 'pending');
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('admin.ordersummary.index', ['order' => $order->order_number]) }}" class="order-number">
                                    #{{ $order->order_number }}
                                </a>
                            </td>
                            <td>
                                <span class="customer-name">
                                    {{ $order->effectiveCustomer?->name ?? $order->effectiveCustomer?->full_name ?? 'N/A' }}
                                </span>
                            </td>
                            <td>{{ $order->order_date?->format('M j, Y') ?? 'N/A' }}</td>
                            <td class="payment-amount">PHP {{ number_format($order->total_amount, 2) }}</td>
                            <td class="payment-amount">PHP {{ number_format($paidAmount, 2) }}</td>
                            <td class="payment-amount {{ $balanceDue > 0 ? 'text-red-600' : 'text-green-600' }}">
                                PHP {{ number_format($balanceDue, 2) }}
                            </td>
                            <td>
                                <span class="payment-status {{ $paymentStatusClass }}">
                                    {{ ucfirst($order->payment_status ?? 'pending') }}
                                </span>
                            </td>
                            <td class="actions-cell">
                                <a href="{{ route('admin.ordersummary.index', ['order' => $order->order_number]) }}" class="btn btn-outline btn-sm" title="View Order">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.orders.payment.edit', ['order' => $order->id]) }}" class="btn btn-outline btn-sm" title="Manage Payment">
                                    <i class="fas fa-credit-card"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-state">
                <h3>No payment transactions found</h3>
                <p>There are no orders with payment information to display.</p>
            </div>
        @endif
    </section>

    @if($orders->hasPages())
        <div class="pagination-container">
            {{ $orders->appends(request()->query())->links() }}
        </div>
    @endif
</main>
@endsection

@push('scripts')
<script>
    // Add any interactive functionality here if needed
    document.addEventListener('DOMContentLoaded', function() {
        // Payment status updates or other interactions can be added here
    });
</script>
@endpush