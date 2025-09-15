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
                    <label>SKU</label>
                    <input type="text" name="sku" value="{{ old('sku') }}" placeholder="e.g. MAT-001">
                </div>
                <div class="form-group">
                    <label>Material Name</label>
                    <input type="text" name="material_name" value="{{ old('material_name') }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Occasion</label>
                    <select name="occasion" required>
                        <option value="">-- Select Occasion --</option>
                        <option value="wedding" {{ old('occasion') == 'wedding' ? 'selected' : '' }}>Wedding</option>
                        <option value="birthday" {{ old('occasion') == 'birthday' ? 'selected' : '' }}>Birthday</option>
                        <option value="baptism" {{ old('occasion') == 'baptism' ? 'selected' : '' }}>Baptism</option>
                        <option value="corporate" {{ old('occasion') == 'corporate' ? 'selected' : '' }}>Corporate</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Product Type</label>
                    <select name="product_type" required>
                        <option value="">-- Select Type --</option>
                        <option value="invitation" {{ old('product_type') == 'invitation' ? 'selected' : '' }}>Invitation</option>
                        <option value="giveaway" {{ old('product_type') == 'giveaway' ? 'selected' : '' }}>Giveaway</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Material Type</label>
                    <select name="material_type" required>
                        <option value="">-- Select Material --</option>
                        <option value="cardstock" {{ old('material_type') == 'cardstock' ? 'selected' : '' }}>Cardstock</option>
                        <option value="envelope" {{ old('material_type') == 'envelope' ? 'selected' : '' }}>Envelope</option>
                        <option value="ink" {{ old('material_type') == 'ink' ? 'selected' : '' }}>Ink</option>
                        <option value="foil" {{ old('material_type') == 'foil' ? 'selected' : '' }}>Foil</option>
                        <option value="lamination" {{ old('material_type') == 'lamination' ? 'selected' : '' }}>Lamination</option>
                        <option value="packaging" {{ old('material_type') == 'packaging' ? 'selected' : '' }}>Packaging</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Size</label>
                    <input type="text" name="size" value="{{ old('size') }}" placeholder="e.g. A4, 5x7 inch">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Color</label>
                    <input type="text" name="color" value="{{ old('color') }}" placeholder="e.g. White, Gold">
                </div>
                <div class="form-group">
                    <label>Weight (GSM)</label>
                    <input type="number" name="weight_gsm" value="{{ old('weight_gsm') }}" placeholder="For cardstock">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Volume (ml) <span style="font-size:12px;">(For Inks)</span></label>
                    <input type="number" step="0.01" name="volume_ml" value="{{ old('volume_ml') }}">
                </div>
                <div class="form-group">
                    <label>Unit (e.g. pcs, ream, liter)</label>
                    <input type="text" name="unit" value="{{ old('unit', 'pcs') }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Unit Cost</label>
                    <input type="number" step="0.01" name="unit_cost" value="{{ old('unit_cost') }}" required>
                </div>
                <div class="form-group">
                    <label>Stock Quantity</label>
                    <input type="number" name="stock_qty" value="{{ old('stock_qty') }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Reorder Point</label>
                    <input type="number" name="reorder_point" value="{{ old('reorder_point', 10) }}" required>
                </div>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description">{{ old('description') }}</textarea>
            </div>

            <div class="btn-group">
                <button type="submit">Save Material</button>
                <a href="{{ route('admin.materials.index') }}" class="btn-back">Back to Materials</a>
            </div>
        </form>
    </div>
</body>
</html>
