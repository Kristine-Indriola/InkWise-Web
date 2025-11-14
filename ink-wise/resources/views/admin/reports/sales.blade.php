@extends('layouts.admin')

@section('title', 'Sales Analytics')

@push('styles')
	<link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
	<link rel="stylesheet" href="{{ asset('css/admin-css/reports.css') }}">
@endpush

@section('content')
@php
	$intervalLabels = [
		'daily' => 'Daily',
		'weekly' => 'Weekly',
		'monthly' => 'Monthly',
		'yearly' => 'Yearly',
	];

	$defaultInterval = $defaultSalesInterval ?? 'weekly';
	$availableIntervals = array_keys($salesIntervals);
	if (!in_array($defaultInterval, $availableIntervals, true)) {
		$defaultInterval = $availableIntervals[0] ?? 'daily';
	}

	$activeIntervalData = $salesIntervals[$defaultInterval] ?? [
		'labels' => [],
		'totals' => [],
		'summary' => ['orders' => 0, 'revenue' => 0, 'averageOrder' => 0],
		'range_label' => null,
	];

	$salesSummary = $salesSummaryTotals ?? ($activeIntervalData['summary'] ?? ['orders' => 0, 'revenue' => 0, 'averageOrder' => 0]);
	$salesRangeLabel = $salesSummaryLabel ?? ($activeIntervalData['range_label'] ?? null);

	$currentFilters = $filters ?? ['startDate' => null, 'endDate' => null];

	$reportPayload = [
		'sales' => [
			'intervals' => $salesIntervals,
			'defaultInterval' => $defaultInterval,
			'summary' => $salesSummary,
			'rangeLabel' => $salesRangeLabel,
		],
		'filters' => [
			'startDate' => $currentFilters['startDate'] ?? null,
			'endDate' => $currentFilters['endDate'] ?? null,
		],
	];
@endphp

<main class="reports-shell admin-page-shell" id="adminSalesReportsShell">
	<header class="page-header reports-page-header">
		<div>
			<h1 class="page-title">Sales analytics</h1>
			<p class="page-subtitle">Visualize order momentum, revenue growth, and  KPIs.</p>
		</div>
		<div class="page-header__quick-actions">
			<a href="{{ route('admin.reports.inventory') }}" class="pill-link">
				<i class="fi fi-rr-boxes" aria-hidden="true"></i> Inventory insights
			</a>
			<button type="button" class="pill-link" data-report-action="refresh">
				<i class="fi fi-rr-rotate-right" aria-hidden="true"></i> Refresh data
			</button>
			<button type="button" class="btn btn-primary" data-report-action="export-sales">
				<i class="fi fi-rr-file-spreadsheet" aria-hidden="true"></i> Export sales
			</button>
		</div>
	</header>

	<section class="reports-filter-bar" aria-label="Filter sales by date range">
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
					<a href="{{ route('admin.reports.sales') }}" class="pill-link">
						<i class="fi fi-rr-refresh" aria-hidden="true"></i> Reset
					</a>
				@endif
			</div>
		</form>
	</section>

	<section class="reports-summary-grid" aria-label="Sales highlights">
		<article class="reports-summary-card">
			<header>
				<span class="summary-label">Orders</span>
				<i class="fi fi-rr-shopping-cart" aria-hidden="true"></i>
			</header>
			<strong data-metric="orders-count">{{ number_format($salesSummary['orders'] ?? 0) }}</strong>
			<p>Total orders captured this period.</p>
		</article>
		<article class="reports-summary-card">
			<header>
				<span class="summary-label">Revenue</span>
				<i class="fi fi-rr-chart-histogram" aria-hidden="true"></i>
			</header>
			<strong data-metric="revenue-total">₱{{ number_format($salesSummary['revenue'] ?? 0, 2) }}</strong>
			<p>Gross sales across fulfilled orders.</p>
		</article>
		<article class="reports-summary-card">
			<header>
				<span class="summary-label">Estimated Sales</span>
				<i class="fi fi-rr-chart-line-up" aria-hidden="true"></i>
			</header>
			<strong data-metric="estimated-sales">₱{{ number_format($salesSummary['estimatedSales'] ?? 0, 2) }}</strong>
			<p>Pending revenue from incomplete orders.</p>
		</article>
		<article class="reports-summary-card">
			<header>
				<span class="summary-label">Avg. Order</span>
				<i class="fi fi-rr-circle-dollar" aria-hidden="true"></i>
			</header>
			<strong data-metric="average-order">₱{{ number_format($salesSummary['averageOrder'] ?? 0, 2) }}</strong>
			<p>Average amount spent per order.</p>
		</article>
		<article class="reports-summary-card">
			<header>
				<span class="summary-label">Inventory Alerts</span>
				<i class="fi fi-rr-boxes" aria-hidden="true"></i>
			</header>
			<strong data-metric="stock-status">{{ $inventoryStats['lowStock'] }} low / {{ $inventoryStats['outStock'] }} out</strong>
			<p>Monitor items impacting order fulfilment.</p>
		</article>
	</section>

	<section class="reports-panel" aria-label="Sales trend">
		<header class="reports-panel__header">
			<div>
				<h2>Sales performance</h2>
				@if ($salesRangeLabel)
					<p class="reports-subtext" data-sales-range>Showing {{ $salesRangeLabel }}</p>
				@else
					<p class="reports-subtext" data-sales-range>Showing the most recent activity.</p>
				@endif
			</div>
			<div class="reports-toolbar">
				<div class="reports-interval-group" role="tablist" aria-label="Sales interval selection">
					@foreach ($intervalLabels as $intervalKey => $intervalLabel)
						@if (array_key_exists($intervalKey, $salesIntervals))
							<button
								type="button"
								class="reports-interval {{ $intervalKey === $defaultInterval ? 'is-active' : '' }}"
								data-sales-interval="{{ $intervalKey }}"
								aria-pressed="{{ $intervalKey === $defaultInterval ? 'true' : 'false' }}"
							>
								{{ $intervalLabel }}
							</button>
						@endif
					@endforeach
				</div>
				<div class="reports-toolbar-actions">
					<button type="button" class="pill-link" data-export="sales" data-format="csv">
						<i class="fi fi-rr-download"></i> Export CSV
					</button>
					<button type="button" class="pill-link" data-print="sales">
						<i class="fi fi-rr-print"></i> Print
					</button>
				</div>
			</div>
		</header>
		<canvas data-chart="sales"></canvas>
	</section>

	<section class="reports-detail-grid single-column">
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
						<tr>
							<td>{{ $sale->order_number ?? $sale->id }}</td>
							<td>{{ $sale->customer_name }}</td>
							<td>{{ $sale->items_list }}</td>
							<td class="text-center">{{ $sale->items_quantity }}</td>
							<td class="text-end">{{ number_format($sale->total_amount_value, 2) }}</td>
							<td>{{ optional($sale->order_date_value)->format('M d, Y') }}</td>
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
	</section>
</main>
@endsection

@section('scripts')
	<script>
		window.__INKWISE_REPORTS__ = {!! json_encode($reportPayload) !!};
	</script>
	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" data-chartjs-src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" defer></script>
	<script src="{{ asset('js/admin/reports.js') }}" defer></script>
@endsection
