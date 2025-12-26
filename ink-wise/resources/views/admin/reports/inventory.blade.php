@extends('layouts.admin')

@section('title', 'Inventory Analytics')

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

		/* Summary Cards - Professional Design */
		.reports-summary-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
			gap: 1.5rem;
			margin-bottom: 3rem;
		}
		.reports-summary-card {
			background: var(--card-bg);
			border: 1px solid var(--border-color);
			border-radius: var(--border-radius-lg);
			padding: 2rem;
			box-shadow: var(--shadow-sm);
			transition: all 0.3s ease;
			position: relative;
			overflow: hidden;
		}
		.reports-summary-card:hover {
			box-shadow: var(--shadow-md);
			transform: translateY(-2px);
		}
		.reports-summary-card header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			margin-bottom: 1rem;
		}
		.summary-label {
			font-size: 0.875rem;
			font-weight: 600;
			color: var(--text-secondary);
			text-transform: uppercase;
			letter-spacing: 0.05em;
		}
		.reports-summary-card strong {
			font-size: 2.25rem;
			font-weight: 700;
			color: var(--text-primary);
			display: block;
			margin-bottom: 0.5rem;
		}
		.reports-summary-card p {
			color: var(--text-muted);
			font-size: 0.875rem;
			margin: 0;
			font-weight: 500;
		}
		.reports-summary-card i {
			font-size: 1.5rem;
			color: var(--accent-color);
			opacity: 0.8;
		}

		/* Value Cards Special Styling */
		.value-card {
			border: 2px solid var(--accent-color);
			background: linear-gradient(135deg, rgba(49, 130, 206, 0.05) 0%, rgba(49, 130, 206, 0.02) 100%);
		}
		.value-card strong {
			color: var(--accent-color);
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

		/* Analysis Section */
		.analysis-section {
			margin-bottom: 3rem;
		}
		.analysis-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
			gap: 2rem;
			margin-top: 2rem;
		}
		.analysis-card {
			background: var(--card-bg);
			border: 1px solid var(--border-color);
			border-radius: var(--border-radius-lg);
			padding: 2rem;
			box-shadow: var(--shadow-sm);
			transition: all 0.3s ease;
			position: relative;
		}
		.analysis-card:hover {
			box-shadow: var(--shadow-md);
			transform: translateY(-2px);
		}
		.analysis-card h3 {
			margin: 0 0 1rem 0;
			font-size: 1.25rem;
			font-weight: 600;
			color: var(--text-primary);
			display: flex;
			align-items: center;
			gap: 0.5rem;
		}
		.analysis-count {
			font-size: 2rem;
			font-weight: 700;
			color: var(--accent-color);
			margin: 0.75rem 0;
		}
		.analysis-description {
			color: var(--text-secondary);
			font-size: 0.9rem;
			margin-bottom: 1.5rem;
			font-weight: 500;
		}
		.analysis-list {
			list-style: none;
			padding: 0;
			margin: 0;
		}
		.analysis-list li {
			padding: 0.5rem 0;
			border-bottom: 1px solid var(--border-color);
			font-size: 0.875rem;
			color: var(--text-primary);
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
		.analysis-list li:last-child {
			border-bottom: none;
		}

		/* Alert Card */
		.alert-card {
			border-color: var(--warning-color);
			background: linear-gradient(135deg, rgba(214, 158, 46, 0.05) 0%, rgba(214, 158, 46, 0.02) 100%);
		}
		.alert-card h3 {
			color: var(--warning-color);
		}
		.analysis-list li.critical {
			color: var(--danger-color);
			font-weight: 600;
		}
		.analysis-list li.warning {
			color: var(--warning-color);
			font-weight: 500;
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

		/* Attention Panel */
		.reports-panel--attention {
			border-color: var(--danger-color);
			background: linear-gradient(135deg, rgba(229, 62, 62, 0.02) 0%, rgba(229, 62, 62, 0.01) 100%);
		}
		.reports-panel--attention .reports-panel__header {
			background: linear-gradient(135deg, rgba(229, 62, 62, 0.05) 0%, rgba(229, 62, 62, 0.02) 100%);
		}

		/* Callout List */
		.reports-callout-list {
			padding: 2rem;
		}
		.reports-callout-list li {
			background: white;
			border-left: 4px solid var(--danger-color);
			padding: 1.5rem;
			margin-bottom: 1rem;
			border-radius: var(--border-radius);
			box-shadow: var(--shadow-sm);
			transition: all 0.2s ease;
		}
		.reports-callout-list li:hover {
			box-shadow: var(--shadow-md);
		}
		.reports-callout-list li[data-status="low"] {
			border-left-color: var(--warning-color);
			background: linear-gradient(135deg, rgba(214, 158, 46, 0.05) 0%, rgba(214, 158, 46, 0.02) 100%);
		}
		.callout-title {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 0.75rem;
			flex-wrap: wrap;
			gap: 0.5rem;
		}
		.callout-title strong {
			font-size: 1.125rem;
			font-weight: 600;
			color: var(--text-primary);
		}
		.callout-meta {
			display: flex;
			gap: 1.5rem;
			flex-wrap: wrap;
			font-size: 0.875rem;
			color: var(--text-secondary);
		}

		/* Table Styles */
		.reports-detail-grid {
			margin-bottom: 3rem;
		}
		.reports-card {
			background: var(--card-bg);
			border: 1px solid var(--border-color);
			border-radius: var(--border-radius-lg);
			box-shadow: var(--shadow-sm);
			overflow: hidden;
		}
		.reports-card header {
			padding: 2rem;
			border-bottom: 1px solid var(--border-color);
			background: var(--light-bg);
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-wrap: wrap;
			gap: 1rem;
		}
		.card-header-content h2 {
			font-size: 1.5rem;
			font-weight: 600;
			color: var(--text-primary);
			margin: 0;
		}
		.card-header-content p {
			color: var(--text-secondary);
			margin: 0.25rem 0 0 0;
			font-size: 0.9rem;
		}
		.reports-card header h2 {
			font-size: 1.5rem;
			font-weight: 600;
			color: var(--text-primary);
			margin: 0;
		}
		.reports-card header p {
			color: var(--text-secondary);
			margin: 0.25rem 0 0 0;
			font-size: 0.9rem;
		}
		.reports-toolbar--filters {
			display: flex;
			gap: 0.5rem;
			flex-wrap: wrap;
		}
		.table-wrapper {
			overflow-x: auto;
		}
		.reports-table {
			width: 100%;
			border-collapse: collapse;
		}
		.reports-table th {
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
		.reports-table td {
			padding: 1rem 0.75rem;
			font-size: 0.875rem;
			border-bottom: 1px solid var(--border-color);
			color: var(--text-primary);
		}
		.reports-table .text-center {
			text-align: center;
		}
		.reports-table .text-end {
			text-align: right;
		}
		.reports-table tbody tr {
			transition: all 0.2s ease;
		}
		.reports-table tbody tr:hover {
			background: var(--light-bg);
		}
		.reports-table tbody tr.needs-attention {
			background: linear-gradient(135deg, rgba(214, 158, 46, 0.05) 0%, rgba(214, 158, 46, 0.02) 100%);
			border-left: 4px solid var(--warning-color);
		}
		.reports-table tbody tr.needs-attention:hover {
			background: linear-gradient(135deg, rgba(214, 158, 46, 0.08) 0%, rgba(214, 158, 46, 0.05) 100%);
		}

		/* Status Badges */
		.status-badge {
			display: inline-block;
			padding: 0.25rem 0.75rem;
			border-radius: 20px;
			font-size: 0.75rem;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 0.05em;
		}
		.status-badge--success {
			background: rgba(56, 161, 105, 0.1);
			color: var(--success-color);
		}
		.status-badge--warning {
			background: rgba(214, 158, 46, 0.1);
			color: var(--warning-color);
		}
		.status-badge--danger {
			background: rgba(229, 62, 62, 0.1);
			color: var(--danger-color);
		}

		/* Purpose Badges */
		.purpose-badge {
			display: inline-block;
			padding: 0.25rem 0.5rem;
			border-radius: 4px;
			font-size: 0.75rem;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 0.05em;
		}
		.purpose-badge--used {
			background: rgba(49, 130, 206, 0.1);
			color: var(--accent-color);
		}
		.purpose-badge--issued {
			background: rgba(102, 102, 255, 0.1);
			color: #6666ff;
		}
		.purpose-badge--sold {
			background: rgba(56, 161, 105, 0.1);
			color: var(--success-color);
		}
		.purpose-badge--adjustment {
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
		.filter-count {
			font-weight: 600;
			opacity: 0.8;
		}

		/* Chart Container */
		.reports-panel canvas {
			padding: 2rem;
			max-height: 400px;
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
			.reports-summary-grid,
			.analysis-grid {
				grid-template-columns: 1fr;
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

	$recentlyRestocked = $materialSnapshots->filter(fn ($snapshot) => $snapshot['stock_in'] > 0)->sortByDesc('stock_in')->take(5);
	$recentlyUsed = $materialSnapshots->filter(fn ($snapshot) => $snapshot['stock_out'] > 0)->sortByDesc('stock_out')->take(5);

	// Collect detailed usage/release details for moving out items
	$usageDetails = collect();
	foreach ($materialSnapshots as $snapshot) {
		$material = $snapshot['model'];
		$movements = $material->stockMovements ?? collect();
		$outMovements = $movements->whereIn('movement_type', ['used', 'issued', 'sold', 'adjustment']);
		
		foreach ($outMovements as $movement) {
			$purpose = match ($movement->movement_type) {
				'used' => 'Used in production',
				'issued' => 'Sample given',
				'sold' => 'Sold',
				'adjustment' => 'Damaged/Adjustment',
				default => ucfirst($movement->movement_type)
			};
			
			$usageDetails->push([
				'material_name' => $material->material_name,
				'material_sku' => $material->sku ?? 'N/A',
				'order_id' => $movement->order_id ?? 'N/A',
				'customer_name' => $movement->customer_name ?? 'N/A',
				'purpose' => $purpose,
				'quantity_deducted' => abs($movement->quantity),
				'date' => $movement->created_at ?? $movement->movement_date ?? now(),
				'movement_type' => $movement->movement_type
			]);
		}
	}
	
	$usageDetails = $usageDetails->sortByDesc('date')->take(20); // Show last 20 movements

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
		<div class="page-header__content">
			<h1 class="page-title">Inventory Analytics Dashboard</h1>
			<p class="page-subtitle">Comprehensive inventory management and analytics platform</p>
		</div>
		<div class="page-header__actions">
			<nav class="page-nav" aria-label="Quick navigation">
				<a href="{{ route('admin.reports.sales') }}" class="pill-link" aria-label="View sales analytics">
					<i class="fi fi-rr-chart-histogram" aria-hidden="true"></i>
					<span>Sales Analytics</span>
				</a>
				<a href="{{ route('admin.reports.pickup-calendar') }}" class="pill-link" aria-label="View pickup calendar">
					<i class="fi fi-rr-calendar" aria-hidden="true"></i>
					<span>Pickup Calendar</span>
				</a>
			</nav>
			<div class="page-actions">
				<button type="button" class="pill-link" data-report-action="refresh" aria-label="Refresh data">
					<i class="fi fi-rr-rotate-right" aria-hidden="true"></i>
					<span>Refresh Data</span>
				</button>
				<button type="button" class="btn btn-primary" data-report-action="export-inventory" aria-label="Export inventory data">
					<i class="fi fi-rr-file-spreadsheet" aria-hidden="true"></i>
					<span>Export Inventory</span>
				</button>
			</div>
		</div>
	</header>

	<section class="reports-filter-bar" aria-label="Filter inventory data by date range">
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
				<small id="start-date-help" class="form-help">Select the beginning date for the inventory analysis period</small>
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
				<small id="end-date-help" class="form-help">Select the ending date for the inventory analysis period</small>
			</fieldset>
			<div class="reports-filter-actions">
				<button type="submit" class="btn btn-primary" aria-label="Apply date filters">
					<i class="fi fi-rr-filter" aria-hidden="true"></i>
					<span>Apply Filters</span>
				</button>
				@if ($currentFilters['startDate'] || $currentFilters['endDate'])
					<a href="{{ route('admin.reports.inventory') }}" class="pill-link" aria-label="Clear all filters">
						<i class="fi fi-rr-refresh" aria-hidden="true"></i>
						<span>Clear Filters</span>
					</a>
				@endif
			</div>
		</form>
	</section>

	<section class="reports-summary-grid" aria-label="Key inventory metrics overview">
		<article class="reports-summary-card" aria-labelledby="total-skus-heading">
			<header>
				<span class="summary-label" id="total-skus-heading">Total SKUs</span>
				<i class="fi fi-rr-box" aria-hidden="true"></i>
			</header>
			<strong data-metric="total-skus">{{ number_format($inventoryStats['totalSkus'] ?? $reportPayload['totalTracked'] ?? 0) }}</strong>
			<p>Active inventory items currently being tracked in the system</p>
		</article>
		<article class="reports-summary-card" aria-labelledby="low-stock-heading">
			<header>
				<span class="summary-label" id="low-stock-heading">Low Stock Alerts</span>
				<i class="fi fi-rr-triangle-warning" aria-hidden="true"></i>
			</header>
			<strong data-metric="low-stock">{{ number_format($inventoryStats['lowStock'] ?? ($reportPayload['statusBreakdown']['low'] ?? 0)) }}</strong>
			<p>Items at or below their configured reorder threshold</p>
		</article>
		<article class="reports-summary-card" aria-labelledby="out-stock-heading">
			<header>
				<span class="summary-label" id="out-stock-heading">Out of Stock</span>
				<i class="fi fi-rr-empty-set" aria-hidden="true"></i>
			</header>
			<strong data-metric="out-stock">{{ number_format($inventoryStats['outStock'] ?? ($reportPayload['statusBreakdown']['out-of-stock'] ?? 0)) }}</strong>
			<p>Items with zero available quantity requiring immediate action</p>
		</article>
		<article class="reports-summary-card" aria-labelledby="total-units-heading">
			<header>
				<span class="summary-label" id="total-units-heading">Total Units</span>
				<i class="fi fi-rr-stats" aria-hidden="true"></i>
			</header>
			<strong data-metric="total-stock">{{ number_format($inventoryStats['totalStock'] ?? 0) }}</strong>
			<p>Combined on-hand inventory across all tracked materials</p>
		</article>
		<article class="reports-summary-card" aria-labelledby="stock-value-heading">
			<header>
				<span class="summary-label" id="stock-value-heading">Inventory Value</span>
				<i class="fi fi-rr-money-bill-wave" aria-hidden="true"></i>
			</header>
			<strong>₱{{ number_format($totalStockValue, 2) }}</strong>
			<p>Total monetary value of current inventory holdings</p>
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
			<div class="panel-header-content">
				<h2>Inventory Analysis & Insights</h2>
				<p>Data-driven insights to optimize inventory management and operational efficiency</p>
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
			<div class="analysis-card">
				<h3>📥 Recently Restocked</h3>
				<p class="analysis-count">{{ $recentlyRestocked->count() }} items</p>
				<p class="analysis-description">Items recently added to inventory</p>
				@if($recentlyRestocked->isNotEmpty())
					<ul class="analysis-list">
						@foreach($recentlyRestocked as $item)
							<li>{{ $item['model']->material_name }} (+{{ number_format($item['stock_in']) }} units)</li>
						@endforeach
					</ul>
				@endif
			</div>
			<div class="analysis-card">
				<h3>📤 Recently Used</h3>
				<p class="analysis-count">{{ $recentlyUsed->count() }} items</p>
				<p class="analysis-description">Items recently consumed or sold</p>
				@if($recentlyUsed->isNotEmpty())
					<ul class="analysis-list">
						@foreach($recentlyUsed as $item)
							<li>{{ $item['model']->material_name }} (-{{ number_format($item['stock_out']) }} units)</li>
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

	@if($usageDetails->isNotEmpty())
		<section class="reports-panel" aria-labelledby="usage-details-heading">
			<header class="reports-panel__header">
				<div class="panel-header-content">
					<h2 id="usage-details-heading">Usage or Release Details (Moving Out)</h2>
					<p>Detailed breakdown of inventory deductions and releases</p>
				</div>
				<div class="reports-toolbar">
					<button type="button" class="pill-link" data-export="usage" data-format="csv">
						<i class="fi fi-rr-download"></i> Export Usage Report
					</button>
				</div>
			</header>
			<div class="table-wrapper">
				<table class="reports-table" id="usageDetailsTable">
					<thead>
						<tr>
							<th scope="col">Item Code</th>
							<th scope="col">Item Name</th>
							<th scope="col">Order ID</th>
							<th scope="col">Customer Name</th>
							<th scope="col">Purpose</th>
							<th scope="col" class="text-center">Quantity Deducted</th>
							<th scope="col" class="text-center">Date</th>
						</tr>
					</thead>
					<tbody>
					@foreach($usageDetails as $detail)
						<tr>
							<td>{{ $detail['material_sku'] }}</td>
							<td>{{ $detail['material_name'] }}</td>
							<td>{{ $detail['order_id'] }}</td>
							<td>{{ $detail['customer_name'] }}</td>
							<td>
								<span class="purpose-badge purpose-badge--{{ $detail['movement_type'] }}">
									{{ $detail['purpose'] }}
								</span>
							</td>
							<td class="text-center">{{ number_format($detail['quantity_deducted']) }}</td>
							<td class="text-center">{{ \Carbon\Carbon::parse($detail['date'])->format('M j, Y') }}</td>
						</tr>
					@endforeach
					</tbody>
				</table>
			</div>
		</section>
	@endif

	@if($hotList->isNotEmpty())
		<section class="reports-panel reports-panel--attention" aria-labelledby="attention-heading">
			<header class="reports-panel__header">
				<div class="panel-header-content">
					<h2 id="attention-heading">Critical Inventory Actions Required</h2>
					<p>Items requiring immediate attention to prevent operational disruptions</p>
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
							<span><strong>On hand:</strong> {{ number_format($snapshot['stock']) }}</span>
							<span><strong>Reorder point:</strong> {{ number_format($snapshot['reorder']) }}</span>
							<span><strong>Status:</strong> {{ $snapshot['coverage_label'] }} &middot; {{ $snapshot['action'] }}</span>
						</div>
					</li>
				@endforeach
			</ul>
		</section>
	@endif

	<section class="reports-detail-grid single-column" aria-labelledby="inventory-table-heading">
		@php
			$totalTracked = $reportPayload['totalTracked'] ?? 0;
			$inStockCount = $reportPayload['statusBreakdown']['in-stock'] ?? 0;
			$lowCount = $reportPayload['statusBreakdown']['low'] ?? 0;
			$outCount = $reportPayload['statusBreakdown']['out-of-stock'] ?? 0;
		@endphp
		<article class="reports-card" aria-label="Detailed inventory status table">
			<header>
				<div class="card-header-content">
					<h2 id="inventory-table-heading">Detailed Inventory Status</h2>
					<p>Comprehensive view of all inventory items with movement tracking and status indicators</p>
				</div>
				<div class="reports-toolbar reports-toolbar--filters" role="group" aria-label="Inventory status filters">
					<button type="button" class="pill-link is-active" data-stock-filter="all" aria-pressed="true">
						<span>All Items</span>
						<span class="filter-count">({{ number_format($totalTracked) }})</span>
					</button>
					<button type="button" class="pill-link" data-stock-filter="in-stock" aria-pressed="false">
						<span>Healthy Stock</span>
						<span class="filter-count">({{ number_format($inStockCount) }})</span>
					</button>
					<button type="button" class="pill-link" data-stock-filter="low" aria-pressed="false">
						<span>Low Stock</span>
						<span class="filter-count">({{ number_format($lowCount) }})</span>
					</button>
					<button type="button" class="pill-link" data-stock-filter="out-of-stock" aria-pressed="false">
						<span>Out of Stock</span>
						<span class="filter-count">({{ number_format($outCount) }})</span>
					</button>
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
