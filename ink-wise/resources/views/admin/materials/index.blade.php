@extends('layouts.admin')

@section('title', 'Materials Management')

@section('content')
<div class="materials-container">
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <h1>Materials Management</h1>

    {{-- Stock Summary Cards --}}
    <style>
        .summary-cards {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .summary-cards .card {
            flex: 1;
            min-width: 180px;
            padding: 20px;
            border-radius: 12px;
            color: #222;
            font-weight: 600;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.06);
            background: #fafafa;
            border: 2px solid #94b9ff;
            transition: transform 0.2s, border 0.2s;
            text-decoration: none;
        }
        .summary-cards .card:hover {
            transform: translateY(-4px);
            border-color: #3cd5c8;
        }
        .summary-cards .card h3 {
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #3a4d5c;
        }
        .summary-cards .card p {
            font-size: 22px;
            margin: 0;
            color: #23b26d;
            font-weight: bold;
        }
        /* Make the "Total Stock Qty" card have #94b9ff text */
        .summary-cards .card.qty p {
            color: #94b9ff;
        }
        /* Add New Material Button */
        .btn-primary {
            background: #94b9ff !important;
            color: #fff !important;
            border: none;
            border-radius: 8px;
            padding: 8px 18px;
            font-weight: 700;
            font-family: 'Nunito', sans-serif;
            transition: background 0.2s, color 0.2s;
            box-shadow: 0 2px 8px rgba(148,185,255,0.10);
        }
        .btn-primary:hover {
            background: #6a9be7 !important;
            color: #fff !important;
        }
        /* Search Button */
        .btn-secondary, .search-bar button {
            background: #94b9ff !important;
            color: #fff !important;
            border: none;
            border-radius: 8px;
            padding: 8px 14px;
            font-weight: 600;
            font-family: 'Nunito', sans-serif;
            margin-left: 8px;
            transition: background 0.2s, color 0.2s;
        }
        .btn-secondary:hover, .search-bar button:hover {
            background: #6a9be7 !important;
            color: #fff !important;
        }
    </style>
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
    <form method="GET" action="{{ route('admin.materials.index') }}" style="display:flex; align-items:center; gap:8px;">
        <span style="color:#94b9ff; font-size:18px; margin-right:4px;">
            <i class="fa-solid fa-magnifying-glass"></i>
        </span>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search materials..." class="form-control">
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
                    <th>SKU</th>
                    <th>Material Name</th>
                    <th>Occasion</th>
                    <th>Product Type</th>
                    <th>Type</th>
                    <th>Size</th>
                    <th>Color</th>
                    <th>Weight (GSM)</th>
                    <th>Volume (ml)</th>
                    <th>Unit</th>
                    <th>Unit Cost</th>
                    <th>Stock Qty</th>
                    <th>Reorder Point</th>
                    <th>Description</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($materials as $material)
                    <tr>
                        <td>{{ $material->material_id }}</td>
                        <td>{{ $material->sku ?? '-' }}</td>
                        <td class="fw-bold">{{ $material->material_name }}</td>
                        <td>{{ ucfirst($material->occasion) }}</td>
                        <td>{{ ucfirst($material->product_type) }}</td>
                        <td>
                            <span class="badge badge-type {{ strtolower($material->material_type) }}">
                                {{ $material->material_type }}
                            </span>
                        </td>
                        <td>{{ $material->size ?? '-' }}</td>
                        <td>{{ $material->color ?? '-' }}</td>
                        <td>{{ $material->weight_gsm ?? '-' }}</td>
                        <td>{{ $material->volume_ml ?? '-' }}</td>
                        <td>{{ $material->unit }}</td>
                        <td>₱{{ number_format($material->unit_cost, 2) }}</td>
                        <td>
                            @php
                                $stock = $material->stock_qty ?? 0;
                                $reorder = $material->reorder_point ?? 0;
                                $isLowStock = $stock <= $reorder;
                            @endphp
                            <span class="badge {{ $stock <= 0 ? 'stock-critical' : ($isLowStock ? 'stock-low' : 'stock-ok') }}"
                                  @if($isLowStock) title="⚠️ Stock is below reorder level!" @endif>
                                {{ $stock }}
                            </span>
                        </td>
                        <td>{{ $material->reorder_point ?? '-' }}</td>
                        <td>{{ $material->description ?? '-' }}</td>
                        <td class="actions">
                            <a href="{{ route('admin.materials.edit', $material->material_id) }}" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('admin.materials.destroy', $material->material_id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this material?');" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="16" class="text-center">No materials found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
