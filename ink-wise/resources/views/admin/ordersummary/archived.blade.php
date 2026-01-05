@extends('layouts.admin')

@section('title', 'Archived Orders')

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
@endphp
<main class="admin-page-shell">
  <header class="page-header">
    <div>
      <h1 class="page-title">Archived Orders</h1>
      <p class="page-subtitle">Archived cancelled and completed orders. Use the table to inspect and navigate to individual order summaries.</p>
    </div>
    <div class="page-header__quick-actions">
      <a href="{{ route('admin.orders.index') }}" class="pill-link">Active Orders</a>
    </div>
  </header>

  <section class="card">
    <div class="card-body">
      @if($orders->isEmpty())
        <p>No orders found.</p>
      @else
        <div class="table-controls" style="display:flex; gap:12px; align-items:center; justify-content:space-between; margin-bottom:10px;">
          <div style="display:flex; gap:8px; align-items:center;">
            <div class="search-wrap">
              <input id="ordersSearch" type="search" placeholder="Search orders, customer or #" aria-label="Search orders" style="padding:8px 12px; border-radius:8px; border:1px solid #e5e7eb; min-width:240px;">
            </div>

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
                <th scope="col">Archived By</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($orders as $order)
                @php
                  $rowStatus = strtolower($order->status ?? 'processing');
                @endphp
                <tr data-order-id="{{ $order->id }}" data-status="{{ $rowStatus }}" data-order-url="{{ route('admin.ordersummary.index', ['order' => $order->order_number]) }}" style="cursor: pointer;">
                  <td>{{ $order->order_number ?? ('#' . $order->id) }}</td>
                  <td>{{ optional($order->customer)->name ?? 'Guest' }}</td>
                  <td class="text-center">{{ (int) data_get($order, 'items_count', 0) }}</td>
                  <td class="text-end">{{ number_format((float) data_get($order, 'total_amount', 0), 2) }}</td>
                  <td>
                    @php
                      $paymentStatus = strtolower($order->payment_status ?? 'pending');
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
                  <td>
                    @php
                      $latestActivity = $order->activities->first();
                      $archivedBy = $latestActivity ? ($latestActivity->user_name ?? 'System') : 'Unknown';
                    @endphp
                    {{ $archivedBy }}
                  </td>
                  <td class="actions-cell">
                    <a href="{{ route('admin.ordersummary.index', ['order' => $order->order_number]) }}" class="btn btn-outline btn-sm btn-icon" aria-label="View order {{ $order->order_number ?? $order->id }}">
                      <i class="fa-solid fa-eye" aria-hidden="true"></i>
                    </a>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="table-footer" style="display:flex; align-items:center; justify-content:center; margin-top:16px;">
          <div class="pagination-links">{{ $orders->links() }}</div>
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
    // Avoid double-init
    if (window.__orders_per_init) return; window.__orders_per_init = true;
    const allowed = [10,20,25,50,100];
    const per = document.getElementById('perPageInput');
    if (!per) return;
    function snap(n){ n = Number(n) || 20; if (allowed.includes(n)) return n; let closest = allowed[0]; let minDiff = Math.abs(n - closest); allowed.forEach(a => { const d = Math.abs(n - a); if (d < minDiff) { minDiff = d; closest = a; }}); return closest; }
    per.addEventListener('change', function(){ const v = snap(this.value); this.value = v; const url = new URL(window.location.href); url.searchParams.set('per_page', v); url.searchParams.delete('page'); window.location.href = url.toString(); });
    per.addEventListener('keydown', function(e){ if (e.key === 'Enter') { e.preventDefault(); this.dispatchEvent(new Event('change')); } });
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
