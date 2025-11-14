@extends('layouts.owner.app')

@section('content')
@include('layouts.owner.sidebar')

<link rel="stylesheet" href="{{ asset('css/admin-css/product.css') }}">

<!-- Inline critical CSS fallback to ensure table is visible and matches admin styles -->
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
    gap: 10px;
    background: #fff;
    border-radius: 12px;
    padding: 8px 14px;
    border: 1px solid rgba(148, 185, 255, 0.26);
    box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
    transition: box-shadow 0.18s ease, border-color 0.18s ease;
  }

  .search-input:focus-within {
    border-color: rgba(59, 130, 246, 0.32);
    box-shadow: 0 14px 32px rgba(59, 130, 246, 0.1);
  }

  .search-input input.form-control {
    border: 0;
    outline: 0;
    width: 360px;
    max-width: 48vw;
    min-width: 180px;
    padding: 8px 10px;
    font-size: 1.05rem;
    background: transparent;
  }

  .owner-products-page {
    font-size: 1.2rem;
    line-height: 1.5;
  }

  .owner-products-page .page-title {
    font-size: 2.1rem;
    font-weight: 800;
  }

  .owner-products-page .page-subtitle {
    font-size: 1.2rem;
  }

  .owner-products-page .summary-card-label,
  .owner-products-page .summary-card-meta {
    font-size: 1.15rem;
  }

  .owner-products-page .summary-card-value {
    font-size: 2rem;
  }

  .owner-products-page .summary-card-chip {
    font-size: 1.1rem;
  }

  .owner-products-page .product-card {
    font-size: 1rem;
  }

  .owner-products-page .product-card-title {
    font-size: 1.75rem;
    font-weight: 800;
  }

  .owner-products-page .product-card-desc,
  .owner-products-page .meta-item,
  .owner-products-page .price,
  .owner-products-page .entries-info {
    font-size: 1rem;
  }

  .owner-products-page .meta-item,
  .owner-products-page .price {
    font-weight: 600;
  }

  @media (max-width: 900px) {
    .owner-dashboard-shell {
      padding-right: 0;
      padding-bottom: 0;
      padding-left: 0;
    }
    .owner-dashboard-main { padding: 24px 20px 32px 12px; }
    .owner-dashboard-inner { padding: 0 4px; }
  }

</style>

<section class="main-content owner-dashboard-shell">
  <main class="materials-page admin-page-shell materials-container owner-dashboard-main owner-products-page" role="main">
  <header class="page-header">
    <div>
      <h1 class="page-title">Products</h1>
      <p class="page-subtitle">Products catalog &amp; stock overview</p>
    </div>
  </header>

  @php
  $productsCollection = collect();
  if ($products instanceof \Illuminate\Contracts\Pagination\Paginator) {
    $productsCollection = collect($products->items());
  } elseif ($products instanceof \Illuminate\Support\Collection) {
    $productsCollection = $products;
  }

  $totalProducts = $totalProducts ?? ($products instanceof \Illuminate\Contracts\Pagination\Paginator
    ? $products->total()
    : $productsCollection->count());

  $countByType = function (string $type) use ($productsCollection) {
    return $productsCollection->filter(function ($product) use ($type) {
      return strtolower($product->product_type ?? '') === strtolower($type);
    })->count();
  };

  $invitationCount = $invitationCount ?? $countByType('Invitation');
  $giveawayCount = $giveawayCount ?? $countByType('Giveaway');

  $inStockCount = $inStockCount ?? $productsCollection->filter(function ($product) {
    $availability = strtolower($product->stock_availability ?? '');
    return $availability === ''
      || str_contains($availability, 'in stock')
      || str_contains($availability, 'available');
  })->count();
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

  @php
    $isPaginator = $products instanceof \Illuminate\Contracts\Pagination\Paginator;
    $hasProducts = $isPaginator ? $products->total() > 0 : ($products->count() > 0);
  @endphp

  <div class="table-container" id="products-table-container">
    <h2>Products List</h2>

    <div class="products-grid" role="list">
      @forelse($products as $product)
        @include('owner.products.partials.card', ['product' => $product])
      @empty
        <div class="no-products">No products found.</div>
      @endforelse
    </div>

    <div class="table-pagination" id="products-list">
      <div class="entries-info">
        @if($isPaginator)
          @if($products->total())
            Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} entries
          @else
            No entries
          @endif
        @else
          {{ $hasProducts ? 'Showing ' . $products->count() . ' entries' : 'No entries' }}
        @endif
      </div>
      <div class="pagination-links">
        @if($isPaginator)
          {{ $products->links() }}
        @endif
      </div>
    </div>
  </div>
  </div> <!-- .page-inner -->
</section>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      function isInteractiveElement(el) {
        if (!el) return false;
        return el.closest('a, button, form, input, select, textarea, .ajax-delete-form, .card-actions');
      }

      document.querySelectorAll('.product-card').forEach(function (card) {
        card.addEventListener('click', function (e) {
          if (isInteractiveElement(e.target)) return;
          var url = card.getAttribute('data-view-url');
          if (!url) return;

          var openInNewTab = e.ctrlKey || e.metaKey || (e.button === 1);
          if (openInNewTab) {
            window.open(url, '_blank');
            return;
          }

          window.location = url;
        });

        card.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ') {
            var url = card.getAttribute('data-view-url');
            if (!url) return;
            e.preventDefault();
            window.location = url;
          }
        });
      });
    });
  </script>

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
