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

            <div class="form-row">
                <div class="form-group">
                    <label>Material Name</label>
                    <input type="text" name="material_name" value="{{ old('material_name') }}" required>
                </div>

                <div class="form-group">
                    <label>Material Type</label>
                    <input type="text" name="material_type" value="{{ old('material_type') }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Unit (e.g. pcs, ream, liter)</label>
                    <input type="text" name="unit" value="{{ old('unit') }}" required>
                </div>

                <div class="form-group">
                    <label>Unit Cost</label>
                    <input type="number" step="0.01" name="unit_cost" value="{{ old('unit_cost') }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Stock Level</label>
                    <input type="number" name="stock_level" value="{{ old('stock_level') }}" required>
                </div>

                <div class="form-group">
                    <label>Reorder Level</label>
                    <input type="number" name="reorder_level" value="{{ old('reorder_level') }}" required>
                </div>
            </div>

            {{-- Remarks Removed - now automatic --}}
            {{-- <div class="form-group">
                <label>Remarks</label>
                <textarea name="remarks">{{ old('remarks') }}</textarea>
            </div> --}}

            <div class="btn-group">
                <button type="submit">Save Material</button>
                <a href="{{ route('admin.materials.index') }}" class="btn-back">Back to Materials</a>
            </div>
        </form>
    </div>
</body>
</html>
