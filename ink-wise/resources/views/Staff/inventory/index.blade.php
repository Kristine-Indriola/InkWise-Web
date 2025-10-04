@extends('layouts.staffapp')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@flaticon/flaticon-uicons/css/all/all.css">
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff-css/inventory.css') }}">
@endpush

@section('title', 'Sample Inventory')

@section('content')
@php
    $inventoriesCollection = $inventories instanceof \Illuminate\Support\Collection ? $inventories : collect($inventories);
    $statusFilter = request('status');

    $lowStockCount = $inventoriesCollection->filter(function ($inventory) {
        $stock = $inventory->stock_level ?? 0;
        $reorder = $inventory->reorder_level ?? 0;
        return $stock > 0 && $stock <= $reorder;
    })->count();

    $outOfStockCount = $inventoriesCollection->filter(function ($inventory) {
        $stock = $inventory->stock_level ?? 0;
        return $stock <= 0;
    })->count();

    $totalStockQty = $inventoriesCollection->sum(function ($inventory) {
        return $inventory->stock_level ?? 0;
    });
@endphp

<main class="materials-page admin-page-shell staff-inventory-page" role="main">
    <header class="page-header">
        <div>
            <h1 class="page-title">Sample Inventory</h1>
            <p class="page-subtitle">Sample inventory for invitations and giveaways - staff focus on order management.</p>
        </div>
    </header>

    @if(session('success'))
        <div class="alert staff-inventory-alert" role="alert" aria-live="polite">
            ✅ {{ session('success') }}
        </div>
    @endif

    <div class="alert alert-info" role="alert">
        <i class="fi fi-rr-info"></i>
        <span>This is sample inventory data for invitations and giveaways. Staff members focus on managing customer orders and sending notifications.</span>
    </div>

    <section class="summary-grid" aria-label="Inventory summary">
        <a href="{{ route('staff.inventory.index', ['status' => 'all']) }}" class="summary-card {{ in_array($statusFilter, [null, 'all'], true) ? 'is-active' : '' }}" aria-label="View all inventory">
            <div class="summary-card-header">
                <span class="summary-card-label">Total Items</span>
                <span class="summary-card-chip accent">Stock</span>
            </div>
            <span class="summary-card-value">{{ number_format($inventoriesCollection->count()) }}</span>
            <span class="summary-card-meta">Items in inventory</span>
        </a>
        <a href="{{ route('staff.inventory.index', ['status' => 'low']) }}" class="summary-card summary-card--low {{ $statusFilter === 'low' ? 'is-active' : '' }}" aria-label="Filter low stock items">
            <div class="summary-card-header">
                <span class="summary-card-label">Low Stock</span>
                <span class="summary-card-chip warning">Action Needed</span>
            </div>
            <span class="summary-card-value">{{ number_format($lowStockCount) }}</span>
            <span class="summary-card-meta">At or near reorder point</span>
        </a>
        <a href="{{ route('staff.inventory.index', ['status' => 'out']) }}" class="summary-card summary-card--out {{ $statusFilter === 'out' ? 'is-active' : '' }}" aria-label="Filter out of stock items">
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
            <span class="summary-card-meta">Combined across items</span>
        </div>
    </section>

    <section class="inventory-table" aria-label="Inventory list">
        <div class="table-wrapper">
            <table class="table" role="grid">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Material</th>
                        <th scope="col">Stock Level</th>
                        <th scope="col">Reorder Level</th>
                        <th scope="col">Pending Orders</th>
                        <th scope="col">Completed Orders</th>
                        <th scope="col">Cancelled Orders</th>
                        <th scope="col">Location</th>
                        <th scope="col">Last Updated</th>
                        <th scope="col" class="status-col">Status</th>
                        <th scope="col" class="actions-col text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inventoriesCollection as $inventory)
                        @php
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
                        @endphp
                        <tr>
                            <td>{{ $inventory->inventory_id ?? $inventory->id }}</td>
                            <td class="material-name">{{ $inventory->material->material_name ?? 'Unknown Material' }}</td>
                            <td>
                                <span class="badge {{ $badgeClass }}">{{ $stock }}</span>
                            </td>
                            <td>{{ $reorder }}</td>
                            <td>
                                <span class="badge badge-warning">{{ $inventory->pending_orders ?? 0 }}</span>
                            </td>
                            <td>
                                <span class="badge badge-success">{{ $inventory->completed_orders ?? 0 }}</span>
                            </td>
                            <td>
                                <span class="badge badge-danger">{{ $inventory->cancelled_orders ?? 0 }}</span>
                            </td>
                            <td>{{ $inventory->location ?? 'Warehouse A' }}</td>
                            <td>{{ ($inventory->updated_at ?? now())->format('M d, Y') }}</td>
                            <td class="status-col">
                                <span class="status-label {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="actions-col text-center">
                                <div class="inventory-actions">
                                    <a href="{{ route('staff.inventory.show', $inventory->inventory_id ?? $inventory->id) }}" class="btn btn-sm btn-primary" title="View details">
                                        <i class="fi fi-rr-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="table-empty">No inventory items found.</td>
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
            const alertBanner = document.querySelector('.staff-inventory-alert');
            if (alertBanner) {
                setTimeout(function () {
                    alertBanner.classList.add('is-dismissing');
                    setTimeout(() => alertBanner.remove(), 600);
                }, 4000);
            }
        });
    </script>
@endpush