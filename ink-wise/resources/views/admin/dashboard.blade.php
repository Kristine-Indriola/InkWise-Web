@extends('layouts.admin')

@section('title', 'Dashboard')


@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-css/dashboard.css') }}">
        <style>
                .page-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: flex-end;
                        gap: 1rem;
                        flex-wrap: wrap;
                }

                .page-action-button {
                        display: inline-flex;
                        align-items: center;
                        gap: 0.5rem;
                        padding: 0.75rem 1.5rem;
                        border-radius: 999px;
                        font-weight: 600;
                        text-decoration: none;
                        background: linear-gradient(120deg, #6a2ebc, #3cd5c8);
                        color: #fff;
                        box-shadow: 0 10px 20px -12px rgba(106, 46, 188, 0.75);
                        transition: transform 0.2s ease, box-shadow 0.2s ease;
                }

                .page-action-button:hover {
                        transform: translateY(-1px);
                        box-shadow: 0 14px 28px -14px rgba(106, 46, 188, 0.85);
                        color: #fff;
                }

                .page-action-button i {
                        font-size: 1rem;
                }
        </style>
@endpush

@section('content')
<main class="admin-page-shell dashboard-page" role="main">
    {{-- ✅ Greeting Message --}}
    @if(session('success'))
        <div id="greetingMessage" class="dashboard-alert" role="alert" aria-live="polite">
            {{ session('success') }}
        </div>
    @endif

    <header class="page-header">
        <div>
            <h1 class="page-title">Dashboard Overview</h1>
            <p class="page-subtitle">Quick look at orders and stock health.</p>
        </div>
        <a href="{{ route('admin.users.passwords.index') }}" class="page-action-button" title="Open password reset console">
            <i class="fa-solid fa-gear" aria-hidden="true"></i>
            <span>Password resets</span>
        </a>
    </header>

    <section class="summary-grid" aria-label="Key performance highlights">
        <div class="summary-card">
            <div class="summary-card-header">
                <span class="summary-card-label">Orders</span>
                <span class="summary-card-chip accent">This Week</span>
            </div>
            <div class="summary-card-body">
                <span class="summary-card-value">20</span>
                <span class="summary-card-icon" aria-hidden="true">🛒</span>
            </div>
            <span class="summary-card-meta">Orders processed</span>
        </div>
        <div class="summary-card">
            <div class="summary-card-header">
                <span class="summary-card-label">Pending</span>
                <span class="summary-card-chip warning">Action Needed</span>
            </div>
            <div class="summary-card-body">
                <span class="summary-card-value">35</span>
                <span class="summary-card-icon" aria-hidden="true">⏳</span>
            </div>
            <span class="summary-card-meta">Awaiting fulfillment</span>
        </div>
        <div class="summary-card">
            <div class="summary-card-header">
                <span class="summary-card-label">Rating</span>
                <span class="summary-card-chip accent">Customer</span>
            </div>
            <div class="summary-card-body">
                <span class="summary-card-value">4.0</span>
                <span class="summary-card-icon" aria-hidden="true">⭐</span>
            </div>
            <span class="summary-card-meta">Average feedback</span>
        </div>
    </section>


    <section class="dashboard-stock" aria-label="Inventory snapshot">
                <header class="section-header">
                        <div>
                                <h2 class="section-title">Stock Levels</h2>
                                <p class="section-subtitle">Click anywhere on the table to jump to full materials management.</p>
                        </div>
                        <a href="{{ route('admin.materials.index') }}" class="pill-link" aria-label="Open full materials dashboard">View Materials</a>
                </header>

        <div class="table-wrapper">
            <table class="table clickable-table" onclick="window.location='{{ route('admin.materials.index') }}'" role="grid">
                <thead>
                    <tr>
                        <th scope="col">Material</th>
                        <th scope="col">Type</th>
                        <th scope="col">Unit</th>
                        <th scope="col">Stock</th>
                        <th scope="col" class="status-col text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($materials as $material)
                        @php
                            $stock = $material->inventory->stock_level ?? 0;
                            $reorder = $material->inventory->reorder_level ?? 0;
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
                            <td class="fw-bold">{{ $material->material_name }}</td>
                            <td>{{ $material->material_type }}</td>
                            <td>{{ $material->unit }}</td>
                            <td>
                                <span class="badge {{ $badgeClass }}">{{ $stock }}</span>
                            </td>
                            <td class="text-center">
                                <span class="status-label {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No materials available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <script>
        // Auto-hide greeting after 4 seconds
        setTimeout(() => {
            const greeting = document.getElementById('greetingMessage');
            if (greeting) {
                greeting.style.opacity = '0';
                setTimeout(() => greeting.remove(), 1000);
            }
        }, 4000);
    </script>
</main>
@endsection
