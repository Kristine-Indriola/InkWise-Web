@extends('layouts.admin')

@section('title', 'Inventory Usage Details')

@push('styles')
	<link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
	<link rel="stylesheet" href="{{ asset('css/admin-css/reports.css') }}">
	<style>
		/* Professional Color Palette */
		:root {
			--primary-color: #1a365d;
			--secondary-color: #2d3748;
			--accent-color: #3182ce;
			--success-color: #38a169;
			--warning-color: #d69e2e;
			--danger-color: #e53e3e;
			--light-bg: #f7fafc;
			--card-bg: #ffffff;
			--border-color: #e2e8f0;
			--text-primary: #1a202c;
			--text-secondary: #4a5568;
			--text-muted: #718096;
			--shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
			--shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
			--shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
			--border-radius: 8px;
			--border-radius-lg: 12px;
		}

		/* Report Header - Professional Corporate Style */
		.report-header-section {
			background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
			color: white;
			padding: 2.5rem;
			margin-bottom: 2rem;
			border-radius: var(--border-radius-lg);
			box-shadow: var(--shadow-lg);
			position: relative;
			overflow: hidden;
		}
		.report-header-section::before {
			content: '';
			position: absolute;
			top: 0;
			right: 0;
			width: 200px;
			height: 200px;
			background: rgba(255, 255, 255, 0.05);
			border-radius: 50%;
			transform: translate(50%, -50%);
		}
		.report-header-content {
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-wrap: wrap;
			gap: 2rem;
			position: relative;
			z-index: 1;
		}
		.company-name {
			font-size: 2.8rem;
			font-weight: 700;
			margin: 0;
			color: white;
			letter-spacing: -0.02em;
		}
		.report-title {
			font-size: 1.25rem;
			font-weight: 400;
			margin: 0.75rem 0 0 0;
			opacity: 0.9;
			color: #e2e8f0;
		}
		.report-meta-info {
			display: flex;
			flex-direction: column;
			gap: 0.75rem;
			background: rgba(255, 255, 255, 0.1);
			padding: 1.5rem;
			border-radius: var(--border-radius);
			backdrop-filter: blur(10px);
		}
		.meta-item {
			font-size: 0.95rem;
			font-weight: 500;
		}
		.meta-item strong {
			display: inline-block;
			min-width: 140px;
			color: #e2e8f0;
			font-weight: 600;
		}

		/* Page Header */
		.page-header {
			margin-bottom: 2.5rem;
		}
		.page-header__content {
			margin-bottom: 1.5rem;
		}
		.page-title {
			font-size: 2.25rem;
			font-weight: 700;
			color: var(--text-primary);
			margin-bottom: 0.5rem;
			letter-spacing: -0.025em;
		}
		.page-subtitle {
			font-size: 1.125rem;
			color: var(--text-secondary);
			margin: 0;
			font-weight: 400;
		}
		.page-header__actions {
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-wrap: wrap;
			gap: 1.5rem;
		}
		.page-nav {
			display: flex;
			gap: 0.75rem;
			flex-wrap: wrap;
		}
		.page-actions {
			display: flex;
			gap: 0.75rem;
			align-items: center;
		}

		/* Filter Bar - Professional */
		.reports-filter-bar {
			background: var(--light-bg);
			border: 1px solid var(--border-color);
			border-radius: var(--border-radius-lg);
			padding: 2rem;
			margin-bottom: 3rem;
			box-shadow: var(--shadow-sm);
		}
		.reports-filter-form {
			display: flex;
			align-items: end;
			gap: 1.5rem;
			flex-wrap: wrap;
		}
		.reports-filter-field {
			display: flex;
			flex-direction: column;
			min-width: 180px;
		}
		.reports-filter-field label {
			font-weight: 600;
			font-size: 0.875rem;
			margin-bottom: 0.75rem;
			color: var(--text-primary);
			text-transform: uppercase;
			letter-spacing: 0.05em;
		}
		.reports-filter-field input {
			padding: 0.75rem 1rem;
			border: 2px solid var(--border-color);
			border-radius: var(--border-radius);
			font-size: 0.875rem;
			transition: all 0.2s ease;
			background: white;
		}
		.reports-filter-field input:focus {
			outline: none;
			border-color: var(--accent-color);
			box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
		}
		.form-help {
			font-size: 0.75rem;
			color: var(--text-muted);
			margin-top: 0.5rem;
			font-weight: 400;
		}
		.reports-filter-actions {
			display: flex;
			gap: 1rem;
			align-items: center;
		}

		/* Reports Panel */
		.reports-panel {
			background: var(--card-bg);
			border: 1px solid var(--border-color);
			border-radius: var(--border-radius-lg);
			box-shadow: var(--shadow-sm);
			margin-bottom: 3rem;
			overflow: hidden;
		}
		.reports-panel__header {
			padding: 2rem;
			border-bottom: 1px solid var(--border-color);
			background: var(--light-bg);
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-wrap: wrap;
			gap: 1rem;
		}
		.panel-header-content h2 {
			font-size: 1.5rem;
			font-weight: 600;
			color: var(--text-primary);
			margin: 0;
		}
		.panel-header-content p {
			color: var(--text-secondary);
			margin: 0.25rem 0 0 0;
			font-size: 0.9rem;
		}
		.reports-toolbar {
			display: flex;
			gap: 0.75rem;
			align-items: center;
		}

		/* Usage Details Section */
		.usage-details-section {
			margin-bottom: 3rem;
		}
		.usage-count {
			font-size: 0.875rem;
			color: var(--text-secondary);
			font-weight: 500;
		}
		.usage-table th {
			background: var(--light-bg);
			font-weight: 600;
			font-size: 0.8rem;
			padding: 1rem 0.75rem;
			text-align: left;
			color: var(--text-secondary);
			text-transform: uppercase;
			letter-spacing: 0.05em;
			border-bottom: 2px solid var(--border-color);
		}
		.usage-table td {
			padding: 1rem 0.75rem;
			font-size: 0.875rem;
			border-bottom: 1px solid var(--border-color);
			color: var(--text-primary);
		}
		.usage-table tbody tr {
			transition: all 0.2s ease;
		}
		.usage-table tbody tr:hover {
			background: var(--light-bg);
		}

		/* Purpose Badges */
		.purpose-badge {
			display: inline-block;
			padding: 0.25rem 0.75rem;
			border-radius: 20px;
			font-size: 0.75rem;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 0.05em;
		}
		.purpose-badge--used {
			background: rgba(56, 161, 105, 0.1);
			color: var(--success-color);
		}
		.purpose-badge--issued {
			background: rgba(49, 130, 206, 0.1);
			color: var(--accent-color);
		}
		.purpose-badge--sold {
			background: rgba(214, 158, 46, 0.1);
			color: var(--warning-color);
		}

		/* Buttons and Links */
		.btn {
			display: inline-flex;
			align-items: center;
			gap: 0.5rem;
			padding: 0.75rem 1.5rem;
			border-radius: var(--border-radius);
			font-weight: 600;
			font-size: 0.875rem;
			text-decoration: none;
			transition: all 0.2s ease;
			border: none;
			cursor: pointer;
		}
		.btn-primary {
			background: var(--accent-color);
			color: white;
		}
		.btn-primary:hover {
			background: #2c5aa0;
			box-shadow: var(--shadow-md);
		}
		.btn-secondary {
			background: var(--light-bg);
			color: var(--text-primary);
			border: 2px solid var(--border-color);
		}
		.btn-secondary:hover {
			background: var(--text-primary);
			color: white;
			border-color: var(--text-primary);
		}
		.pill-link {
			display: inline-flex;
			align-items: center;
			gap: 0.5rem;
			padding: 0.5rem 1rem;
			border-radius: 20px;
			font-size: 0.8rem;
			font-weight: 500;
			text-decoration: none;
			color: var(--text-secondary);
			background: var(--light-bg);
			border: 1px solid var(--border-color);
			transition: all 0.2s ease;
		}
		.pill-link:hover {
			background: var(--accent-color);
			color: white;
			border-color: var(--accent-color);
		}
		.pill-link.is-active {
			background: var(--accent-color);
			color: white;
			border-color: var(--accent-color);
		}

		/* Responsive Design */
		@media (max-width: 768px) {
			.report-header-content {
				flex-direction: column;
				text-align: center;
			}
			.company-name {
				font-size: 2.2rem;
			}
			.reports-filter-form {
				flex-direction: column;
				align-items: stretch;
			}
			.reports-filter-field {
				min-width: auto;
			}
			.reports-panel__header,
			.reports-card header {
				flex-direction: column;
				align-items: flex-start;
			}
			.reports-toolbar {
				width: 100%;
				justify-content: center;
			}
			.callout-meta {
				flex-direction: column;
				gap: 0.5rem;
			}
			.usage-table {
				font-size: 0.8rem;
			}
			.usage-table th,
			.usage-table td {
				padding: 0.5rem 0.25rem;
			}
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

	// Collect detailed usage/release details for moving out items
	$usageDetails = collect();
	foreach ($materialCollection as $material) {
		$movements = $material->stockMovements ?? collect();
		$outMovements = $movements->whereIn('movement_type', ['used', 'issued', 'sold']);

		foreach ($outMovements as $movement) {
			$usageDetails->push([
				'material_name' => $material->material_name,
				'material_sku' => $material->sku ?? 'N/A',
				'movement_type' => $movement->movement_type,
				'quantity' => abs($movement->quantity),
				'order_id' => $movement->order_id ?? null,
				'customer_name' => $movement->customer_name ?? null,
				'purpose' => match($movement->movement_type) {
					'used' => 'Used in production',
					'issued' => 'Released/Dispatched',
					'sold' => 'Sold',
					default => 'Other'
				},
				'date' => $movement->created_at ?? $movement->movement_date ?? now(),
				'reference' => $movement->reference ?? null,
			]);
		}
	}
	$usageDetails = $usageDetails->sortByDesc('date');
@endphp

<main class="reports-shell admin-page-shell" id="adminUsageDetailsShell">
	<!-- Report Header -->
	<section class="report-header-section" aria-label="Usage details report header">
		<div class="report-header-content">
			<div class="report-company-info">
				<h1 class="company-name">InkWise</h1>
				<h2 class="report-title">Inventory Usage Details Report</h2>
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
					<strong>Total Transactions:</strong> {{ number_format($usageDetails->count()) }}
				</div>
			</div>
		</div>
	</section>

	<header class="page-header reports-page-header">
		<div class="page-header__content">
			<h1 class="page-title">Inventory Usage & Release Details</h1>
			<p class="page-subtitle">Detailed transaction history for inventory deductions and releases</p>
		</div>
		<div class="page-header__actions">
			<nav class="page-nav" aria-label="Quick navigation">
				<a href="{{ route('admin.reports.inventory') }}" class="pill-link" aria-label="Return to inventory analytics">
					<i class="fi fi-rr-arrow-left" aria-hidden="true"></i>
					<span>Back to Inventory</span>
				</a>
			</nav>
			<div class="page-actions">
				<button type="button" class="btn btn-primary" data-report-action="export-usage" aria-label="Export usage details">
					<i class="fi fi-rr-file-spreadsheet" aria-hidden="true"></i>
					<span>Export Details</span>
				</button>
			</div>
		</div>
	</header>

	<section class="reports-filter-bar" aria-label="Filter usage details by date range">
		<form method="GET" class="reports-filter-form" role="search">
			<fieldset class="reports-filter-field">
				<label for="filterStartDate">Report Start Date</label>
				<input
					type="date"
					id="filterStartDate"
					name="start_date"
					value="{{ $currentFilters['startDate'] ?? '' }}"
					max="{{ now()->format('Y-m-d') }}"
					aria-describedby="start-date-help"
				>
				<small id="start-date-help" class="form-help">Select the beginning date for the usage analysis period</small>
			</fieldset>
			<fieldset class="reports-filter-field">
				<label for="filterEndDate">Report End Date</label>
				<input
					type="date"
					id="filterEndDate"
					name="end_date"
					value="{{ $currentFilters['endDate'] ?? '' }}"
					max="{{ now()->format('Y-m-d') }}"
					aria-describedby="end-date-help"
				>
				<small id="end-date-help" class="form-help">Select the ending date for the usage analysis period</small>
			</fieldset>
			<div class="reports-filter-actions">
				<button type="submit" class="btn btn-primary" aria-label="Apply date filters">
					<i class="fi fi-rr-filter" aria-hidden="true"></i>
					<span>Apply Filters</span>
				</button>
				@if ($currentFilters['startDate'] || $currentFilters['endDate'])
					<a href="{{ route('admin.reports.usage-details') }}" class="pill-link" aria-label="Clear all filters">
						<i class="fi fi-rr-refresh" aria-hidden="true"></i>
						<span>Clear Filters</span>
					</a>
				@endif
			</div>
		</form>
	</section>

	<!-- Usage Details Section -->
	<section class="reports-panel usage-details-section" aria-labelledby="usage-details-heading">
		<header class="reports-panel__header">
			<div class="panel-header-content">
				<h2 id="usage-details-heading">Usage or Release Details (Moving Out)</h2>
				<p>Complete transaction history showing how inventory is being consumed or released</p>
			</div>
			<div class="reports-toolbar">
				<span class="usage-count">Total: {{ number_format($usageDetails->count()) }} transactions</span>
			</div>
		</header>
		<div class="table-wrapper">
			<table class="reports-table usage-table" id="usageDetailsTable" aria-label="Detailed usage and release transactions">
				<thead>
					<tr>
						<th scope="col">Date & Time</th>
						<th scope="col">Item Name</th>
						<th scope="col">SKU</th>
						<th scope="col">Purpose</th>
						<th scope="col">Order ID</th>
						<th scope="col">Customer</th>
						<th scope="col" class="text-center">Quantity Deducted</th>
						<th scope="col">Reference</th>
					</tr>
				</thead>
				<tbody>
				@forelse($usageDetails as $detail)
					<tr>
						<td>{{ \Carbon\Carbon::parse($detail['date'])->format('M j, Y g:i A') }}</td>
						<td>{{ $detail['material_name'] }}</td>
						<td>{{ $detail['material_sku'] }}</td>
						<td>
							<span class="purpose-badge purpose-badge--{{ $detail['movement_type'] }}">
								{{ $detail['purpose'] }}
							</span>
						</td>
						<td>{{ $detail['order_id'] ?? '-' }}</td>
						<td>{{ $detail['customer_name'] ?? '-' }}</td>
						<td class="text-center">{{ number_format($detail['quantity']) }}</td>
						<td>{{ $detail['reference'] ?? '-' }}</td>
					</tr>
				@empty
					<tr>
						<td colspan="8" class="text-center">No usage or release transactions found for the selected period.</td>
					</tr>
				@endforelse
				</tbody>
			</table>
		</div>
	</section>
</main>
@endsection

@section('scripts')
	<script>
		window.__INKWISE_USAGE__ = {!! json_encode([
			'transactions' => $usageDetails->count(),
			'period' => $reportPeriod,
			'filters' => $currentFilters
		]) !!};

		document.addEventListener('DOMContentLoaded', function () {
			// Add any interactive functionality here if needed
			console.log('Usage Details Report Loaded');
		});
	</script>
@endsection