@extends('layouts.admin')

@section('title', 'Add Inventory')

@section('content')
<div class="inventory-create">
    <h1>Add Inventory Item</h1>

    <form action="{{ route('admin.inventory.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="material_id">Material</label>
            <select name="material_id" id="material_id" required>
                <option value="">-- Select Material --</option>
                @foreach($materials as $material)
                    <option value="{{ $material->material_id }}">{{ $material->material_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="stock_level">Stock Level</label>
            <input type="number" name="stock_level" id="stock_level" required>
        </div>

        <div class="form-group">
            <label for="reorder_level">Reorder Level</label>
            <input type="number" name="reorder_level" id="reorder_level" required>
        </div>

        <div class="form-group">
            <label for="remarks">Remarks</label>
            <textarea name="remarks" id="remarks"></textarea>
        </div>

        <button type="submit" class="btn btn-success">Save</button>
    </form>
</div>
@endsection
