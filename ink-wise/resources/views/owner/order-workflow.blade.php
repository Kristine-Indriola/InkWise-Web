@extends('layouts.owner.app')
@section('content')
@include('layouts.owner.sidebar')

<link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">

<!-- Summary cards CSS (page-scoped) -->
<style>
  .summary-grid { margin:0 0 12px 0; display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px; }
  .summary-card { background:#fff; border-radius:10px; padding:12px; box-shadow:0 6px 18px rgba(15,23,42,0.04); display:block; text-decoration:none; color:inherit; }
  .summary-card-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; }
  .summary-card-label { font-size:0.86rem; color:#475569; }
  .summary-card-value { display:block; font-size:1.25rem; font-weight:800; color:#0f172a; margin-top:4px; }
  .summary-card-meta { color:#6b7280; font-size:0.82rem; }

  /* Dark mode styles */
  .dark-mode .summary-card { background:#374151; color:#f9fafb; }
  .dark-mode .summary-card-label { color:#d1d5db; }
  .dark-mode .summary-card-value { color:#f9fafb; }
  .dark-mode .summary-card-meta { color:#9ca3af; }

  .dark-mode body { background:#111827; }
</style>

<section class="main-content">
  <main class="materials-page admin-page-shell materials-container" role="main">
    <header class="page-header">
      <div>
        <h1 class="page-title">Orders</h1>
        <p class="page-subtitle">Confirmed orders and workflow status</p>
      </div>
    </header>

    <div class="page-inner">
      @php
        $totalOrders = 0; $confirmed = 0; $pending = 0;
        if (isset($orders) && $orders instanceof \Illuminate\Support\Collection) {
          $totalOrders = $orders->count();
          $confirmed = $orders->filter(fn($o) => strtolower(trim($o->status ?? '')) === 'confirmed')->count();
          $pending = $orders->filter(fn($o) => in_array(strtolower(trim($o->status ?? '')), ['pending','processing']))->count();
        } elseif (class_exists(\App\Models\Order::class)) {
          try {
            $totalOrders = \App\Models\Order::count();
            $confirmed = \App\Models\Order::whereRaw("LOWER(COALESCE(status, '')) = 'confirmed'")->count();
            $pending = \App\Models\Order::whereIn('status', ['pending','processing'])->count();
          } catch (\Exception $e) {}
        }
      @endphp

      <section class="summary-grid" aria-label="Orders summary">
        <a href="{{ url()->current() }}" class="summary-card">
          <div class="summary-card-header">
            <div style="display:flex;align-items:center;gap:8px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M3 7h18M3 12h18M3 17h18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="summary-card-label">Total Orders</span></div>
            <span class="summary-card-chip accent">All</span>
          </div>
          <span class="summary-card-value">{{ number_format($totalOrders) }}</span>
          <span class="summary-card-meta">Orders recorded</span>
        </a>

        <a href="{{ request()->fullUrlWithQuery(['status' => 'confirmed']) }}" class="summary-card">
          <div class="summary-card-header">
            <div style="display:flex;align-items:center;gap:8px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M20 12a8 8 0 11-16 0 8 8 0 0116 0z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.5 12.5l1.8 1.8 4.2-4.2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="summary-card-label">Confirmed</span></div>
            <span class="summary-card-chip accent">Done</span>
          </div>
          <span class="summary-card-value">{{ number_format($confirmed) }}</span>
          <span class="summary-card-meta">Confirmed orders</span>
        </a>

        <a href="{{ request()->fullUrlWithQuery(['status' => 'pending']) }}" class="summary-card">
          <div class="summary-card-header">
            <div style="display:flex;align-items:center;gap:8px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.6"/><path d="M12 8v5l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="summary-card-label">Pending</span></div>
            <span class="summary-card-chip accent">Review</span>
          </div>
          <span class="summary-card-value">{{ number_format($pending) }}</span>
          <span class="summary-card-meta">Awaiting action</span>
        </a>
      </section>
      <section class="materials-toolbar" aria-label="Order filters and actions">
        <div class="materials-toolbar__search">
          <form method="GET" action="{{ url()->current() }}">
            <div class="search-input">
              <span class="search-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="8" stroke="#9aa6c2" stroke-width="2"/><path d="M21 21l-4.35-4.35" stroke="#9aa6c2" stroke-width="2" stroke-linecap="round"/></svg></span>
              <input type="text" name="search" value="{{ request('search') }}" placeholder="Search orders, customer or product" class="form-control">
            </div>
            <button type="submit" class="btn btn-secondary">Search</button>
          </form>
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
          <tbody>
            {{-- Example rows; replace with dynamic data in controller --}}
            <tr>
              <td class="fw-bold">#1001</td>
              <td>Leanne Mae</td>
              <td>2025-04-25</td>
              <td>Wedding Invitations — 100 pcs</td>
              <td><span class="badge stock-ok">Confirmed</span></td>
            </tr>
            <tr>
              <td class="fw-bold">#1002</td>
              <td>Kristine Mae</td>
              <td>2025-04-26</td>
              <td>Keychains — 20 pcs</td>
              <td><span class="badge stock-low">Pending</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</section>

@endsection


