@extends('layouts.staffapp')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@flaticon/flaticon-uicons/css/all/all.css">
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff-css/materials.css') }}">
    <style>
        .restock-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(26, 32, 44, 0.55);
            backdrop-filter: blur(2px);
            z-index: 1050;
        }

        .restock-modal.is-open {
            display: flex;
        }

        .restock-modal__dialog {
            width: min(420px, 92%);
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.25);
            padding: 1.75rem;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .restock-modal__header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
        }

        .restock-modal__header h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: #1a202c;
        }

        .restock-close-btn {
            border: none;
            background: transparent;
            color: #718096;
            font-size: 1.25rem;
            cursor: pointer;
        }

        .restock-modal__body label {
            display: block;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.35rem;
        }

        .restock-modal__body .form-control {
            width: 100%;
            border: 1px solid #cbd5e0;
            border-radius: 8px;
            padding: 0.65rem 0.75rem;
            font-size: 0.95rem;
            color: #2d3748;
        }

        .restock-modal__hint {
            font-size: 0.85rem;
            color: #4a5568;
        }

        .restock-modal__actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        .btn-ghost {
            background: transparent;
            border: 1px solid #cbd5e0;
            color: #4a5568;
        }
    </style>
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
                                    <button type="button"
                                            class="btn btn-sm btn-success btn-restock"
                                            data-action="{{ route('staff.materials.restock', $material->material_id) }}"
                                            data-name="{{ $material->material_name }}"
                                            data-unit="{{ $material->unit ?? 'units' }}"
                                            data-stock="{{ $stock }}"
                                            title="Restock material"
                                            aria-label="Restock {{ $material->material_name }}">
                                        <i class="fi fi-rr-plus-small"></i>
                                    </button>
                                    <a href="{{ route('staff.materials.edit', $material->material_id) }}" class="btn btn-sm btn-warning" title="Edit material">
                                        <i class="fi fi-rr-pencil"></i>
                                    </a>
                                    <form action="{{ route('staff.materials.destroy', $material->material_id) }}" method="POST" class="inline-form" onsubmit="return confirm('Archive this material? It will be moved to the archive and removed from active inventory.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Archive material">
                                            <i class="fi fi-rr-box"></i>
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

    <div id="staffRestockModal" class="restock-modal" aria-hidden="true">
        <div class="restock-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="staffRestockModalTitle">
            <div class="restock-modal__header">
                <h2 id="staffRestockModalTitle">Restock Material</h2>
                <button type="button" class="restock-close-btn" data-close-restock aria-label="Close restock dialog">&times;</button>
            </div>
            <div class="restock-modal__body">
                <p class="restock-modal__hint" data-restock-summary>Enter the quantity to add to inventory.</p>
                <form id="staffRestockForm" method="POST" action="#">
                    @csrf
                    <div class="form-group">
                        <label for="staffRestockQuantity">Quantity to add</label>
                        <input type="number" name="quantity" id="staffRestockQuantity" class="form-control" min="1" required>
                    </div>
                    <div class="restock-modal__actions">
                        <button type="button" class="btn btn-sm btn-ghost" data-close-restock>Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary">Update Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
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
    <script>
        (function () {
            const modal = document.getElementById('staffRestockModal');
            if (!modal) return;

            const form = document.getElementById('staffRestockForm');
            const quantityInput = document.getElementById('staffRestockQuantity');
            const summary = modal.querySelector('[data-restock-summary]');
            const title = document.getElementById('staffRestockModalTitle');
            const closeButtons = modal.querySelectorAll('[data-close-restock]');
            const restockButtons = document.querySelectorAll('.btn-restock');

            const closeModal = () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                if (form) {
                    form.reset();
                }
            };

            const openModal = (config) => {
                if (!form) return;
                form.action = config.action;
                title.textContent = `Restock ${config.name}`;
                summary.textContent = `Current stock: ${config.stock} ${config.unit}. Enter the quantity to add.`;
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                requestAnimationFrame(() => {
                    quantityInput?.focus();
                });
            };

            restockButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    openModal({
                        action: button.dataset.action,
                        name: button.dataset.name,
                        unit: button.dataset.unit || 'units',
                        stock: button.dataset.stock || '0',
                    });
                });
            });

            closeButtons.forEach((btn) => btn.addEventListener('click', closeModal));

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                    closeModal();
                }
            });
        })();
    </script>
@endpush
