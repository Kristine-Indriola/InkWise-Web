@extends('layouts.admin')

@section('title', 'Orders')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin-css/ordersummary.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin-css/orders.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin-css/orders-table.css') }}"> 
<style>
  .orders-controls {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    align-items: stretch;
    margin-bottom: 16px;
  }

  .summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 12px;
    flex: 1 1 100%;
  }

  .summary-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 96px;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
    cursor: pointer;
    text-align: left;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
  }

  .summary-card:focus-visible {
    outline: 3px solid #4f46e5;
    outline-offset: 2px;
  }

  .summary-card.is-active {
    border-color: #4f46e5;
    box-shadow: 0 6px 20px rgba(79, 70, 229, 0.15);
    transform: translateY(-2px);
  }

  .summary-card--highlight {
    border-color: #10b981;
    background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
  }

  .summary-card--highlight .summary-card__label {
    color: #065f46;
  }

  .summary-card--highlight .summary-card__value {
    color: #047857;
  }

  .summary-card__label {
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #6b7280;
    margin-bottom: 8px;
    font-weight: 600;
  }

  .summary-card__value {
    font-size: 26px;
    font-weight: 700;
    color: #1f2937;
    line-height: 1.1;
  }

  .summary-cards__info {
    flex: 1 1 100%;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
    font-size: 14px;
    color: #374151;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
  }

  .summary-cards__info strong {
    color: #111827;
  }

  @media (max-width: 640px) {
    .summary-card {
      min-height: 72px;
      padding: 14px;
    }

    .summary-card__value {
      font-size: 22px;
    }
  }

  .summary-card-button {
    background: none;
    border: none;
    padding: 0;
    font: inherit;
    width: 100%;
    text-align: left;
    cursor: pointer;
  }

  .summary-card-button:focus-visible .summary-card {
    outline: 3px solid #4f46e5;
    outline-offset: 2px;
  }

  .summary-card-button:hover .summary-card:not(.is-active) {
    border-color: #c7d2fe;
    box-shadow: 0 4px 16px rgba(79, 70, 229, 0.12);
  }

  .table-row--hidden {
    display: none;
  }

  .admin-orders-table tbody tr[data-order-url] {
    transition: background-color 0.2s ease;
  }

  .admin-orders-table tbody tr[data-order-url]:hover {
    background-color: #f8fafc;
  }

  .admin-orders-table tbody tr[data-order-url]:focus {
    outline: 2px solid #4f46e5;
    outline-offset: -2px;
  }

  /* Status and Payment highlights */
  .status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .status-badge.status-draft {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
  }

  .status-badge.status-pending {
    background: #fef3c7;
    color: #d97706;
    border: 1px solid #f59e0b;
  }

  .status-badge.status-processing,
  .status-badge.status-in_production {
    background: #dbeafe;
    color: #2563eb;
    border: 1px solid #3b82f6;
  }

  .status-badge.status-confirmed {
    background: #fef3c7;
    color: #d97706;
    border: 1px solid #f59e0b;
  }

  .status-badge.status-completed {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
  }

  .status-badge.status-cancelled {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #ef4444;
  }

  .payment-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .payment-badge.payment-pending {
    background: #fef3c7;
    color: #d97706;
    border: 1px solid #f59e0b;
  }

  .payment-badge.payment-paid {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
  }

  .payment-badge.payment-pending {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #ef4444;
  }

  .payment-badge.payment-partial {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
  }
</style>
@endpush

@section('content')
@php
  $statusOptions = [
    'draft' => 'New Order',
    'pending' => 'Order Received',
    'processing' => 'Processing',
    'in_production' => 'In Progress',
    'confirmed' => 'Ready for Pickup',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
  ];
  $isPaginator = $orders instanceof \Illuminate\Contracts\Pagination\Paginator;
  $ordersCollection = $isPaginator
    ? collect($orders->items())
    : ($orders instanceof \Illuminate\Support\Collection ? $orders : collect($orders));
@endphp
<main class="admin-page-shell">
  <header class="page-header">
    <div>
      <h1 class="page-title">Orders</h1>
      <p class="page-subtitle">All orders in the system. Use the table to inspect and navigate to individual order summaries.</p>
    </div>
    <div class="page-header__quick-actions">
      <a href="{{ route('admin.orders.archived') }}" class="pill-link">Archived Orders</a>
      <a href="#" class="pill-link">Export</a>
      <a href="{{ route('admin.reports.pickup-calendar') }}" class="pill-link">
				<i class="fi fi-rr-calendar" aria-hidden="true"></i> Pickup calendar
			</a>
    </div>
  </header>

  <!-- Summary cards + controls -->
  <section class="orders-controls">
    <div class="summary-cards">
      @php
        $totalOrders = $isPaginator ? $orders->total() : $ordersCollection->count();
        $statusCounts = $ordersCollection->groupBy(fn($o) => strtolower(data_get($o,'status','processing')))
          ->map->count();
        $pendingCount = $statusCounts->get('pending', 0);
        $draftCount = $statusCounts->get('draft', 0);
        $processingCount = $statusCounts->get('processing', 0);
        $inProductionCount = $statusCounts->get('in_production', 0);
        $confirmedCount = $statusCounts->get('confirmed', 0);
        $completedCount = $statusCounts->get('completed', 0);
        $cancelledCount = $statusCounts->get('cancelled', 0);
        $inProgressCount = $processingCount + $inProductionCount;
      @endphp
      <button type="button" class="summary-card-button" data-summary-filter="all" data-summary-label="All orders" data-summary-description="Includes every order regardless of status.">
        <div class="summary-card" data-summary-count="{{ $totalOrders }}">
          <div class="summary-card__label">Total orders</div>
          <div class="summary-card__value">{{ $totalOrders }}</div>
        </div>
      </button>
      <button type="button" class="summary-card-button" data-summary-filter="draft" data-summary-label="New Orders" data-summary-description="Fresh orders that need attention.">
        <div class="summary-card summary-card--highlight" data-summary-count="{{ $draftCount }}">
          <div class="summary-card__label">New Orders</div>
          <div class="summary-card__value">{{ $draftCount }}</div>
        </div>
      </button>
      <button type="button" class="summary-card-button" data-summary-filter="pending" data-summary-label="Pending" data-summary-description="Orders awaiting confirmation or updates.">
        <div class="summary-card" data-summary-count="{{ $pendingCount }}">
          <div class="summary-card__label">Pending</div>
          <div class="summary-card__value">{{ $pendingCount }}</div>
        </div>
      </button>
      <button type="button" class="summary-card-button" data-summary-filter="in_progress" data-summary-label="In progress" data-summary-description="Orders currently being produced or processed.">
        <div class="summary-card" data-summary-count="{{ $inProgressCount }}">
          <div class="summary-card__label">In progress</div>
          <div class="summary-card__value">{{ $inProgressCount }}</div>
        </div>
      </button>
      <button type="button" class="summary-card-button" data-summary-filter="ready_pickup" data-summary-label="Ready for pickup" data-summary-description="Orders packed and ready for customer pickup.">
        <div class="summary-card" data-summary-count="{{ $confirmedCount }}">
          <div class="summary-card__label">Ready for pickup</div>
          <div class="summary-card__value">{{ $confirmedCount }}</div>
        </div>
      </button>
      <button type="button" class="summary-card-button" data-summary-filter="completed" data-summary-label="Completed" data-summary-description="Orders successfully fulfilled and closed.">
        <div class="summary-card" data-summary-count="{{ $completedCount }}">
          <div class="summary-card__label">Completed</div>
          <div class="summary-card__value">{{ $completedCount }}</div>
        </div>
      </button>
      <button type="button" class="summary-card-button" data-summary-filter="cancelled" data-summary-label="Cancelled" data-summary-description="Orders voided or cancelled by the team or customer.">
        <div class="summary-card" data-summary-count="{{ $cancelledCount }}">
          <div class="summary-card__label">Cancelled</div>
          <div class="summary-card__value">{{ $cancelledCount }}</div>
        </div>
      </button>
    </div>

    <div class="summary-cards__info" id="summaryCardDetails" role="status" aria-live="polite">
      <strong>All orders</strong>: Showing every order in the table.
    </div>

    {{-- controls moved into the table container for closer context --}}
  </section>

  <section class="card">
    <div class="card-body">
      @if($ordersCollection->isEmpty())
        <p>No orders found.</p>
      @else
        <div class="table-controls" style="display:flex; gap:12px; align-items:center; justify-content:space-between; margin-bottom:10px;">
          <div style="display:flex; gap:8px; align-items:center;">
            <div class="search-wrap">
              <input id="ordersSearch" type="search" placeholder="Search orders, customer or #" aria-label="Search orders" style="padding:8px 12px; border-radius:8px; border:1px solid #e5e7eb; min-width:240px;">
            </div>

            <form method="get" action="{{ route('admin.orders.index') }}" style="display:flex; gap:8px; align-items:center;">
              <label style="font-size:13px; color:#6b7280;">From</label>
              <input name="start_date" type="date" value="{{ request()->query('start_date') }}" aria-label="Start date" style="padding:6px 10px; border-radius:6px; border:1px solid #e5e7eb;">
              <label style="font-size:13px; color:#6b7280;">To</label>
              <input name="end_date" type="date" value="{{ request()->query('end_date') }}" aria-label="End date" style="padding:6px 10px; border-radius:6px; border:1px solid #e5e7eb;">
              <button type="submit" class="btn btn-sm" style="padding:6px 10px; border-radius:6px;">Filter</button>
              <a href="{{ route('admin.orders.index') }}" class="btn btn-sm" style="padding:6px 10px; border-radius:6px; background:#f3f4f6; margin-left:4px;">Clear</a>
            </form>

            <div class="filters" role="toolbar" aria-label="Order filters" style="display:flex; gap:8px;">
              <button type="button" class="filter-btn" data-filter="all" aria-pressed="true" title="All">All</button>
              <button type="button" class="filter-btn" data-filter="pending" title="Pending">
                <i class="fi fi-rr-clock"></i>
              </button>
            </div>
          </div>

          <div style="display:flex; gap:8px; align-items:center;">&nbsp;</div>
        </div>

        <div class="table-responsive">
          <table class="table admin-orders-table" role="grid">
            <thead>
              <tr>
                <th scope="col">Order #</th>
                <th scope="col">Customer</th>
                <th scope="col" class="text-center">Items</th>
                <th scope="col" class="text-end">Total</th>
                <th scope="col">Payment</th>
                <th scope="col">Status</th>
                <th scope="col">Placed</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($ordersCollection as $order)
                @php
                  $rowStatus = strtolower($order->status ?? 'processing');
                @endphp
                <tr data-order-id="{{ $order->id }}" data-status="{{ $rowStatus }}" data-order-url="{{ route('admin.ordersummary.show', ['order' => $order->id]) }}" style="cursor: pointer;">
                  <td>{{ $order->order_number ?? ('#' . $order->id) }}</td>
                  <td>{{ optional($order->customer)->name ?? 'Guest' }}</td>
                  <td class="text-center">{{ (int) data_get($order, 'items_count', 0) }}</td>
                  <td class="text-end">{{ number_format((float) data_get($order, 'total_amount', 0), 2) }}</td>
                  <td>
                    @php
                      $paymentStatusRaw = data_get($order, 'payment_status', 'pending');
                      $paymentStatus = strtolower((string) ($paymentStatusRaw ?: 'pending'));
                      $metadataRaw = data_get($order, 'metadata');
                      if (is_string($metadataRaw) && $metadataRaw !== '') {
                        $decodedMetadata = json_decode($metadataRaw, true);
                        $metadata = json_last_error() === JSON_ERROR_NONE && is_array($decodedMetadata) ? $decodedMetadata : [];
                      } elseif (is_array($metadataRaw)) {
                        $metadata = $metadataRaw;
                      } else {
                        $metadata = [];
                      }
                      $financialMetadata = data_get($metadata, 'financial', []);
                      $paymentsSummary = collect(data_get($order, 'payments_summary', []));
                      $grandTotal = (float) ($paymentsSummary->get('grand_total') ?? data_get($order, 'total_amount', 0));
                      $paidOverrideRaw = data_get($financialMetadata, 'total_paid_override');
                      $balanceOverrideRaw = data_get($financialMetadata, 'balance_due_override');
                      $paidOverride = is_numeric($paidOverrideRaw) ? (float) $paidOverrideRaw : null;
                      $balanceOverride = is_numeric($balanceOverrideRaw) ? (float) $balanceOverrideRaw : null;
                      $totalPaid = $paidOverride ?? (float) ($paymentsSummary->get('total_paid') ?? data_get($order, 'total_paid', 0));
                      $balanceDue = $balanceOverride ?? (float) ($paymentsSummary->get('balance_due') ?? max($grandTotal - $totalPaid, 0));
                      if ($paymentStatus !== 'paid') {
                        if ($grandTotal > 0 && $balanceDue <= 0.01 && $totalPaid >= max($grandTotal - 0.01, 0)) {
                          $paymentStatus = 'paid';
                        } elseif ($totalPaid > 0 && $balanceDue > 0.01) {
                          $paymentStatus = 'partial';
                        }
                      }
                      $paymentClass = 'payment-' . $paymentStatus;
                    @endphp
                    <span class="payment-badge {{ $paymentClass }}">
                      {{ ucfirst(str_replace('_', ' ', $paymentStatus)) }}
                    </span>
                  </td>
                  <td>
                    @php
                      $orderStatus = strtolower($order->status ?? 'processing');
                      $statusClass = 'status-' . $orderStatus;
                    @endphp
                    <span class="status-badge {{ $statusClass }}">
                      {{ $statusOptions[$orderStatus] ?? ucfirst(str_replace('_', ' ', $orderStatus)) }}
                    </span>
                  </td>
                  <td>{{ optional($order->order_date)->format('M j, Y') ?? optional($order->created_at)->format('M j, Y') }}</td>
                  <td class="actions-cell">
                    <a href="{{ route('admin.ordersummary.show', ['order' => $order->id]) }}" class="btn btn-outline btn-sm btn-icon" aria-label="View order {{ $order->order_number ?? $order->id }}">
                      <i class="fa-solid fa-eye" aria-hidden="true"></i>
                    </a>
                    @if(in_array(strtolower($order->status ?? ''), ['cancelled', 'completed']))
                      <button type="button" class="btn btn-outline btn-sm btn-icon btn-archive" data-order-id="{{ $order->id }}" aria-label="Archive order {{ $order->order_number ?? $order->id }}" title="Archive order">
                        <i class="fa-solid fa-archive" aria-hidden="true"></i>
                      </button>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

      @endif
    </div>
  </section>
</main>
@endsection

@push('scripts')
<script src="{{ asset('js/admin/ordersummary.js') }}"></script>
<script src="{{ asset('js/admin/orders-table.js') }}"></script>
<script>
  (function(){
  const summaryButtons = Array.from(document.querySelectorAll('[data-summary-filter]'));
  const tableRows = Array.from(document.querySelectorAll('.admin-orders-table tbody tr'));
    const STATUS_STORAGE_KEY = 'inkwiseOrderStatusUpdate';
    const STATUS_CONSUMER_ID = 'orders-table';
    const KNOWN_STATUS_CONSUMERS = ['orders-table', 'order-summary'];
    const hasLocalStorage = (() => {
      try {
        const testKey = '__inkwise_status_probe__';
        window.localStorage.setItem(testKey, '1');
        window.localStorage.removeItem(testKey);
        return true;
      } catch (error) {
        return false;
      }
    })();
    const detailsEl = document.getElementById('summaryCardDetails');
    if (!summaryButtons.length || !detailsEl) return;

    const statusMap = {
      all: null,
      draft: ['draft'],
      pending: ['pending'],
      in_progress: ['processing', 'in_production'],
      ready_pickup: ['confirmed'],
      completed: ['completed'],
      cancelled: ['cancelled']
    };

    let activeButton = null;

    function formatStatusLabel(status, fallback) {
      if (!status) return fallback || '';
      return status.replace(/_/g, ' ').replace(/\b[a-z]/g, chr => chr.toUpperCase());
    }

    function computeCounts() {
      const counts = {
        total: 0,
        draft: 0,
        pending: 0,
        in_progress: 0,
        ready_pickup: 0,
        completed: 0,
        cancelled: 0
      };

      const inProgressStatuses = statusMap.in_progress;
      const readyPickupStatuses = statusMap.ready_pickup;

      tableRows.forEach(row => {
        if (!row.isConnected) {
          return;
        }
        const status = row.dataset.status || 'processing';
        counts.total += 1;
        if (status === 'draft') counts.draft += 1;
        if (status === 'pending') counts.pending += 1;
        if (inProgressStatuses.includes(status)) counts.in_progress += 1;
        if (readyPickupStatuses.includes(status)) counts.ready_pickup += 1;
        if (status === 'completed') counts.completed += 1;
        if (status === 'cancelled') counts.cancelled += 1;
      });

      return counts;
    }

    function getCountForFilter(filterKey, counts) {
      switch (filterKey) {
        case 'draft': return counts.draft;
        case 'pending': return counts.pending;
        case 'in_progress': return counts.in_progress;
        case 'ready_pickup': return counts.ready_pickup;
        case 'completed': return counts.completed;
        case 'cancelled': return counts.cancelled;
        case 'all':
        default:
          return counts.total;
      }
    }

    function updateSummaryCards() {
      const counts = computeCounts();
      summaryButtons.forEach(button => {
        const card = button.querySelector('.summary-card');
        const valueEl = button.querySelector('.summary-card__value');
        if (!card || !valueEl) return;
        const filterKey = button.getAttribute('data-summary-filter');
        const count = getCountForFilter(filterKey, counts);
        card.setAttribute('data-summary-count', count);
        valueEl.textContent = count;
      });
      return counts;
    }

    function setActiveCard(button) {
      summaryButtons.forEach(btn => {
        const card = btn.querySelector('.summary-card');
        if (!card) return;
        const isActive = btn === button;
        card.classList.toggle('is-active', isActive);
      });
      activeButton = button;
    }

    function filterRows(filterKey) {
      const allowedStatuses = statusMap[filterKey] ?? null;
      let visibleCount = 0;
      tableRows.forEach(row => {
        const rowStatus = row.dataset.status || 'processing';
        const shouldShow = !allowedStatuses || allowedStatuses.includes(rowStatus);
        row.classList.toggle('table-row--hidden', !shouldShow);
        if (shouldShow) {
          visibleCount += 1;
        }
      });
      return visibleCount;
    }

    function renderDetails(button, visibleCount) {
      const label = button.getAttribute('data-summary-label') || 'Orders';
      const description = button.getAttribute('data-summary-description') || '';
      const plural = visibleCount === 1 ? 'order' : 'orders';
      detailsEl.innerHTML = `<strong>${label}</strong>: Showing ${visibleCount} ${plural}. ${description}`;
    }

    function handleCardClick(button) {
      updateSummaryCards();
      const filterKey = button.getAttribute('data-summary-filter');
      const visibleCount = filterRows(filterKey);
      setActiveCard(button);
      renderDetails(button, visibleCount);
    }

    function applyStatusUpdateFromStorage(reapplyFilter = true) {
  if (!hasLocalStorage) return;
  const raw = localStorage.getItem(STATUS_STORAGE_KEY);
      if (!raw) return;

      let payload = null;
      try {
        payload = JSON.parse(raw);
      } catch (error) {
        localStorage.removeItem(STATUS_STORAGE_KEY);
        return;
      }

      if (!payload || !payload.orderId || !payload.status) {
        localStorage.removeItem(STATUS_STORAGE_KEY);
        return;
      }

      const maxAgeMs = 10 * 60 * 1000; // 10 minutes
      if (payload.timestamp && (Date.now() - payload.timestamp) > maxAgeMs) {
        localStorage.removeItem(STATUS_STORAGE_KEY);
        return;
      }

      const consumedBy = Array.isArray(payload.consumedBy) ? payload.consumedBy.slice() : [];
      if (consumedBy.includes(STATUS_CONSUMER_ID)) {
        if (reapplyFilter && activeButton) {
          handleCardClick(activeButton);
        }
        return;
      }

      const targetRow = tableRows.find(row => row.dataset.orderId === String(payload.orderId));
      if (targetRow) {
        targetRow.dataset.status = String(payload.status).toLowerCase();
        const statusCell = targetRow.cells[5];
        if (statusCell) {
          const label = payload.statusLabel || formatStatusLabel(payload.status, statusCell.textContent.trim());
          statusCell.textContent = label;
        }
      }

      consumedBy.push(STATUS_CONSUMER_ID);
      payload.consumedBy = Array.from(new Set(consumedBy));

      const allConsumed = KNOWN_STATUS_CONSUMERS.every(id => payload.consumedBy.includes(id));
      if (allConsumed) {
        localStorage.removeItem(STATUS_STORAGE_KEY);
      } else {
        try {
          localStorage.setItem(STATUS_STORAGE_KEY, JSON.stringify(payload));
        } catch (error) {
          console.warn('Unable to persist status sync payload for orders table.', error);
        }
      }

      updateSummaryCards();

      if (reapplyFilter && activeButton) {
        handleCardClick(activeButton);
      }
    }

    summaryButtons.forEach(button => {
      button.addEventListener('click', () => handleCardClick(button));
      button.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          handleCardClick(button);
        }
      });
    });

    const defaultButton = summaryButtons.find(btn => btn.getAttribute('data-summary-filter') === 'all') || summaryButtons[0];
    if (defaultButton) {
      handleCardClick(defaultButton);
    }

    updateSummaryCards();
    applyStatusUpdateFromStorage(true);

    window.addEventListener('pageshow', () => {
      applyStatusUpdateFromStorage(true);
    });

    if (hasLocalStorage) {
      window.addEventListener('storage', (event) => {
        if (event.key === STATUS_STORAGE_KEY) {
          applyStatusUpdateFromStorage(true);
        }
      });
    }
  })();

  // Make table rows clickable
  (function(){
    const tableBody = document.querySelector('.admin-orders-table tbody');

    if (tableBody) {
      tableBody.addEventListener('click', function(event) {
        const row = event.target.closest('tr[data-order-url]');
        if (!row) return;

        // Don't navigate if clicking on action buttons or other interactive elements
        if (event.target.closest('.actions-cell') ||
            event.target.closest('button') ||
            event.target.closest('a')) {
          return;
        }

        const url = row.getAttribute('data-order-url');
        if (url) {
          window.location.href = url;
        }
      });

      // Add keyboard support for accessibility
      tableBody.addEventListener('keydown', function(event) {
        if (event.key === 'Enter' || event.key === ' ') {
          const row = event.target.closest('tr[data-order-url]');
          if (!row) return;

          event.preventDefault();
          const url = row.getAttribute('data-order-url');
          if (url) {
            window.location.href = url;
          }
        }
      });

      // Make all rows focusable for keyboard navigation
      const tableRows = tableBody.querySelectorAll('tr[data-order-url]');
      tableRows.forEach(row => {
        row.setAttribute('tabindex', '0');
        row.setAttribute('role', 'button');
        row.setAttribute('aria-label', `View order ${row.querySelector('td:first-child').textContent.trim()}`);
      });
    }
  })();
</script>
@endpush
