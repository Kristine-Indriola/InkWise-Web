@extends('layouts.owner.app')
@section('content')
@include('layouts.owner.sidebar')

<link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">

<!-- Summary cards CSS (page-scoped) -->
<style>
  .owner-dashboard-shell {
    padding-right: 0;
    padding-bottom: 0;
    padding-left: 0;
  }

  .owner-dashboard-main {
    max-width: var(--owner-content-shell-max, 1440px);
    margin: 0;
    padding: 0 28px 36px 12px;
    width: 100%;
  }

  .owner-dashboard-inner {
    max-width: var(--owner-content-shell-max, 1390px);
    margin: 0;
    width: 100%;
    padding: 0;
  }

  .owner-dashboard-main .page-header {
    margin-bottom: 24px;
  }

  .summary-grid {
    margin: 0 0 20px 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 18px;
  }
  .summary-card {
    position: relative;
    background:#fff;
    border-radius:12px;
    padding:18px 22px 24px;
    box-shadow:0 14px 28px rgba(15,23,42,0.08);
    display:block;
    text-decoration:none;
    color:inherit;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
  }
  .summary-card:hover { transform: translateY(-4px); box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12); }
  .summary-card.is-active { box-shadow:0 20px 40px rgba(59, 130, 246, 0.25); outline:2px solid rgba(145, 167, 241, 0.45); }
  .summary-card-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
  .summary-card-label { font-size:0.92rem; font-weight:600; color:#475569; }
  .summary-card-value { display:block; font-size:1.6rem; font-weight:800; color:#0f172a; margin-top:6px; }
  .summary-card-meta { color:#6b7280; font-size:0.84rem; }
  .summary-card-chip {
    padding:4px 12px;
    border-radius:999px;
    background: rgba(148, 185, 255, 0.18);
    color: #5a8de0;
    font-weight: 600;
    font-size: 0.78rem;
  }

  .summary-card::after {
    content: "";
    position: absolute;
    left:22px;
    right:22px;
    bottom:14px;
    height:3px;
    border-radius:999px;
    background: linear-gradient(90deg, rgba(148, 185, 255, 0.45), rgba(111, 150, 227, 0.55));
  }

  .summary-card-chip.accent { background: rgba(148, 185, 255, 0.18); color: #5a8de0; }
  .summary-card.is-active .summary-card-chip { background: rgba(111, 150, 227, 0.28); }

  .dark-mode .summary-card::after {
    background: linear-gradient(90deg, rgba(148, 185, 255, 0.65), rgba(111, 150, 227, 0.75));
  }

  /* Dark mode styles */
  .dark-mode .summary-card { background:#374151; color:#f9fafb; }
  .dark-mode .summary-card-label { color:#d1d5db; }
  .dark-mode .summary-card-value { color:#f9fafb; }
  .dark-mode .summary-card-meta { color:#9ca3af; }

  .dark-mode body { background:#111827; }

  .table-empty td {
    text-align: center;
    padding: 24px;
    color: #6b7280;
    font-style: italic;
    background: rgba(148, 185, 255, 0.05);
  }

  .table-footnote {
    margin-top: 12px;
    color: #64748b;
    font-size: 0.82rem;
  }

  .is-hidden {
    display: none !important;
  }

  .orders-back-wrapper {
    margin-top: 14px;
  }

  .orders-back-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 14px;
    border-radius: 12px;
    border: 1px solid rgba(148, 185, 255, 0.35);
    background: #ffffff;
    color: #4c5b8c;
    font-weight: 600;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
    transition: transform 0.18s ease, box-shadow 0.18s ease;
  }

  .orders-back-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 34px rgba(15, 23, 42, 0.12);
  }

  .orders-back-button svg {
    width: 16px;
    height: 16px;
  }
</style>

<section class="main-content owner-dashboard-shell">
  <main class="materials-page admin-page-shell materials-container owner-dashboard-main" role="main">
    <header class="page-header">
      <div>
        <h1 class="page-title">Orders</h1>
        <p class="page-subtitle">Confirmed orders and workflow status</p>
      </div>
    </header>

  <div class="page-inner owner-dashboard-inner">
      @php
        $counts = $counts ?? ['total' => 0, 'confirmed' => 0, 'pending' => 0, 'cancelled' => 0];
        $orders = $orders ?? [];
        $filters = $filters ?? ['status' => null, 'search' => null, 'limit' => 50];
        $activeStatus = $filters['status'] ?? null;
        $pendingStatuses = ['pending', 'in_production', 'processing', 'to_receive'];
        $confirmedStatuses = ['confirmed', 'completed'];
        $cancelledStatuses = ['cancelled'];
        $queryBase = [];
        if (!empty($filters['search'])) {
          $queryBase['search'] = $filters['search'];
        }
        if (!empty($filters['limit']) && (int) $filters['limit'] !== 50) {
          $queryBase['limit'] = $filters['limit'];
        }
      @endphp

      <section class="summary-grid" aria-label="Orders summary">
        <a href="{{ route('owner.order.workflow', $queryBase) }}" class="summary-card{{ $activeStatus === null ? ' is-active' : '' }}" data-summary-card="total" data-status-filter="total">
          <div class="summary-card-header">
            <div style="display:flex;align-items:center;gap:8px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M3 7h18M3 12h18M3 17h18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="summary-card-label">Total Orders</span></div>
            <span class="summary-card-chip accent">All</span>
          </div>
          <span class="summary-card-value" data-orders-stat="total">{{ number_format($counts['total'] ?? 0) }}</span>
          <span class="summary-card-meta">Orders recorded</span>
        </a>

        <a href="{{ route('owner.order.workflow', array_merge($queryBase, ['status' => 'confirmed'])) }}" class="summary-card{{ in_array($activeStatus, $confirmedStatuses, true) ? ' is-active' : '' }}" data-summary-card="confirmed" data-status-filter="confirmed">
          <div class="summary-card-header">
            <div style="display:flex;align-items:center;gap:8px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M20 12a8 8 0 11-16 0 8 8 0 0116 0z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.5 12.5l1.8 1.8 4.2-4.2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="summary-card-label">Confirmed</span></div>
            <span class="summary-card-chip accent">Done</span>
          </div>
          <span class="summary-card-value" data-orders-stat="confirmed">{{ number_format($counts['confirmed'] ?? 0) }}</span>
          <span class="summary-card-meta">Confirmed orders</span>
        </a>

        <a href="{{ route('owner.order.workflow', array_merge($queryBase, ['status' => 'pending'])) }}" class="summary-card{{ in_array($activeStatus, $pendingStatuses, true) ? ' is-active' : '' }}" data-summary-card="pending" data-status-filter="pending">
          <div class="summary-card-header">
            <div style="display:flex;align-items:center;gap:8px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.6"/><path d="M12 8v5l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="summary-card-label">Pending</span></div>
            <span class="summary-card-chip accent">Review</span>
          </div>
          <span class="summary-card-value" data-orders-stat="pending">{{ number_format($counts['pending'] ?? 0) }}</span>
          <span class="summary-card-meta">Awaiting action</span>
        </a>

        <a href="{{ route('owner.order.workflow', array_merge($queryBase, ['status' => 'cancelled'])) }}" class="summary-card{{ in_array($activeStatus, $cancelledStatuses, true) ? ' is-active' : '' }}" data-summary-card="cancelled" data-status-filter="cancelled">
          <div class="summary-card-header">
            <div style="display:flex;align-items:center;gap:8px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 21a9 9 0 1 0-9-9 9 9 0 0 0 9 9Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="m9 9 6 6m-6 0 6-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="summary-card-label">Cancelled</span></div>
            <span class="summary-card-chip accent">Closed</span>
          </div>
          <span class="summary-card-value" data-orders-stat="cancelled">{{ number_format($counts['cancelled'] ?? 0) }}</span>
          <span class="summary-card-meta">Orders marked cancelled</span>
        </a>
      </section>
      <section class="materials-toolbar" aria-label="Order filters and actions">
        <div class="materials-toolbar__search">
          <form method="GET" action="{{ url()->current() }}" id="orders-search-form">
            <div class="search-input">
              <span class="search-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="8" stroke="#9aa6c2" stroke-width="2"/><path d="M21 21l-4.35-4.35" stroke="#9aa6c2" stroke-width="2" stroke-linecap="round"/></svg></span>
              <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search orders, customer or product" class="form-control">
            </div>
            <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
            <input type="hidden" name="limit" value="{{ $filters['limit'] ?? 50 }}">
            <button type="submit" class="btn btn-secondary">Search</button>
          </form>
          <div class="orders-back-wrapper{{ empty($filters['search']) ? ' is-hidden' : '' }}" id="orders-back-wrapper">
            <button type="button" class="orders-back-button" id="orders-back-button">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M15 5l-7 7 7 7" />
              </svg>
              <span>Back</span>
            </button>
          </div>
        </div>
        <div class="materials-toolbar__actions">
          <!-- placeholder for future actions (export, filters) -->
        </div>
      </section>

      <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Customer</th>
              <th>Date Ordered</th>
              <th>Order Details</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody id="orders-table-body">
            @forelse ($orders as $order)
              <tr data-order-id="{{ $order['id'] }}" data-order-status="{{ $order['status'] }}">
                <td class="fw-bold">{{ $order['order_number'] }}</td>
                <td>{{ $order['customer_name'] }}</td>
                <td>{{ $order['ordered_at'] }}</td>
                <td>{{ $order['summary'] }}</td>
                <td>
                  <span class="{{ $order['status_badge_class'] }}">{{ $order['status_label'] }}</span>
                </td>
              </tr>
            @empty
              <tr class="table-empty">
                <td colspan="5">No orders found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @php $limit = (int) ($filters['limit'] ?? 50); @endphp
      @if($limit > 0 && count($orders) === $limit)
        <p class="table-footnote">Showing the latest {{ number_format($limit) }} orders. Narrow your search or increase the limit for more results.</p>
      @endif
    </div>
  </main>
</section>

@endsection


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const rawParams = @json([
    'status' => $filters['status'] ?? null,
    'search' => $filters['search'] ?? null,
    'limit' => $filters['limit'] ?? 50
  ]);

  const state = {
    baseUrl: "{{ route('owner.order.workflow') }}",
    endpoint: "{{ route('owner.order.workflow.data') }}",
    params: normalizeParams(rawParams),
    refreshMs: 45000,
    isFetching: false,
    needsRefresh: false,
    numberFormatter: new Intl.NumberFormat('en-US')
  };

  const elements = {
    searchForm: document.getElementById('orders-search-form'),
    summaryCards: Array.from(document.querySelectorAll('[data-summary-card]')),
    backButton: document.getElementById('orders-back-button'),
    backWrapper: document.getElementById('orders-back-wrapper'),
  };

  elements.searchInput = elements.searchForm ? elements.searchForm.querySelector('input[name="search"]') : null;
  elements.statusInput = elements.searchForm ? elements.searchForm.querySelector('input[name="status"]') : null;
  elements.limitInput = elements.searchForm ? elements.searchForm.querySelector('input[name="limit"]') : null;

  if (elements.backButton) {
    elements.backButton.addEventListener('click', function () {
      elements.searchInput.value = '';
      updateParams({ search: null, status: null }, { sync: true });
      refreshData();
    });
  }

  function toggleBackButtonVisibility() {
    if (!elements.backWrapper) {
      return;
    }

    const shouldShow = !!state.params.search;
    elements.backWrapper.classList.toggle('is-hidden', !shouldShow);
  }

  function normalizeParams(params) {
    return {
      status: normalizeStatus(params.status),
      search: normalizeText(params.search),
      limit: normalizeLimit(params.limit),
    };
  }

  function normalizeStatus(value) {
    if (value === undefined || value === null) {
      return null;
    }

    const trimmed = String(value).trim().toLowerCase();
    if (trimmed === '' || trimmed === 'all' || trimmed === 'total') {
      return null;
    }

    return trimmed;
  }

  function normalizeText(value) {
    if (value === undefined || value === null) {
      return null;
    }

    const trimmed = String(value).trim();
    return trimmed === '' ? null : trimmed;
  }

  function normalizeLimit(value) {
    const numeric = parseInt(value, 10);
    if (Number.isNaN(numeric)) {
      return 50;
    }

    return Math.max(10, Math.min(numeric, 200));
  }

  function deriveSummaryKey(status) {
    const normalized = normalizeStatus(status);
    if (!normalized) {
      return 'total';
    }

    if (['confirmed', 'completed'].includes(normalized)) {
      return 'confirmed';
    }

    if (['pending', 'in_production', 'processing', 'to_receive'].includes(normalized)) {
      return 'pending';
    }

    if (['cancelled'].includes(normalized)) {
      return 'cancelled';
    }

    return normalized;
  }

  function syncHiddenInputs() {
    if (elements.statusInput) {
      elements.statusInput.value = state.params.status ?? '';
    }

    if (elements.limitInput) {
      elements.limitInput.value = state.params.limit ?? '';
    }
  }

  function syncSearchInput() {
    if (elements.searchInput && document.activeElement !== elements.searchInput) {
      elements.searchInput.value = state.params.search ?? '';
    }
  }

  function setActiveSummaryCard() {
    const activeKey = deriveSummaryKey(state.params.status);
    elements.summaryCards.forEach((card) => {
      const cardKey = deriveSummaryKey(card.dataset.statusFilter);
      card.classList.toggle('is-active', cardKey === activeKey);
    });
  }

  function buildQuery() {
    const params = new URLSearchParams();

    if (state.params.search) {
      params.set('search', state.params.search);
    }

    if (state.params.status) {
      params.set('status', state.params.status);
    }

    if (state.params.limit && state.params.limit !== 50) {
      params.set('limit', state.params.limit);
    }

    return params.toString();
  }

  function syncUrl() {
    const query = buildQuery();
    const nextUrl = query ? `${state.baseUrl}?${query}` : state.baseUrl;
    window.history.replaceState({ path: nextUrl }, '', nextUrl);
  }

  function updateParams(changes, options = {}) {
    const next = { ...state.params };

    if (Object.prototype.hasOwnProperty.call(changes, 'status')) {
      next.status = normalizeStatus(changes.status);
    }

    if (Object.prototype.hasOwnProperty.call(changes, 'search')) {
      next.search = normalizeText(changes.search);
    }

    if (Object.prototype.hasOwnProperty.call(changes, 'limit')) {
      next.limit = normalizeLimit(changes.limit);
    }

    state.params = next;
    syncHiddenInputs();
    syncSearchInput();
    setActiveSummaryCard();
    toggleBackButtonVisibility();

    if ((options && options.sync) !== false) {
      syncUrl();
    }
  }

  function updateCounts(counts) {
    if (!counts) {
      return;
    }

    Object.entries(counts).forEach(([key, value]) => {
      document.querySelectorAll('[data-orders-stat="' + key + '"]').forEach((node) => {
        node.textContent = state.numberFormatter.format(value ?? 0);
      });
    });
  }

  function renderOrders(orders) {
    const tbody = document.getElementById('orders-table-body');
    if (!tbody) {
      return;
    }

    tbody.innerHTML = '';

    if (!orders || orders.length === 0) {
      const emptyRow = document.createElement('tr');
      emptyRow.className = 'table-empty';
      const emptyCell = document.createElement('td');
      emptyCell.colSpan = 5;
      emptyCell.textContent = 'No orders found.';
      emptyRow.appendChild(emptyCell);
      tbody.appendChild(emptyRow);
      return;
    }

    orders.forEach((order) => {
      const row = document.createElement('tr');
      row.dataset.orderId = order.id;
      row.dataset.orderStatus = order.status;

      const columns = [
        { className: 'fw-bold', text: order.order_number },
        { text: order.customer_name },
        { text: order.ordered_at },
        { text: order.summary },
      ];

      columns.forEach((column) => {
        const cell = document.createElement('td');
        if (column.className) {
          cell.className = column.className;
        }
        cell.textContent = column.text ?? '';
        row.appendChild(cell);
      });

      const statusCell = document.createElement('td');
      const badge = document.createElement('span');
      badge.className = order.status_badge_class || 'badge';
      badge.textContent = order.status_label || '';
      statusCell.appendChild(badge);
      row.appendChild(statusCell);

      tbody.appendChild(row);
    });
  }

  async function refreshData() {
    if (document.hidden) {
      return;
    }

    if (state.isFetching) {
      state.needsRefresh = true;
      return;
    }

    state.isFetching = true;
    state.needsRefresh = false;

    try {
      const query = buildQuery();
      const response = await fetch(state.endpoint + (query ? '?' + query : ''), {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
      });

      if (!response.ok) {
        throw new Error('Failed to refresh orders');
      }

      const payload = await response.json();

      if (payload.filters) {
        updateParams(payload.filters, { sync: false });
      }

      updateCounts(payload.counts);
      renderOrders(payload.orders);
    } catch (error) {
      console.warn('Owner order refresh error:', error);
    } finally {
      state.isFetching = false;

      if (state.needsRefresh) {
        state.needsRefresh = false;
        refreshData();
      }
    }
  }

  if (elements.searchForm) {
    elements.searchForm.addEventListener('submit', (event) => {
      event.preventDefault();
      const value = elements.searchInput ? elements.searchInput.value : '';
      updateParams({ search: value });
      refreshData();
    });

    if (elements.searchInput) {
      elements.searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
          event.preventDefault();
          event.stopPropagation();
          elements.searchInput.value = '';
          updateParams({ search: null });
          refreshData();
        }
      });
    }
  }

  elements.summaryCards.forEach((card) => {
    card.addEventListener('click', (event) => {
      if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
      }

      event.preventDefault();
      updateParams({ status: card.dataset.statusFilter ?? null });
      refreshData();
    });
  });

  syncHiddenInputs();
  syncSearchInput();
  setActiveSummaryCard();
  toggleBackButtonVisibility();
  syncUrl();

  updateCounts(@json($counts));
  setInterval(refreshData, state.refreshMs);

  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
      refreshData();
    }
  });

  refreshData();
});
</script>
@endpush


