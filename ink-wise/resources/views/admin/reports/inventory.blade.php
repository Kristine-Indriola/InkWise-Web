@extends('layouts.admin')

@section('title', 'Inventory Analytics')

@push('styles')
	<link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
	<link rel="stylesheet" href="{{ asset('css/admin-css/reports.css') }}">
@endpush

@section('content')
@php
	$materialCollection = $materials instanceof \Illuminate\Support\Collection ? $materials : collect($materials);

	$evaluateMaterial = function ($material) {
		$stockLevel = optional($material->inventory)->stock_level
			?? $material->stock_qty
			?? 0;
		$reorderLevel = optional($material->inventory)->reorder_level
			?? $material->reorder_point
			?? 0;
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
	<header class="page-header reports-page-header">
		<div>
			<h1 class="page-title">Inventory analytics</h1>
			<p class="page-subtitle">Assess stock coverage, reorder priorities, and warehouse health.</p>
		</div>
		<div class="page-header__quick-actions">
			<a href="{{ route('admin.reports.sales') }}" class="pill-link">
				<i class="fi fi-rr-chart-histogram" aria-hidden="true"></i> Sales analytics
			</a>
			<button type="button" class="pill-link" data-report-action="refresh">
				<i class="fi fi-rr-rotate-right" aria-hidden="true"></i> Refresh data
			</button>
			<button type="button" class="btn btn-primary" data-report-action="export-inventory">
				<i class="fi fi-rr-file-spreadsheet" aria-hidden="true"></i> Export inventory
			</button>
		</div>
	</header>

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

	@if($reorderQueue->isNotEmpty())
		<section class="reports-panel reports-panel--queue" aria-label="Reorder queue">
			<header class="reports-panel__header">
				<div>
					<h2>Reorder queue</h2>
					<p>Review the next materials to restock, sorted by urgency.</p>
				</div>
			</header>
			<div class="table-wrapper">
				<table class="reports-table reports-table--compact" id="reorderQueueTable">
					<thead>
						<tr>
							<th scope="col">Material</th>
							<th scope="col">Status</th>
							<th scope="col" class="text-center">On hand</th>
							<th scope="col" class="text-center">Target</th>
							<th scope="col">Coverage</th>
							<th scope="col">Action</th>
						</tr>
					</thead>
					<tbody>
					@foreach($reorderQueue as $snapshot)
						<tr data-status="{{ $snapshot['status_key'] }}">
							<td>{{ $snapshot['model']->material_name }}</td>
							<td><span class="status-pill status-pill--{{ $snapshot['status_key'] }}">{{ $snapshot['status'] }}</span></td>
							<td class="text-center">{{ number_format($snapshot['stock']) }}</td>
							<td class="text-center">{{ number_format($snapshot['reorder']) }}</td>
							<td>
								@if($snapshot['coverage_percent'] !== null)
									<span class="coverage-indicator coverage-indicator--{{ $snapshot['status_key'] }}">{{ $snapshot['coverage_label'] }} ({{ $snapshot['coverage_percent'] }}%)</span>
								@else
									<span class="coverage-indicator coverage-indicator--unknown">No target</span>
								@endif
							</td>
							<td>{{ $snapshot['action'] }}</td>
						</tr>
					@endforeach
					</tbody>
				</table>
			</div>
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
							<th scope="col">Material</th>
							<th scope="col">Category</th>
							<th scope="col" class="text-center">Stock</th>
							<th scope="col" class="text-center">Reorder</th>
							<th scope="col">Coverage</th>
							<th scope="col">Status</th>
							<th scope="col">Next step</th>
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
						@endphp
						<tr data-stock-status="{{ $snapshot['status_key'] }}">
							<td>{{ $material->material_name }}</td>
							<td>{{ $material->material_type }}</td>
							<td class="text-center">{{ number_format($snapshot['stock']) }}</td>
							<td class="text-center">{{ number_format($snapshot['reorder']) }}</td>
							<td>
								@if($snapshot['coverage_percent'] !== null)
									<span class="coverage-indicator coverage-indicator--{{ $snapshot['status_key'] }}">{{ $snapshot['coverage_label'] }} ({{ $snapshot['coverage_percent'] }}%)</span>
								@else
									<span class="coverage-indicator coverage-indicator--unknown">No target</span>
								@endif
							</td>
							<td>
								<span class="{{ $statusClass }}">{{ $snapshot['status'] }}</span>
							</td>
							<td>{{ $snapshot['action'] }}</td>
						</tr>
					@empty
						<tr>
							<td colspan="7" class="text-center">No materials found.</td>
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
	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" data-chartjs-src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" defer></script>
	<script src="{{ asset('js/admin/reports.js') }}" defer></script>
@endsection
