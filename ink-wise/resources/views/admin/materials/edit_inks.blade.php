<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Ink</title>
    <link rel="stylesheet" href="{{ asset('css/admin-css/create_materials.css') }}">
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap" rel="stylesheet">
    <style>
        body, input, select, textarea, button, .form-control, .styled-select {
            font-family: 'Poppins', Arial, sans-serif !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Ink</h2>

        @if(session('success'))
            <div class="success">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Ink Edit Form --}}
        <form id="inkForm" action="{{ route('admin.inks.update', $ink->id) }}" method="POST">
            @csrf
            @method('PUT')  <!-- ✅ Added: For PUT request -->

            @php
                $occasionValues = old('occasion', $ink->occasion ? explode(',', $ink->occasion) : []);
                if (!is_array($occasionValues)) {
                    $occasionValues = [$occasionValues];
                }
                $occasionValues = array_filter($occasionValues, fn ($value) => $value !== null && $value !== '');
                $normalizedOccasions = array_map(function ($value) {
                    $value = strtolower(trim($value));
                    return in_array($value, ['all occasion', 'all_occasion', 'all'], true) ? 'all' : $value;
                }, $occasionValues);
                $allOccasions = ['wedding', 'birthday', 'baptism', 'corporate'];
                $hasAllOccasion = in_array('all', $normalizedOccasions, true) || empty(array_diff($allOccasions, $normalizedOccasions));
            @endphp

            <div class="form-row">
                <div class="form-group">
                    <label for="material_type">Material Type</label>
                    <select id="material_type" name="material_type" class="styled-select" required>
                        <option value="ink" selected>INK</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="occasion">Occasion</label>
                    <select id="occasion" name="occasion[]" class="styled-select" multiple required>
                        <option value="ALL OCCASION" {{ $hasAllOccasion ? 'selected' : '' }}>All Occasions</option>
                        <option value="wedding" {{ in_array('wedding', $normalizedOccasions) ? 'selected' : '' }}>Wedding</option>
                        <option value="birthday" {{ in_array('birthday', $normalizedOccasions) ? 'selected' : '' }}>Birthday</option>
                        <option value="baptism" {{ in_array('baptism', $normalizedOccasions) ? 'selected' : '' }}>Baptism</option>
                        <option value="corporate" {{ in_array('corporate', $normalizedOccasions) ? 'selected' : '' }}>Corporate</option>
                    </select>
                    <small style="color:#94b9ff;">Hold Ctrl (Windows) or Command (Mac) to select multiple.</small>
                </div>
                <div class="form-group">
                    <label for="product_type">Product Type</label>
                    <input type="text" id="product_type" name="product_type" class="form-control" value="{{ old('product_type', $ink->product_type ?? 'invitation') }}" readonly>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="material_name">Material Name</label>
                    <input type="text" id="material_name" name="material_name" class="form-control" value="{{ old('material_name', $ink->material_name) }}" required>
                    <small style="color:#6b7280;">Required. Example: "Premium Black Ink"</small>
                </div>
                <div class="form-group">
                    <label for="ink_color">Ink Color</label>
                    <input type="text" id="ink_color" name="ink_color" class="form-control" value="{{ old('ink_color', $ink->ink_color) }}" required>
                    <small style="color:#6b7280;">Required. Color name or code (e.g. Black, Cyan, Magenta).</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="unit">Unit (for cans)</label>
                    <input type="text" id="unit" name="unit" class="form-control" value="{{ old('unit', $ink->unit ?? 'can') }}" required placeholder="e.g. can">
                    <small style="color:#6b7280;">Required. Usually "can" for inks.</small>
                </div>
                <div class="form-group">
                    <label for="size">Size (ml per can)</label>
                    <input type="text" id="size" name="size" class="form-control" value="{{ old('size', $ink->size) }}" required placeholder="e.g. 500 or 500ml">
                    <small style="color:#6b7280;">Required. Enter a number (e.g. 500) or include unit (e.g. 500ml). Numeric values will display as "500 ml" in lists.</small>
                </div>
                <div class="form-group">
                    <label for="stock_qty">Stock Qty (number of cans)</label>
                    <input type="number" id="stock_qty" name="stock_qty" class="form-control" value="{{ old('stock_qty', $ink->stock_qty ?? 0) }}" min="0" required>
                    <small style="color:#6b7280;">Required. Enter how many cans you currently have (integer).</small>
                </div>
                <div class="form-group">
                    <label for="reorder_level">Reorder Point (cans)</label>
                    <input type="number" id="reorder_level" name="reorder_level" class="form-control" value="{{ old('reorder_level', optional($ink->inventory)->reorder_level ?? 10) }}" min="0" required>
                    <small style="color:#6b7280;">When stock falls at or below this number, the system marks the ink as Low Stock.</small>
                    @error('reorder_level')
                        <small style="color:red;">{{ $message }}</small>
                    @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="cost_per_ml">Cost per ml (₱)</label>
                    <input type="number" step="0.01" id="cost_per_ml" name="cost_per_ml" class="form-control" value="{{ old('cost_per_ml', $ink->cost_per_ml) }}" min="0" required>
                    <small style="color:#6b7280;">Required. Unit price per milliliter (e.g. 0.25).</small>
                </div>
                <div class="form-group">
                    <label for="avg_usage_per_invite_ml">Average Usage per Invite (ml)</label>
                    <input type="number" step="0.01" id="avg_usage_per_invite_ml" name="avg_usage_per_invite_ml" class="form-control" value="{{ old('avg_usage_per_invite_ml', $ink->avg_usage_per_invite_ml) }}" min="0">
                    <small style="color:#6b7280;">Optional. Estimated ml used per invite (for inventory planning).</small>
                </div>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">Update Ink</button>
                <a href="{{ route('admin.materials.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <style>
        /* Reuse styles from create_materials.css or inline for consistency */
        .styled-select,
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }
        .styled-select:focus,
        .form-control:focus {
            border-color: #94b9ff;
            outline: none;
        }
        .btn-primary {
            background: #94b9ff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-primary:hover {
            background: #6a9be7;
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
            border: 1px solid #ccc;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
        }
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #c3e6cb;
        }
    </style>
</body>
</html>
