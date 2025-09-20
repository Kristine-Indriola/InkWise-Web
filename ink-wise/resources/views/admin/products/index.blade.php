{{-- resources/views/products/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Products Dashboard')

@section('content')
<div class="dashboard-container">
    <h1 class="page-title">Products</h1>

    <!-- Summary Cards -->
    <div class="summary-cards compact">
        <div class="card">
                        
                <div class="card-title">All Session</div>
            <div class="card-subtitle">No Shop - 56.15%</div>
            <div class="card-number">245.15k</div>
            <div class="card-percentage text-green">+7.11%</div>
          
        </div>
        <div class="card">
            <div class="card-title">Product Views</div>
            <div class="card-subtitle">No Shop - 26.22%</div>
            <div class="card-number">154.12k</div>
            <div class="card-percentage text-green">+2.11%</div>
        </div>
        
        <div class="card">
            <div class="card-title">In the Cart</div>
            <div class="card-subtitle">Wishlist - 50.15%</div>
            <div class="card-number">101.05k</div>
            <div class="card-percentage text-green">+1.11%</div>
        </div>
        <div class="card">
            <div class="card-title">Ordered</div>
            <div class="card-subtitle">Cancelled - 15.05%</div>
            <div class="card-number">95.34k</div>
            <div class="card-percentage text-red">-0.11%</div>
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

    <!-- Products Table -->
    <div class="table-container">
        <h2>Products List</h2>
        <table class="products-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Unit Price</th>
                    <th>Order/Stock</th>
                    <th>Total Value</th>
                    <th>Status</th> <!-- New Status Column -->
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <img src="{{ asset($product->image) }}" alt="{{ $product->name }}" class="product-thumb">
                            {{ $product->name }}
                        </td>
                        <td>{{ $product->category }}</td>
                        <td>₱{{ number_format($product->unit_price, 2) }}</td>
                        <td>{{ $product->stock }}</td>
                        <td>₱{{ number_format($product->unit_price * $product->stock, 2) }}</td>
                        <td>{{ $product->status }}</td>
                        <td>
                            <a href="{{ route('admin.products.show', $product->id) }}" class="btn-view" title="View"><i class="fi fi-sr-eye"></i></a>
                            <a href="{{ route('admin.products.edit', $product->id) }}" class="btn-update" title="Update"><i class="fa-solid fa-pen-to-square"></i></a>
                            <form method="POST" action="{{ route('admin.products.destroy', $product->id) }}" style="display:inline;">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-delete" title="Delete" onclick="return confirm('Are you sure?')"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">No products found.</td></tr>
                @endforelse
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="table-pagination">
            <div class="entries-info">Showing 1 to 6 of 8 entries</div>
            <div class="pagination-links">
                <button class="page-link">«</button>
                <button class="page-link active">1</button>
                <button class="page-link">2</button>
                <button class="page-link">»</button>
            </div>
        </div>
    </div>
</div>

<!-- CSS + JS -->
<link rel="stylesheet" href="{{ asset('css/admin-css/product.css') }}">
<script src="{{ asset('js/admin-js/product.js') }}" defer></script>
@endsection
@if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif
