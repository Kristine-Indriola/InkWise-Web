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

        .analytics-grid {
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        }

        .analytics-card {
            background: var(--admin-surface);
            border-radius: 18px;
            border: 1px solid rgba(148, 185, 255, 0.18);
            box-shadow: var(--admin-shadow-soft);
            padding: 24px;
            display: grid;
            gap: 16px;
            position: relative;
            overflow: hidden;
        }

        .analytics-card__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .analytics-card__header h2 {
            margin: 0;
            font-size: 1.18rem;
        }

        .analytics-card__tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.78rem;
            padding: 0.35rem 0.85rem;
            border-radius: 999px;
            background: rgba(90, 141, 224, 0.12);
            color: var(--admin-accent-strong);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .analytics-card p {
            margin: 0;
            color: var(--admin-text-secondary);
            line-height: 1.65;
        }

        .analytics-card__list {
            margin: 0;
            padding-left: 1.2rem;
            display: grid;
            gap: 6px;
            color: var(--admin-text-secondary);
        }

        .insights-grid {
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }

        .insight-stat {
            display: grid;
            gap: 8px;
            padding: 16px;
            border-radius: 16px;
            background: rgba(148, 185, 255, 0.08);
            border: 1px solid rgba(148, 185, 255, 0.12);
        }

        .insight-stat--primary {
            background: linear-gradient(135deg, rgba(58, 133, 244, 0.18), rgba(105, 231, 206, 0.18));
            border-color: rgba(58, 133, 244, 0.22);
        }

        .insight-label {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--admin-text-secondary);
            font-weight: 700;
        }

        .insight-value {
            font-size: 1.65rem;
            font-weight: 700;
            color: var(--admin-text-primary);
        }

        .insight-delta {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.86rem;
            font-weight: 600;
            padding: 0.35rem 0.65rem;
            border-radius: 999px;
            background: rgba(148, 185, 255, 0.16);
            color: var(--admin-text-secondary);
            width: fit-content;
        }

        .insight-delta--up {
            background: rgba(56, 193, 114, 0.12);
            color: #25a86b;
        }

        .insight-delta--down {
            background: rgba(255, 107, 107, 0.12);
            color: #ff6b6b;
        }

        .insight-delta--flat {
            background: rgba(161, 174, 192, 0.16);
            color: var(--admin-text-secondary);
        }

        .insight-delta__icon {
            font-size: 0.9rem;
            line-height: 1;
        }

        .insight-footnote {
            margin: 0;
            font-size: 0.76rem;
            color: var(--admin-text-secondary);
        }

        .insight-meta-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .insight-meta-card {
            padding: 18px;
            border-radius: 16px;
            background: rgba(148, 185, 255, 0.08);
            border: 1px dashed rgba(148, 185, 255, 0.24);
            display: grid;
            gap: 6px;
        }

        .insight-meta-label {
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--admin-text-secondary);
            font-weight: 700;
        }

        .insight-meta-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--admin-text-primary);
        }

        .insight-meta-caption {
            font-size: 0.82rem;
            color: var(--admin-text-secondary);
        }

        .analytics-card--design {
            grid-template-rows: auto 1fr;
        }

        .design-highlight {
            display: grid;
            gap: 16px;
            grid-template-columns: minmax(120px, 160px) 1fr;
            align-items: stretch;
        }

        .design-highlight__image {
            border-radius: 14px;
            background: rgba(148, 185, 255, 0.16);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .design-highlight__image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .design-highlight__placeholder {
            text-align: center;
            font-size: 0.82rem;
            color: var(--admin-text-secondary);
            padding: 18px;
        }

        .design-highlight__meta {
            display: grid;
            gap: 6px;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            margin: 0;
        }

        .design-highlight__meta dt {
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--admin-text-secondary);
            font-weight: 700;
        }

        .design-highlight__meta dd {
            margin: 0 0 12px;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--admin-text-primary);
        }

        .design-highlight__cta {
            margin-top: auto;
            width: fit-content;
        }

        .analytics-card__empty {
            margin: 0;
            color: var(--admin-text-secondary);
            font-size: 0.88rem;
        }

        @media (max-width: 640px) {
            .design-highlight {
                grid-template-columns: 1fr;
            }
        }
        </style>
@endpush

@section('content')
@php
    $metrics = $dashboardMetrics ?? [
    'ordersThisWeek' => 0,
    'revenueThisWeek' => 0,
    'averageOrderValue' => 0,
    'pendingOrders' => 0,
    'lowStock' => 0,
    'outOfStock' => 0,
    'totalStockUnits' => 0,
    'totalSkus' => 0,
    'ordersWoW' => ['change' => 0, 'percent' => 0, 'direction' => 'flat'],
    'revenueWoW' => ['change' => 0, 'percent' => 0, 'direction' => 'flat'],
    'inventoryRiskPercent' => 0,
    'stockCoverageDays' => null,
    ];
    $popular = $popularDesign ?? null;
@endphp
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

    </header>

    <section class="analytics-grid" aria-label="Sales and inventory analytics">
        <article class="analytics-card">
            <header class="analytics-card__header">
                <h2>Sales &amp; Inventory Insights</h2>
                <span class="analytics-card__tag">This Week</span>
            </header>
            @php
                $ordersDelta = $metrics['ordersWoW'] ?? ['change' => 0, 'percent' => 0, 'direction' => 'flat'];
                $revenueDelta = $metrics['revenueWoW'] ?? ['change' => 0, 'percent' => 0, 'direction' => 'flat'];
                $directionIcons = ['up' => '▲', 'down' => '▼', 'flat' => '⭘'];
                $deltaClasses = [
                    'up' => 'insight-delta insight-delta--up',
                    'down' => 'insight-delta insight-delta--down',
                    'flat' => 'insight-delta insight-delta--flat',
                ];
                $formatChange = function ($value, $decimals = 0) {
                    $numeric = (float) $value;
                    $formatted = number_format(abs($numeric), $decimals);
                    if ($numeric > 0) {
                        return '+' . $formatted;
                    }
                    if ($numeric < 0) {
                        return '-' . $formatted;
                    }
                    return '0';
                };
            @endphp

            <div class="insights-grid" role="list">
                <div class="insight-stat insight-stat--primary" role="listitem">
                    <span class="insight-label">Orders</span>
                    <div class="insight-value">{{ number_format($metrics['ordersThisWeek']) }}</div>
                    <span class="{{ $deltaClasses[$ordersDelta['direction']] ?? 'insight-delta' }}" aria-label="{{ $formatChange($ordersDelta['percent'], 1) }} percent versus last week">
                        <span class="insight-delta__icon" aria-hidden="true">{{ $directionIcons[$ordersDelta['direction']] ?? '⭘' }}</span>
                        <span>{{ $formatChange($ordersDelta['change']) }} ({{ $formatChange($ordersDelta['percent'], 1) }}%)</span>
                    </span>
                    <p class="insight-footnote">vs last week</p>
                </div>
                <div class="insight-stat insight-stat--primary" role="listitem">
                    <span class="insight-label">Revenue</span>
                    <div class="insight-value">₱{{ number_format($metrics['revenueThisWeek'], 2) }}</div>
                    <span class="{{ $deltaClasses[$revenueDelta['direction']] ?? 'insight-delta' }}" aria-label="{{ $formatChange($revenueDelta['percent'], 1) }} percent versus last week">
                        <span class="insight-delta__icon" aria-hidden="true">{{ $directionIcons[$revenueDelta['direction']] ?? '⭘' }}</span>
                        <span>{{ $formatChange($revenueDelta['change'], 2) }} ({{ $formatChange($revenueDelta['percent'], 1) }}%)</span>
                    </span>
                    <p class="insight-footnote">vs last week</p>
                </div>
                <div class="insight-stat" role="listitem">
                    <span class="insight-label">Avg. Order Value</span>
                    <div class="insight-value">₱{{ number_format($metrics['averageOrderValue'], 2) }}</div>
                    <p class="insight-footnote">Average basket size for the current week.</p>
                </div>
                <div class="insight-stat" role="listitem">
                    <span class="insight-label">Pending Orders</span>
                    <div class="insight-value">{{ number_format($metrics['pendingOrders']) }}</div>
                    <p class="insight-footnote">Queued for fulfillment or follow-up.</p>
                </div>
            </div>

            <div class="insight-meta-grid" role="list">
                <div class="insight-meta-card" role="listitem">
                    <span class="insight-meta-label">Inventory Risk Exposure</span>
                    <span class="insight-meta-value">{{ number_format((float) $metrics['inventoryRiskPercent'], 1) }}%</span>
                    <span class="insight-meta-caption">{{ number_format($metrics['lowStock']) }} low stock / {{ number_format($metrics['outOfStock']) }} out of stock</span>
                </div>
                <div class="insight-meta-card" role="listitem">
                    <span class="insight-meta-label">Stock Coverage</span>
                    <span class="insight-meta-value">
                        @if(!is_null($metrics['stockCoverageDays']))
                            {{ number_format($metrics['stockCoverageDays'], 1) }} days
                        @else
                            —
                        @endif
                    </span>
                    <span class="insight-meta-caption">{{ number_format($metrics['totalStockUnits']) }} units on hand across {{ number_format($metrics['totalSkus']) }} SKUs</span>
                </div>
            </div>
        </article>

        <article class="analytics-card analytics-card--design" aria-label="Popular design highlight">
            <header class="analytics-card__header">
                <h2>Popular Design</h2>
                @if($popular)
                    <span class="analytics-card__tag">{{ number_format($popular['orders']) }} orders</span>
                @endif
            </header>

            @if($popular)
                <div class="design-highlight">
                    <div class="design-highlight__image">
                        @if($popular['image'])
                            <img src="{{ $popular['image'] }}" alt="{{ $popular['name'] }} preview">
                        @else
                            <div class="design-highlight__placeholder">No preview available</div>
                        @endif
                    </div>
                    <div>
                        <dl class="design-highlight__meta">
                            <div>
                                <dt>Design</dt>
                                <dd>{{ $popular['name'] }}</dd>
                            </div>
                            <div>
                                <dt>Units Sold</dt>
                                <dd>{{ number_format($popular['quantity']) }}</dd>
                            </div>
                            <div>
                                <dt>Orders</dt>
                                <dd>{{ number_format($popular['orders']) }}</dd>
                            </div>
                        </dl>
                        @if(!empty($popular['product']))
                            <a href="{{ route('admin.products.edit', ['id' => $popular['product']->id]) }}" class="pill-link design-highlight__cta">
                                Manage design
                            </a>
                        @endif
                    </div>
                </div>
            @else
                <p class="analytics-card__empty">No design trends yet. Once orders flow in, the top-performing layout will surface here.</p>
            @endif
        </article>
    </section>

    <section class="summary-grid" aria-label="Key performance highlights">
        <div class="summary-card">
            <div class="summary-card-header">
                <span class="summary-card-label">Orders</span>
                <span class="summary-card-chip accent">This Week</span>
            </div>
            <div class="summary-card-body">
                <span class="summary-card-value">{{ number_format($metrics['ordersThisWeek']) }}</span>
                <span class="summary-card-icon" aria-hidden="true">🛒</span>
            </div>
            <span class="summary-card-meta">Orders processed</span>
        </div>
        <div class="summary-card">
            <div class="summary-card-header">
                <span class="summary-card-label">Revenue</span>
                <span class="summary-card-chip accent">This Week</span>
            </div>
            <div class="summary-card-body">
                <span class="summary-card-value">₱{{ number_format($metrics['revenueThisWeek'], 2) }}</span>
                <span class="summary-card-icon" aria-hidden="true">📈</span>
            </div>
            <span class="summary-card-meta">Gross sales generated</span>
        </div>
        <div class="summary-card">
            <div class="summary-card-header">
                <span class="summary-card-label">Pending</span>
                <span class="summary-card-chip warning">Action Needed</span>
            </div>
            <div class="summary-card-body">
                <span class="summary-card-value">{{ number_format($metrics['pendingOrders']) }}</span>
                <span class="summary-card-icon" aria-hidden="true">⏳</span>
            </div>
            <span class="summary-card-meta">Awaiting fulfillment</span>
        </div>
        <div class="summary-card">
            <div class="summary-card-header">
                <span class="summary-card-label">Avg. Order</span>
                <span class="summary-card-chip accent">This Week</span>
            </div>
            <div class="summary-card-body">
                <span class="summary-card-value">₱{{ number_format($metrics['averageOrderValue'], 2) }}</span>
                <span class="summary-card-icon" aria-hidden="true">💳</span>
            </div>
            <span class="summary-card-meta">Average basket value</span>
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
