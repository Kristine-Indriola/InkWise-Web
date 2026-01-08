@extends('layouts.owner.app')
@section('content')
@include('layouts.owner.sidebar')

<link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">

<style>
  .owner-dashboard-shell { padding-right: 0; padding-bottom: 0; padding-left: 0; }
  .owner-dashboard-main { max-width: var(--owner-content-shell-max, 1440px); margin: 0; padding: 0 28px 36px 12px; width: 100%; }
  .owner-dashboard-inner { max-width: var(--owner-content-shell-max, 1390px); margin: 0; width: 100%; padding: 0; }
  .page-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
  .page-header__quick-actions { display: flex; align-items: center; gap: 8px; }
  .pill-link { display: inline-flex; align-items: center; justify-content: center; padding: 10px 16px; border-radius: 999px; background: #f3f4ff; color: #4f46e5; font-weight: 600; text-decoration: none; border: 1px solid rgba(79, 70, 229, 0.2); transition: box-shadow 0.18s ease, transform 0.18s ease; }
  .pill-link:hover { transform: translateY(-1px); box-shadow: 0 10px 24px rgba(79, 70, 229, 0.18); }
  .materials-toolbar { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 12px; margin: 24px 0 16px; }
  .materials-toolbar__search { display: flex; align-items: stretch; gap: 8px; flex-wrap: wrap; }
  .search-input { position: relative; flex: 1 0 260px; }
  .search-input input[type='text'] { width: 100%; padding: 10px 12px 10px 36px; border-radius: 12px; border: 1px solid #dbe3f5; background: #fff; font-size: 0.95rem; transition: border-color 0.18s ease, box-shadow 0.18s ease; }
  .search-input input[type='text']:focus { outline: none; border-color: #7c88d0; box-shadow: 0 0 0 3px rgba(124, 136, 208, 0.15); }
  .search-icon { position: absolute; top: 50%; left: 12px; transform: translateY(-50%); color: #9aa6c2; }
  .btn { display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; border: 1px solid transparent; padding: 10px 16px; font-weight: 600; cursor: pointer; transition: background 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease; }
  .btn.btn-secondary { background: #4f46e5; color: #fff; border-color: #4f46e5; }
  .btn.btn-secondary:hover { transform: translateY(-1px); box-shadow: 0 10px 22px rgba(79, 70, 229, 0.2); }
  .orders-back-button { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 12px; border: 1px solid rgba(148, 185, 255, 0.35); background: #ffffff; color: #4c5b8c; font-weight: 600; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08); transition: transform 0.18s ease, box-shadow 0.18s ease; }
  .orders-back-button:hover { transform: translateY(-1px); box-shadow: 0 14px 30px rgba(15, 23, 42, 0.12); }
  .table-wrapper { overflow-x: auto; border-radius: 16px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 14px 32px rgba(15, 23, 42, 0.08); background: #fff; }
  table.table { width: 100%; border-collapse: collapse; min-width: 780px; }
  table.table thead { background: #f8faff; }
  table.table th, table.table td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #eef2f8; font-size: 0.92rem; color: #1f2937; }
  table.table th { font-size: 0.88rem; letter-spacing: 0.04em; text-transform: uppercase; color: #6b7280; }
  table.table tbody tr:hover { background: rgba(148, 185, 255, 0.08); }
  .numeric-cell { text-align: right; font-variant-numeric: tabular-nums; }
  .badge { display: inline-flex; align-items: center; justify-content: center; padding: 4px 10px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; border-radius: 10px; letter-spacing: 0.04em; }
  .badge.stock-ok { background: #e8f9f0; color: #047857; border: 1px solid rgba(16, 185, 129, 0.6); }
  .badge.stock-low { background: #eef2ff; color: #4338ca; border: 1px solid rgba(79, 70, 229, 0.45); }
  .badge.stock-critical { background: #fef2f2; color: #b91c1c; border: 1px solid rgba(239, 68, 68, 0.6); }
  .table-empty td { text-align: center; padding: 24px; font-style: italic; color: #6b7280; }
  .orders-footer { margin-top: 12px; color: #64748b; font-size: 0.82rem; }
  .is-hidden { display: none !important; }
  @media (max-width: 720px) { .materials-toolbar { flex-direction: column; align-items: stretch; } .materials-toolbar__search { width: 100%; } .orders-back-button { width: 100%; justify-content: center; } }
</style>

<section class="main-content owner-dashboard-shell">
  <main class="materials-page admin-page-shell materials-container owner-dashboard-main" role="main">
    <header class="page-header">
      <div>
        <h1 class="page-title">Archived Orders</h1>
        <p class="page-subtitle">Completed or cancelled orders that have been archived.</p>
      </div>
      <div class="page-header__quick-actions">
        <a href="{{ route('owner.order.workflow') }}" class="pill-link">Active Orders</a>
      </div>
    </header>

    <div class="page-inner owner-dashboard-inner">
      @php
        $filters = $filters ?? ['search' => null, 'limit' => 50];
        $hasOrders = !empty($orders);
      @endphp

      <section class="materials-toolbar" aria-label="Archived orders search">
        <div class="materials-toolbar__search">
          <form method="GET" action="{{ url()->current() }}" id="archived-orders-search-form">
            <div class="search-input">
              <span class="search-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <circle cx="11" cy="11" r="8" stroke="#9aa6c2" stroke-width="2" />
                  <path d="M21 21l-4.35-4.35" stroke="#9aa6c2" stroke-width="2" stroke-linecap="round" />
                </svg>
              </span>
              <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search archived orders or customers">
            </div>
            <input type="hidden" name="limit" value="{{ $filters['limit'] ?? 50 }}">
            <button type="submit" class="btn btn-secondary">Search</button>
          </form>
          <button type="button" class="orders-back-button{{ empty($filters['search']) ? ' is-hidden' : '' }}" id="archived-orders-clear">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" width="16" height="16">
              <path d="M15 5l-7 7 7 7" />
            </svg>
            <span>Clear Search</span>
          </button>
        </div>
      </section>

      <div class="table-wrapper">
        <table class="table" role="grid">
          <thead>
            <tr>
              <th scope="col">Order #</th>
              <th scope="col">Customer</th>
              <th scope="col" class="numeric-cell">Items</th>
              <th scope="col" class="numeric-cell">Total</th>
              <th scope="col">Payment</th>
              <th scope="col">Status</th>
              <th scope="col">Placed</th>
              <th scope="col">Archived By</th>
              <th scope="col">Archived On</th>
            </tr>
          </thead>
          <tbody>
            @forelse($orders as $order)
              <tr>
                <td>{{ $order['order_number'] }}</td>
                <td>{{ $order['customer_name'] }}</td>
                <td class="numeric-cell">{{ number_format((int) ($order['items_count'] ?? 0)) }}</td>
                <td class="numeric-cell">{{ $order['total'] }}</td>
                <td><span class="{{ $order['payment_badge_class'] }}">{{ $order['payment_label'] }}</span></td>
                <td><span class="{{ $order['status_badge_class'] }}">{{ $order['status_label'] }}</span></td>
                <td>{{ $order['ordered_at'] }}</td>
                <td>{{ $order['archived_by'] }}</td>
                <td>{{ $order['archived_at'] }}</td>
              </tr>
            @empty
              <tr class="table-empty">
                <td colspan="9">No archived orders found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @php $limit = (int) ($filters['limit'] ?? 50); @endphp
      @if($limit > 0 && $hasOrders && count($orders) === $limit)
        <p class="orders-footer">Showing the latest {{ number_format($limit) }} archived orders.</p>
      @endif
    </div>
  </main>
</section>

@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('archived-orders-search-form');
    var clearButton = document.getElementById('archived-orders-clear');

    if (!form || !clearButton) {
      return;
    }

    clearButton.addEventListener('click', function () {
      var searchInput = form.querySelector('input[name="search"]');
      if (searchInput) {
        searchInput.value = '';
      }
      form.submit();
    });
  });
</script>
@endpush
