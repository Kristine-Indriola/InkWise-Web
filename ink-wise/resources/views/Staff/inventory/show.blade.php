@extends('layouts.staffapp')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@flaticon/flaticon-uicons/css/all/all.css">
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff-css/inventory.css') }}">
@endpush

@section('title', 'Sample Inventory Details')

@section('content')
<main class="materials-page admin-page-shell staff-inventory-show-page" role="main">
    <header class="page-header">
        <div>
            <h1 class="page-title">Sample Inventory Details</h1>
            <p class="page-subtitle">Sample details for invitation/giveaway inventory items.</p>
        </div>
        <div class="page-header__quick-actions">
            <a href="{{ route('staff.inventory.index') }}" class="pill-link is-active" aria-label="Back to inventory list">
                <i class="fi fi-rr-arrow-left"></i>&nbsp;Back to List
            </a>
        </div>
    </header>

    @if(session('success'))
        <div class="alert staff-inventory-alert" role="alert" aria-live="polite">
            ✅ {{ session('success') }}
        </div>
    @endif

    <div class="alert alert-info" role="alert">
        <i class="fi fi-rr-info"></i>
        <span>This is sample inventory data for invitations and giveaways.</span>
    </div>

    <section class="inventory-details" aria-label="Inventory details">
        <div class="details-wrapper">
            <div class="details-card">
                <h3 class="details-title">Item Information</h3>
                <div class="details-grid">
                    <div class="detail-item">
                        <span class="detail-label">Inventory ID</span>
                        <span class="detail-value">{{ $inventory->inventory_id ?? $inventory->id }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Material</span>
                        <span class="detail-value">{{ $inventory->material->material_name ?? 'Unknown' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Stock Level</span>
                        <span class="detail-value stock-level {{ ($inventory->stock_level ?? 0) <= ($inventory->reorder_level ?? 0) ? 'low' : 'ok' }}">
                            {{ $inventory->stock_level ?? 0 }}
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Reorder Level</span>
                        <span class="detail-value">{{ $inventory->reorder_level ?? 0 }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Pending Orders</span>
                        <span class="detail-value">{{ $inventory->pending_orders ?? 0 }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Completed Orders</span>
                        <span class="detail-value">{{ $inventory->completed_orders ?? 0 }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Cancelled Orders</span>
                        <span class="detail-value">{{ $inventory->cancelled_orders ?? 0 }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Location</span>
                        <span class="detail-value">{{ $inventory->location ?? 'Warehouse A' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Last Updated</span>
                        <span class="detail-value">{{ $inventory->updated_at->format('M d, Y H:i') }}</span>
                    </div>
                    @if($inventory->remarks)
                        <div class="detail-item full-width">
                            <span class="detail-label">Remarks</span>
                            <span class="detail-value">{{ $inventory->remarks }}</span>
                        </div>
                    @endif
                </div>
            </div>
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