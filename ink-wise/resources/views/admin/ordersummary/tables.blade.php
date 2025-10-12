@extends('layouts.admin')

@section('title', 'Orders')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin-css/ordersummary.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin-css/orders.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin-css/orders-table.css') }}">
@endpush

@section('content')
<main class="admin-page-shell">
  <header class="page-header">
    <div>
      <h1 class="page-title">Orders</h1>
      <p class="page-subtitle">All orders in the system. Use the table to inspect and navigate to individual order summaries.</p>
    </div>
    <div class="page-header__quick-actions">
      <a href="#" class="pill-link">Export</a>
    </div>
  </header>

  <!-- Summary cards + controls -->
  <section class="orders-controls" style="margin-bottom:16px; display:flex; gap:16px; align-items:flex-start; flex-wrap:wrap;">
    <div class="summary-cards" style="display:flex; gap:12px; align-items:stretch;">
      @php
        $totalOrders = $orders->total();
        $statusCounts = collect($orders->items())->groupBy(fn($o) => strtolower(data_get($o,'status','processing')))
          ->map->count();
        $pendingCount = $statusCounts->get('pending', 0);
        $completedCount = $statusCounts->get('completed', 0);
        $cancelledCount = $statusCounts->get('cancelled', 0);
      @endphp
      <div class="summary-card">
        <div class="summary-card__label">Total orders</div>
        <div class="summary-card__value">{{ $totalOrders }}</div>
      </div>
      <div class="summary-card">
        <div class="summary-card__label">Pending</div>
        <div class="summary-card__value">{{ $pendingCount }}</div>
      </div>
      <div class="summary-card">
        <div class="summary-card__label">Completed</div>
        <div class="summary-card__value">{{ $completedCount }}</div>
      </div>
      <div class="summary-card">
        <div class="summary-card__label">Cancelled</div>
        <div class="summary-card__value">{{ $cancelledCount }}</div>
      </div>
    </div>

    {{-- controls moved into the table container for closer context --}}
  </section>

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
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($orders as $order)
                <tr data-order-id="{{ $order->id }}">
                  <td><a href="{{ route('admin.ordersummary.index', ['order' => $order->order_number]) }}">{{ $order->order_number ?? ('#' . $order->id) }}</a></td>
                  <td>{{ optional($order->customer)->name ?? 'Guest' }}</td>
                  <td class="text-center">{{ (int) data_get($order, 'items_count', 0) }}</td>
                  <td class="text-end">{{ number_format((float) data_get($order, 'total_amount', 0), 2) }}</td>
                  <td>{{ ucfirst(str_replace('_', ' ', $order->payment_status ?? 'pending')) }}</td>
                  <td>{{ ucfirst($order->status ?? 'processing') }}</td>
                  <td>{{ optional($order->order_date)->format('M j, Y') ?? optional($order->created_at)->format('M j, Y') }}</td>
                  <td class="actions-cell">
                    <a href="{{ route('admin.ordersummary.index', ['order' => $order->order_number]) }}" class="btn btn-outline btn-sm btn-icon" aria-label="View order {{ $order->order_number ?? $order->id }}">
                      <i class="fa-solid fa-eye" aria-hidden="true"></i>
                    </a>
                    <button type="button" class="btn btn-outline btn-sm btn-icon btn-delete" data-order-id="{{ $order->id }}" aria-label="Delete order {{ $order->order_number ?? $order->id }}" title="Delete order">
                      <i class="fa-solid fa-trash" aria-hidden="true"></i>
                    </button>
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
</script>
@endpush
