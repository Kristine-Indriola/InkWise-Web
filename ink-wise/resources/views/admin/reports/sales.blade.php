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

	$currentFilters = $filters ?? ['startDate' => null, 'endDate' => null, 'interval' => $defaultInterval];
	$paymentSummary = $paymentSummary ?? [
		'totalPaid' => 0,
		'full' => ['count' => 0, 'amount' => 0],
		'half' => ['count' => 0, 'amount' => 0, 'balance' => 0],
	];
	$orderStatusCurrent = $currentFilters['orderStatus'] ?? 'completed';
	$paymentStatusCurrent = $currentFilters['paymentStatus'] ?? 'all';

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
			'interval' => $currentFilters['interval'] ?? $defaultInterval,
		],
		'payments' => [
			'summary' => $paymentSummary,
		],
	];
@endphp

<main class="reports-shell admin-page-shell" id="adminSalesReportsShell">
	<header class="page-header reports-page-header">
		<div>
			<h1 class="page-title">Sales analytics</h1>
			<p class="page-subtitle">Visualize order momentum, revenue growth, and KPIs.</p>
		</div>
		<div class="page-header__quick-actions">
			<a href="{{ route('admin.reports.inventory') }}" class="pill-link">
				<i class="fi fi-rr-boxes" aria-hidden="true"></i> Inventory insights
			</a>
			<a href="{{ route('admin.reports.pickup-calendar') }}" class="pill-link">
				<i class="fi fi-rr-calendar" aria-hidden="true"></i> Pickup calendar
			</a>
			<button type="button" class="pill-link" data-report-action="refresh">
				<i class="fi fi-rr-rotate-right" aria-hidden="true"></i> Refresh data
			</button>
			<form id="archiveReportForm" method="POST" action="{{ route('admin.reports.sales.archive') }}" class="archive-form" style="display:none;">
				@csrf
				<label for="archivePeriod" class="sr-only">Archive period</label>
				<select id="archivePeriod" name="period">
					<option value="daily">Daily</option>
					<option value="weekly" selected>Weekly</option>
					<option value="monthly">Monthly</option>
					<option value="yearly">Yearly</option>
				</select>
				<button type="submit" class="pill-link" id="archiveSubmit">
					<i class="fi fi-rr-archive" aria-hidden="true"></i> Archive
				</button>
			</form>
			<button type="button" class="pill-link" data-toggle-archive>
				<i class="fi fi-rr-archive" aria-hidden="true"></i> Archive report
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
			<div class="reports-filter-field">
				<label for="filterInterval">Interval</label>
				<select id="filterInterval" name="interval">
					<option value="daily" {{ ($currentFilters['interval'] ?? $defaultInterval) === 'daily' ? 'selected' : '' }}>Daily</option>
					<option value="weekly" {{ ($currentFilters['interval'] ?? $defaultInterval) === 'weekly' ? 'selected' : '' }}>Weekly</option>
					<option value="monthly" {{ ($currentFilters['interval'] ?? $defaultInterval) === 'monthly' ? 'selected' : '' }}>Monthly</option>
					<option value="yearly" {{ ($currentFilters['interval'] ?? $defaultInterval) === 'yearly' ? 'selected' : '' }}>Yearly</option>
				</select>
			</div>

			<div class="reports-filter-actions">
				<button type="submit" class="btn btn-primary">
					<i class="fi fi-rr-filter" aria-hidden="true"></i> Apply
				</button>
				@if ($currentFilters['startDate'] || $currentFilters['endDate'] || (($currentFilters['interval'] ?? $defaultInterval) !== $defaultInterval))
					<a href="{{ route('admin.reports.sales') }}" class="pill-link">
						<i class="fi fi-rr-refresh" aria-hidden="true"></i> Reset
					</a>
				@endif
			</div>
		</form>
	</section>

	<section class="reports-panel" aria-label="Period summaries">
 		<header class="reports-panel__header">
 			<div>
 				<h2>Period summaries</h2>
 				<p class="reports-subtext">Quick totals for recent periods.</p>
 			</div>
 		</header>
		<div class="reports-summary-grid">
			@if(array_key_exists('weekly', $salesIntervals))
			<article class="reports-summary-card">
				<header>
					<span class="summary-label">Weekly report</span>
				</header>
				@php
					$w = $salesIntervals['weekly'] ?? null;
					$wCounts = $w['counts'] ?? [];
					$wTotals = $w['totals'] ?? [];
					$last = max(0, count($wTotals) - 1);
				@endphp
				<strong>{{ number_format($wCounts[$last] ?? 0) }} orders</strong>
				<p>₱{{ number_format($wTotals[$last] ?? 0, 2) }} revenue</p>
				<p class="muted">{{ $w['labels'][$last] ?? ($w['range_label'] ?? '') }}</p>
			</article>
			@endif

			@if(array_key_exists('monthly', $salesIntervals))
			<article class="reports-summary-card">
				<header>
					<span class="summary-label">Monthly report</span>
				</header>
				@php
					$m = $salesIntervals['monthly'] ?? null;
					$mCounts = $m['counts'] ?? [];
					$mTotals = $m['totals'] ?? [];
					$lastM = max(0, count($mTotals) - 1);
				@endphp
				<strong>{{ number_format($mCounts[$lastM] ?? 0) }} orders</strong>
				<p>₱{{ number_format($mTotals[$lastM] ?? 0, 2) }} revenue</p>
				<p class="muted">{{ $m['labels'][$lastM] ?? ($m['range_label'] ?? '') }}</p>
			</article>
			@endif

			@if(array_key_exists('yearly', $salesIntervals))
			<article class="reports-summary-card">
				<header>
					<span class="summary-label">Yearly report</span>
				</header>
				@php
					$y = $salesIntervals['yearly'] ?? null;
					$yCounts = $y['counts'] ?? [];
					$yTotals = $y['totals'] ?? [];
					$lastY = max(0, count($yTotals) - 1);
				@endphp
				<strong>{{ number_format($yCounts[$lastY] ?? 0) }} orders</strong>
				<p>₱{{ number_format($yTotals[$lastY] ?? 0, 2) }} revenue</p>
				<p class="muted">{{ $y['labels'][$lastY] ?? ($y['range_label'] ?? '') }}</p>
			</article>
			@endif
		</div>
	</section>

	<section class="reports-summary-grid" aria-label="Sales highlights">
		<article class="reports-summary-card">
			<header>
				<span class="summary-label">Orders</span>
				<i class="fi fi-rr-shopping-cart" aria-hidden="true"></i>
			</header>
			<strong data-metric="orders-count">{{ number_format($salesSummary['orders'] ?? 0) }}</strong>
			<p>Orders matching your filters.</p>
		</article>
		<article class="reports-summary-card">
			<header>
				<span class="summary-label">Not completed</span>
				<i class="fi fi-rr-clock" aria-hidden="true"></i>
			</header>
			<strong>{{ number_format($salesSummary['nonCompletedCount'] ?? 0) }} orders</strong>
			<p>Estimated: ₱{{ number_format($salesSummary['estimatedSales'] ?? 0, 2) }}</p>
			<p class="muted">Orders not yet completed</p>
		</article>
		<article class="reports-summary-card">
			<header>
				<span class="summary-label">Partial payments</span>
				<i class="fa-solid fa-coins" aria-hidden="true"></i>
			</header>
			<strong>{{ number_format($paymentSummary['half']['count'] ?? 0) }} orders</strong>
			<p>₱{{ number_format($paymentSummary['half']['amount'] ?? 0, 2) }} received • ₱{{ number_format($paymentSummary['half']['balance'] ?? 0, 2) }} due.</p>
			<p class="muted">Orders with partial payments</p>
		</article>
		<article class="reports-summary-card">
			<header>
				<span class="summary-label">Fully paid revenue</span>
				<i class="fi fi-rr-chart-histogram" aria-hidden="true"></i>
			</header>
			<strong data-metric="revenue-paid">₱{{ number_format($salesSummary['revenue'] ?? 0, 2) }}</strong>
			<p>Revenue recognized from fully settled orders.</p>
		</article>
		<article class="reports-summary-card">
			<header>
				<span class="summary-label">Material cost</span>
				<i class="fi fi-rr-layers" aria-hidden="true"></i>
			</header>
			
		<article class="reports-summary-card">
			<header>
				<span class="summary-label">Net profit</span>
				<i class="fi fi-rr-trending-up" aria-hidden="true"></i>
			</header>
			<strong data-metric="profit-total">₱{{ number_format($salesSummary['profit'] ?? 0, 2) }}</strong>
			<p>
				Profit after material costs.
				<span data-metric="profit-margin">{{ number_format($salesSummary['profitMargin'] ?? 0, 1) }}% margin</span>
			</p>
		</article>
		
		<article class="reports-summary-card">
			<header>
				<span class="summary-label">Avg. order value</span>
				<i class="fi fi-rr-circle-dollar" aria-hidden="true"></i>
			</header>
			<strong data-metric="average-order">₱{{ number_format($salesSummary['averageOrder'] ?? 0, 2) }}</strong>
			<p>Average revenue per completed order.</p>
		</article>
	</section>

	<section class="reports-panel" aria-label="Payment insights">
		<header class="reports-panel__header">
			<div>
				<h2>Payment insights</h2>
				<p class="reports-subtext">Collection status across completed orders.</p>
			</div>
		</header>
		<div class="reports-summary-grid">
			<article class="reports-summary-card">
				<header>
					<span class="summary-label">Total collected</span>
					<i class="fa-solid fa-wallet" aria-hidden="true"></i>
				</header>
				<strong>₱{{ number_format($paymentSummary['totalPaid'] ?? 0, 2) }}</strong>
				<p>Cash realised from completed orders.</p>
			</article>
			<article class="reports-summary-card">
				<header>
					<span class="summary-label">Fully paid orders</span>
					<i class="fa-solid fa-badge-check" aria-hidden="true"></i>
				</header>
				<strong>{{ number_format($paymentSummary['full']['count'] ?? 0) }}</strong>
				<p>₱{{ number_format($paymentSummary['full']['amount'] ?? 0, 2) }} collected in full.</p>
			</article>
			<article class="reports-summary-card">
				<header>
					<span class="summary-label">Partial payments</span>
					<i class="fa-solid fa-coins" aria-hidden="true"></i>
				</header>
				<strong>{{ number_format($paymentSummary['half']['count'] ?? 0) }}</strong>
				<p>₱{{ number_format($paymentSummary['half']['amount'] ?? 0, 2) }} received • ₱{{ number_format($paymentSummary['half']['balance'] ?? 0, 2) }} due.</p>
			</article>
			<article class="reports-summary-card">
				<header>
					<span class="summary-label">Estimated pipeline</span>
					<i class="fa-solid fa-chart-line" aria-hidden="true"></i>
				</header>
				<strong>₱{{ number_format($salesSummary['estimatedSales'] ?? 0, 2) }}</strong>
				<p>Forecast value of in-progress, non-completed orders.</p>
			</article>
		</div>
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
							<th scope="col" class="text-center">Payment Status</th>
							<th scope="col" class="text-end">Total (PHP)</th>
							<th scope="col" class="text-end">Profit (PHP)</th>
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
							<td class="text-center">{{ $sale->payment_status }}</td>
							<td class="text-end">{{ number_format($sale->total_amount_value, 2) }}</td>
							<td class="text-end">{{ number_format($sale->profit_value, 2) }}</td>
							<td>{{ optional($sale->order_date_value)->format('M d, Y') }}</td>
						</tr>
					@empty
						<tr>
							<td colspan="8" class="text-center">No sales records available.</td>
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
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const toggle = document.querySelector('[data-toggle-archive]');
			const form = document.getElementById('archiveReportForm');
			if (toggle && form) {
				toggle.addEventListener('click', function () {
					form.style.display = form.style.display === 'none' ? 'inline-flex' : 'none';
				});
				form.addEventListener('submit', function (ev) {
					if (!confirm('Archive sales for the selected period? This will mark matching orders as archived and reset the current dashboard.')) {
						ev.preventDefault();
						return;
					}
				});
			}
		});
	</script>
@endsection
