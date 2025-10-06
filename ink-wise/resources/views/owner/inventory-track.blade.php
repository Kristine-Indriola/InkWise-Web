@php
    $materials = $materials ?? collect();
@endphp

@extends('layouts.owner.app')

@push('styles')
  <link rel="stylesheet" href="css/owner/staffapp.css">
@endpush

@section('content')
@include('layouts.owner.sidebar')

<link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">

<!-- Page-scoped layout overrides: keep materials.css untouched, adjust only this blade -->
<style>
  /* Reduce outer page padding so content isn't pushed under sidebar/topbar */
  main.materials-page.admin-page-shell.materials-container {
    /* Add top padding to keep content below any fixed topbar; respects a CSS variable if available */
    padding: 12px 18px 18px 18px !important;
    padding-top: calc(var(--owner-topbar-height, 64px) + 12px) !important;
    margin-left: 230px !important;
  }

  /* Constrain inner content width so tables don't overflow under the sidebar */
  main.materials-page .page-inner {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 10px;
  }

  /* Make table wrapper more compact and allow horizontal scrolling on small screens */
  main.materials-page .table-wrapper {
    padding: 10px !important;
    overflow-x: auto;
  }

  /* Avoid forcing a very large min-width on the inventory table; let it be responsive */
  main.materials-page .table {
    min-width: 720px; /* modest minimum */
    font-size: 0.92rem;
  }

  /* Tweak table cell padding for denser rows on owner pages */
  main.materials-page .table tbody td,
  main.materials-page .table thead th {
    padding: 10px 12px;
  }

  /* Dark mode for table */
  .dark-mode .table { background:#1f2937; color:#f9fafb; }
  .dark-mode .table thead th { background:#374151; color:#f9fafb; border-color:#4b5563; }
  .dark-mode .table tbody td { border-color:#4b5563; }
  .dark-mode .table tbody tr:hover { background:#374151; }

  /* Dark mode for panel */
  .dark-mode .panel { background:#374151; color:#f9fafb; }
  .dark-mode .panel h3 { color:#f9fafb; }

  /* Dark mode for body */
  .dark-mode body { background:#111827; }

  /* Ensure the header doesn't get extra left padding that misaligns the title */
  main.materials-page .page-header { padding-left: 8px !important; position:relative; z-index:5; }

  /* Small screens: reduce min-width and allow better fit */
  @media (max-width: 900px) {
    main.materials-page .page-inner { max-width: 100%; padding: 0 8px; }
    main.materials-page .table { min-width: 600px; font-size:0.9rem; }
    main.materials-page { padding: 10px !important; margin-left: 90px !important; }
  }

  /* Summary cards for inventory (page-scoped) */
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
</style>

<main class="materials-page admin-page-shell materials-container" role="main">
  <header class="page-header">
    <div>
      <h1 class="page-title">Inventory</h1>
      <p class="page-subtitle">Track stock levels across materials</p>
    </div>
  </header>
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
</main>

@endsection

