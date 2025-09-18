@extends('layouts.admin')

@section('title', 'Materials Management')

@section('content')
<!-- Add Fontisto CDN for fi icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@flaticon/flaticon-uicons/css/all/all.css">
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

        <!-- Floating Action Button for Add New Material -->
        <div class="fab-container" style="position: relative;">
            <button id="fab-main" class="btn btn-primary" type="button" style="position: relative; z-index: 2;">
                <i class="fi fi-br-plus-small"></i> Add New Material
            </button>
            <div id="fab-options" style="display: none; position: absolute; right: 0; top: 110%; z-index: 3; flex-direction: column; gap: 8px;">
                <a href="{{ route('admin.materials.create', ['type' => 'invitation']) }}" class="btn btn-primary" style="background:#3cd5c8;">
                    <i class="fa-solid fa-envelope"></i> Invitation
                </a>
                <a href="{{ route('admin.materials.create', ['type' => 'giveaway']) }}" class="btn btn-primary" style="position: relative; z-index: 2;">
                    <i class="fa-solid fa-gift"></i> Giveaway
                </a>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const fabMain = document.getElementById('fab-main');
                const fabOptions = document.getElementById('fab-options');
                fabMain.addEventListener('click', function(e) {
                    e.stopPropagation();
                    fabOptions.style.display = fabOptions.style.display === 'flex' ? 'none' : 'flex';
                });
                document.addEventListener('click', function() {
                    fabOptions.style.display = 'none';
                });
            });
        </script>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="alert alert-success">
            ✅ {{ session('success') }}
        </div>
    @endif
        {{-- Search Bar --}}


    {{-- Materials Table --}}
    <div class="table-wrapper" style="background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(148,185,255,0.08); padding: 18px;">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Material Name</th>
                    <th>Occasion</th>
                    <th>Product Type</th>
                    <th>Type</th>
                    <th>Size</th>
                    <th>Color</th>
                    <th>Weight (GSM)</th>
                    <th>Unit</th>
                    <th>Unit Price(₱)/Per(ml)</th>
                    <th>Stock Qty</th>
                    <th>Stock Qty (ml)</th> <!-- Added column for volume_ml -->
                    <th>Reorder Point</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($materials as $material)
                    @php
                        $stock = $material->material_type === 'ink'
                            ? ($material->volume_ml ?? 0)
                            : ($material->inventory->stock_level ?? 0);
                        $reorder = $material->inventory->reorder_level ?? 0;
                    @endphp
                    <tr>
                        <td>{{ $material->material_id }}</td>
                        <td class="fw-bold">{{ $material->material_name }}</td>
                        <td>{{ ucfirst($material->occasion) }}</td>
                        <td>{{ ucfirst($material->product_type) }}</td>
                        <td>
                            <span class="badge badge-type {{ strtolower($material->material_type) }}">
                                {{ $material->material_type }}
                            </span>
                        </td>
                        <td>{{ $material->size ?? '-' }}</td>
                        <td>
                            @if($material->material_type === 'ink' && $material->color)
                                <span style="display:inline-block;width:18px;height:18px;border-radius:4px;background:{{ $material->color }};border:1px solid #ccc;margin-right:6px;"></span>
                            @endif
                            {{ $material->color ?? '-' }}
                        </td>
                        <td>{{ $material->weight_gsm ?? '-' }}</td>
                        <td>{{ $material->unit ?? '-' }}</td>
                        <td>
                            ₱{{ number_format($material->unit_cost, 2) }}
                            @if($material->material_type === 'ink')
                                /ml
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $stock <= 0 ? 'stock-critical' : ($stock > 0 && $stock <= $reorder ? 'stock-low' : 'stock-ok') }}">
                                {{ $stock }}
                                @if($material->material_type === 'ink')
                                    ml
                                @endif
                            </span>
                        </td>
                        <td>
                            @if($material->material_type === 'ink')
                                {{ $material->volume_ml ?? '-' }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $reorder }}</td>
                        <td>{{ $material->description ?? '-' }}</td>
                        <td>
                            @if($stock <= 0)
                                <span class="badge" style="color:#ff5349;">Out of Stock</span>
                            @elseif($stock > 0 && $stock <= $reorder)
                                <span class="badge" style="color:#ff6633;">Low Stock</span>
                            @else
                                <span class="badge badge-success">In Stock</span>
                            @endif
                        </td>
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
