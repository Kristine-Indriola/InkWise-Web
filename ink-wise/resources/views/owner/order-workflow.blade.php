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

  .dark-mode .summary-card::after {
    background: linear-gradient(90deg, rgba(148, 185, 255, 0.65), rgba(111, 150, 227, 0.75));
  }

  /* Dark mode styles */
  .dark-mode .summary-card { background:#374151; color:#f9fafb; }
  .dark-mode .summary-card-label { color:#d1d5db; }
  .dark-mode .summary-card-value { color:#f9fafb; }
  .dark-mode .summary-card-meta { color:#9ca3af; }

  .dark-mode body { background:#111827; }
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


