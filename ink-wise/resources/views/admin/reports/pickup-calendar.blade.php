@extends('layouts.admin')

@section('title', 'Pickup Calendar')

@push('styles')
	<link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
	<link rel="stylesheet" href="{{ asset('css/admin-css/reports.css') }}">
@endpush

@section('content')
<main class="reports-shell admin-page-shell" id="adminPickupCalendarShell">
	<header class="page-header reports-page-header">
		<div>
			<h1 class="page-title">Pickup Calendar</h1>
			<p class="page-subtitle">View scheduled pickups for the selected period.</p>
		</div>
		<div class="page-header__quick-actions">
			<a href="{{ route('admin.reports.sales') }}" class="pill-link">
				<i class="fi fi-rr-chart-line-up" aria-hidden="true"></i> Sales analytics
			</a>
			<a href="{{ route('admin.reports.inventory') }}" class="pill-link">
				<i class="fi fi-rr-boxes" aria-hidden="true"></i> Inventory insights
			</a>
		</div>
	</header>

	<section class="reports-filter-bar" aria-label="Filter pickup calendar by period">
		<form method="GET" class="reports-filter-form">
			<div class="reports-filter-field">
				<label for="filterPeriod">Period</label>
				<select id="filterPeriod" name="period">
					<option value="day" {{ $period === 'day' ? 'selected' : '' }}>Next Day</option>
					<option value="week" {{ $period === 'week' ? 'selected' : '' }}>Next Week</option>
					<option value="current_month" {{ $period === 'current_month' ? 'selected' : '' }}>Current Month</option>
					<option value="month" {{ $period === 'month' ? 'selected' : '' }}>Next Month</option>
					<option value="year" {{ $period === 'year' ? 'selected' : '' }}>Current Year</option>
				</select>
			</div>
			<div class="reports-filter-actions">
				<button type="submit" class="btn btn-primary">
					<i class="fi fi-rr-filter" aria-hidden="true"></i> Apply
				</button>
			</div>
		</form>
	</section>

	<section class="reports-content">
		@if($period === 'year')
			<div class="year-calendar-grid">
				@foreach($calendarData as $monthKey => $data)
					<div class="calendar-month {{ $data['total_orders'] > 0 ? 'has-orders' : 'no-orders' }}">
						<div class="calendar-month-header">
							<h3 class="calendar-month-title">{{ $data['month_name'] }}</h3>
						</div>
						<div class="calendar-month-summary">
							@if($data['total_orders'] > 0)
								<div class="month-summary-stats">
									<span class="stat">{{ $data['total_orders'] }} order{{ $data['total_orders'] !== 1 ? 's' : '' }}</span>
									<span class="stat">${{ number_format($data['total_amount'], 2) }}</span>
								</div>
								<div class="calendar-month-orders">
									@foreach(array_slice($data['orders'], 0, 5) as $order) {{-- Show first 5 orders --}}
										<div class="order-card-compact" data-order-id="{{ $order['id'] }}">
											<div class="order-compact-header">
												<span class="order-id">{{ $order['inv'] }}</span>
												<span class="order-amount">${{ number_format($order['total_amount'], 2) }}</span>
											</div>
											<div class="order-compact-details">
												<span class="order-customer">{{ Str::limit($order['customer_name'], 20) }}</span>
												<span class="pickup-date">{{ \Carbon\Carbon::parse($order['date_needed'])->format('M j') }}</span>
											</div>
										</div>
									@endforeach
									@if(count($data['orders']) > 5)
										<div class="more-orders-indicator">
											+{{ count($data['orders']) - 5 }} more order{{ count($data['orders']) - 5 !== 1 ? 's' : '' }}
										</div>
									@endif
								</div>
							@else
								<div class="no-orders-message">
									<i class="fi fi-rr-calendar-xmark" aria-hidden="true"></i>
									<span>No pickups</span>
								</div>
							@endif
						</div>
					</div>
				@endforeach
			</div>
		@else
			<div class="calendar-grid">
				@foreach($calendarData as $date => $data)
					<div class="calendar-day {{ $data['total_orders'] > 0 ? 'has-orders' : 'no-orders' }}">
						<div class="calendar-day-header">
							<h3 class="calendar-day-title">{{ \Carbon\Carbon::parse($data['date'])->format('M j, Y') }}</h3>
							<span class="calendar-day-name">{{ $data['day_name'] }}</span>
						</div>
						<div class="calendar-day-summary">
							@if($data['total_orders'] > 0)
								<div class="summary-stats">
									<span class="stat">{{ $data['total_orders'] }} order{{ $data['total_orders'] !== 1 ? 's' : '' }}</span>
									<span class="stat">${{ number_format($data['total_amount'], 2) }}</span>
								</div>
								<div class="calendar-day-orders">
									@foreach($data['orders'] as $order)
										<div class="order-card" data-order-id="{{ $order['id'] }}">
											<div class="order-header">
												<span class="order-id">{{ $order['inv'] }}</span>
												<span class="order-customer">{{ $order['customer_name'] }}</span>
											</div>
											<div class="order-details">
												<span class="order-amount">${{ number_format($order['total_amount'], 2) }}</span>
												<span class="order-items">{{ $order['items_count'] }} item{{ $order['items_count'] !== 1 ? 's' : '' }}</span>
											</div>
											@if($order['items_list'])
												<div class="order-items-list" title="{{ $order['items_list'] }}">
													{{ Str::limit($order['items_list'], 50) }}
												</div>
											@endif
											<div class="order-status">
												<span class="status-badge status-{{ $order['status'] }}">{{ ucfirst($order['status']) }}</span>
												<span class="pickup-time">{{ \Carbon\Carbon::parse($order['date_needed'])->format('g:i A') }}</span>
											</div>
										</div>
									@endforeach
								</div>
							@else
								<div class="no-orders-message">
									<i class="fi fi-rr-calendar-xmark" aria-hidden="true"></i>
									<span>No pickups scheduled</span>
								</div>
							@endif
						</div>
					</div>
				@endforeach
			</div>
		@endif
	</section>
</main>

@push('scripts')
<script>
	document.addEventListener('DOMContentLoaded', function() {
		// Add click handlers for order cards
		document.querySelectorAll('.order-card').forEach(card => {
			card.addEventListener('click', function() {
				const orderId = this.dataset.orderId;
				if (orderId) {
					window.location.href = '{{ route("admin.ordersummary.show", ["order" => ":id"]) }}'.replace(':id', orderId);
				}
			});
		});

		// Also handle compact order cards for year view
		document.querySelectorAll('.order-card-compact').forEach(card => {
			card.addEventListener('click', function() {
				const orderId = this.dataset.orderId;
				if (orderId) {
					window.location.href = '{{ route("admin.ordersummary.show", ["order" => ":id"]) }}'.replace(':id', orderId);
				}
			});
		});
	});
</script>
@endpush

<style>
.calendar-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
	gap: 1rem;
	margin-top: 1rem;
}

.calendar-day {
	border: 1px solid #e5e7eb;
	border-radius: 8px;
	padding: 1rem;
	background: white;
	min-height: 200px;
}

.calendar-day.has-orders {
	border-color: #3b82f6;
	background: #f0f9ff;
}

.calendar-day.no-orders {
	background: #f9fafb;
}

.calendar-day-header {
	margin-bottom: 1rem;
	text-align: center;
}

.calendar-day-title {
	margin: 0;
	font-size: 1.125rem;
	font-weight: 600;
	color: #111827;
}

.calendar-day-name {
	display: block;
	font-size: 0.875rem;
	color: #6b7280;
	margin-top: 0.25rem;
}

.summary-stats {
	display: flex;
	justify-content: space-between;
	margin-bottom: 1rem;
	font-weight: 500;
}

.stat {
	color: #374151;
}

.calendar-day-orders {
	display: flex;
	flex-direction: column;
	gap: 0.75rem;
}

.order-card {
	border: 1px solid #e5e7eb;
	border-radius: 6px;
	padding: 0.75rem;
	background: white;
	cursor: pointer;
	transition: all 0.2s;
}

.order-card:hover {
	border-color: #3b82f6;
	box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.order-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 0.5rem;
}

.order-id {
	font-weight: 600;
	color: #111827;
}

.order-customer {
	font-size: 0.875rem;
	color: #6b7280;
}

.order-details {
	display: flex;
	justify-content: space-between;
	margin-bottom: 0.5rem;
	font-size: 0.875rem;
}

.order-amount {
	font-weight: 600;
	color: #059669;
}

.order-items {
	color: #6b7280;
}

.order-items-list {
	font-size: 0.75rem;
	color: #9ca3af;
	margin-bottom: 0.5rem;
}

.order-status {
	display: flex;
	justify-content: space-between;
	align-items: center;
	font-size: 0.75rem;
}

.status-badge {
	padding: 0.125rem 0.5rem;
	border-radius: 9999px;
	font-size: 0.625rem;
	font-weight: 500;
	text-transform: uppercase;
}

.status-completed {
	background: #dcfce7;
	color: #166534;
}

.status-pending {
	background: #fef3c7;
	color: #92400e;
}

.status-cancelled {
	background: #fee2e2;
	color: #991b1b;
}

.pickup-time {
	color: #6b7280;
	font-weight: 500;
}

.no-orders-message {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	height: 120px;
	color: #9ca3af;
}

.no-orders-message i {
	font-size: 2rem;
	margin-bottom: 0.5rem;
}

/* Year View Styles */
.year-calendar-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
	gap: 1rem;
	margin-top: 1rem;
}

.calendar-month {
	border: 1px solid #e5e7eb;
	border-radius: 8px;
	padding: 1rem;
	background: white;
	min-height: 250px;
}

.calendar-month.has-orders {
	border-color: #3b82f6;
	background: #f0f9ff;
}

.calendar-month.no-orders {
	background: #f9fafb;
}

.calendar-month-header {
	margin-bottom: 1rem;
	text-align: center;
}

.calendar-month-title {
	margin: 0;
	font-size: 1.25rem;
	font-weight: 600;
	color: #111827;
}

.month-summary-stats {
	display: flex;
	justify-content: space-between;
	margin-bottom: 1rem;
	font-weight: 500;
	font-size: 0.875rem;
}

.calendar-month-orders {
	display: flex;
	flex-direction: column;
	gap: 0.5rem;
}

.order-card-compact {
	border: 1px solid #e5e7eb;
	border-radius: 4px;
	padding: 0.5rem;
	background: white;
	cursor: pointer;
	transition: all 0.2s;
	font-size: 0.75rem;
}

.order-card-compact:hover {
	border-color: #3b82f6;
	box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1);
}

.order-compact-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 0.25rem;
}

.order-compact-details {
	display: flex;
	justify-content: space-between;
	align-items: center;
	color: #6b7280;
}

.pickup-date {
	font-weight: 500;
	color: #374151;
}

.more-orders-indicator {
	text-align: center;
	padding: 0.5rem;
	color: #6b7280;
	font-size: 0.75rem;
	font-style: italic;
}
</style>
@endsection