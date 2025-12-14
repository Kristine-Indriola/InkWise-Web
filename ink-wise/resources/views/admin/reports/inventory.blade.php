@extends('layouts.admin')

@section('title', 'Inventory Analytics')

@push('styles')
	<link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
	<link rel="stylesheet" href="{{ asset('css/admin-css/reports.css') }}">
	<style>
		.reports-table tbody tr.needs-attention {
			background-color: #fff3cd !important;
			border-left: 4px solid #ffc107;
		}
		.reports-table tbody tr.needs-attention:hover {
			background-color: #ffeaa7 !important;
		}
		.reports-table tbody tr.needs-attention td {
			font-weight: 500;
		}
		.reports-callout-list li[data-status="out-of-stock"] {
			background-color: #f8d7da;
			border-left: 4px solid #dc3545;
			padding: 1rem;
			margin-bottom: 0.5rem;
			border-radius: 4px;
		}
		.reports-callout-list li[data-status="low"] {
			background-color: #fff3cd;
			border-left: 4px solid #ffc107;
			padding: 1rem;
			margin-bottom: 0.5rem;
			border-radius: 4px;
		}
		.reports-callout-list li[data-status="out-of-stock"] .callout-title {
			color: #721c24;
			font-weight: 600;
		}
		.reports-callout-list li[data-status="low"] .callout-title {
			color: #856404;
			font-weight: 600;
		}

		/* Report Header Styles */
		.report-header-section {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: white;
			padding: 2rem;
			margin-bottom: 2rem;
			border-radius: 8px;
		}
		.report-header-content {
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-wrap: wrap;
			gap: 2rem;
		}
		.company-name {
			font-size: 2.5rem;
			font-weight: 700;
			margin: 0;
			color: white;
		}
		.report-title {
			font-size: 1.5rem;
			font-weight: 400;
			margin: 0.5rem 0 0 0;
			opacity: 0.9;
		}
		.report-meta-info {
			display: flex;
			flex-direction: column;
			gap: 0.5rem;
		}
		.meta-item {
			font-size: 0.9rem;
		}
		.meta-item strong {
			display: inline-block;
			min-width: 120px;
		}

		/* Value Summary Styles */
		.value-summary-section {
			margin-bottom: 2rem;
		}
		.value-card {
			border: 2px solid #e9ecef;
		}
		.value-card strong {
			font-size: 1.5rem;
			color: #28a745;
		}

		/* Analysis Section Styles */
		.analysis-section {
			margin-bottom: 2rem;
		}
		.analysis-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
			gap: 1.5rem;
			margin-top: 1rem;
		}
		.analysis-card {
			background: white;
			border: 1px solid #e9ecef;
			border-radius: 8px;
			padding: 1.5rem;
			box-shadow: 0 2px 4px rgba(0,0,0,0.1);
		}
		.analysis-card h3 {
			margin: 0 0 0.5rem 0;
			font-size: 1.1rem;
			color: #495057;
		}
		.analysis-count {
			font-size: 1.8rem;
			font-weight: 700;
			color: #007bff;
			margin: 0.5rem 0;
		}
		.analysis-description {
			color: #6c757d;
			font-size: 0.9rem;
			margin-bottom: 1rem;
		}
		.analysis-list {
			list-style: none;
			padding: 0;
			margin: 0;
		}
		.analysis-list li {
			padding: 0.25rem 0;
		 border-bottom: 1px solid #f8f9fa;
			font-size: 0.85rem;
			color: #495057;
		}
		.analysis-list li:last-child {
			border-bottom: none;
		}
		.alert-card {
			border-color: #ffc107;
			background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
		}
		.alert-card h3 {
			color: #856404;
		}
		.analysis-list li.critical {
			color: #dc3545;
			font-weight: 600;
		}
		.analysis-list li.warning {
			color: #ffc107;
			font-weight: 500;
		}

		/* Filter Bar Styles */
		.reports-filter-bar {
			background: #f8f9fa;
			border: 1px solid #e9ecef;
			border-radius: 8px;
			padding: 1.5rem;
			margin-bottom: 2rem;
		}
		.reports-filter-form {
			display: flex;
			align-items: end;
			gap: 1rem;
			flex-wrap: wrap;
		}
		.reports-filter-field {
			display: flex;
			flex-direction: column;
			min-width: 150px;
		}
		.reports-filter-field label {
			font-weight: 600;
			font-size: 0.875rem;
			margin-bottom: 0.5rem;
			color: #495057;
		}
		.reports-filter-field input {
			padding: 0.5rem;
			border: 1px solid #ced4da;
			border-radius: 4px;
			font-size: 0.875rem;
		}
		.reports-filter-field input:focus {
			outline: none;
			border-color: #007bff;
			box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
		}
		.reports-filter-actions {
			display: flex;
			gap: 0.5rem;
			align-items: center;
		}

		/* Enhanced Table Styles */
		.reports-table th {
			background-color: #f8f9fa;
			font-weight: 600;
			font-size: 0.85rem;
			padding: 0.75rem 0.5rem;
		}
		.reports-table td {
			padding: 0.75rem 0.5rem;
			font-size: 0.85rem;
		}
		.reports-table .text-center {
			text-align: center;
		}
		.reports-table .text-end {
			text-align: right;
		}
	</style>
@endpush

@section('content')
@php
	$currentFilters = $filters ?? ['startDate' => null, 'endDate' => null];
	$reportDate = now()->format('F j, Y');
	$reportPeriod = $currentFilters['startDate'] && $currentFilters['endDate']
		? \Carbon\Carbon::parse($currentFilters['startDate'])->format('M j') . ' - ' . \Carbon\Carbon::parse($currentFilters['endDate'])->format('M j, Y')
		: 'All Time';

	$materialCollection = $materials instanceof \Illuminate\Support\Collection ? $materials : collect($materials);

	$evaluateMaterial = function ($material) use ($currentFilters) {
		$stockLevel = optional($material->inventory)->stock_level
			?? $material->stock_qty
			?? 0;
		$reorderLevel = optional($material->inventory)->reorder_level
			?? $material->reorder_point
			?? 0;
		$unitCost = $material->unit_cost ?? 0;
		$lastRestock = optional($material->inventory)->last_restock_date ?? null;
		$stockValue = $stockLevel * $unitCost;

		// Calculate stock movements for the period
		$movements = $material->stockMovements ?? collect();
		$stockIn = $movements->where('movement_type', 'restock')->sum('quantity');
		$stockOut = abs($movements->whereIn('movement_type', ['used', 'issued', 'sold'])->sum('quantity'));
		$adjustments = $movements->where('movement_type', 'adjustment')->sum('quantity');

		// Calculate beginning stock (current stock - movements in period)
		$beginningStock = $stockLevel - $stockIn + $stockOut - $adjustments;

		$status = 'In stock';
		$statusKey = 'in-stock';
		$action = 'Monitor usage each week.';

		if ($stockLevel <= 0) {
			$status = 'Out of stock';
			$statusKey = 'out-of-stock';
			$action = 'Order replacement immediately.';
		} elseif ($stockLevel <= $reorderLevel) {
			$status = 'Low';
			$statusKey = 'low';
			$action = 'Schedule a reorder within 3 days.';
		}

		$coveragePercent = null;
		$coverageLabel = 'No target set';
		if ($reorderLevel > 0) {
			$ratio = $stockLevel / max(1, $reorderLevel);
			$coveragePercent = (int) round(min(300, max(0, $ratio * 100)));
			if ($stockLevel <= 0) {
				$coverageLabel = 'No coverage';
			} elseif ($ratio < 1) {
				$coverageLabel = 'Below target';
			} elseif ($ratio < 1.5) {
				$coverageLabel = 'Meets target';
			} else {
				$coverageLabel = 'Healthy buffer';
			}
		}

		$priorityKey = match ($statusKey) {
			'out-of-stock' => 0,
			'low' => 1,
			default => 2,
		};

		// Determine movement category for analysis
		$totalMovement = $stockIn + $stockOut;
		$movementCategory = 'normal';
		if ($totalMovement > 50) {
			$movementCategory = 'fast-moving';
		} elseif ($totalMovement < 5) {
			$movementCategory = 'slow-moving';
		}
		if ($stockLevel > $reorderLevel * 2) {
			$movementCategory = 'overstocked';
		}

		return [
			'model' => $material,
			'stock' => $stockLevel,
			'reorder' => $reorderLevel,
			'status' => $status,
			'status_key' => $statusKey,
			'action' => $action,
			'coverage_percent' => $coveragePercent,
			'coverage_label' => $coverageLabel,
			'priority_key' => $priorityKey,
			'stock_value' => $stockValue,
			'last_restock' => $lastRestock,
			'beginning_stock' => max(0, $beginningStock),
			'stock_in' => $stockIn,
			'stock_out' => $stockOut,
			'adjustments' => $adjustments,
			'ending_stock' => $stockLevel,
			'movement_category' => $movementCategory,
		];
	};

	$materialSnapshots = $materialCollection->map($evaluateMaterial);

	$statusCounts = [
		'in-stock' => 0,
		'low' => 0,
		'out-of-stock' => 0,
	];

	foreach ($materialSnapshots as $snapshot) {
		$statusCounts[$snapshot['status_key']] = ($statusCounts[$snapshot['status_key']] ?? 0) + 1;
	}

	$hotList = $materialSnapshots
		->filter(fn ($snapshot) => in_array($snapshot['status_key'], ['low', 'out-of-stock'], true))
		->sortBy(fn ($snapshot) => [$snapshot['priority_key'], $snapshot['stock']])
		->values();

	$reorderQueue = $hotList->take(8);

	$totalStockValue = $materialSnapshots->sum('stock_value');

	// Analysis/Insights calculations
	$fastMovingItems = $materialSnapshots->where('movement_category', 'fast-moving');
	$slowMovingItems = $materialSnapshots->where('movement_category', 'slow-moving');
	$overstockedItems = $materialSnapshots->where('movement_category', 'overstocked');
	$outOfStockItems = $materialSnapshots->where('status_key', 'out-of-stock');
	$lowStockItems = $materialSnapshots->where('status_key', 'low');

	$reportPayload = [
		'inventory' => [
			'labels' => $materialLabels->values(),
			'stock' => $materialStockLevels->values(),
			'reorder' => $materialReorderLevels->values(),
		],
		'statusBreakdown' => $statusCounts,
		'totalTracked' => $materialSnapshots->count(),
	];
@endphp

<main class="reports-shell admin-page-shell" id="adminInventoryReportsShell">
	<!-- Report Header -->
	<section class="report-header-section" aria-label="Report header">
		<div class="report-header-content">
			<div class="report-company-info">
				<h1 class="company-name">InkWise</h1>
				<h2 class="report-title">Inventory Summary Report</h2>
			</div>
			<div class="report-meta-info">
				<div class="meta-item">
					<strong>Date Generated:</strong> {{ $reportDate }}
				</div>
				<div class="meta-item">
					<strong>Reporting Period:</strong> {{ $reportPeriod }}
				</div>
				<div class="meta-item">
					<strong>Prepared by:</strong> System Administrator
				</div>
				<div class="meta-item">
					<strong>Total Items:</strong> {{ number_format($materialSnapshots->count()) }}
				</div>
			</div>
		</div>
	</section>

	<header class="page-header reports-page-header">
		<div>
			<h1 class="page-title">Inventory analytics</h1>
			<p class="page-subtitle">Assess stock coverage, reorder priorities, and warehouse health.</p>
		</div>
		<div class="page-header__quick-actions">
			<a href="{{ route('admin.reports.sales') }}" class="pill-link">
				<i class="fi fi-rr-chart-histogram" aria-hidden="true"></i> Sales analytics
			</a>
			<a href="{{ route('admin.reports.pickup-calendar') }}" class="pill-link">
				<i class="fi fi-rr-calendar" aria-hidden="true"></i> Pickup calendar
			</a>
			<button type="button" class="pill-link" data-report-action="refresh">
				<i class="fi fi-rr-rotate-right" aria-hidden="true"></i> Refresh data
			</button>
			<button type="button" class="btn btn-primary" data-report-action="export-inventory">
				<i class="fi fi-rr-file-spreadsheet" aria-hidden="true"></i> Export inventory
			</button>
		</div>
	</header>

	<section class="reports-filter-bar" aria-label="Filter inventory by date range">
		<form method="GET" class="reports-filter-form">
			<div class="reports-filter-field">
				<label for="filterStartDate">Start date</label>
				<input
					type="date"
					id="filterStartDate"
					name="start_date"
					value="{{ $currentFilters['startDate'] ?? '' }}"
					max="{{ now()->format('Y-m-d') }}"
				>
			</div>
			<div class="reports-filter-field">
				<label for="filterEndDate">End date</label>
				<input
					type="date"
					id="filterEndDate"
					name="end_date"
					value="{{ $currentFilters['endDate'] ?? '' }}"
					max="{{ now()->format('Y-m-d') }}"
				>
			</div>
			<div class="reports-filter-actions">
				<button type="submit" class="btn btn-primary">
					<i class="fi fi-rr-filter" aria-hidden="true"></i> Apply
				</button>
				@if ($currentFilters['startDate'] || $currentFilters['endDate'])
					<a href="{{ route('admin.reports.inventory') }}" class="pill-link">
						<i class="fi fi-rr-refresh" aria-hidden="true"></i> Reset
					</a>
				@endif
			</div>
		</form>
	</section>

	<section class="reports-summary-grid" aria-label="Inventory highlights">
		<article class="reports-summary-card">
			<header>
				<span class="summary-label">Total SKUs</span>
				<i class="fi fi-rr-box" aria-hidden="true"></i>
			</header>
			<strong data-metric="total-skus">{{ number_format($inventoryStats['totalSkus'] ?? $reportPayload['totalTracked'] ?? 0) }}</strong>
			<p>Unique materials currently tracked.</p>
		</article>
		<article class="reports-summary-card">
			<header>
				<span class="summary-label">Low stock</span>
				<i class="fi fi-rr-triangle-warning" aria-hidden="true"></i>
			</header>
			<strong data-metric="low-stock">{{ number_format($inventoryStats['lowStock'] ?? ($reportPayload['statusBreakdown']['low'] ?? 0)) }}</strong>
			<p>Materials at or below their reorder point.</p>
		</article>
		<article class="reports-summary-card">
			<header>
				<span class="summary-label">Out of stock</span>
				<i class="fi fi-rr-empty-set" aria-hidden="true"></i>
			</header>
			<strong data-metric="out-stock">{{ number_format($inventoryStats['outStock'] ?? ($reportPayload['statusBreakdown']['out-of-stock'] ?? 0)) }}</strong>
			<p>Materials with zero available quantity.</p>
		</article>
		<article class="reports-summary-card">
			<header>
				<span class="summary-label">Total units</span>
				<i class="fi fi-rr-stats" aria-hidden="true"></i>
			</header>
			<strong data-metric="total-stock">{{ number_format($inventoryStats['totalStock'] ?? 0) }}</strong>
			<p>Total on-hand units across all SKUs.</p>
		</article>
		<article class="reports-summary-card">
			<header>
				<span class="summary-label">Stock Value</span>
				<i class="fi fi-rr-money-bill-wave" aria-hidden="true"></i>
			</header>
			<strong>₱{{ number_format($totalStockValue, 2) }}</strong>
			<p>Overall value of your current inventory.</p>
		</article>
	</section>

	<!-- Value Summary Section -->
	<section class="reports-summary-grid value-summary-section" aria-label="Inventory value summary">
		<article class="reports-summary-card value-card">
			<header>
				<span class="summary-label">Total Inventory Value</span>
				<i class="fi fi-rr-money-bill-wave" aria-hidden="true"></i>
			</header>
			<strong>₱{{ number_format($totalStockValue, 2) }}</strong>
			<p>Overall worth of current inventory</p>
		</article>
		<article class="reports-summary-card value-card">
			<header>
				<span class="summary-label">Average Item Value</span>
				<i class="fi fi-rr-calculator" aria-hidden="true"></i>
			</header>
			<strong>₱{{ $materialSnapshots->count() > 0 ? number_format($totalStockValue / $materialSnapshots->count(), 2) : '0.00' }}</strong>
			<p>Average value per inventory item</p>
		</article>
		<article class="reports-summary-card value-card">
			<header>
				<span class="summary-label">Stock Turnover</span>
				<i class="fi fi-rr-refresh" aria-hidden="true"></i>
			</header>
			<strong>{{ $materialSnapshots->sum('stock_out') > 0 ? number_format(($materialSnapshots->sum('stock_out') / max(1, $materialSnapshots->avg('beginning_stock'))) * 100, 1) : 0 }}%</strong>
			<p>Items moved during reporting period</p>
		</article>
	</section>

	<!-- Analysis & Insights Section -->
	<section class="reports-panel analysis-section" aria-label="Inventory analysis and insights">
		<header class="reports-panel__header">
			<div>
				<h2>Analysis & Insights</h2>
				<p>Key insights about inventory movement and status</p>
			</div>
		</header>
		<div class="analysis-grid">
			<div class="analysis-card">
				<h3>🚀 Fast-Moving Items</h3>
				<p class="analysis-count">{{ $fastMovingItems->count() }} items</p>
				<p class="analysis-description">High turnover items that need frequent restocking</p>
				@if($fastMovingItems->isNotEmpty())
					<ul class="analysis-list">
						@foreach($fastMovingItems->take(3) as $item)
							<li>{{ $item['model']->material_name }} ({{ number_format($item['stock_out']) }} units moved)</li>
						@endforeach
					</ul>
				@endif
			</div>
			<div class="analysis-card">
				<h3>🐌 Slow-Moving Items</h3>
				<p class="analysis-count">{{ $slowMovingItems->count() }} items</p>
				<p class="analysis-description">Items with minimal movement that may need review</p>
				@if($slowMovingItems->isNotEmpty())
					<ul class="analysis-list">
						@foreach($slowMovingItems->take(3) as $item)
							<li>{{ $item['model']->material_name }} ({{ number_format($item['stock_out']) }} units moved)</li>
						@endforeach
					</ul>
				@endif
			</div>
			<div class="analysis-card">
				<h3>📦 Overstocked Items</h3>
				<p class="analysis-count">{{ $overstockedItems->count() }} items</p>
				<p class="analysis-description">Items with excess quantities tying up capital</p>
				@if($overstockedItems->isNotEmpty())
					<ul class="analysis-list">
						@foreach($overstockedItems->take(3) as $item)
							<li>{{ $item['model']->material_name }} ({{ number_format($item['stock']) }} units in stock)</li>
						@endforeach
					</ul>
				@endif
			</div>
			<div class="analysis-card alert-card">
				<h3>⚠️ Critical Stock Alerts</h3>
				<p class="analysis-count">{{ $outOfStockItems->count() + $lowStockItems->count() }} items</p>
				<p class="analysis-description">Items requiring immediate attention</p>
				@if($hotList->isNotEmpty())
					<ul class="analysis-list">
						@foreach($hotList->take(3) as $item)
							<li class="{{ $item['status_key'] === 'out-of-stock' ? 'critical' : 'warning' }}">
								{{ $item['model']->material_name }} - {{ $item['status'] }}
							</li>
						@endforeach
					</ul>
				@endif
			</div>
		</div>
	</section>

	<section class="reports-panel" aria-label="Inventory chart">
		<header class="reports-panel__header">
			<div>
				<h2>Inventory coverage</h2>
				<p>Compare stock levels against reorder thresholds at a glance.</p>
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
	</section>

	@if($hotList->isNotEmpty())
		<section class="reports-panel reports-panel--attention" aria-label="Immediate inventory actions">
			<header class="reports-panel__header">
				<div>
					<h2>What needs attention</h2>
					<p>Focus on these materials before they impact production.</p>
				</div>
			</header>
			<ul class="reports-callout-list">
				@foreach($hotList as $snapshot)
					<li data-status="{{ $snapshot['status_key'] }}">
						<div class="callout-title">
							<strong>{{ $snapshot['model']->material_name }}</strong>
							<span class="status-pill status-pill--{{ $snapshot['status_key'] }}">{{ $snapshot['status'] }}</span>
						</div>
						<div class="callout-meta">
							<span>On hand: {{ number_format($snapshot['stock']) }}</span>
							<span>Reorder point: {{ number_format($snapshot['reorder']) }}</span>
							<span>{{ $snapshot['coverage_label'] }} &middot; {{ $snapshot['action'] }}</span>
						</div>
					</li>
				@endforeach
			</ul>
		</section>
	@endif

	<section class="reports-detail-grid single-column">
		<article class="reports-card" aria-label="Inventory table">
			<header>
				<div>
					<h2>Inventory status</h2>
					<p>Monitor materials, their health, and the next recommended action.</p>
				</div>
				@php
					$totalTracked = $reportPayload['totalTracked'] ?? 0;
					$inStockCount = $reportPayload['statusBreakdown']['in-stock'] ?? 0;
					$lowCount = $reportPayload['statusBreakdown']['low'] ?? 0;
					$outCount = $reportPayload['statusBreakdown']['out-of-stock'] ?? 0;
				@endphp
				<div class="reports-toolbar reports-toolbar--filters" aria-label="Inventory filters">
					<button type="button" class="pill-link is-active" data-stock-filter="all">All ({{ number_format($totalTracked) }})</button>
					<button type="button" class="pill-link" data-stock-filter="in-stock">Healthy ({{ number_format($inStockCount) }})</button>
					<button type="button" class="pill-link" data-stock-filter="low">Low ({{ number_format($lowCount) }})</button>
					<button type="button" class="pill-link" data-stock-filter="out-of-stock">Out ({{ number_format($outCount) }})</button>
				</div>
			</header>
			<div class="table-wrapper">
				<table class="reports-table" id="inventoryTable">
					<thead>
						<tr>
							<th scope="col">Item Code</th>
							<th scope="col">Item Name</th>
							<th scope="col">Category</th>
							<th scope="col">UOM</th>
							<th scope="col" class="text-center">Reorder Level</th>
							<th scope="col" class="text-center">Beginning Stock</th>
							<th scope="col" class="text-center">Stock In</th>
							<th scope="col" class="text-center">Stock Out</th>
							<th scope="col" class="text-center">Adjustments</th>
							<th scope="col" class="text-center">Ending Stock</th>
							<th scope="col" class="text-end">Unit Cost</th>
							<th scope="col" class="text-end">Total Value</th>
							<th scope="col">Status</th>
						</tr>
					</thead>
					<tbody>
					@forelse($materialSnapshots as $snapshot)
						@php
							$material = $snapshot['model'];
							$statusClass = match ($snapshot['status_key']) {
								'out-of-stock' => 'status-badge status-badge--danger',
								'low' => 'status-badge status-badge--warning',
								default => 'status-badge status-badge--success',
							};
							$rowClass = in_array($snapshot['status_key'], ['low', 'out-of-stock'], true) ? 'needs-attention' : '';
						@endphp
						<tr data-stock-status="{{ $snapshot['status_key'] }}" class="{{ $rowClass }}">
							<td>{{ $material->sku ?? 'N/A' }}</td>
							<td>{{ $material->material_name }}</td>
							<td>{{ $material->material_type }}</td>
							<td>{{ $material->unit ?? 'pcs' }}</td>
							<td class="text-center">{{ number_format($snapshot['reorder']) }}</td>
							<td class="text-center">{{ number_format($snapshot['beginning_stock']) }}</td>
							<td class="text-center">{{ number_format($snapshot['stock_in']) }}</td>
							<td class="text-center">{{ number_format($snapshot['stock_out']) }}</td>
							<td class="text-center">{{ $snapshot['adjustments'] != 0 ? number_format($snapshot['adjustments']) : '-' }}</td>
							<td class="text-center">{{ number_format($snapshot['ending_stock']) }}</td>
							<td class="text-end">₱{{ number_format($material->unit_cost ?? 0, 2) }}</td>
							<td class="text-end">₱{{ number_format($snapshot['stock_value'], 2) }}</td>
							<td><span class="{{ $statusClass }}">{{ $snapshot['status'] }}</span></td>
						</tr>
					@empty
						<tr>
							<td colspan="14" class="text-center">No materials found.</td>
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

		document.addEventListener('DOMContentLoaded', function () {
			const filterButtons = document.querySelectorAll('[data-stock-filter]');
			const tableRows = document.querySelectorAll('#inventoryTable tbody tr');
			if (!filterButtons.length || !tableRows.length) {
				return;
			}

			const applyFilter = function (filter) {
				filterButtons.forEach(function (button) {
					const isActive = button.getAttribute('data-stock-filter') === filter;
					button.classList.toggle('is-active', isActive);
				});

				tableRows.forEach(function (row) {
					const status = row.getAttribute('data-stock-status');
					const shouldShow = filter === 'all' || status === filter;
					row.style.display = shouldShow ? '' : 'none';
				});
			};

			filterButtons.forEach(function (button) {
				button.addEventListener('click', function () {
					applyFilter(button.getAttribute('data-stock-filter'));
				});
			});

			applyFilter('all');
		});
	</script>
	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" defer></script>
	<script src="{{ asset('js/admin/reports.js') }}" defer></script>
@endsection
