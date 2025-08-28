@extends('layouts.admin')

@section('title', 'Edit Inventory')

@section('content')
<div class="inventory-edit">
    <h1>Edit Inventory Item</h1>

    <form action="{{ route('admin.inventory.update', $inventory->inventory_id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="material_id">Material</label>
            <select name="material_id" id="material_id" required>
                @foreach($materials as $material)
                    <option value="{{ $material->material_id }}" 
                        {{ $inventory->material_id == $material->material_id ? 'selected' : '' }}>
                        {{ $material->material_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="stock_level">Stock Level</label>
            <input type="number" name="stock_level" id="stock_level" value="{{ $inventory->stock_level }}" required>
        </div>

        <div class="form-group">
            <label for="reorder_level">Reorder Level</label>
            <input type="number" name="reorder_level" id="reorder_level" value="{{ $inventory->reorder_level }}" required>
        </div>

        <div class="form-group">
            <label for="remarks">Remarks</label>
            <textarea name="remarks" id="remarks">{{ $inventory->remarks }}</textarea>
        </div>

        <button type="submit" class="btn btn-success">Update</button>
    </form>
</div>
@endsection
