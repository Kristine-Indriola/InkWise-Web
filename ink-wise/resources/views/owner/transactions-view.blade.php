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

    $totalTransactions = isset($summaryTotals['total_transactions'])
      ? (int) $summaryTotals['total_transactions']
      : 0;

    $totalAmount = isset($summaryTotals['total_amount'])
      ? (float) $summaryTotals['total_amount']
      : 0.00;

    $paidCount = isset($summaryTotals['paid_count'])
      ? (int) $summaryTotals['paid_count']
      : 0;

    $pendingCount = isset($summaryTotals['pending_count'])
      ? (int) $summaryTotals['pending_count']
      : 0;

    $usingDemoData = false;

    $transactionRows = collect();

    if (isset($transactions)) {
      if ($transactions instanceof \Illuminate\Support\Collection) {
        $transactionRows = $transactions;
        if ($totalTransactions === 0) {
          $totalTransactions = $transactions->count();
        }
      } elseif ($transactions instanceof \Illuminate\Contracts\Pagination\Paginator) {
        $items = method_exists($transactions, 'items') ? $transactions->items() : [];
        $transactionRows = collect($items);
        if ($totalTransactions === 0 && method_exists($transactions, 'total')) {
          $totalTransactions = (int) $transactions->total();
        } elseif ($totalTransactions === 0) {
          $totalTransactions = $transactionRows->count();
        }
      } elseif (is_array($transactions)) {
        $transactionRows = collect($transactions);
        if ($totalTransactions === 0) {
          $totalTransactions = $transactionRows->count();
        }
      }
    }

    if ($transactionRows->isEmpty()) {
      $transactionRows = collect([
        [
          'transaction_id' => 'TXN10001',
          'order_id' => '#1001',
          'customer' => 'Frechy',
          'payment_method' => 'GCash',
          'date' => '2025-04-28',
          'amount' => 12000,
          'status' => 'Paid',
        ],
        [
          'transaction_id' => 'TXN10002',
          'order_id' => '#1002',
          'customer' => 'Kristine',
          'payment_method' => 'COD',
          'date' => '2025-04-28',
          'amount' => 7500,
          'status' => 'Pending',
        ],
      ]);
      $usingDemoData = true;

      if ($totalTransactions === 0) {
        $totalTransactions = $transactionRows->count();
      }

      if ($totalAmount <= 0) {
        $totalAmount = $transactionRows->sum('amount');
      }

      if ($paidCount === 0) {
        $paidCount = $transactionRows->filter(fn ($row) => strtolower($row['status']) === 'paid')->count();
      }

      if ($pendingCount === 0) {
        $pendingCount = $transactionRows->filter(fn ($row) => strtolower($row['status']) === 'pending')->count();
      }
    }

    $normalizedPaidStatuses = ['paid', 'complete', 'completed', 'settled'];
    $normalizedPendingStatuses = ['pending', 'processing', 'unpaid', 'awaiting', 'awaiting payment'];
    $normalizedFailedStatuses = ['failed', 'cancelled', 'canceled', 'refunded', 'void'];

    $normalizeStatus = static function ($value) {
      return \Illuminate\Support\Str::lower(trim((string) $value));
    };

    $resolveBadgeClass = static function (string $status) use ($normalizedPaidStatuses, $normalizedFailedStatuses) {
      if (in_array($status, $normalizedPaidStatuses, true)) {
        return 'stock-ok';
      }
      if (in_array($status, $normalizedFailedStatuses, true)) {
        return 'stock-critical';
      }
      return 'stock-low';
    };

    $transformTransaction = static function ($transaction) use ($normalizeStatus, $resolveBadgeClass) {
      $transactionId = data_get($transaction, 'transaction_id')
        ?? data_get($transaction, 'provider_payment_id')
        ?? data_get($transaction, 'intent_id')
        ?? data_get($transaction, 'reference')
        ?? data_get($transaction, 'id')
        ?? '—';
      $transactionId = is_string($transactionId) ? trim($transactionId) : (is_numeric($transactionId) ? (string) $transactionId : '—');

      $orderId = data_get($transaction, 'order_id') ?? data_get($transaction, 'order.reference') ?? data_get($transaction, 'order.order_number') ?? data_get($transaction, 'order.id');
      if (is_array($orderId)) {
        $orderId = $orderId['order_number'] ?? $orderId['id'] ?? null;
      }
      if ($orderId instanceof \DateTimeInterface) {
        $orderId = $orderId->format('Y-m-d');
      }
      if (is_numeric($orderId)) {
        $orderId = '#' . ltrim((string) $orderId, '#');
      }
      $orderId = $orderId ? (string) $orderId : '—';

      $customerName = data_get($transaction, 'customer_name')
        ?? data_get($transaction, 'customer.name')
        ?? data_get($transaction, 'customer.full_name')
        ?? data_get($transaction, 'order.customer.name')
        ?? data_get($transaction, 'order.customer.full_name')
        ?? data_get($transaction, 'order.customerOrder.customer.name');
      if (!$customerName) {
        $customerSource = data_get($transaction, 'customer');
        if (!$customerSource) {
          $customerSource = data_get($transaction, 'order.customer') ?? data_get($transaction, 'order.customerOrder.customer');
        }
        if (is_array($customerSource)) {
          $customerName = $customerSource['name'] ?? trim(($customerSource['first_name'] ?? '') . ' ' . ($customerSource['last_name'] ?? ''));
        } elseif (is_object($customerSource)) {
          $customerName = $customerSource->name ?? $customerSource->full_name ?? trim(($customerSource->first_name ?? '') . ' ' . ($customerSource->last_name ?? ''));
          if (!$customerName && method_exists($customerSource, '__toString')) {
            $customerName = (string) $customerSource;
          }
        } elseif (is_null($customerSource)) {
          $order = data_get($transaction, 'order');
          if (is_object($order) && method_exists($order, 'effectiveCustomer')) {
            $effective = $order->effectiveCustomer();
            if (is_object($effective)) {
              $customerName = $effective->name
                ?? $effective->full_name
                ?? trim(($effective->first_name ?? '') . ' ' . ($effective->last_name ?? ''));
            } elseif (is_array($effective)) {
              $customerName = $effective['name'] ?? trim(($effective['first_name'] ?? '') . ' ' . ($effective['last_name'] ?? ''));
            } elseif (is_string($effective)) {
              $customerName = $effective;
            }
          }
        } elseif (is_string($customerSource)) {
          $customerName = $customerSource;
        }
      }
      $customerName = $customerName ? trim((string) $customerName) : '—';

      $paymentMethod = data_get($transaction, 'payment_method')
        ?? data_get($transaction, 'method')
        ?? data_get($transaction, 'mode')
        ?? data_get($transaction, 'payment.method');
      if (is_array($paymentMethod)) {
        $paymentMethod = $paymentMethod['name'] ?? $paymentMethod['label'] ?? null;
      }
      $paymentMethod = $paymentMethod ? (string) $paymentMethod : '—';

      $rawDate = data_get($transaction, 'date')
        ?? data_get($transaction, 'paid_at')
        ?? data_get($transaction, 'recorded_at')
        ?? data_get($transaction, 'created_at')
        ?? data_get($transaction, 'updated_at');
      if ($rawDate instanceof \DateTimeInterface) {
        $displayDate = $rawDate->format('Y-m-d');
      } elseif (is_string($rawDate) && strlen($rawDate) >= 10) {
        $displayDate = substr($rawDate, 0, 10);
      } else {
        $displayDate = '—';
      }

      $amountValue = data_get($transaction, 'amount');
      if ($amountValue === null) {
        $amountValue = data_get($transaction, 'total');
      }
      $currency = strtoupper((string) data_get($transaction, 'currency', 'PHP'));
      $currencyPrefix = $currency === 'PHP' ? '₱' : ($currency !== '' ? $currency . ' ' : '');
      if (is_numeric($amountValue)) {
        $amountNumeric = (float) $amountValue;
        $amountDisplay = $currencyPrefix . number_format($amountNumeric, 2);
      } elseif (is_string($amountValue) && trim($amountValue) !== '') {
        $amountNumeric = null;
        $amountDisplay = trim($amountValue);
      } else {
        $amountNumeric = null;
        $amountDisplay = '—';
      }

      $statusRaw = $normalizeStatus(data_get($transaction, 'status', ''));
      $statusLabel = $statusRaw !== '' ? ucwords(str_replace(['-', '_'], ' ', $statusRaw)) : '—';
      $statusClass = $statusLabel === '—' ? null : $resolveBadgeClass($statusRaw);

      $searchTargets = array_filter([
        $transactionId,
        $orderId,
        $customerName,
        $paymentMethod,
        $displayDate,
        $amountDisplay,
        $statusLabel,
        data_get($transaction, 'provider'),
        $currency,
      ]);

      return [
        'raw' => $transaction,
        'transaction_id' => $transactionId,
        'order_id' => $orderId,
        'customer_name' => $customerName,
        'payment_method' => $paymentMethod,
        'display_date' => $displayDate,
        'amount_display' => $amountDisplay,
        'amount_numeric' => $amountNumeric,
        'status_raw' => $statusRaw,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'search_blob' => \Illuminate\Support\Str::lower(implode(' ', $searchTargets)),
        'currency' => $currency,
      ];
    };

    $transformedRows = $transactionRows->map($transformTransaction);

    if ($totalAmount <= 0 && $transformedRows->isNotEmpty()) {
      $totalAmount = $transformedRows->reduce(function ($carry, $row) {
        return $carry + ($row['amount_numeric'] ?? 0);
      }, 0);
    }

    $calculatedPaid = $transformedRows->filter(function ($row) use ($normalizedPaidStatuses) {
      return in_array($row['status_raw'], $normalizedPaidStatuses, true);
    })->count();

    $calculatedPending = $transformedRows->filter(function ($row) use ($normalizedPendingStatuses) {
      return in_array($row['status_raw'], $normalizedPendingStatuses, true);
    })->count();

    if ($usingDemoData || $paidCount === 0) {
      $paidCount = $calculatedPaid;
    }

    if ($usingDemoData || $pendingCount === 0) {
      $pendingCount = $calculatedPending;
    }

    $requestedStatus = $normalizeStatus(request()->query('status', ''));
    $activeStatus = $requestedStatus !== '' ? $requestedStatus : 'all';

    $statusGroupMap = [
      'paid' => $normalizedPaidStatuses,
      'pending' => $normalizedPendingStatuses,
      'failed' => $normalizedFailedStatuses,
    ];

    $filteredRows = $transformedRows;

    if ($activeStatus !== 'all') {
      $allowedStatuses = $statusGroupMap[$activeStatus] ?? [$activeStatus];
      $filteredRows = $filteredRows->filter(function ($row) use ($allowedStatuses) {
        return in_array($row['status_raw'], $allowedStatuses, true);
      })->values();
    }

    $searchQuery = trim((string) request()->query('search', ''));
    $searchQueryLower = \Illuminate\Support\Str::lower($searchQuery);

    if ($searchQueryLower !== '') {
      $filteredRows = $filteredRows->filter(function ($row) use ($searchQueryLower) {
        return $row['search_blob'] !== '' && str_contains($row['search_blob'], $searchQueryLower);
      })->values();
    }

    $displayRows = $filteredRows;
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

      <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}" class="summary-card summary-card--link {{ $activeStatus === 'all' ? 'summary-card--active' : '' }}" aria-label="Total amount">
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
        <!-- Export (placeholder) -->
        <button type="button" class="btn btn-secondary" data-action="export-csv" title="Export CSV">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 10l5-5 5 5M12 5v12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Export
        </button>

        <!-- Quick filters: Paid / Pending -->
        <a href="{{ request()->fullUrlWithQuery(['status' => 'paid']) }}" class="btn" title="Show paid">Paid</a>
        <a href="{{ request()->fullUrlWithQuery(['status' => 'pending']) }}" class="btn btn-warning" title="Show pending">Pending</a>

        <!-- View History placeholder -->
        <button type="button" class="btn btn-outline" data-action="open-history" title="View history">History</button>
      </div>
    </section>

    <!-- Modal placeholders (non-functional flows) -->
    <div id="modalOverlay" style="display:none; position:fixed; inset:0; background:rgba(2,6,23,0.5); z-index:60;">
      <div id="modalBox" style="max-width:720px; margin:6vh auto; background:#fff; border-radius:10px; padding:18px; position:relative;">
        <button id="modalClose" style="position:absolute; right:12px; top:12px; border:0; background:transparent; font-size:18px;">×</button>
        <div id="modalContent">
          <!-- Content will be injected by page script to demonstrate flows -->
        </div>
      </div>
    </div>

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
          @forelse($displayRows as $transaction)
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

    @if(isset($transactions) && $transactions instanceof \Illuminate\Contracts\Pagination\Paginator)
      <div class="table-pagination">
        {{ $transactions->links() }}
      </div>
    @endif
  </div>
</main>
</section>

@endsection

@push('scripts')
<script>
  (function(){
    const overlay = document.getElementById('modalOverlay');
    const box = document.getElementById('modalBox');
    const content = document.getElementById('modalContent');
    const closeBtn = document.getElementById('modalClose');

    function openModal(html) {
      if (!overlay) return;
      content.innerHTML = html;
      overlay.style.display = 'block';
    }
    function closeModal() { if (!overlay) return; overlay.style.display='none'; content.innerHTML=''; }

    document.addEventListener('click', function(e){
      const btn = e.target.closest('[data-action]');
      if (!btn) return;
      const action = btn.dataset.action;
      if (action === 'open-add-transaction') {
        openModal(`<h3>Add Transaction (UI Demo)</h3>
          <p>This is a non-functional demo of the add transaction flow. Fill fields and click Submit to simulate.</p>
          <form id="demoAddForm">
            <label>Order ID<br><input name="order_id" class="form-control" /></label><br>
            <label>Amount<br><input name="amount" class="form-control" /></label><br>
            <label>Payment Method<br><input name="method" class="form-control" /></label><br>
            <div style="margin-top:10px;"><button type="button" id="demoSubmit" class="btn btn-primary">Submit (demo)</button></div>
          </form>`);
      }

      if (action === 'open-import-csv') {
        openModal(`<h3>Import CSV (UI Demo)</h3>
          <p>Select a CSV file to preview rows. This demo does not upload.</p>
          <input type="file" accept=".csv" id="demoCsvInput" />
          <div id="demoCsvPreview" style="margin-top:12px; font-size:0.9rem; color:#374151;"></div>`);
      }

      if (action === 'export-csv') {
        openModal(`<h3>Export CSV (UI Demo)</h3>
          <p>This would trigger a server export in a real app. Here it's just a demo.</p>
          <div style="margin-top:12px;"><button class="btn btn-secondary" id="demoExportBtn">Download (demo)</button></div>`);
      }

      if (action === 'open-history') {
        openModal(`<h3>Transaction History (UI Demo)</h3>
          <p>Show recent activity; this is a visual-only placeholder.</p>
          <ul style="margin-top:8px; list-style:none; padding-left:0; color:#374151;"><li>2025-04-28 — TXN10001 was processed (Paid)</li><li>2025-04-28 — TXN10002 created (Pending)</li></ul>`);
      }
    });

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (overlay) overlay.addEventListener('click', function(e){ if (e.target === overlay) closeModal(); });

    // Demo handlers inside modal
    document.addEventListener('click', function(e){
      if (e.target && e.target.id === 'demoSubmit') {
        alert('Demo: transaction submitted (no server request)');
        closeModal();
      }
      if (e.target && e.target.id === 'demoExportBtn') {
        alert('Demo: export started (no server request)');
      }
      if (e.target && e.target.id === 'demoCsvInput') {
        // noop
      }
    });

    // CSV preview handling (delegated)
    document.addEventListener('change', function(e){
      if (e.target && e.target.id === 'demoCsvInput') {
        const file = e.target.files && e.target.files[0];
        const preview = document.getElementById('demoCsvPreview');
        if (!file || !preview) return;
        const reader = new FileReader();
        reader.onload = function(ev){
          const text = ev.target.result || '';
          const lines = text.split(/\r?\n/).slice(0,6).map(l => `<div style="padding:6px 0;border-bottom:1px solid #eef2ff;">${l}</div>`).join('');
          preview.innerHTML = `<div style="max-height:220px; overflow:auto;">${lines}</div>`;
        };
        reader.readAsText(file);
      }
    });
  })();
</script>
@endpush
