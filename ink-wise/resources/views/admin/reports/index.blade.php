@extends('layouts.admin')

@section('title', 'Reports & Analytics')

@push('styles')
	<link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
	<link rel="stylesheet" href="{{ asset('css/admin-css/reports.css') }}">
@endpush

@section('content')
@php
	$inventoryStats = [
		'totalSkus' => $materials->count(),
		'lowStock' => $materials->filter(fn ($m) => optional($m->inventory)->stock_level <= optional($m->inventory)->reorder_level && optional($m->inventory)->stock_level > 0)->count(),
		'outStock' => $materials->filter(fn ($m) => optional($m->inventory)->stock_level <= 0)->count(),
		'totalStock' => $materials->sum(fn ($m) => optional($m->inventory)->stock_level ?? 0),
	];

	$salesStats = [
		'orders' => $sales->count(),
		'revenue' => (float) $sales->sum('total_price'),
		'averageOrder' => $sales->count() ? (float) $sales->avg('total_price') : 0,
	];

	$reportPayload = [
		'inventory' => [
			'labels' => $materialLabels->values(),
			'stock' => $materialStockLevels->values(),
			'reorder' => $materialReorderLevels->values(),
		],
		'sales' => [
			'labels' => $monthlyLabels,
			'totals' => $monthlyTotals,
		],
		'summaries' => [
			'inventory' => $inventoryStats,
			'sales' => $salesStats,
		],
	];
@endphp

<main class="reports-shell admin-page-shell" id="adminReportsShell">
	<header class="page-header reports-page-header">
		<div>
			<h1 class="page-title">Reports &amp; Analytics</h1>
			<p class="page-subtitle">Monitor key performance metrics, inventory health, and monthly sales in one place.</p>
		</div>
		<div class="page-header__quick-actions">
			<button type="button" class="pill-link" data-report-action="refresh">
				<i class="fi fi-rr-rotate-right" aria-hidden="true"></i> Refresh data
			</button>
			<button type="button" class="btn btn-primary" data-report-action="export-all">
				<i class="fi fi-rr-file-spreadsheet" aria-hidden="true"></i> Export all
			</button>
		</div>
	</header>

	<section class="reports-summary-grid" aria-label="Performance highlights">
		<article class="reports-summary-card">
			<header>
				<span class="summary-label">Orders</span>
				<i class="fi fi-rr-shopping-cart" aria-hidden="true"></i>
			</header>
			<strong data-metric="orders-count">{{ number_format($salesStats['orders']) }}</strong>
			<p>Total orders captured this period.</p>
		</article>
		<article class="reports-summary-card">
			<header>
				<span class="summary-label">Revenue</span>
				<i class="fi fi-rr-chart-histogram" aria-hidden="true"></i>
			</header>
			<strong data-metric="revenue-total">₱{{ number_format($salesStats['revenue'], 2) }}</strong>
			<p>Gross sales across fulfilled orders.</p>
		</article>
		<article class="reports-summary-card">
			<header>
				<span class="summary-label">Avg. Order</span>
				<i class="fi fi-rr-circle-dollar" aria-hidden="true"></i>
			</header>
			<strong data-metric="average-order">₱{{ number_format($salesStats['averageOrder'], 2) }}</strong>
			<p>Average amount spent per order.</p>
		</article>
		<article class="reports-summary-card">
			<header>
				<span class="summary-label">Low / Out of Stock</span>
				<i class="fi fi-rr-boxes" aria-hidden="true"></i>
			</header>
			<strong data-metric="stock-status">{{ $inventoryStats['lowStock'] }} / {{ $inventoryStats['outStock'] }}</strong>
			<p>Products needing restock attention.</p>
		</article>
	</section>

	<section class="reports-panel" aria-label="Trends and charts">
		<nav class="reports-tabs" role="tablist">
			<button type="button" class="reports-tab is-active" role="tab" aria-selected="true" data-tab-target="sales-trend">Sales trend</button>
			<button type="button" class="reports-tab" role="tab" aria-selected="false" data-tab-target="inventory-trend">Inventory levels</button>
		</nav>
		<div class="reports-tab-panels">
			<article id="sales-trend" class="reports-panel__content is-visible" role="tabpanel">
				<header class="reports-panel__header">
					<div>
						<h2>Monthly sales</h2>
						<p>Track revenue movement across the past months.</p>
					</div>
					<div class="reports-toolbar">
						<select class="reports-select" data-report-filter="sales-range" aria-label="Filter sales range">
							<option value="6m" selected>Last 6 months</option>
							<option value="12m">Last 12 months</option>
							<option value="ytd">Year to date</option>
						</select>
						<button type="button" class="pill-link" data-export="sales" data-format="csv">
							<i class="fi fi-rr-download"></i> Export CSV
						</button>
						<button type="button" class="pill-link" data-print="sales">
							<i class="fi fi-rr-print"></i> Print
						</button>
					</div>
				</header>
				<canvas data-chart="sales"></canvas>
			</article>

			<article id="inventory-trend" class="reports-panel__content" role="tabpanel" hidden>
				<header class="reports-panel__header">
					<div>
						<h2>Inventory overview</h2>
						<p>Compare stock levels against reorder thresholds.</p>
					</div>
					<div class="reports-toolbar">
						<button type="button" class="pill-link" data-export="inventory" data-format="csv">
							<i class="fi fi-rr-download"></i> Export CSV
						</button>
						<button type="button" class="pill-link" data-print="inventory">
							<i class="fi fi-rr-print"></i> Print
						</button>
					</div>
				</header>
				<canvas data-chart="inventory"></canvas>
			</article>
		</div>
	</section>

	<section class="reports-detail-grid">
		<article class="reports-card" aria-label="Sales table">
			<header>
				<div>
					<h2>Recent sales</h2>
					<p>Line items captured from recent orders.</p>
				</div>
			</header>
			<div class="table-wrapper">
				<table class="reports-table" id="salesTable">
					<thead>
						<tr>
							<th scope="col">Order ID</th>
							<th scope="col">Customer</th>
							<th scope="col">Items</th>
							<th scope="col" class="text-center">Qty</th>
							<th scope="col" class="text-end">Total (PHP)</th>
							<th scope="col">Date</th>
						</tr>
					</thead>
					<tbody>
					@forelse($sales as $sale)
						@php
							$items = optional($sale->items)->pluck('name')->join(', ');
							$quantity = optional($sale->items)->sum('pivot.quantity') ?? 0;
						@endphp
						<tr>
							<td>{{ $sale->id }}</td>
							<td>{{ optional($sale->customer)->name ?? '—' }}</td>
							<td>{{ $items }}</td>
							<td class="text-center">{{ $quantity }}</td>
							<td class="text-end">{{ number_format($sale->total_price, 2) }}</td>
							<td>{{ optional($sale->created_at)->format('M d, Y') ?? '—' }}</td>
						</tr>
					@empty
						<tr>
							<td colspan="6" class="text-center">No sales records available.</td>
						</tr>
					@endforelse
					</tbody>
				</table>
			</div>
		</article>

		<article class="reports-card" aria-label="Inventory table">
			<header>
				<div>
					<h2>Inventory status</h2>
					<p>Monitor materials and highlight items nearing depletion.</p>
				</div>
			</header>
			<div class="table-wrapper">
				<table class="reports-table" id="inventoryTable">
					<thead>
						<tr>
							<th scope="col">Material</th>
							<th scope="col">Category</th>
							<th scope="col" class="text-center">Stock</th>
							<th scope="col" class="text-center">Reorder</th>
							<th scope="col">Status</th>
						</tr>
					</thead>
					<tbody>
					@forelse($materials as $material)
						@php
							$stockLevel = optional($material->inventory)->stock_level ?? 0;
							$reorderLevel = optional($material->inventory)->reorder_level ?? 0;
							$status = 'In stock';
							if ($stockLevel <= 0) {
								$status = 'Out of stock';
							} elseif ($stockLevel <= $reorderLevel) {
								$status = 'Low';
							}
						@endphp
						<tr data-stock-status="{{ \Illuminate\Support\Str::slug($status) }}">
							<td>{{ $material->material_name }}</td>
							<td>{{ $material->material_type }}</td>
							<td class="text-center">{{ $stockLevel }}</td>
							<td class="text-center">{{ $reorderLevel }}</td>
							<td>{{ $status }}</td>
						</tr>
					@empty
						<tr>
							<td colspan="5" class="text-center">No materials found.</td>
						</tr>
					@endforelse
					</tbody>
				</table>
			</div>
		</article>
	</section>
</main>
@endsection

@section('scripts')
	<script>
		window.__INKWISE_REPORTS__ = {!! json_encode($reportPayload) !!};
	</script>
	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" integrity="sha384-X8BaO5jM90oCbGyF/FOW+9CAdDbu1mz7wl53LmpM2bn4MdojPXZ61NlFLn2WPC6n" crossorigin="anonymous"></script>
	<script src="{{ asset('js/admin/reports.js') }}" defer></script>
@endsection
