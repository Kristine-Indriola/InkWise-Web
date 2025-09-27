@php
    $materials = $materials ?? collect();
@endphp

@extends('layouts.owner.app')

@push('styles')
  <link rel="stylesheet" href="css/owner/staffapp.css">
@endpush

@section('content')
@include('layouts.owner.sidebar')

<section class="main-content">
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

       
      <div class="panel">
        <h3>Stock Levels</h3>

         {{-- SEARCH FORM --}}
      <form method="GET" action="{{ route('owner.inventory-track') }}">
      <div class="search-wrap" style="margin: 0 0 15px 0; padding: 0; display: flex; align-items: center;">
        <input class="search-input" type="text" name="search" placeholder="Search by item name or category..." value="{{ request()->input('search') }}" />
        <button type="submit" class="search-btn" style="padding: 6px 10px; width: 65px;">Search</button>
      </div>
    </form>


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
                  $statusClass = 'out-of-stock';
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
