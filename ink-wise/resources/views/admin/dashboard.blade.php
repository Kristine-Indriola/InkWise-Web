@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
  <div class="cards">
    <div class="card">
      <div>🛒</div>
      <h3>Orders</h3>
      <p>20</p>
    </div>
    <div class="card">
      <div>⏳</div>
      <h3>Pending</h3>
      <p>35</p>
    </div>
    <div class="card">
      <div>⭐</div>
      <h3>Rating</h3>
      <p>4.0</p>
    </div>
  </div>

  <div class="stock">
    <h3>Stock Level</h3>

    <table class="clickable-table" onclick="window.location='{{ route('admin.materials.index') }}'">
      <thead>
        <tr>
          <th>Materials</th>
          <th>Type</th>
          <th>Unit</th>
          <th>Stock Level</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($materials as $material)
          @php
              $stock = $material->inventory->stock_level ?? 0;
              $reorder = $material->inventory->reorder_level ?? 0;
              $status = 'in';
              $statusLabel = 'In Stock';

              if ($stock <= 0) {
                  $status = 'critical';
                  $statusLabel = 'Out of Stock';
              } elseif ($stock <= $reorder) {
                  $status = 'low';
                  $statusLabel = 'Low Stock';
              }
          @endphp

          <tr>
            <td>{{ $material->material_name }}</td>
            <td>{{ $material->material_type }}</td>
            <td>{{ $material->unit }}</td>
            <td>{{ $stock }}</td>
            <td><span class="status {{ $status }}">{{ $statusLabel }}</span></td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="text-center">No materials available.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <style>
    .clickable-table {
      cursor: pointer;
    }
    .clickable-table tbody tr:hover {
      background-color: #f1f1f1;
    }
  </style>
@endsection
