<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Material</title>
    <link rel="stylesheet" href="{{ asset('css/admin-css/create_materials.css') }}">
</head>
<body>
    <div class="container">
        <h2>Add New Material</h2>

        @if(session('success'))
            <div class="success">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('admin.materials.store') }}" method="POST">
            @csrf

            <div>
                <label>Material Name</label>
                <input type="text" name="material_name" value="{{ old('material_name') }}" required>
            </div>

            <div>
                <label>Material Type</label>
                <input type="text" name="material_type" value="{{ old('material_type') }}" required>
            </div>

            <div>
                <label>Unit (e.g. pcs, ream, liter)</label>
                <input type="text" name="unit" value="{{ old('unit') }}" required>
            </div>

            <div>
                <label>Unit Cost</label>
                <input type="number" step="0.01" name="unit_cost" value="{{ old('unit_cost') }}" required>
            </div>

            <div>
                <label>Stock Level</label>
                <input type="number" name="stock_level" value="{{ old('stock_level') }}" required>
            </div>

            <div>
                <label>Reorder Level</label>
                <input type="number" name="reorder_level" value="{{ old('reorder_level') }}" required>
            </div>

            <div>
                <label>Remarks</label>
                <textarea name="remarks">{{ old('remarks') }}</textarea>
            </div>

            <div style="display:flex; flex-wrap:wrap; gap:10px;">
                <button type="submit">Save Material</button>
                <a href="{{ route('admin.materials.index') }}" class="btn-back">Back to Materials</a>
            </div>
        </form>
    </div>
</body>
</html>
