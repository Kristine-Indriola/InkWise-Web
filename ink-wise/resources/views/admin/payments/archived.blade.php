@extends('layouts.admin')

@section('title', 'Archived Payments')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/reports.css') }}">
@endpush

@section('content')
<main class="payments-shell">
    <header class="payments-header">
        <div>
            <h1>Archived Payments</h1>
            <p class="muted">Previously archived payment transactions.</p>
        </div>
        <div class="filter-actions-row">
            <a href="{{ route('admin.payments.index') }}" class="btn-secondary">Back to payments</a>
        </div>
    </header>

    <section class="payments-table-container">
        @if($transactions->count() > 0)
            <table class="payments-table">
                <thead>
                    <tr>
                        <th>Transaction</th>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Balance</th>
                        <th>Payment Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transformedRows as $row)
                        @php $payment = $row['raw']; $order = $payment?->order; @endphp
                        <tr>
                            <td><span class="order-number">{{ $row['transaction_id'] ?? '—' }}</span></td>
                            <td>
                                @if($order)
                                    <a href="{{ route('admin.ordersummary.show', ['order' => $order->id]) }}" class="order-number">#{{ $order->order_number }}</a>
                                @else
                                    <span class="order-number">{{ $row['order_id'] ?? '—' }}</span>
                                @endif
                            </td>
                            <td><span class="customer-name">{{ $row['customer_name'] ?? '—' }}</span></td>
                            <td>{{ $row['payment_method'] ?? '—' }}</td>
                            <td>{{ $row['display_date'] ?? '—' }}</td>
                            <td class="payment-amount">{{ $row['amount_display'] ?? '—' }}</td>
                            <td class="payment-amount">{{ $row['remaining_balance_display'] ?? '—' }}</td>
                            <td>
                                @if(!empty($row['status_label']) && $row['status_label'] !== '—')
                                    <span class="payment-status">{{ $row['status_label'] }}</span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-state">
                <h3>No archived payment transactions</h3>
                <p>There are no archived payments to show.</p>
            </div>
        @endif
    </section>

    @if($transactions->hasPages())
        <div class="pagination-container">
            {{ $transactions->appends(request()->query())->links('admin.payments.pagination') }}
        </div>
    @endif
</main>
@endsection
