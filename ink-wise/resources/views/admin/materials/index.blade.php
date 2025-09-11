@extends('layouts.admin')

@section('title', 'Materials Management')

@section('content')
<div class="materials-container">
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <h1>📦 Materials Management</h1>

    {{-- Stock Summary Cards --}}
  <div class="summary-cards">
    <a href="{{ route('admin.materials.index', ['status' => 'all']) }}" class="card total">
        <h3>Total Materials</h3>
        <p>{{ $materials->count() }}</p>
    </a>

    <a href="{{ route('admin.materials.index', ['status' => 'low']) }}" class="card low">
        <h3>Low Stock</h3>
        <p>
            {{ $materials->filter(function($m) { 
                $stock = $m->inventory->stock_level ?? 0;
                $reorder = $m->inventory->reorder_level ?? 0;
                return $stock > 0 && $stock <= $reorder; 
            })->count() }}
        </p>
    </a>

    <a href="{{ route('admin.materials.index', ['status' => 'out']) }}" class="card out">
        <h3>Out of Stock</h3>
        <p>
            {{ $materials->filter(function($m) { 
                return ($m->inventory->stock_level ?? 0) <= 0; 
            })->count() }}
        </p>
    </a>

    <a href="{{ route('admin.materials.index', ['status' => 'qty']) }}" class="card qty">
        <h3>Total Stock Qty</h3>
        <p>
            {{ $materials->sum(function($m) { 
                return $m->inventory->stock_level ?? 0; 
            }) }}
        </p>
    </a>
</div>



    {{-- Add Material Button --}}
    <div class="top-actions">

        <div class="search-bar">
    <form method="GET" action="{{ route('admin.materials.index') }}">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="🔍 Search materials..." class="form-control">
        <button type="submit" class="btn btn-secondary">Search</button>
    </form>
</div>

        <a href="{{ route('admin.materials.create') }}" class="btn btn-primary">➕ Add New Material</a>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="alert alert-success">
            ✅ {{ session('success') }}
        </div>
    @endif
        {{-- Search Bar --}}


    {{-- Materials Table --}}
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Material Name</th>
                    <th>Type</th>
                    <th>Unit</th>
                    <th>Unit Cost</th>
                    <th>Stock Level</th>
                    <th>Reorder Level</th>
                    <th>Remarks</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($materials as $material)
                    <tr>
                        <td>{{ $material->material_id }}</td>
                        <td class="fw-bold">{{ $material->material_name }}</td>
                        <td>
                            <span class="badge badge-type {{ strtolower($material->material_type) }}">
                                {{ $material->material_type }}
                            </span>
                        </td>
                        <td>{{ $material->unit }}</td>
                        <td>₱{{ number_format($material->unit_cost, 2) }}</td>
                        <td>
                            @php
                                $stock = $material->inventory->stock_level ?? 0;
                                $reorder = $material->inventory->reorder_level ?? 0;
                                $isLowStock = $stock <= $reorder;
                            @endphp
                            <span class="badge {{ $isLowStock ? 'stock-low' : 'stock-ok' }}"
                                  @if($isLowStock) title="⚠️ Stock is below reorder level!" @endif>
                                {{ $material->inventory->stock_level ?? 'N/A' }}
                            </span>
                        </td>
                        <td>{{ $material->inventory->reorder_level ?? 'N/A' }}</td>
                        <td>
    @php
        $stock = $material->inventory->stock_level ?? 0;
        $reorder = $material->inventory->reorder_level ?? 0;

        if ($stock <= 0) {
            $remark = 'Out of Stock';
            $remarkClass = 'stock-critical';
        } elseif ($stock <= $reorder) {
            $remark = 'Low Stock';
            $remarkClass = 'stock-low';
        } else {
            $remark = 'In Stock';
            $remarkClass = 'stock-ok';
        }
    @endphp

    <span class="badge {{ $remarkClass }}">{{ $remark }}</span>
</td>

                        <td class="actions">
                            <a href="{{ route('admin.materials.edit', $material->material_id) }}" class="btn btn-sm btn-warning">✏️</a>
                            <form action="{{ route('admin.materials.destroy', $material->material_id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this material?');">🗑️</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center">No materials found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
