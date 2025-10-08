@php
    $materials = $materials ?? collect();
@endphp

@extends('layouts.owner.app')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/owner/staffapp.css') }}">
@endpush

@section('content')
@include('layouts.owner.sidebar')

<link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">

<!-- Page-scoped layout overrides aligned with owner dashboard sizing -->
<style>
  .owner-dashboard-shell {
    padding: 20px 24px 32px;
    padding-left: clamp(24px, 3vw, 48px);
  }

  .owner-dashboard-main {
    max-width: 1440px;
    margin: 0 auto;
    padding: 28px 28px 36px;
    width: 100%;
  }

  .owner-dashboard-inner {
    max-width: 1390px;
    margin: 0 auto;
    width: 100%;
    padding: 0;
  }

  .owner-dashboard-main .page-header {
    margin-bottom: 24px;
  }

  .owner-dashboard-inner .panel {
    width: 100%;
    max-width: 100%;
    background: #fff;
    border-radius: 18px;
    border: 1px solid rgba(148, 185, 255, 0.22);
    box-shadow: 0 18px 36px rgba(15, 23, 42, 0.08);
    padding: 24px 24px 28px;
    margin: 0;
  }

  .owner-dashboard-inner .panel h3 {
    margin: 0 0 20px;
    font-size: 1.15rem;
    font-weight: 700;
    color: #0f172a;
  }

  .owner-dashboard-inner .materials-toolbar {
    margin-bottom: 20px;
  }

  .owner-dashboard-inner .table-wrapper {
    margin-top: 18px;
    border-radius: 14px;
    border: 1px solid rgba(148, 185, 255, 0.2);
    background: #f8fbff;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
    overflow-x: auto;
    overflow-y: hidden;
  }

  .owner-dashboard-inner .table {
    min-width: 720px;
    font-size: 0.92rem;
  }

  .owner-dashboard-inner .table tbody td,
  .owner-dashboard-inner .table thead th {
    padding: 12px 18px;
  }

  .owner-dashboard-inner .table thead th {
    background: rgba(148, 185, 255, 0.16);
    color: #1e293b;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.02em;
  }

  .owner-dashboard-inner .table tbody tr:hover {
    background: rgba(148, 185, 255, 0.08);
  }

  .summary-grid {
    margin: 0 0 20px 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
  }

  .summary-card {
    position: relative;
    background: #fff;
    border-radius: 12px;
    padding: 16px 20px 24px;
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
    display: block;
    text-decoration: none;
    color: inherit;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
  }

  .summary-card::after {
    content: "";
    position: absolute;
    left: 20px;
    right: 20px;
    bottom: 14px;
    height: 3px;
    border-radius: 999px;
    background: linear-gradient(90deg, rgba(148, 185, 255, 0.45), rgba(111, 150, 227, 0.55));
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
  }

  .summary-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
  }

  .summary-card-label {
    font-size: 0.9rem;
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

  /* Dark mode adjustments */
  .dark-mode body { background: #111827; }
  .dark-mode .summary-card { background: #374151; color: #f9fafb; box-shadow: 0 12px 24px rgba(15, 23, 42, 0.4); }
  .dark-mode .summary-card::after { background: linear-gradient(90deg, rgba(148, 185, 255, 0.65), rgba(111, 150, 227, 0.75)); }
  .dark-mode .summary-card-label { color: #d1d5db; }
  .dark-mode .summary-card-value { color: #f9fafb; }
  .dark-mode .summary-card-meta { color: #9ca3af; }
  .dark-mode .summary-card-chip { background: rgba(148, 185, 255, 0.28); color: #cbd9ff; }
  .dark-mode .table { background: #1f2937; color: #f9fafb; }
  .dark-mode .table thead th { background: #374151; color: #f9fafb; border-color: #4b5563; }
  .dark-mode .table tbody td { border-color: #4b5563; }
  .dark-mode .table tbody tr:hover { background: #374151; }
  .dark-mode .panel {
    background: #1f2937;
    border-color: rgba(148, 185, 255, 0.35);
    box-shadow: 0 16px 34px rgba(0, 0, 0, 0.35);
    color: #f9fafb;
  }
  .dark-mode .panel h3 { color: #f9fafb; }
  .dark-mode .owner-dashboard-inner .table-wrapper {
    background: #161e2e;
    border-color: rgba(148, 185, 255, 0.28);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
  }
  .dark-mode .owner-dashboard-inner .table tbody tr:hover {
    background: rgba(148, 185, 255, 0.12);
  }

  @media (max-width: 900px) {
    .owner-dashboard-shell { padding: 16px; }
    .owner-dashboard-main { padding: 24px 20px 32px; }
    .owner-dashboard-inner { padding: 0 4px; }
    .owner-dashboard-inner .table { min-width: 600px; font-size: 0.9rem; }
  }
</style>

<section class="main-content owner-dashboard-shell">
<main class="materials-page admin-page-shell materials-container owner-dashboard-main" role="main">
  <header class="page-header">
    <div>
      <h1 class="page-title">Inventory</h1>
      <p class="page-subtitle">Track stock levels across materials</p>
    </div>
  </header>
  <div class="page-inner owner-dashboard-inner">
      @php
          // Compute counts using provided $materials when possible to avoid extra queries
          $lowCount = 0; $outCount = 0; $totalMaterials = 0;
          if (isset($materials) && $materials instanceof \Illuminate\Support\Collection) {
              $totalMaterials = $materials->count();
              foreach ($materials as $m) {
                  $stock = $m->inventory->stock_level ?? 0;
                  $reorder = $m->inventory->reorder_level ?? 0;
                  if ($stock <= 0) { $outCount++; }
                  elseif ($stock <= $reorder) { $lowCount++; }
              }
          } elseif (class_exists(\App\Models\Material::class)) {
              try {
                  $lowCount = \App\Models\Material::whereHas('inventory', function($q) {
                      $q->whereColumn('stock_level', '<=', 'reorder_level')
                        ->where('stock_level', '>', 0);
                  })->count();

                  $outCount = \App\Models\Material::whereHas('inventory', function($q) {
                      $q->where('stock_level', '<=', 0);
                  })->count();

                  $totalMaterials = \App\Models\Material::count();
              } catch (\Exception $e) {
                  $lowCount = $outCount = $totalMaterials = 0;
              }
          }
          $notifCount = $lowCount + $outCount;
      @endphp

  <section class="summary-grid" aria-label="Inventory summary">
        <a href="{{ url()->current() }}" class="summary-card">
          <div class="summary-card-header">
            <div style="display:flex;align-items:center;gap:8px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M3 7h18M3 12h18M3 17h18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="summary-card-label">Total Items</span></div>
            <span class="summary-card-chip accent">All</span>
          </div>
          <span class="summary-card-value">{{ number_format($totalMaterials) }}</span>
          <span class="summary-card-meta">Materials tracked</span>
        </a>

        <a href="{{ request()->fullUrlWithQuery(['stock' => 'in']) }}" class="summary-card">
          <div class="summary-card-header">
            <div style="display:flex;align-items:center;gap:8px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 20V10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 14l4-4 4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="summary-card-label">In Stock</span></div>
            <span class="summary-card-chip accent">Available</span>
          </div>
          <span class="summary-card-value">{{ number_format(max(0, $totalMaterials - $notifCount)) }}</span>
          <span class="summary-card-meta">Sufficient stock</span>
        </a>

        <a href="{{ request()->fullUrlWithQuery(['stock' => 'low']) }}" class="summary-card">
          <div class="summary-card-header">
            <div style="display:flex;align-items:center;gap:8px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 8v5l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="summary-card-label">Low Stock</span></div>
            <span class="summary-card-chip accent">Attention</span>
          </div>
          <span class="summary-card-value">{{ number_format($lowCount) }}</span>
          <span class="summary-card-meta">Reorder recommended</span>
        </a>

        <a href="{{ request()->fullUrlWithQuery(['stock' => 'out']) }}" class="summary-card">
          <div class="summary-card-header">
            <div style="display:flex;align-items:center;gap:8px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 8v8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 12h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="summary-card-label">Out of Stock</span></div>
            <span class="summary-card-chip accent">Critical</span>
          </div>
          <span class="summary-card-value">{{ number_format($outCount) }}</span>
          <span class="summary-card-meta">Requires immediate restock</span>
        </a>
      </section>

       
  <div class="panel">
        <h3>Stock Levels</h3>

         {{-- SEARCH FORM --}}
      <section class="materials-toolbar" aria-label="Inventory filters and actions">
        <div class="materials-toolbar__search">
          <form method="GET" action="{{ url()->current() }}">
            <div class="search-input">
              <span class="search-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="8" stroke="#9aa6c2" stroke-width="2"/><path d="M21 21l-4.35-4.35" stroke="#9aa6c2" stroke-width="2" stroke-linecap="round"/></svg></span>
              <input class="form-control" type="text" name="search" placeholder="Search by item name or category..." value="{{ request()->input('search') }}" />
            </div>
            <button type="submit" class="btn btn-secondary">Search</button>
          </form>
        </div>
        <div class="materials-toolbar__actions"></div>
      </section>


        @if(request()->has('search') && request()->input('search') != '')
          <div style="margin: 10px 0;">
            <a href="{{ route('owner.inventory-track') }}" 
              style="display:inline-flex; align-items:center; gap:6px;
                      background:#f9fafb; color:#1f2937; padding:6px 14px;
                      border-radius:6px; border:1px solid #d1d5db; 
                      font-weight:600; font-size:15px; text-decoration:none;
                      cursor:pointer; transition:all 0.2s ease;">

              <!-- SVG Arrow Icon -->
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" 
                  viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
              </svg>
            </a>
          </div>
        @endif

    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>Item Name</th>
            <th>Category</th>
            <th>Stock Quantity</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse($materials as $material)
            @php
              $stock = $material->inventory->stock_level ?? 0;
              $reorder = $material->inventory->reorder_level ?? 0;

              if ($stock <= 0) {
                  $badgeClass = 'stock-critical';
                  $statusText = 'Out of Stock';
              } elseif ($stock <= $reorder) {
                  $badgeClass = 'stock-low';
                  $statusText = 'Low Stock';
              } else {
                  $badgeClass = 'stock-ok';
                  $statusText = 'In Stock';
              }
            @endphp
            <tr>
              <td class="fw-bold">{{ $material->material_name }}</td>
              <td>{{ $material->material_type }}</td>
              <td>{{ $stock }}</td>
              <td><span class="badge {{ $badgeClass }}">{{ $statusText }}</span></td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="text-center" style="padding:18px; color:#64748b;">No materials found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    </div>
  </div>
</main>
</section>

@endsection

