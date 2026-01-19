
@extends('layouts.owner.app')

@section('content')
@include('layouts.owner.sidebar')

<section class="main-content owner-dashboard-shell">
  <main class="materials-page admin-page-shell materials-container owner-dashboard-main" role="main">
    <header class="page-header">
      <div>
        <h1 class="page-title">Archived Transactions</h1>
        <p class="page-subtitle">Previously archived payment records.</p>
      </div>
      <div class="page-header__quick-actions">
        <a href="{{ route('owner.transactions-view') }}" class="pill-link">Back to transactions</a>
      </div>
    </header>

    <div class="page-inner owner-dashboard-inner">
      <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>Transaction ID</th>
              <th>Order ID</th>
              <th>Customer</th>
              <th>Payment Method</th>
              <th>Date</th>
              <th>Amount (PHP)</th>
              <th>Remaining Balance</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @forelse($transformedRows as $transaction)
              <tr>
                <td class="fw-bold">{{ $transaction['transaction_id'] }}</td>
                <td>{{ $transaction['order_id'] }}</td>
                <td>{{ $transaction['customer_name'] }}</td>
                <td>{{ $transaction['payment_method'] }}</td>
                <td>{{ $transaction['display_date'] }}</td>
                <td>{{ $transaction['amount_display'] }}</td>
                <td>{{ $transaction['remaining_balance_display'] ?? '—' }}</td>
                <td>
                  @if(!empty($transaction['status_label']) && $transaction['status_label'] !== '—')
                    <span class="badge {{ $transaction['status_class'] ?? 'stock-low' }}">{{ $transaction['status_label'] }}</span>
                  @else
                    —
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center" style="padding:18px; color:#64748b;">No archived transactions.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if(isset($transactions) && $transactions instanceof \Illuminate\Pagination\AbstractPaginator)
        <div class="table-pagination">
          {{ $transactions->onEachSide(1)->links('owner.partials.pagination') }}
        </div>
      @endif
    </div>
  </main>
</section>

@endsection
