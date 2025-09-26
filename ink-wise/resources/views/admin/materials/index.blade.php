@extends('layouts.admin')

@section('title', 'Materials Management')

@section('content')
<!-- Add Fontisto CDN for fi icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@flaticon/flaticon-uicons/css/all/all.css">
<div class="materials-container">
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <h1>Materials Management</h1>

    
    {{-- Stock Summary Cards --}}
    <style>
        .summary-cards {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .summary-cards .card {
            flex: 1;
            min-width: 180px;
            padding: 20px;
            border-radius: 12px;
            color: #222;
            font-weight: 600;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.06);
            background: #fafafa;
            border: 2px solid #94b9ff;
            transition: transform 0.2s, border 0.2s;
            text-decoration: none;
        }
        .summary-cards .card:hover {
            transform: translateY(-4px);
            border-color: #3cd5c8;
        }
        .summary-cards .card h3 {
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #3a4d5c;
        }
        .summary-cards .card p {
            font-size: 22px;
            margin: 0;
            color: #23b26d;
            font-weight: bold;
        }
        /* Make the "Total Stock Qty" card have #94b9ff text */
        .summary-cards .card.qty p {
            color: #94b9ff;
        }
        /* Add New Material Button */
        .btn-primary {
            background: #94b9ff !important;
            color: #fff !important;
            border: none;
            border-radius: 8px;
            padding: 8px 18px;
            font-weight: 700;
            font-family: 'Nunito', sans-serif;
            transition: background 0.2s, color 0.2s;
            box-shadow: 0 2px 8px rgba(148,185,255,0.10);
        }
        .btn-primary:hover {
            background: #6a9be7 !important;
            color: #fff !important;
        }
        /* Search Button */
        .btn-secondary, .search-bar button {
            background: #94b9ff !important;
            color: #fff !important;
            border: none;
            border-radius: 8px;
            padding: 8px 14px;
            font-weight: 600;
            font-family: 'Nunito', sans-serif;
            margin-left: 8px;
            transition: background 0.2s, color 0.2s;
        }
        .btn-secondary:hover, .search-bar button:hover {
            background: #6a9be7 !important;
            color: #fff !important;
        }
    </style>
    <div class="summary-cards">
        <a href="{{ route('admin.materials.index', ['status' => 'all']) }}" class="card total">
            <h3>Total Materials</h3>
            <p>{{ $materials->count() + $inks->count() }}</p>
        </a>
        <a href="{{ route('admin.materials.index', ['status' => 'low']) }}" class="card low">
            <h3>Low Stock</h3>
            <p>
                {{
                    $materials->filter(function($m) {
                        $stock = $m->inventory->stock_level ?? $m->stock_qty ?? 0;
                        $reorder = $m->inventory->reorder_level ?? $m->reorder_point ?? 0;
                        return $stock > 0 && $stock <= $reorder;
                    })->count()
                    +
                    $inks->filter(function($ink) {
                        $stock = $ink->stock_qty_ml ?? 0;
                        $reorder = $ink->reorder_point_ml ?? 10;
                        return $stock > 0 && $stock <= $reorder;
                    })->count()
                }}
            </p>
        </a>
        <a href="{{ route('admin.materials.index', ['status' => 'out']) }}" class="card out">
            <h3>Out of Stock</h3>
            <p>
                {{
                    $materials->filter(function($m) {
                        $stock = $m->inventory->stock_level ?? $m->stock_qty ?? 0;
                        return $stock <= 0;
                    })->count()
                    +
                    $inks->filter(function($ink) {
                        return ($ink->stock_qty_ml ?? 0) <= 0;
                    })->count()
                }}
            </p>
        </a>
        <a href="{{ route('admin.materials.index', ['status' => 'qty']) }}" class="card qty">
            <h3>Total Stock Qty</h3>
            <p>
                {{
                    $materials->sum(function($m) {
                        return $m->inventory->stock_level ?? $m->stock_qty ?? 0;
                    })
                    +
                    $inks->sum(function($ink) {
                        return $ink->stock_qty_ml ?? 0;
                    })
                }}
            </p>
        </a>
    </div>

    {{-- Add Material Button --}}
    <div class="top-actions">
        <div class="search-bar">
            <form method="GET" action="{{ route('admin.materials.index') }}" style="display:flex; align-items:center; gap:8px;">
                <span style="color:#94b9ff; font-size:18px; margin-right:4px;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search materials or inks..." class="form-control">
                <div class="filter-wrapper" style="position:relative;">
                    <input type="hidden" name="occasion" id="occasionInput" value="{{ request('occasion') }}">
                    <button type="button" id="filterToggle" class="btn btn-secondary" title="Filter occasions" style="display:flex; align-items:center; gap:6px;">
                        <i class="fi fi-ss-filter-list"></i>
                    </button>
                    <div id="filterMenu" class="filter-menu" style="display:none; position:absolute; right:0; top:42px; min-width:180px; background:#fff; border:1px solid #e5e7eb; border-radius:8px; box-shadow:0 6px 18px rgba(0,0,0,0.08); z-index:1200; padding:8px;">
                        <button type="button" class="filter-option btn" data-value="" style="display:block; width:100%; text-align:left; padding:8px 10px;">All Occasions</button>
                        <button type="button" class="filter-option btn" data-value="all" style="display:block; width:100%; text-align:left; padding:8px 10px;">All (explicit)</button>
                        <button type="button" class="filter-option btn" data-value="wedding" style="display:block; width:100%; text-align:left; padding:8px 10px;">Wedding</button>
                        <button type="button" class="filter-option btn" data-value="birthday" style="display:block; width:100%; text-align:left; padding:8px 10px;">Birthday</button>
                        <button type="button" class="filter-option btn" data-value="baptism" style="display:block; width:100%; text-align:left; padding:8px 10px;">Baptism</button>
                        <button type="button" class="filter-option btn" data-value="corporate" style="display:block; width:100%; text-align:left; padding:8px 10px;">Corporate</button>
                    </div>
                </div>
                <button type="submit" class="btn btn-secondary">Search</button>
            </form>
        </div>
        <div style="display:flex; gap:10px; position:relative;">
            <button id="addMaterialBtn" class="btn btn-primary" style="position:relative;">
                <i class="fi fi-br-plus-small"></i> Add New Material
            </button>
            <!-- Floating options -->
            <div id="floatingOptions" style="display:none; position:absolute; top:45px; left:0; z-index:1000;">
                <a href="{{ route('admin.materials.create', ['type' => 'invitation']) }}" class="btn btn-primary" style="margin-bottom:8px; width:180px;">
                    <i class="fa-solid fa-envelope"></i> Invitation
                </a>
                <a href="{{ route('admin.materials.create', ['type' => 'giveaway']) }}" class="btn btn-primary" style="background:#23b26d; width:180px;">
                    <i class="fa-solid fa-gift"></i> Giveaways
                </a>
            </div>
        </div>
    </div>

    <script>
        const addBtn = document.getElementById('addMaterialBtn');
        const floating = document.getElementById('floatingOptions');

        // Show floating options on mouseover
        addBtn.addEventListener('mouseenter', function() {
            floating.style.display = 'block';
        });

        // Hide floating options when mouse leaves both button and floatingOptions
        addBtn.addEventListener('mouseleave', function(e) {
            setTimeout(() => {
                if (!floating.matches(':hover')) {
                    floating.style.display = 'none';
                }
            }, 100);
        });
        floating.addEventListener('mouseleave', function() {
            floating.style.display = 'none';
        });

        // Prevent hiding when hovering over floatingOptions
        floating.addEventListener('mouseenter', function() {
            floating.style.display = 'block';
        });
    </script>

    <script>
        // Filter icon menu behavior
        (function(){
            const filterToggle = document.getElementById('filterToggle');
            const filterMenu = document.getElementById('filterMenu');
            const occasionInput = document.getElementById('occasionInput');
            const searchForm = filterToggle ? filterToggle.closest('form') : null;

            if (!filterToggle) return;

            filterToggle.addEventListener('click', function(e){
                e.stopPropagation();
                filterMenu.style.display = filterMenu.style.display === 'block' ? 'none' : 'block';
            });

            document.querySelectorAll('#filterMenu .filter-option').forEach(btn => {
                btn.addEventListener('click', function(){
                    const val = this.getAttribute('data-value');
                    occasionInput.value = val;
                    // close menu then submit
                    filterMenu.style.display = 'none';
                    if (searchForm) searchForm.submit();
                });
            });

            // close when clicking outside
            document.addEventListener('click', function(e){
                if (!filterMenu.contains(e.target) && e.target !== filterToggle) {
                    filterMenu.style.display = 'none';
                }
            });
        })();
    </script>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="alert alert-success">
            ✅ {{ session('success') }}
        </div>
    @endif
        {{-- Search Bar --}}


    {{-- Materials Table --}}
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Material Name</th>
                    <th>Occasion</th>
                    <th>Product Type</th>
                    <th>Material Type</th>
                    <th>Size/ML</th>
                    <th>Color</th>
                    <th>Weight (GSM)</th>
                    <th>Unit</th>
                    <th>Unit Price/Per(ml)(₱)</th>
                    <th>Average Usage per Invite (ml)</th>
                    <th>Stock Qty</th>
                    <th>Reorder Point</th>
                    <th class="status-col text-center">Status</th>
                    <th class="actions-col text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                {{-- Materials --}}
                @forelse($materials as $material)
                    @php
                        $stock = $material->inventory->stock_level ?? $material->stock_qty ?? 0;
                        $reorder = $material->inventory->reorder_level ?? $material->reorder_point ?? 0;
                    @endphp
                    <tr>
                        <td>{{ $material->material_id }}</td>
                        <td class="fw-bold">{{ $material->material_name }}</td>
                        <td>
                            @php
                                // Normalize stored occasion values (CSV) and detect the "all occasions" case
                                $occasionsStored = is_string($material->occasion) ? explode(',', $material->occasion) : (array) $material->occasion;
                                $occasionsStored = array_map('trim', $occasionsStored);
                                $occasionsLower = array_map('strtolower', $occasionsStored);
                                $definedOccasions = ['wedding', 'birthday', 'baptism', 'corporate'];
                                sort($occasionsLower);
                                $allSet = $definedOccasions;
                                sort($allSet);

                                if ($occasionsLower === $allSet) {
                                    // Show the literal label the user requested
                                    echo 'ALL OCCASION';
                                } else {
                                    $occasionsPretty = array_map(function($o) { return ucfirst($o); }, $occasionsStored);
                                    echo implode(', ', $occasionsPretty);
                                }
                            @endphp
                        </td>
                        <td>{{ ucfirst($material->product_type) }}</td>
                        <td>
                            @php
                                $mt = $material->material_type ?? '';
                                // Normalize display: show PAPER when stored as 'cardstock'
                                $mtDisplay = (is_string($mt) && strtolower($mt) === 'cardstock') ? 'PAPER' : $mt;
                                $mtClass = (is_string($mt) && strtolower($mt) === 'cardstock') ? 'paper' : strtolower(str_replace(' ', '-', $mt));
                            @endphp
                            <span class="badge badge-type {{ $mtClass }}">
                                {{ $mtDisplay }}
                            </span>
                        </td>
                        <td>{{ $material->size ?? '-' }}</td>
                        <td>{{ $material->color ?? '-' }}</td>
                        <td>{{ $material->weight_gsm ?? '-' }}</td>
                        <td>{{ $material->unit ?? '-' }}</td>
                        <td>
                            ₱{{ number_format($material->unit_cost, 2) }}
                            @if($material->material_type === 'ink')
                                /ml
                            @endif
                        </td>
                        <td>
                            {{ $material->avg_usage_per_invite_ml ?? '-' }}
                        </td>
                        <td>
                            <span class="badge {{ $stock <= 0 ? 'stock-critical' : ($stock > 0 && $stock <= $reorder ? 'stock-low' : 'stock-ok') }}">
                                {{ $stock }}
                                @if($material->material_type === 'ink')
                                    ml
                                @endif
                            </span>
                        </td>
                        <td>{{ $reorder }}</td>
                        <td class="status-col text-center">
                            @if($stock <= 0)
                                <span class="badge" style="color: red;">Out of Stock</span>
                            @elseif($stock > 0 && $stock <= $reorder)
                                <span class="badge" style="color: orange;">Low Stock</span>
                            @else
                                <span class="badge" style="color: green;">In Stock</span>
                            @endif
                        </td>
                        <td class="actions-col text-center">
                            <a href="{{ route('admin.materials.edit', $material->material_id) }}" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('admin.materials.destroy', $material->material_id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this material?');" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                        <tr>
                            <td colspan="15" class="text-center">No materials found.</td>
                    </tr>
                @endforelse

                {{-- Inks --}}
                @forelse($inks as $ink)
                    @php
                        // ✅ Updated: Use dynamic reorder_point_ml with fallback
                        $reorder = $ink->reorder_point_ml ?? 10;
                    @endphp
                    <tr>
                        <td>{{ $ink->id }}</td>
                        <td class="fw-bold">{{ $ink->material_name }}</td>
                        <td>
                            @php
                                $inkOccStored = is_string($ink->occasion) ? explode(',', $ink->occasion) : (array) $ink->occasion;
                                $inkOccStored = array_map('trim', $inkOccStored);
                                $inkOccLower = array_map('strtolower', $inkOccStored);
                                $definedOccasions = ['wedding', 'birthday', 'baptism', 'corporate'];
                                sort($inkOccLower);
                                $allSet = $definedOccasions;
                                sort($allSet);

                                if ($inkOccLower === $allSet) {
                                    echo 'ALL OCCASION';
                                } else {
                                    $inkPretty = array_map(function($o) { return ucfirst($o); }, $inkOccStored);
                                    echo implode(', ', $inkPretty);
                                }
                            @endphp
                        </td>
                        <td>{{ ucfirst($ink->product_type) }}</td>
                        <td>
                            <span class="badge badge-type ink">Ink</span>
                        </td>
                        <td>
                            @if(!empty($ink->size))
                                @php
                                    $sizeRaw = trim($ink->size);
                                    // If it's just a number (integer or decimal) and doesn't already include 'ml', append ' ml'
                                    if (preg_match('/^\d+(?:\.\d+)?$/', $sizeRaw) && !preg_match('/ml/i', $sizeRaw)) {
                                        $sizeDisplay = $sizeRaw . ' ml';
                                    } else {
                                        $sizeDisplay = $sizeRaw;
                                    }
                                @endphp
                                {{ $sizeDisplay }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $ink->ink_color }}</td>
                        <td>-</td>
                        <td>{{ $ink->unit ?? 'can' }}</td>
                        <td>₱{{ number_format($ink->cost_per_ml, 2) }}/ml</td>
                        <td>{{ $ink->avg_usage_per_invite_ml ?? '-' }}</td>
                        <td>
                            @php $inkQty = $ink->stock_qty ?? $ink->stock_qty_ml ?? 0; @endphp
                            <span class="badge {{ $inkQty <= 0 ? 'stock-critical' : ($inkQty > 0 && $inkQty <= $reorder ? 'stock-low' : 'stock-ok') }}">
                                {{ $ink->stock_qty ?? ($ink->stock_qty_ml ? $ink->stock_qty_ml . ' ml' : 0) }}
                            </span>
                        </td>
                        <td>{{ $reorder }}</td>
                        <td class="status-col text-center">
                            @php $inkStock = $ink->stock_qty ?? ($ink->stock_qty_ml ?? 0); @endphp
                            @if($inkStock <= 0)
                                <span class="badge" style="color: red;">Out of Stock</span>
                            @elseif($inkStock > 0 && $inkStock <= $reorder)
                                <span class="badge" style="color: orange;">Low Stock</span>
                            @else
                                <span class="badge" style="color: green;">In Stock</span>
                            @endif
                        </td>
                        <td class="actions-col text-center">
                            <a href="{{ route('admin.inks.edit', $ink->id) }}" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('admin.inks.destroy', $ink->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this ink?');" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                            <td colspan="15" class="text-center">No inks found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
