<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Material</title>
    <link rel="stylesheet" href="{{ asset('css/staff-css/create_materials.css') }}">
</head>
<body>
    <div class="container">
        <h2>Add New Material</h2>

        @if(session('success'))
            <div class="success">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('staff.materials.store') }}" method="POST">
            @csrf

            <div class="form-row">
                <div class="form-group">
                    <label>Material Name</label>
                    <input type="text" name="material_name" value="{{ old('material_name') }}" required>
                </div>

                <div class="form-group">
                    <label>Material Type</label>
                    <select name="material_type" required>
                        <option value="">-- Select Material Type --</option>
                        <option value="paper" {{ old('material_type') == 'paper' ? 'selected' : '' }}>PAPER</option>
                        <option value="ink" {{ old('material_type') == 'ink' ? 'selected' : '' }}>INK</option>
                        <option value="envelopes" {{ old('material_type') == 'envelopes' ? 'selected' : '' }}>ENVELOPES</option>
                        <option value="ribbon" {{ old('material_type') == 'ribbon' ? 'selected' : '' }}>RIBBON</option>
                        <option value="powder" {{ old('material_type') == 'powder' ? 'selected' : '' }}>POWDER</option>
                    </select>
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
                <a href="{{ route('staff.materials.index') }}" class="btn-back">Back to Materials</a>
            </div>
        </form>
    </div>
</body>
</html>
