@extends('layouts.owner.app')

@section('content')
@include('layouts.owner.sidebar')

<section class="main-content">
  <div class="topbar">
    <div class="welcome-text"><strong>Welcome, Owner!</strong></div>

    <div class="topbar-actions">
      {{-- Notification (DB query here — safe for all pages) --}}
      @php
          $lowCount = \App\Models\Material::whereHas('inventory', function($q) {
              $q->whereColumn('stock_level', '<=', 'reorder_level')
                ->where('stock_level', '>', 0);
          })->count();

          $outCount = \App\Models\Material::whereHas('inventory', function($q) {
              $q->where('stock_level', '<=', 0);
          })->count();

          $notifCount = $lowCount + $outCount;
      @endphp

      

      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="logout-btn">Logout</button>
      </form>
    </div>
  </div>

  <div class="panel">
    <h3>Stock Levels</h3>

    <div class="search-wrap">
      <input class="search-input" type="text" placeholder="Search by item name, category…" />
    </div>

    <div class="table-wrap">
      <table class="inventory-table">
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
                  $statusClass = 'out-stock';
                  $statusText = 'Out of Stock';
              } elseif ($stock <= $reorder) {
                  $statusClass = 'low-stock';
                  $statusText = 'Low Stock';
              } else {
                  $statusClass = 'in-stock';
                  $statusText = 'In Stock';
              }
            @endphp
            <tr>
              <td>{{ $material->material_name }}</td>
              <td>{{ $material->material_type }}</td>
              <td>{{ $stock }}</td>
              <td><span class="status {{ $statusClass }}">{{ $statusText }}</span></td>
            </tr>
          @empty
            <tr><td colspan="4" class="text-center">No materials found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</section>
@endsection
