@extends('layouts.admin')

@section('title', 'Materials Management')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@flaticon/flaticon-uicons/css/all/all.css">
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
@endpush

@section('content')
<main class="materials-page admin-page-shell materials-container" role="main">
    <header class="page-header">
        <div>
            <h1 class="page-title">Materials Management</h1>
            <p class="page-subtitle">Track stock levels, availability, and reorder health.</p>
        </div>
    </header>
    <section class="summary-grid" aria-label="Inventory summary">
        <a href="{{ route('admin.materials.index', ['status' => 'all']) }}" class="summary-card" aria-label="View all materials">
            <div class="summary-card-header">
                <span class="summary-card-label">Total Materials</span>
                <span class="summary-card-chip accent">Catalog</span>
            </div>
            <span class="summary-card-value">{{ number_format($summary['total_items']) }}</span>
            <span class="summary-card-meta">Overall items on record</span>
        </a>
        <a href="{{ route('admin.materials.index', ['status' => 'low']) }}" class="summary-card summary-card--low" aria-label="Filter low stock materials">
            <div class="summary-card-header">
                <span class="summary-card-label">Low Stock</span>
                <span class="summary-card-chip warning">Action Needed</span>
            </div>
            <span class="summary-card-value">{{ number_format($summary['low_stock']) }}</span>
            <span class="summary-card-meta">At or near reorder level</span>
        </a>
        <a href="{{ route('admin.materials.index', ['status' => 'out']) }}" class="summary-card summary-card--out" aria-label="Filter out of stock materials">
            <div class="summary-card-header">
                <span class="summary-card-label">Out of Stock</span>
                <span class="summary-card-chip danger">Unavailable</span>
            </div>
            <span class="summary-card-value">{{ number_format($summary['out_stock']) }}</span>
            <span class="summary-card-meta">Requires immediate replenishment</span>
        </a>
        <a href="{{ route('admin.materials.index', ['status' => 'qty']) }}" class="summary-card summary-card--qty" aria-label="View total stock quantity">
            <div class="summary-card-header">
                <span class="summary-card-label">Total Stock Qty</span>
                <span class="summary-card-chip accent">Units</span>
            </div>
            <span class="summary-card-value">{{ number_format($summary['total_stock_qty']) }}</span>
            <span class="summary-card-meta">Combined across all materials</span>
        </a>
    </section>

    <section class="materials-toolbar" aria-label="Material filters and actions">
        <div class="materials-toolbar__search">
            <form method="GET" action="{{ route('admin.materials.index') }}">
                <div class="search-input">
                    <span class="search-icon">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search materials or inks..." class="form-control">
                </div>
                <div class="filter-wrapper">
                    <input type="hidden" name="occasion" id="occasionInput" value="{{ request('occasion') }}">
                    <button type="button" id="filterToggle" class="btn btn-secondary filter-toggle" title="Filter occasions" aria-haspopup="true" aria-expanded="false">
                        <i class="fi fi-ss-filter-list"></i>
                    </button>
                    <div id="filterMenu" class="filter-menu" role="menu" aria-hidden="true">
                        <button type="button" class="filter-option-btn" data-value="" role="menuitem">All Occasions</button>
                        <button type="button" class="filter-option-btn" data-value="all" role="menuitem">All (explicit)</button>
                        <button type="button" class="filter-option-btn" data-value="wedding" role="menuitem">Wedding</button>
                        <button type="button" class="filter-option-btn" data-value="birthday" role="menuitem">Birthday</button>
                        <button type="button" class="filter-option-btn" data-value="baptism" role="menuitem">Baptism</button>
                        <button type="button" class="filter-option-btn" data-value="corporate" role="menuitem">Corporate</button>
                    </div>
                </div>
                <button type="submit" class="btn btn-secondary">Search</button>
            </form>
        </div>
        <div class="materials-toolbar__actions">
            <button id="addMaterialBtn" class="btn btn-primary btn-add" aria-haspopup="true" aria-expanded="false">
                <i class="fi fi-br-plus-small"></i> Add New Material
            </button>
            <div id="floatingOptions" class="floating-options" role="menu" aria-hidden="true">
                <a href="{{ route('admin.materials.create', ['type' => 'invitation']) }}" class="floating-link" role="menuitem">
                    <span class="floating-icon"><i class="fa-solid fa-envelope"></i></span>
                    <span>Invitation</span>
                </a>
                <a href="{{ route('admin.materials.create', ['type' => 'giveaway']) }}" class="floating-link" role="menuitem">
                    <span class="floating-icon"><i class="fa-solid fa-gift"></i></span>
                    <span>Giveaways</span>
                </a>
            </div>
        </div>
    </section>

    <script>
        const addBtn = document.getElementById('addMaterialBtn');
        const floating = document.getElementById('floatingOptions');

        const showFloating = () => {
            floating.style.display = 'block';
            floating.setAttribute('aria-hidden', 'false');
            addBtn.setAttribute('aria-expanded', 'true');
        };

        const hideFloating = () => {
            floating.style.display = 'none';
            floating.setAttribute('aria-hidden', 'true');
            addBtn.setAttribute('aria-expanded', 'false');
        };

        addBtn.addEventListener('mouseenter', showFloating);
        addBtn.addEventListener('mouseleave', () => {
            setTimeout(() => {
                if (!floating.matches(':hover')) {
                    hideFloating();
                }
            }, 100);
        });

        floating.addEventListener('mouseenter', showFloating);
        floating.addEventListener('mouseleave', hideFloating);
    </script>

    <script>
        // Filter icon menu behavior
        (function(){
            const filterToggle = document.getElementById('filterToggle');
            const filterMenu = document.getElementById('filterMenu');
            const occasionInput = document.getElementById('occasionInput');
            const searchForm = filterToggle ? filterToggle.closest('form') : null;

            if (!filterToggle || !filterMenu) return;

            const openMenu = () => {
                filterMenu.style.display = 'block';
                filterMenu.setAttribute('aria-hidden', 'false');
                filterToggle.setAttribute('aria-expanded', 'true');
            };

            const closeMenu = () => {
                filterMenu.style.display = 'none';
                filterMenu.setAttribute('aria-hidden', 'true');
                filterToggle.setAttribute('aria-expanded', 'false');
            };

            filterToggle.addEventListener('click', function(e){
                e.stopPropagation();
                const isOpen = filterMenu.style.display === 'block';
                if (isOpen) {
                    closeMenu();
                } else {
                    openMenu();
                }
            });

            document.querySelectorAll('#filterMenu .filter-option-btn').forEach(btn => {
                btn.addEventListener('click', function(){
                    const val = this.getAttribute('data-value');
                    occasionInput.value = val;
                    closeMenu();
                    if (searchForm) searchForm.submit();
                });
            });

            // close when clicking outside
            document.addEventListener('click', function(e){
                if (!filterMenu.contains(e.target) && e.target !== filterToggle) {
                    closeMenu();
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
                    <th>Unit Price (₱)</th>
                    <th>Average Usage per Invite (ml)</th>
                    <th>Stock Qty</th>
                    <th>Reorder Point</th>
                    <th class="status-col text-center">Status</th>
                    <th class="actions-col text-center">Actions</th>
                </tr>
            </thead>
            @php $rowIndex = 0; @endphp
            <tbody class="materials-table-body">
                {{-- Materials --}}
                @forelse($materials as $material)
                    @php
                        $stock = $material->inventory->stock_level ?? $material->stock_qty ?? 0;
                        $reorder = $material->inventory->reorder_level ?? $material->reorder_point ?? 0;
                        $rowIndex++;
                    @endphp
                    <tr data-entry-index="{{ $rowIndex }}">
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
                        @php
                            $materialStatusClass = $stock <= 0 ? 'out' : ($stock <= $reorder ? 'low' : 'ok');
                            $materialStatusLabel = $stock <= 0 ? 'Out of Stock' : ($stock <= $reorder ? 'Low Stock' : 'In Stock');
                        @endphp
                        <td class="status-col text-center">
                            <span class="status-label {{ $materialStatusClass }}">{{ $materialStatusLabel }}</span>
                        </td>
                        <td class="actions-col text-center">
                            <a href="{{ route('admin.materials.edit', $material->material_id) }}" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('admin.materials.destroy', $material->material_id) }}" method="POST" class="inline-form">
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
                        $inventory = $ink->inventory;
                        $stockLevel = $inventory->stock_level ?? $ink->stock_qty ?? $ink->stock_qty_ml ?? 0;
                        $reorder = $inventory->reorder_level ?? 10;
                        $statusRemark = $inventory->remarks ?? ($stockLevel <= 0 ? 'Out of Stock' : ($stockLevel <= $reorder ? 'Low Stock' : 'In Stock'));
                        $statusBadgeClass = $stockLevel <= 0 ? 'stock-critical' : ($stockLevel <= $reorder ? 'stock-low' : 'stock-ok');
                        $rowIndex++;
                    @endphp
                    <tr data-entry-index="{{ $rowIndex }}">
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
                            <span class="badge {{ $statusBadgeClass }}">
                                {{ $stockLevel }} {{ $ink->unit ?? 'units' }}
                            </span>
                            @if(!empty($ink->stock_qty_ml))
                                <div class="approx-note">≈ {{ number_format($ink->stock_qty_ml, 0) }} ml</div>
                            @endif
                        </td>
                        <td>{{ $reorder }} {{ $ink->unit ?? 'units' }}</td>
                        @php
                            $inkStatusClass = $statusRemark === 'Out of Stock' ? 'out' : ($statusRemark === 'Low Stock' ? 'low' : 'ok');
                        @endphp
                        <td class="status-col text-center">
                            <span class="status-label {{ $inkStatusClass }}">{{ $statusRemark }}</span>
                        </td>
                        <td class="actions-col text-center">
                            <a href="{{ route('admin.inks.edit', $ink->id) }}" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('admin.inks.destroy', $ink->id) }}" method="POST" class="inline-form">
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

    @php
        $totalEntries = $summary['total_items'] ?? 0;
        $startEntry = $totalEntries > 0 ? 1 : 0;
        $endEntry = $totalEntries;
    @endphp
    <div class="table-footer" aria-live="polite">
        <span class="table-footer__info" data-entry-info>
            Showing {{ $startEntry }} to {{ $endEntry }} of {{ $totalEntries }} {{ \Illuminate\Support\Str::plural('entry', $totalEntries) }}
        </span>
        <div class="table-footer__nav" role="navigation" aria-label="Entries navigation">
            <button type="button" class="entries-btn" id="entriesPrev" disabled>Previous</button>
            <button type="button" class="entries-btn" id="entriesNext" disabled>Next</button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const rows = Array.from(document.querySelectorAll('.materials-table-body tr[data-entry-index]'));
            const infoEl = document.querySelector('[data-entry-info]');
            const prevBtn = document.getElementById('entriesPrev');
            const nextBtn = document.getElementById('entriesNext');
            const totalEntries = rows.length;
            const pageSize = 10;
            let currentPage = 1;

            if (!rows.length) {
                if (infoEl) {
                    infoEl.textContent = 'Showing 0 to 0 of 0 entries';
                }
                return;
            }

            const updateView = () => {
                const totalPages = Math.max(1, Math.ceil(totalEntries / pageSize));
                currentPage = Math.min(Math.max(1, currentPage), totalPages);
                const startIndex = (currentPage - 1) * pageSize;
                const endIndex = Math.min(startIndex + pageSize, totalEntries);

                rows.forEach((row, idx) => {
                    row.style.display = (idx >= startIndex && idx < endIndex) ? 'table-row' : 'none';
                });

                if (infoEl) {
                    const entryLabel = (totalEntries === 1) ? 'entry' : 'entries';
                    infoEl.textContent = `Showing ${startIndex + 1} to ${endIndex} of ${totalEntries} ${entryLabel}`;
                }

                if (prevBtn) prevBtn.disabled = currentPage <= 1;
                if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
            };

            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    if (currentPage > 1) {
                        currentPage--;
                        updateView();
                    }
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    const totalPages = Math.max(1, Math.ceil(totalEntries / pageSize));
                    if (currentPage < totalPages) {
                        currentPage++;
                        updateView();
                    }
                });
            }

            updateView();
        });
    </script>
</main>
@endsection
