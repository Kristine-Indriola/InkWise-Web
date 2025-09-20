<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Material</title>
    <link rel="stylesheet" href="{{ asset('css/admin-css/create_materials.css') }}">
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap" rel="stylesheet">
    <style>
        body, input, select, textarea, button, .form-control, .styled-select {
            font-family: 'Poppins', Arial, sans-serif !important;
        }
    </style>
</head>
<body>
    @php
        $productType = request('type') ? strtolower(request('type')) : '';
    @endphp

    <div class="container">
        <h2>Add New Material</h2>

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

        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-bottom: 20px;">
            @if($productType === 'giveaway')
                <a href="{{ route('admin.materials.create', ['type' => 'invitation']) }}" class="btn btn-secondary" style="background:#94b9ff; color:#fff; border:none; border-radius:5px; padding:8px 18px; font-weight:600; text-decoration:none;">
                    Switch to Invitation Form
                </a>
            @else
                <a href="{{ route('admin.materials.create', ['type' => 'giveaway']) }}" class="btn btn-secondary" style="background: #94b9ff; color:#fff; border:none; border-radius:5px; padding:8px 18px; font-weight:600; text-decoration:none;">
                    Switch to Giveaways Form
                </a>
            @endif
        </div>

        @if($productType === 'giveaway')
            {{-- Giveaways Form --}}
            <form action="{{ route('admin.materials.store') }}" method="POST" style="margin-bottom:32px;">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label>Material Name</label>
                        <input type="text" name="material_name" class="form-control" required>
                        @error('material_name') <small style="color:red;">{{ $message }}</small> @enderror
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Occasion</label>
                        <select name="occasion[]" required class="form-control styled-select" multiple>
                            <option value="wedding" {{ in_array('wedding', old('occasion', [])) ? 'selected' : '' }}>Wedding</option>
                            <option value="birthday" {{ in_array('birthday', old('occasion', [])) ? 'selected' : '' }}>Birthday</option>
                            <option value="baptism" {{ in_array('baptism', old('occasion', [])) ? 'selected' : '' }}>Baptism</option>
                            <option value="corporate" {{ in_array('corporate', old('occasion', [])) ? 'selected' : '' }}>Corporate</option>
                        </select>
                        <small style="color:#94b9ff;">Hold Ctrl (Windows) or Command (Mac) to select multiple.</small>
                    </div>
                    <div class="form-group">
                        <label>Product Type</label>
                        <input type="text" name="product_type" class="form-control" value="giveaway" readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Material Type</label>
                        <input type="text" name="material_type" class="form-control" placeholder="e.g. Mug, T-shirt, Keychain">  <!-- ✅ Added: Optional text input for custom types -->
                        @error('material_type') <small style="color:red;">{{ $message }}</small> @enderror
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" placeholder="Description"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Unit of Measure</label>
                        <input type="text" name="unit" class="form-control" placeholder="e.g. pcs, pack" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Stock Qty</label>
                        <input type="number" name="stock_qty" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Reorder Point</label>
                        <input type="number" name="reorder_point" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Unit Cost (₱)</label>
                        <input type="number" step="0.01" name="unit_cost" class="form-control" required>
                    </div>
                </div>
                <div class="btn-group">
                    <button type="submit">Save Giveaways Material</button>
                    <a href="{{ route('admin.materials.index') }}" class="btn-back">Back to Materials</a>
                </div>
            </form>
        @elseif($productType === 'invitation' || $productType === 'ink')
            {{-- Invitation/Other Material Form --}}
            <form id="materialForm" action="{{ route('admin.materials.store') }}" method="POST">
    @csrf
    <div id="default-fields">
        <div class="form-row">
            <div class="form-group">
                <label>Material Name</label>
                <input type="text" name="material_name" value="{{ old('material_name') }}" required class="form-control styled-select">
                @error('material_name') <small style="color:red;">{{ $message }}</small> @enderror
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Occasion</label>
                <select name="occasion[]" required class="form-control styled-select" multiple>
                    <option value="wedding" {{ in_array('wedding', old('occasion', [])) ? 'selected' : '' }}>Wedding</option>
                    <option value="birthday" {{ in_array('birthday', old('occasion', [])) ? 'selected' : '' }}>Birthday</option>
                    <option value="baptism" {{ in_array('baptism', old('occasion', [])) ? 'selected' : '' }}>Baptism</option>
                    <option value="corporate" {{ in_array('corporate', old('occasion', [])) ? 'selected' : '' }}>Corporate</option>
                </select>
                <small style="color:#94b9ff;">Hold Ctrl (Windows) or Command (Mac) to select multiple.</small>
            </div>
            <div class="form-group">
                <label>Product Type</label>
                <input type="text" name="product_type" class="form-control" value="invitation" readonly>
            </div>
            <div class="form-group">
                <label>Material Type</label>
                <select name="material_type" id="materialTypeSelect" required class="form-control styled-select">
                    <option value="">-- Select Material Type --</option>
                    <option value="cardstock" {{ old('material_type') == 'cardstock' ? 'selected' : '' }}>Cardstock</option>
                    <option value="envelope" {{ old('material_type') == 'envelope' ? 'selected' : '' }}>Envelope</option>
                    <option value="foil" {{ old('material_type') == 'foil' ? 'selected' : '' }}>Foil</option>
                    <option value="lamination" {{ old('material_type') == 'lamination' ? 'selected' : '' }}>Lamination</option>
                    <option value="packaging" {{ old('material_type') == 'packaging' ? 'selected' : '' }}>Packaging</option>
                    <option value="ink" {{ old('material_type') == 'ink' ? 'selected' : '' }}>Ink</option>
                </select>
            </div>
        </div>
        <div class="form-row" id="other-fields">
            <div class="form-group">
                <label>Size</label>
                <input type="text" name="size" value="{{ old('size') }}" required class="form-control styled-select">
            </div>
            <div class="form-group">
                <label>Color</label>
                <input type="text" name="color" value="{{ old('color') }}" required class="form-control styled-select">
            </div>
            <div class="form-group">
                <label>Weight (GSM)</label>
                <input type="number" name="weight_gsm" value="{{ old('weight_gsm') }}" required class="form-control styled-select">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Unit</label>
                <input type="text" name="unit" value="{{ old('unit') }}" required class="form-control styled-select">
            </div>
            <div class="form-group">
                <label>Unit Cost</label>
                <input type="number" step="0.01" name="unit_cost" value="{{ old('unit_cost') }}" required class="form-control styled-select">
            </div>
            <div class="form-group">
                <label>Stock Qty</label>
                <input type="number" name="stock_qty" value="{{ old('stock_qty') }}" required class="form-control styled-select">
            </div>
            <div class="form-group">
                <label>Reorder Point</label>
                <input type="number" name="reorder_point" value="{{ old('reorder_point') }}" required class="form-control styled-select">
            </div>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" required class="form-control styled-select">{{ old('description') }}</textarea>
        </div>
    </div>

    <!-- Ink fields (hidden by default) -->
    <div id="ink-fields" style="display:none;">
        <div class="form-row">
            <div class="form-group">
                <label>Material Name</label>
                <input type="text" name="material_name" value="{{ old('material_name') }}" required class="form-control styled-select">
            </div>
            <div class="form-group">
                <label>Material Type</label>
                <input type="text" name="material_type" value="ink" readonly class="form-control styled-select" style="background-color:#e9ecef;">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Occasion</label>
                <select name="occasion[]" required class="form-control styled-select" multiple>
                    <option value="wedding" {{ in_array('wedding', old('occasion', [])) ? 'selected' : '' }}>Wedding</option>
                    <option value="birthday" {{ in_array('birthday', old('occasion', [])) ? 'selected' : '' }}>Birthday</option>
                    <option value="baptism" {{ in_array('baptism', old('occasion', [])) ? 'selected' : '' }}>Baptism</option>
                    <option value="corporate" {{ in_array('corporate', old('occasion', [])) ? 'selected' : '' }}>Corporate</option>
                </select>
                <small style="color:#94b9ff;">Hold Ctrl (Windows) or Command (Mac) to select multiple.</small>
            </div>
            <div class="form-group">
                <label>Product Type</label>
                <select name="product_type" required class="form-control styled-select">
                    <option value="invitation">Invitation</option>
                    <option value="giveaway">Giveaway</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Ink Color</label>
                <input type="text" name="ink_color" value="{{ old('ink_color') }}" required class="form-control styled-select">
            </div>
            <div class="form-group">
                <label>Stock Qty (ml)</label>
                <input type="number" name="stock_qty_ml" value="{{ old('stock_qty_ml') }}" required class="form-control styled-select">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Cost per ml (₱)</label>
                <input type="number" step="0.01" name="cost_per_ml" value="{{ old('cost_per_ml') }}" required class="form-control styled-select">
            </div>
            <div class="form-group">
                <label>Average Usage per Invite (ml)</label>
                <input type="number" step="0.01" name="avg_usage_per_invite_ml" value="{{ old('avg_usage_per_invite_ml') }}" required class="form-control styled-select">
            </div>
            <div class="form-group">
                <label>Cost per Invite (₱)</label>
                <input type="number" step="0.01" name="cost_per_invite" value="{{ old('cost_per_invite') }}" class="form-control styled-select">  <!-- ✅ Added back without required -->
            </div>
        </div>
    </div>

    <div class="btn-group">
        <button type="submit">Save Material</button>
        <a href="{{ route('admin.materials.index') }}" class="btn-back">Back to Materials</a>
    </div>
</form>
        @else
            <p>Please select a material type.</p>
        @endif


    <style>
        /* Nice select box styling */
        .styled-select,
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1.5px solid #94b9ff;
            border-radius: 6px;
            background: #fafdff;
            font-size: 16px;
            color: #2a3d4d;
            transition: border 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(148,185,255,0.05);
            appearance: none;
            outline: none;
        }

        .styled-select:focus,
        .form-control:focus {
            border-color: #3cd5c8;
            box-shadow: 0 0 0 2px #c7f7f2;
        }

        .styled-select {
            background-image: url("data:image/svg+xml,%3Csvg width='16' height='16' fill='gray' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M4 6l4 4 4-4z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px top 50%;
            padding-right: 40px;
        }

        .material-type-checkboxes input[type="checkbox"] {
            accent-color: #94b9ff;
            width: 18px;
            height: 18px;
        }
        .material-type-checkboxes label {
            background: #fafdff;
            border: 1.5px solid #94b9ff;
            border-radius: 6px;
            padding: 6px 12px;
            cursor: pointer;
            transition: border 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(148,185,255,0.05);
            margin-bottom: 4px;
        }
        .material-type-checkboxes label:hover {
            border-color: #3cd5c8;
            box-shadow: 0 0 0 2px #c7f7f2;
        }
    </style>
    <script>
        // Limit selection to 4, or allow "All" to override
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.material-type-checkbox');
            const allCheckbox = document.getElementById('material-type-all');
            checkboxes.forEach(cb => {
                cb.addEventListener('change', function() {
                    // If "All" is checked, uncheck others
                    if (this.value === 'all' && this.checked) {
                        checkboxes.forEach(c => { if(c.value !== 'all') c.checked = false; });
                    }
                    // If any other is checked, uncheck "All"
                    if (this.value !== 'all' && this.checked && allCheckbox && allCheckbox.checked) {
                        allCheckbox.checked = false;
                    }
                    // Limit to 4 (excluding "All")
                    const checked = Array.from(checkboxes).filter(c => c.checked && c.value !== 'all');
                    if (checked.length > 4) {
                        this.checked = false;
                        alert('You can select up to 4 material types only.');
                    }
                });
            });
        });

        document.getElementById('materialTypeSelect').addEventListener('change', function() {
            if (this.value === 'ink') {
                document.getElementById('default-fields').style.display = 'none';
                document.getElementById('ink-fields').style.display = '';
            } else {
                document.getElementById('default-fields').style.display = '';
                document.getElementById('ink-fields').style.display = 'none';
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
        function toggleFields() {
            const isInk = document.getElementById('materialTypeSelect').value === 'ink';
            // Default fields visibility
            document.getElementById('default-fields').style.display = isInk ? 'none' : '';
            // Disable/enable default fields but DO NOT disable CSRF hidden token
            Array.from(document.querySelectorAll('#default-fields input, #default-fields select, #default-fields textarea')).forEach(el => {
                // keep the CSRF token and any other hidden inputs enabled
                if (el.name === '_token' || el.type === 'hidden') return;
                el.disabled = isInk;
            });

            // Ink fields visibility & enablement
            document.getElementById('ink-fields').style.display = isInk ? '' : 'none';
            Array.from(document.querySelectorAll('#ink-fields input, #ink-fields select, #ink-fields textarea')).forEach(el => {
                el.disabled = !isInk;
            });
        }

        const materialTypeSelect = document.getElementById('materialTypeSelect');
        if (materialTypeSelect) {
            materialTypeSelect.addEventListener('change', toggleFields);
            toggleFields(); // initial
        }
    });
</script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const materialTypeSelect = document.getElementById('materialTypeSelect');
        const form = document.getElementById('materialForm');
        materialTypeSelect.addEventListener('change', function() {
            if (this.value === 'ink') {
                form.action = "{{ route('admin.inks.store') }}";
            } else {
                form.action = "{{ route('admin.materials.store') }}";
            }
        });
        // Initial set
        if (materialTypeSelect.value === 'ink') {
            form.action = "{{ route('admin.inks.store') }}";
        }
    });
</script>
</body>
</html>
