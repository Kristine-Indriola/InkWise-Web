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

            <div class="form-row">
                <div class="form-group">
                    <label for="material_name">Material Name</label>
                    <input type="text" id="material_name" name="material_name" class="form-control" value="{{ old('material_name', $ink->material_name) }}" required>
                </div>
                <div class="form-group">
                    <label for="occasion">Occasion</label>
                    <select id="occasion" name="occasion" class="styled-select" required>
                        <option value="">Select Occasion</option>
                        <option value="wedding" {{ old('occasion', $ink->occasion) == 'wedding' ? 'selected' : '' }}>Wedding</option>
                        <option value="birthday" {{ old('occasion', $ink->occasion) == 'birthday' ? 'selected' : '' }}>Birthday</option>
                        <option value="baptism" {{ old('occasion', $ink->occasion) == 'baptism' ? 'selected' : '' }}>Baptism</option>
                        <option value="corporate" {{ old('occasion', $ink->occasion) == 'corporate' ? 'selected' : '' }}>Corporate</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="product_type">Product Type</label>
                    <select id="product_type" name="product_type" class="styled-select" required>
                        <option value="">Select Product Type</option>
                        <option value="invitation" {{ old('product_type', $ink->product_type) == 'invitation' ? 'selected' : '' }}>Invitation</option>
                        <option value="giveaway" {{ old('product_type', $ink->product_type) == 'giveaway' ? 'selected' : '' }}>Giveaway</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="ink_color">Ink Color</label>
                    <input type="text" id="ink_color" name="ink_color" class="form-control" value="{{ old('ink_color', $ink->ink_color) }}" required>
                </div>
                <div class="form-group">
                    <label for="stock_qty_ml">Stock Qty (ml)</label>
                    <input type="number" id="stock_qty_ml" name="stock_qty_ml" class="form-control" value="{{ old('stock_qty_ml', $ink->stock_qty_ml) }}" min="0" required>
                </div>
                <div class="form-group">
                    <label for="cost_per_ml">Cost per ml (₱)</label>
                    <input type="number" step="0.01" id="cost_per_ml" name="cost_per_ml" class="form-control" value="{{ old('cost_per_ml', $ink->cost_per_ml) }}" min="0" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="avg_usage_per_invite_ml">Average Usage per Invite (ml)</label>
                    <input type="number" step="0.01" id="avg_usage_per_invite_ml" name="avg_usage_per_invite_ml" class="form-control" value="{{ old('avg_usage_per_invite_ml', $ink->avg_usage_per_invite_ml) }}" min="0">
                </div>
                <div class="form-group">
                    <label for="reorder_point_ml">Reorder Point (ml)</label>
                    <input type="number" id="reorder_point_ml" name="reorder_point_ml" class="form-control" value="{{ old('reorder_point_ml', $ink->reorder_point_ml ?? 10) }}" min="0">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group full-width">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3">{{ old('description', $ink->description) }}</textarea>
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