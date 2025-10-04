@extends('layouts.staffapp')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@flaticon/flaticon-uicons/css/all/all.css">
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff-css/materials.css') }}">
@endpush

@section('title', 'Materials Management')

@section('content')
@php
    $materialsCollection = $materials instanceof \Illuminate\Support\Collection ? $materials : collect($materials);
    $statusFilter = request('status');

    $lowStockCount = $materialsCollection->filter(function ($material) {
        $stock = $material->inventory->stock_level ?? 0;
        $reorder = $material->inventory->reorder_level ?? 0;
        return $stock > 0 && $stock <= $reorder;
    })->count();

    $outOfStockCount = $materialsCollection->filter(function ($material) {
        $stock = $material->inventory->stock_level ?? 0;
        return $stock <= 0;
    })->count();

    $totalStockQty = $materialsCollection->sum(function ($material) {
        return $material->inventory->stock_level ?? 0;
    });
@endphp

<main class="materials-page admin-page-shell staff-materials-page" role="main">
    <header class="page-header">
        <div>
            <h1 class="page-title">Materials Management</h1>
            <p class="page-subtitle">Track stock health and respond quickly to low inventory alerts.</p>
        </div>
        <div class="page-header__quick-actions">
            <a href="{{ route('staff.materials.notification') }}" class="pill-link is-active" aria-label="Open low stock notifications">
                <i class="fi fi-rr-bell"></i>&nbsp;Notifications
            </a>
        </div>
    </header>

    @if(session('success'))
        <div class="alert staff-materials-alert" role="alert" aria-live="polite">
            ✅ {{ session('success') }}
        </div>
    @endif

    <section class="summary-grid" aria-label="Inventory summary">
        <a href="{{ route('staff.materials.index', ['status' => 'all']) }}" class="summary-card {{ in_array($statusFilter, [null, 'all'], true) ? 'is-active' : '' }}" aria-label="View all materials">
            <div class="summary-card-header">
                <span class="summary-card-label">Total Materials</span>
                <span class="summary-card-chip accent">Catalog</span>
            </div>
            <span class="summary-card-value">{{ number_format($materialsCollection->count()) }}</span>
            <span class="summary-card-meta">Overall items tracked</span>
        </a>
        <a href="{{ route('staff.materials.index', ['status' => 'low']) }}" class="summary-card summary-card--low {{ $statusFilter === 'low' ? 'is-active' : '' }}" aria-label="Filter low stock materials">
            <div class="summary-card-header">
                <span class="summary-card-label">Low Stock</span>
                <span class="summary-card-chip warning">Action Needed</span>
            </div>
            <span class="summary-card-value">{{ number_format($lowStockCount) }}</span>
            <span class="summary-card-meta">At or near reorder point</span>
        </a>
        <a href="{{ route('staff.materials.index', ['status' => 'out']) }}" class="summary-card summary-card--out {{ $statusFilter === 'out' ? 'is-active' : '' }}" aria-label="Filter out of stock materials">
            <div class="summary-card-header">
                <span class="summary-card-label">Out of Stock</span>
                <span class="summary-card-chip danger">Unavailable</span>
            </div>
            <span class="summary-card-value">{{ number_format($outOfStockCount) }}</span>
            <span class="summary-card-meta">Requires immediate restock</span>
        </a>
        <div class="summary-card summary-card--qty" aria-label="Total stock quantity on hand">
            <div class="summary-card-header">
                <span class="summary-card-label">Total Stock Qty</span>
                <span class="summary-card-chip accent">Units</span>
            </div>
            <span class="summary-card-value">{{ number_format($totalStockQty) }}</span>
            <span class="summary-card-meta">Combined across materials</span>
        </div>
    </section>

    <section class="materials-toolbar staff-materials-toolbar" aria-label="Search materials">
        <form method="GET" action="{{ route('staff.materials.index') }}" class="staff-materials-search">
            <div class="search-input">
                <span class="search-icon"><i class="fi fi-rr-search"></i></span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search materials or types" class="form-control" aria-label="Search materials">
            </div>
            <div class="staff-materials-toolbar-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fi fi-rr-search"></i>
                    <span>Search</span>
                </button>
                @if(request()->filled('search') || in_array($statusFilter, ['low', 'out', 'all'], true))
                    <a href="{{ route('staff.materials.index') }}" class="btn btn-secondary" aria-label="Reset filters">Clear</a>
                @endif
            </div>
        </form>
    </section>

    <section class="staff-materials-table" aria-label="Materials list">
        <div class="table-wrapper">
            <table class="table" role="grid">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Material Name</th>
                        <th scope="col">Type</th>
                        <th scope="col">Unit</th>
                        <th scope="col">Unit Cost (₱)</th>
                        <th scope="col">Stock Level</th>
                        <th scope="col">Reorder Level</th>
                        <th scope="col" class="status-col">Status</th>
                        <th scope="col" class="actions-col text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($materialsCollection as $material)
                        @php
                            $inventory = $material->inventory;
                            $stock = $inventory->stock_level ?? 0;
                            $reorder = $inventory->reorder_level ?? 0;
                            $statusClass = 'ok';
                            $statusLabel = 'In Stock';
                            $badgeClass = 'stock-ok';

                            if ($stock <= 0) {
                                $statusClass = 'out';
                                $statusLabel = 'Out of Stock';
                                $badgeClass = 'stock-critical';
                            } elseif ($stock <= $reorder) {
                                $statusClass = 'low';
                                $statusLabel = 'Low Stock';
                                $badgeClass = 'stock-low';
                            }

                            $typeSlug = strtolower(str_replace(' ', '-', $material->material_type ?? ''));
                            $typeClass = $typeSlug ?: 'unknown';
                        @endphp
                        <tr>
                            <td>{{ $material->material_id }}</td>
                            <td class="material-name">{{ $material->material_name }}</td>
                            <td>
                                <span class="badge badge-type {{ $typeClass }}">
                                    {{ strtoupper($material->material_type) }}
                                </span>
                            </td>
                            <td>{{ $material->unit ?? '—' }}</td>
                            <td>₱{{ number_format($material->unit_cost ?? 0, 2) }}</td>
                            <td>
                                <span class="badge {{ $badgeClass }}">{{ $stock }}</span>
                            </td>
                            <td>{{ $reorder }}</td>
                            <td class="status-col">
                                <span class="status-label {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="actions-col text-center">
                                <div class="materials-actions">
                                    <a href="{{ route('staff.materials.edit', $material->material_id) }}" class="btn btn-sm btn-warning" title="Edit material">
                                        <i class="fi fi-rr-pencil"></i>
                                    </a>
                                    <form action="{{ route('staff.materials.destroy', $material->material_id) }}" method="POST" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this material?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete material">
                                            <i class="fi fi-rr-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="table-empty">No materials found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const alertBanner = document.querySelector('.staff-materials-alert');
            if (alertBanner) {
                setTimeout(function () {
                    alertBanner.classList.add('is-dismissing');
                    setTimeout(() => alertBanner.remove(), 600);
                }, 4000);
            }
        });
    </script>
@endpush
