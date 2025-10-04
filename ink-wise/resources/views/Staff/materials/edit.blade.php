<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Material</title>
    <link rel="stylesheet" href="{{ asset('css/staff-css/create_materials.css') }}">
</head>
<body>
    <div class="container">
        <h2>Edit Material</h2>

        @if ($errors->any())
            <div class="success" style="background-color: #fee2e2; color:#b91c1c;">
                <ul style="margin:0; padding-left:20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('staff.materials.update', $material->material_id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-row">
                <div class="form-group">
                    <label>Material Name</label>
                    <input type="text" name="material_name" value="{{ old('material_name', $material->material_name) }}" required>
                </div>

                <div class="form-group">
                    <label>Material Type</label>
                    <select name="material_type" required>
                        <option value="">-- Select Material Type --</option>
                        <option value="paper" {{ old('material_type', $material->material_type) == 'paper' ? 'selected' : '' }}>PAPER</option>
                        <option value="ink" {{ old('material_type', $material->material_type) == 'ink' ? 'selected' : '' }}>INK</option>
                        <option value="envelopes" {{ old('material_type', $material->material_type) == 'envelopes' ? 'selected' : '' }}>ENVELOPES</option>
                        <option value="ribbon" {{ old('material_type', $material->material_type) == 'ribbon' ? 'selected' : '' }}>RIBBON</option>
                        <option value="powder" {{ old('material_type', $material->material_type) == 'powder' ? 'selected' : '' }}>POWDER</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Unit (e.g. pcs, ream, liter)</label>
                    <input type="text" name="unit" value="{{ old('unit', $material->unit) }}" required>
                </div>

                <div class="form-group">
                    <label>Unit Cost</label>
                    <input type="number" step="0.01" name="unit_cost" value="{{ old('unit_cost', $material->unit_cost) }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Stock Level</label>
                    <input type="number" name="stock_level" value="{{ old('stock_level', $material->inventory->stock_level ?? 0) }}" required>
                </div>

                <div class="form-group">
                    <label>Reorder Level</label>
                    <input type="number" name="reorder_level" value="{{ old('reorder_level', $material->inventory->reorder_level ?? 0) }}" required>
                </div>
            </div>

            {{-- Remarks removed since it's automatic --}}
            
            <div class="btn-group">
                <button type="submit">Update Material</button>
                <a href="{{ route('staff.materials.index') }}" class="btn-back">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
