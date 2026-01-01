<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Material</title>
    <link rel="stylesheet" href="{{ asset('css/admin-css/create_materials.css') }}">
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap" rel="stylesheet">
    <style>
        body, input, select, textarea, button, .form-control, .styled-select {
            font-family: 'Poppins', Arial, sans-serif !important;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const materialTypeSelect = document.getElementById('materialTypeSelect');
            const defaultFields = document.getElementById('default-fields');
            const inkFields = document.getElementById('ink-fields');
            const defaultInputs = defaultFields.querySelectorAll('input, select, textarea');
            const inkInputs = inkFields.querySelectorAll('input, select, textarea');

            function recordOriginalRequirement(inputs) {
                inputs.forEach(input => {
                    input.dataset.originalRequired = input.hasAttribute('required') ? 'true' : 'false';
                });
            }

            function setGroupState(inputs, enable) {
                inputs.forEach(input => {
                    if (enable) {
                        input.disabled = false;
                        if (input.dataset.originalRequired === 'true') {
                            input.setAttribute('required', 'required');
                        } else {
                            input.removeAttribute('required');
                        }
                    } else {
                        input.disabled = true;
                        input.removeAttribute('required');
                    }
                });
            }

            recordOriginalRequirement(defaultInputs);
            recordOriginalRequirement(inkInputs);

            function toggleFields() {
                const selectedType = materialTypeSelect.value;
                if (selectedType === 'ink') {
                    defaultFields.style.display = 'none';
                    inkFields.style.display = 'block';
                    setGroupState(defaultInputs, false);
                    setGroupState(inkInputs, true);
                } else {
                    defaultFields.style.display = 'block';
                    inkFields.style.display = 'none';
                    setGroupState(defaultInputs, true);
                    setGroupState(inkInputs, false);
                }
            }

            materialTypeSelect.addEventListener('change', toggleFields);
            toggleFields(); // Initial check
        });
    </script>
</head>
<body>
    <div class="container">
        <h2>Edit Material</h2>

        {{-- Success/Error Messages --}}
        @if(session('success'))
            <div class="success">{{ session('success') }}</div>
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

        <form action="{{ route('admin.materials.update', $material->material_id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-row">
                <div class="form-group">
                    <label>Material Name</label>
                    <input type="text" name="material_name" value="{{ old('material_name', $material->material_name) }}" required class="form-control">
                    @error('material_name') <small style="color:red;">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label>Material Type</label>
                    <select name="material_type" id="materialTypeSelect" required class="form-control styled-select">
                        <option value="">-- Select Material Type --</option>
                        <option value="paper" {{ old('material_type', $material->material_type) == 'paper' ? 'selected' : '' }}>PAPER</option>
                        <option value="ink" {{ old('material_type', $material->material_type) == 'ink' ? 'selected' : '' }}>INK</option>
                        <option value="envelopes" {{ old('material_type', $material->material_type) == 'envelopes' ? 'selected' : '' }}>ENVELOPES</option>
                        <option value="ribbon" {{ old('material_type', $material->material_type) == 'ribbon' ? 'selected' : '' }}>RIBBON</option>
                        <option value="powder" {{ old('material_type', $material->material_type) == 'powder' ? 'selected' : '' }}>POWDER</option>
                        <option value="souvenirs" {{ old('material_type', $material->material_type) == 'souvenirs' ? 'selected' : '' }}>SOUVENIRS</option>
                    </select>
                    @error('material_type') <small style="color:red;">{{ $message }}</small> @enderror
                </div>
            </div>

            <div id="default-fields">
                <div class="form-row">
                    <div class="form-group">
                        <label>Unit (e.g. pcs, ream, liter)</label>
                        <input type="text" name="unit" value="{{ old('unit', $material->unit) }}" required class="form-control">
                        @error('unit') <small style="color:red;">{{ $message }}</small> @enderror
                    </div>

                    <div class="form-group">
                        <label>Unit Price</label>
                        <input type="number" step="0.01" name="unit_cost" value="{{ old('unit_cost', $material->unit_cost) }}" required class="form-control">
                        @error('unit_cost') <small style="color:red;">{{ $message }}</small> @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Stock Level</label>
                        <input type="number" name="stock_level" value="{{ old('stock_level', $material->inventory->stock_level ?? 0) }}" required class="form-control">
                        @error('stock_level') <small style="color:red;">{{ $message }}</small> @enderror
                    </div>

                    <div class="form-group">
                        <label>Reorder Level</label>
                        <input type="number" name="reorder_level" value="{{ old('reorder_level', $material->inventory->reorder_level ?? 0) }}" required class="form-control">
                        @error('reorder_level') <small style="color:red;">{{ $message }}</small> @enderror
                    </div>
                </div>
            </div>

            <!-- Ink fields (hidden by default) -->
            <div id="ink-fields" style="display:none;">
                <div class="form-row">
                    <div class="form-group">
                        <label>Material Name</label>
                        <input type="text" name="material_name" value="{{ old('material_name', $material->material_name) }}" required class="form-control styled-select">
                        <small style="color:#6b7280;">Required. Example: "Premium Black Ink"</small>
                        @error('material_name') <small style="color:red;">{{ $message }}</small> @enderror
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Ink Color</label>
                        <input type="text" name="ink_color" value="{{ old('ink_color', $material->ink_color ?? '') }}" required class="form-control styled-select">
                        <small style="color:#6b7280;">Required. Color name or code (e.g. Black, Cyan, Magenta).</small>
                        @error('ink_color') <small style="color:red;">{{ $message }}</small> @enderror
                    </div>
                    <div class="form-group">
                        <label>Unit (for cans)</label>
                        <input type="text" name="unit" value="{{ old('unit', $material->unit ?? 'can') }}" required class="form-control styled-select" placeholder="e.g. can">
                        <small style="color:#6b7280;">Required. Usually "can" for inks.</small>
                        @error('unit') <small style="color:red;">{{ $message }}</small> @enderror
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Size (ml per can)</label>
                        <input type="text" name="size" value="{{ old('size', $material->size ?? '') }}" required class="form-control styled-select" placeholder="e.g. 500 or 500ml">
                        <small style="color:#6b7280;">Required. Enter a number (e.g. 500) or include unit (e.g. 500ml). Numeric values will display as "500 ml" in lists.</small>
                        @error('size') <small style="color:red;">{{ $message }}</small> @enderror
                    </div>
                    <div class="form-group">
                        <label>Stock Qty (number of cans)</label>
                        <input type="number" name="stock_qty" value="{{ old('stock_qty', $material->inventory->stock_level ?? 0) }}" required class="form-control styled-select" min="0">
                        <small style="color:#6b7280;">Required. Enter how many cans you currently have (integer).</small>
                        @error('stock_qty') <small style="color:red;">{{ $message }}</small> @enderror
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Cost per ml (₱)</label>
                        <input type="number" step="0.01" name="cost_per_ml" value="{{ old('cost_per_ml', $material->cost_per_ml ?? '') }}" required class="form-control styled-select">
                        <small style="color:#6b7280;">Required. Unit price per milliliter (e.g. 0.25).</small>
                        @error('cost_per_ml') <small style="color:red;">{{ $message }}</small> @enderror
                    </div>
                    <div class="form-group">
                        <label>Average Usage per Invite (ml)</label>
                        <input type="number" step="0.01" name="avg_usage_per_invite_ml" value="{{ old('avg_usage_per_invite_ml', $material->avg_usage_per_invite_ml ?? '') }}" class="form-control styled-select">
                        <small style="color:#6b7280;">Optional. Estimated ml used per invite (for inventory planning).</small>
                        @error('avg_usage_per_invite_ml') <small style="color:red;">{{ $message }}</small> @enderror
                    </div>
                </div>
            </div>

            <div class="btn-group">
                <button type="submit">Update Material</button>
                <a href="{{ route('admin.materials.index') }}" class="btn-back">Back to Materials</a>
            </div>
        </form>
    </div>
</body>
</html>
