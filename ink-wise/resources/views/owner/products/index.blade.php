@extends('layouts.owner.app')

@section('content')
@include('layouts.owner.sidebar')

<link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">

<!-- Inline critical CSS fallback to ensure table is visible and matches admin styles -->
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

  .page-title {
    font-size: 1.8rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 6px;
  }

  .page-subtitle {
    margin: 0;
    color: #6b7280;
    font-size: 0.98rem;
  }

  .summary-grid {
    margin: 0 0 20px 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 18px;
  }

  .summary-card {
    position: relative;
    background: #fff;
    border-radius: 12px;
    padding: 18px 22px 24px;
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
    display: block;
    text-decoration: none;
    color: inherit;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
  }

  .summary-card::after {
    content: "";
    position: absolute;
    left: 22px;
    right: 22px;
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

  .table-wrapper {
    border-radius: 14px;
    border: 1px solid rgba(148, 185, 255, 0.2);
    background: #f8fbff;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6), 0 16px 32px rgba(15, 23, 42, 0.08);
    overflow-x: auto;
  }

  .table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.95rem;
    color: #0f172a;
    min-width: 880px;
  }

  .table thead th {
    background: rgba(148, 185, 255, 0.16);
    padding: 14px 20px;
    text-transform: uppercase;
    font-size: 0.78rem;
    letter-spacing: 0.06em;
    font-weight: 700;
  }

  .table tbody td {
    padding: 14px 20px;
    border-bottom: 1px solid rgba(148, 185, 255, 0.12);
    vertical-align: middle;
  }

  .table tbody tr:last-child td {
    border-bottom: none;
  }

  .table tbody tr:hover {
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

  .fw-bold { font-weight: 700; }

  @media (max-width: 900px) {
    .owner-dashboard-shell { padding: 16px; }
    .owner-dashboard-main { padding: 24px 20px 32px; }
    .owner-dashboard-inner { padding: 0 4px; }
    .table { min-width: 720px; }
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
  .dark-mode .table-wrapper { background: #1f2937; border-color: rgba(148, 185, 255, 0.32); box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04); }
  .dark-mode .table { color: #f9fafb; }
  .dark-mode .table thead th { background: rgba(148, 185, 255, 0.22); color: #0f172a; }
  .dark-mode .table tbody td { border-color: rgba(148, 185, 255, 0.18); }
  .dark-mode .table tbody tr:hover { background: rgba(148, 185, 255, 0.12); }
</style>

<section class="main-content owner-dashboard-shell">
  <main class="materials-page admin-page-shell materials-container owner-dashboard-main" role="main">
  <header class="page-header">
    <div>
      <h1 class="page-title">Products</h1>
      <p class="page-subtitle">Products catalog &amp; stock overview</p>
    </div>
  </header>

  @php
    // Compute summary counts from the passed collection when available,
    // otherwise attempt to query the Product model (safe fallback).
    $totalProducts = 0;
    $invitationCount = 0;
    $giveawayCount = 0;
    $inStockCount = 0;

    if (isset($products) && $products instanceof \Illuminate\Support\Collection) {
        $totalProducts = $products->count();
        $invitationCount = $products->where('product_type', 'invitation')->count();
        $giveawayCount = $products->where('product_type', 'giveaway')->count();
        $inStockCount = $products->filter(fn($p) => (int)($p->stock ?? 0) > 0)->count();
    } elseif (class_exists(\App\Models\Product::class)) {
        try {
            $totalProducts = \App\Models\Product::count();
            $invitationCount = \App\Models\Product::where('product_type', 'invitation')->count();
            $giveawayCount = \App\Models\Product::where('product_type', 'giveaway')->count();
            $inStockCount = \App\Models\Product::where('stock', '>', 0)->count();
        } catch (\Exception $e) {
            // swallow DB exceptions in the view; keep counts zero
        }
    }
  @endphp

  <div class="page-inner owner-dashboard-inner">
  <section class="summary-grid" aria-label="Products summary">
    <a href="{{ route('owner.products.index') }}" class="summary-card" aria-label="Total products">
      <div class="summary-card-header">
        <span class="summary-card-label">Total Products</span>
        <span class="summary-card-chip accent">All</span>
      </div>
      <span class="summary-card-value">{{ number_format($totalProducts) }}</span>
      <span class="summary-card-meta">Items in catalog</span>
    </a>

    <a href="{{ route('owner.products.index', ['type' => 'invitation']) }}" class="summary-card" aria-label="Invitations">
      <div class="summary-card-header">
        <span class="summary-card-label">Invitations</span>
        <span class="summary-card-chip accent">Products</span>
      </div>
      <span class="summary-card-value">{{ number_format($invitationCount) }}</span>
      <span class="summary-card-meta">Invitation products</span>
    </a>

    <a href="{{ route('owner.products.index', ['type' => 'giveaway']) }}" class="summary-card summary-card--qty" aria-label="Giveaways">
      <div class="summary-card-header">
        <span class="summary-card-label">Giveaways</span>
        <span class="summary-card-chip accent">Products</span>
      </div>
      <span class="summary-card-value">{{ number_format($giveawayCount) }}</span>
      <span class="summary-card-meta">Giveaway items</span>
    </a>

    <a href="{{ route('owner.products.index', ['stock' => 'in']) }}" class="summary-card summary-card--low" aria-label="In Stock">
      <div class="summary-card-header">
        <span class="summary-card-label">In Stock</span>
        <span class="summary-card-chip accent">Available</span>
      </div>
      <span class="summary-card-value">{{ number_format($inStockCount) }}</span>
      <span class="summary-card-meta">Products currently in stock</span>
    </a>
  </section>

  <section class="materials-toolbar" aria-label="Product filters and actions">
    <div class="materials-toolbar__search">
      <form method="GET" action="{{ route('owner.products.index') }}">
        <div class="search-input">
          <span class="search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
          <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products..." class="form-control">
        </div>
        <div class="filter-wrapper">
          <input type="hidden" name="type" id="typeInput" value="{{ request('type') }}">
          <input type="hidden" name="stock" id="stockInput" value="{{ request('stock') }}">
          <button type="button" id="filterToggle" class="btn btn-secondary filter-toggle" title="Filter" aria-haspopup="true" aria-expanded="false">
            <i class="fa-solid fa-filter" aria-hidden="true"></i>
            <span class="sr-only">Filter</span>
          </button>
          <div id="filterMenu" class="filter-menu" role="menu" aria-hidden="true" style="display:none;">
            <button type="button" class="filter-option-btn" data-value="" role="menuitem">All Types</button>
            <button type="button" class="filter-option-btn" data-value="invitation" role="menuitem">Invitations</button>
            <button type="button" class="filter-option-btn" data-value="giveaway" role="menuitem">Giveaways</button>
            <button type="button" class="filter-option-btn" data-value="in" role="menuitem">In Stock</button>
          </div>
        </div>
        <button type="submit" class="btn btn-secondary">Search</button>
      </form>
    </div>
    <div class="materials-toolbar__actions">
      <!-- Actions (placeholder) -->
    </div>
  </section>

  <div class="table-wrapper">
    <table class="table">
      <thead>
        <tr>
          <th>Name</th>
          <th>SKU</th>
          <th>Price</th>
          <th>Stock</th>
        </tr>
      </thead>
      <tbody>
        @forelse($products as $product)
          @php $stock = $product->stock ?? 0; @endphp
          <tr>
            <td class="fw-bold">{{ $product->name ?? '-' }}</td>
            <td>{{ $product->sku ?? '-' }}</td>
            <td>₱{{ isset($product->price) ? number_format($product->price, 2) : '-' }}</td>
            <td>
              <span class="badge {{ $stock <= 0 ? 'stock-critical' : ($stock > 0 && $stock <= 5 ? 'stock-low' : 'stock-ok') }}">
                {{ $stock }}
              </span>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="4" class="text-center" style="padding:18px; color:#64748b;">No products yet.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if(method_exists($products, 'links'))
    <div style="margin-top:12px;">
      {{ $products->links() }}
    </div>
  @endif
  </div> <!-- .page-inner -->
</section>

  <script>
    // Filter icon menu behavior for owner products
    (function(){
      const filterToggle = document.getElementById('filterToggle');
      const filterMenu = document.getElementById('filterMenu');
      const typeInput = document.getElementById('typeInput');
      const stockInput = document.getElementById('stockInput');
      const searchForm = filterToggle ? filterToggle.closest('form') : null;

      if (!filterToggle || !filterMenu) return;

      const openMenu = () => {
        filterMenu.style.display = 'block';
        filterMenu.setAttribute('aria-hidden', 'false');
        filterToggle.setAttribute('aria-expanded', 'true');
      };

      const closeMenu = () => {
        filterMenu.style.display = 'none';
        filterMenu.setAttribute('aria-hidden', 'true');
        filterToggle.setAttribute('aria-expanded', 'false');
      };

      filterToggle.addEventListener('click', function(e){
        e.stopPropagation();
        const isOpen = filterMenu.style.display === 'block';
        if (isOpen) { closeMenu(); } else { openMenu(); }
      });

      document.querySelectorAll('#filterMenu .filter-option-btn').forEach(btn => {
        btn.addEventListener('click', function(){
          const val = this.getAttribute('data-value');
          // Determine if option is a stock filter or type filter
          if (['in','out'].includes(val)) {
            stockInput.value = val;
          } else {
            typeInput.value = val;
          }
          closeMenu();
          if (searchForm) searchForm.submit();
        });
      });

      document.addEventListener('click', function(e){
        if (!filterMenu.contains(e.target) && e.target !== filterToggle) {
          closeMenu();
        }
      });
    })();
  </script>

  <script src="{{ asset('js/owner-products.js') }}"></script>
  </main>
</section>
@endsection