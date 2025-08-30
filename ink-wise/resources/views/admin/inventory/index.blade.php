@extends('layouts.admin')

@section('title', 'Inventory')

@section('content')
<div class="inventory-container">
    <h1>Inventory Management</h1>

    <a href="{{ route('admin.inventory.create') }}" class="btn btn-primary">➕ Add Inventory</a>

    <table class="table">
    <thead>
        <tr>
            <th>Material Name</th>
            <th>Type</th>
            <th>Unit</th>
            <th>Stock Level</th>
            <th>Reorder Level</th>
            <th>Remarks</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($inventories as $item)
            <tr>
                <td>{{ $item->material->material_name }}</td>
                <td>{{ $item->material->material_type }}</td>
                <td>{{ $item->material->unit }}</td>
                <td>{{ $item->stock_level }}</td>
                <td>{{ $item->reorder_level }}</td>
                <td>{{ $item->remarks }}</td>
                <td>
                    <a href="{{ route('admin.inventory.edit', $item->inventory_id) }}" class="btn btn-sm btn-warning">✏️ Edit</a>
                    <form action="{{ route('admin.inventory.destroy', $item->inventory_id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this item?');">🗑️ Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

</div>
@endsection
