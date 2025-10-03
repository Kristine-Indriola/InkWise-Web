{{-- resources/views/products/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Products Dashboard')

@section('content')
<div class="dashboard-container admin-page-shell">
    <h1 class="page-title">Products</h1>

    <!-- Summary Cards -->
    <div class="summary-cards compact">
        <div class="card">
            <div class="card-icon">S</div>
            <div class="card-body">
                <div class="card-title">All Session</div>
                <div class="card-subtitle">No Shop · 56.15%</div>
                <div class="card-footer">
                    <div class="card-number">245.15k</div>
                    <div class="card-percentage positive">+7.11%</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-icon">V</div>
            <div class="card-body">
                <div class="card-title">Product Views</div>
                <div class="card-subtitle">No Shop · 26.22%</div>
                <div class="card-footer">
                    <div class="card-number">154.12k</div>
                    <div class="card-percentage positive">+2.11%</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-icon">C</div>
            <div class="card-body">
                <div class="card-title">In the Cart</div>
                <div class="card-subtitle">Wishlist · 50.15%</div>
                <div class="card-footer">
                    <div class="card-number">101.05k</div>
                    <div class="card-percentage positive">+1.11%</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-icon">O</div>
            <div class="card-body">
                <div class="card-title">Ordered</div>
                <div class="card-subtitle">Cancelled · 15.05%</div>
                <div class="card-footer">
                    <div class="card-number">95.34k</div>
                    <div class="card-percentage negative">-0.11%</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search + Add Buttons -->
    <div class="search-and-add">
        <div class="add-buttons">
            <button class="btn-add-new" aria-label="Add new product"><i class="fi fi-rr-pen-nib"></i> Add New Product</button>
            <div class="floating-buttons">
                <a href="{{ route('admin.products.create.invitation') }}" class="btn-floating btn-invitation"><i class="fa-solid fa-envelope"></i> Add Invitation</a>
                <button class="btn-floating btn-giveaway"><i class="fa-solid fa-gift"></i> Add Giveaway</button>
            </div>
        </div>
    </div>

    <!-- Table Controls -->
    <div class="table-controls">
        <div class="controls-left">
            <div class="search-bar-wrapper">
                <label class="search-input-wrapper" for="productSearch">
                    <span class="search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input class="search-input" id="productSearch" type="text" placeholder="Search product..." aria-describedby="search-help">
                    <span id="search-help" class="sr-only">Type to search products by name or category.</span>
                    <button type="button" class="filter-icon" title="Filters">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                </label>
            </div>
        </div>
        <div class="controls-right">
            <button class="btn-download-all" title="Download CSV"><i class="fa-solid fa-file-arrow-down"></i></button>
            <div class="sort-group">
                <button class="btn-sort-up" title="Sort ascending"><i class="fa-solid fa-arrow-up"></i></button>
                <button class="btn-sort-down" title="Sort descending"><i class="fa-solid fa-arrow-down"></i></button>
            </div>
            <div class="filter-group">
                <select class="filter-select">
                    <option value="all">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Inks Table -->
    <div class="table-container">
        <h2>Product Inks</h2>
        @php
            // Flatten inks into a collection for listing
            $inksList = collect();
            foreach($products as $product) {
                foreach($product->inks as $ink) {
                    $item = (object) [
                        'id' => $ink->id ?? null,
                        'product_name' => $product->name ?? '-',
                        'item' => $ink->item,
                        'type' => $ink->type,
                        'usage' => $ink->usage,
                        'cost_per_ml' => $ink->cost_per_ml,
                        'total_cost' => $ink->total_cost,
                    ];
                    $inksList->push($item);
                }
            }
        @endphp

        <table class="inks-table" aria-describedby="inks-list">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Usage</th>
                    <th>Qty (ml)</th>
                    <th>Cost per mL</th>
                    <th>Total Cost</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inksList as $i => $ink)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $ink->product_name }}</td>
                        <td>{{ $ink->item }}</td>
                        <td>{{ $ink->type }}</td>
                        <td>{{ $ink->usage }}</td>
                        <td>{{ $ink->qty ?? '' }}</td>
                        <td>₱{{ number_format($ink->cost_per_ml ?? 0, 2) }}</td>
                        <td>₱{{ number_format($ink->total_cost ?? 0, 2) }}</td>
                        <td>
                            <button class="btn-view" title="View Ink"><i class="fi fi-sr-eye"></i></button>
                            <button class="btn-update" title="Edit Ink"><i class="fa-solid fa-pen-to-square"></i></button>
                            @if($ink->id)
                                <button type="button" class="btn-delete ajax-delete" data-id="{{ $ink->id }}" title="Delete Ink"><i class="fa-solid fa-trash"></i></button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">No inks found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>


<!-- CSS + JS -->
<link rel="stylesheet" href="{{ asset('css/admin-css/product.css') }}">
<script src="{{ asset('js/admin/product.js') }}" defer></script>
@endsection
@if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif
@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

