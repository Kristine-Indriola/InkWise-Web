@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-css/products.css') }}">
<script src="{{ asset('js/admin/product.js') }}"></script>
<div class="products-container">
    <h1>Products Management</h1>
    <div class="top-actions">
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary">➕ Add New Product</a>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Occasion</th>
                    <th>Stock</th>
                    <th>Unit Cost</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                <tr>
                    <td>{{ $product->sku }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ ucfirst($product->type) }}</td>
                    <td>{{ ucfirst($product->occasion) }}</td>
                    <td>{{ $product->stock_qty }}</td>
                    <td>₱{{ number_format($product->unit_cost, 2) }}</td>
                    <td>
                        <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-sm btn-primary">Edit</a>
                        <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this product?')">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection