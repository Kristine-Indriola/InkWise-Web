@extends('layouts.owner.app')

@section('content')
@include('layouts.owner.sidebar')

<style>
  .summary-grid {
    display: grid;
    gap: 18px;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    margin-bottom: 28px;
  }

  .summary-card {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 14px;
    padding: 20px;
    border-radius: 18px;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.92), rgba(241, 245, 255, 0.95));
    border: 1px solid rgba(148, 185, 255, 0.26);
    box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
    color: inherit;
    text-decoration: none;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
  }

  .summary-card::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(120deg, rgba(148, 185, 255, 0.12), rgba(59, 130, 246, 0.08));
    z-index: 0;
  }

  .summary-card > * {
    position: relative;
    z-index: 1;
  }

  .summary-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }

  .summary-card--link {
    color: inherit;
  }

  .summary-card--link:hover {
    transform: translateY(-3px);
    box-shadow: 0 26px 50px rgba(15, 23, 42, 0.16);
  }

  .summary-card--active {
    border-color: rgba(59, 130, 246, 0.5);
    box-shadow: 0 22px 44px rgba(59, 130, 246, 0.18);
  }

  .card-icon {
    color: #2563eb;
  }

  .summary-card-label {
    font-size: 0.92rem;
    font-weight: 600;
    color: #475569;
  }

  .summary-card-value {
    display: block;
    font-size: 1.6rem;
    font-weight: 800;
    color: #0f172a;
    margin-top: 6px;
  }

  .summary-card-meta {
    color: #6b7280;
    font-size: 0.84rem;
  }

  .summary-card-chip {
    padding: 4px 12px;
    border-radius: 999px;
    background: rgba(148, 185, 255, 0.18);
    color: #5a8de0;
    font-weight: 600;
    font-size: 0.78rem;
  }

  .summary-card-chip.accent {
    background: rgba(148, 185, 255, 0.22);
  }

  .materials-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 18px;
  }

  .materials-toolbar__search form {
    display: flex;
    gap: 10px;
    align-items: center;
  }

  .materials-toolbar__actions {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
  }

  .search-input {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    border-radius: 10px;
    padding: 6px 10px;
    border: 1px solid rgba(148, 185, 255, 0.22);
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
    transition: box-shadow 0.18s ease, border-color 0.18s ease;
  }

  .search-input:focus-within {
    border-color: rgba(59, 130, 246, 0.32);
    box-shadow: 0 12px 28px rgba(59, 130, 246, 0.08);
  }

  .search-input input.form-control {
    border: 0;
    outline: 0;
    width: 280px;
    max-width: 40vw;
    min-width: 140px;
    padding: 6px 8px;
    font-size: 0.95rem;
    background: transparent;
  }

  .owner-dashboard-inner .table-wrapper {
    margin-top: 18px;
    border-radius: 14px;
    border: 1px solid rgba(148, 185, 255, 0.2);
    background: #f8fbff;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6), 0 16px 32px rgba(15, 23, 42, 0.08);
    overflow-x: auto;
  }

  .owner-dashboard-inner .table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.95rem;
    color: #0f172a;
    min-width: 880px;
  }

  .owner-dashboard-inner .table thead th {
    background: rgba(148, 185, 255, 0.16);
    padding: 14px 20px;
    text-transform: uppercase;
    font-size: 0.78rem;
    letter-spacing: 0.06em;
    font-weight: 700;
  }

  .owner-dashboard-inner .table tbody td {
    padding: 14px 20px;
    border-bottom: 1px solid rgba(148, 185, 255, 0.12);
    vertical-align: middle;
  }

  .owner-dashboard-inner .table tbody tr:last-child td {
    border-bottom: none;
  }

  .owner-dashboard-inner .table tbody tr:hover {
    background: rgba(148, 185, 255, 0.08);
  }

  .badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
  }

  .badge.stock-ok { background: rgba(34, 197, 94, 0.16); color: #15803d; }
  .badge.stock-low { background: rgba(251, 191, 36, 0.16); color: #b45309; }
  .badge.stock-critical { background: rgba(239, 68, 68, 0.16); color: #b91c1c; }

  .table-pagination {
    margin-top: 16px;
  }

  @media (max-width: 900px) {
    .owner-dashboard-shell {
      padding-right: 0;
      padding-bottom: 0;
      padding-left: 0;
    }
    .owner-dashboard-main { padding: 24px 20px 32px 12px; }
    .owner-dashboard-inner { padding: 0 4px; }
    .owner-dashboard-inner .table { min-width: 720px; }
    .materials-toolbar { flex-direction: column; align-items: stretch; }
    .materials-toolbar__search form { width: 100%; flex-direction: column; align-items: stretch; }
    .materials-toolbar__search .search-input { width: 100%; }
    .materials-toolbar__actions { width: 100%; justify-content: flex-start; }
  }

  /* Dark mode */
  .dark-mode body { background: #111827; }
  .dark-mode .summary-card { background: #374151; color: #f9fafb; box-shadow: 0 12px 24px rgba(15, 23, 42, 0.4); }
  .dark-mode .summary-card::after { background: linear-gradient(90deg, rgba(148, 185, 255, 0.65), rgba(111, 150, 227, 0.75)); }
  .dark-mode .summary-card-label { color: #d1d5db; }
  .dark-mode .summary-card-value { color: #f9fafb; }
  .dark-mode .summary-card-meta { color: #9ca3af; }
  .dark-mode .summary-card-chip { background: rgba(148, 185, 255, 0.28); color: #cbd9ff; }
  .dark-mode .summary-card-chip.accent { background: rgba(148, 185, 255, 0.32); }
  .dark-mode .search-input { background: #374151; border-color: #4b5563; box-shadow: 0 6px 18px rgba(0, 0, 0, 0.35); }
  .dark-mode .search-input input.form-control { color: #f9fafb; }
  .dark-mode .owner-dashboard-inner .table-wrapper { background: #1f2937; border-color: rgba(148, 185, 255, 0.32); box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04); }
  .dark-mode .owner-dashboard-inner .table { color: #f9fafb; }
  .dark-mode .owner-dashboard-inner .table thead th { background: rgba(148, 185, 255, 0.22); color: #0f172a; }
  .dark-mode .owner-dashboard-inner .table tbody td { border-color: rgba(148, 185, 255, 0.18); }
  .dark-mode .owner-dashboard-inner .table tbody tr:hover { background: rgba(148, 185, 255, 0.12); }
</style>

<section class="main-content owner-dashboard-shell">
<main class="materials-page admin-page-shell materials-container owner-dashboard-main" role="main">
  <header class="page-header">
    <div>
      <h1 class="page-title">Transactions</h1>
      <p class="page-subtitle">Payment summaries and statuses</p>
    </div>
  </header>

  <div class="page-inner owner-dashboard-inner">
  @php
    $summaryTotals = $summary ?? [];

    $totalTransactions = (int) ($summaryTotals['total_transactions'] ?? 0);
    $totalAmount = (float) ($summaryTotals['total_amount'] ?? 0.00);
    $paidCount = (int) ($summaryTotals['paid_count'] ?? 0);
    $pendingCount = (int) ($summaryTotals['pending_count'] ?? 0);

    $transformedRows = $transformedRows ?? collect();
    if (!($transformedRows instanceof \Illuminate\Support\Collection)) {
      $transformedRows = collect($transformedRows);
    }

    if (!isset($statusGroups) || !is_array($statusGroups)) {
      $statusGroups = \App\Support\Owner\TransactionPresenter::statusGroups();
    }

    if ($totalTransactions === 0) {
      if (isset($transactions) && $transactions instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
        $totalTransactions = (int) $transactions->total();
      } else {
        $totalTransactions = $transformedRows->count();
      }
    }

    if ($totalAmount <= 0 && $transformedRows->isNotEmpty()) {
      $totalAmount = $transformedRows->reduce(static function ($carry, $row) {
        return $carry + ($row['amount_numeric'] ?? 0);
      }, 0);
    }

    if ($paidCount === 0) {
      $paidCount = $transformedRows->filter(static function ($row) use ($statusGroups) {
        return in_array($row['status_raw'], $statusGroups['paid'], true);
      })->count();
    }

    if ($pendingCount === 0) {
      $pendingCount = $transformedRows->filter(static function ($row) use ($statusGroups) {
        return in_array($row['status_raw'], $statusGroups['pending'], true);
      })->count();
    }

    $activeStatus = \Illuminate\Support\Str::lower((string) request()->query('status', 'all'));
    if ($activeStatus === '') {
      $activeStatus = 'all';
    }

    $exportQueryParams = static function (string $format): array {
      $params = array_merge(request()->query(), ['format' => $format]);

      return array_filter($params, static function ($value) {
        return $value !== null && $value !== '';
      });
    };
  @endphp

    <section class="summary-grid" aria-label="Transactions summary">
      <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}" class="summary-card summary-card--link {{ $activeStatus === 'all' ? 'summary-card--active' : '' }}" aria-label="Total transactions">
        <div class="summary-card-header">
          <div style="display:flex;align-items:center;gap:8px;">
            <!-- Icon: list -->
            <svg class="card-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span class="summary-card-label">Total Transactions</span>
          </div>
          <span class="summary-card-chip accent">All</span>
        </div>
        <span class="summary-card-value">{{ number_format($totalTransactions) }}</span>
        <span class="summary-card-meta">Transactions recorded</span>
      </a>

  <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}" class="summary-card summary-card--link" aria-label="Total amount">
        <div class="summary-card-header">
          <div style="display:flex;align-items:center;gap:8px;">
            <!-- Icon: peso / money -->
            <svg class="card-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8v8M9 6h6M9 18h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span class="summary-card-label">Total Amount</span>
          </div>
          <span class="summary-card-chip accent">PHP</span>
        </div>
        <span class="summary-card-value">₱{{ number_format($totalAmount, 2) }}</span>
        <span class="summary-card-meta">Collected / processed</span>
      </a>

      <a href="{{ request()->fullUrlWithQuery(['status' => 'paid']) }}" class="summary-card summary-card--link {{ $activeStatus === 'paid' ? 'summary-card--active' : '' }}" aria-label="Paid transactions">
        <div class="summary-card-header">
          <div style="display:flex;align-items:center;gap:8px;">
            <!-- Icon: check circle -->
            <svg class="card-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 12a8 8 0 11-16 0 8 8 0 0116 0z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.5 12.5l1.8 1.8 4.2-4.2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span class="summary-card-label">Paid</span>
          </div>
          <span class="summary-card-chip accent">Settled</span>
        </div>
        <span class="summary-card-value">{{ number_format($paidCount) }}</span>
        <span class="summary-card-meta">Confirmed payments</span>
      </a>

      <a href="{{ request()->fullUrlWithQuery(['status' => 'pending']) }}" class="summary-card summary-card--link {{ $activeStatus === 'pending' ? 'summary-card--active' : '' }}" aria-label="Pending transactions">
        <div class="summary-card-header">
          <div style="display:flex;align-items:center;gap:8px;">
            <!-- Icon: clock -->
            <svg class="card-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.6"/><path d="M12 8v5l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span class="summary-card-label">Pending</span>
          </div>
          <span class="summary-card-chip accent">Review</span>
        </div>
        <span class="summary-card-value">{{ number_format($pendingCount) }}</span>
        <span class="summary-card-meta">Awaiting confirmation</span>
      </a>
    </section>
    <section class="materials-toolbar" aria-label="Transactions search">
      <div class="materials-toolbar__search">
        <form method="GET" action="{{ url()->current() }}">
          <div class="search-input">
            <span class="search-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="8" stroke="#9aa6c2" stroke-width="2"/><path d="M21 21l-4.35-4.35" stroke="#9aa6c2" stroke-width="2" stroke-linecap="round"/></svg></span>
            <input class="form-control" type="text" name="search" placeholder="Search by Transaction ID, Order or Customer" value="{{ request('search') }}">
          </div>
          <input type="hidden" name="status" value="{{ request('status') }}">
          <button type="submit" class="btn btn-secondary">Search</button>
        </form>
      </div>
      <div class="materials-toolbar__actions">
        <a class="btn btn-secondary" href="{{ route('owner.transactions-export', $exportQueryParams('csv')) }}" title="Download CSV export">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 10l5-5 5 5M12 5v12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Export CSV
        </a>
      </div>
    </section>
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
              <td colspan="7" class="text-center" style="padding:18px; color:#64748b;">No transactions found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if(isset($transactions) && $transactions instanceof \Illuminate\Pagination\AbstractPaginator)
      <div class="table-pagination">
        {{ $transactions->links() }}
      </div>
    @endif
  </div>
</main>
</section>

@endsection
