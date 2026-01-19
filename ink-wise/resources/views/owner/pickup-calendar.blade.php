@extends('layouts.owner.app')

@section('title', 'Pickup Calendar')

@push('styles')
	<link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
	<link rel="stylesheet" href="{{ asset('css/admin-css/reports.css') }}">
<style>
  .owner-dashboard-shell {
    padding-right: 0;
    padding-bottom: 0;
    padding-left: 0;
  }

  .owner-dashboard-main {
    max-width: var(--owner-content-shell-max, 1440px);
    margin: 0;
    padding: 0 28px 36px 12px;
    width: min(100%, calc(100vw - var(--owner-sidebar-width, 230px)));
    margin-left: var(--owner-sidebar-width, 230px);
    margin-top: 80px; /* Account for fixed topbar height (64px) + spacing */
  }

  .owner-dashboard-inner {
    max-width: var(--owner-content-shell-max, 1390px);
    margin: 0;
    width: 100%;
    padding: 0;
  }

  .owner-dashboard-main .page-header {
    margin-bottom: 24px;
  }

  .materials-toolbar {
    margin: 0 0 20px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
  }

  .materials-toolbar__search {
    flex: 1;
    max-width: 400px;
  }

  .materials-toolbar__actions {
    display: flex;
    gap: 0.75rem;
  }

  /* Filter Form Styles */
  .reports-filter-form {
    display: flex;
    align-items: end;
    gap: 1rem;
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
  }

  .reports-filter-form:hover {
    border-color: #d1d5db;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  }

  .reports-filter-field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 200px;
  }

  .reports-filter-field label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .reports-filter-field label i {
    color: #6b7280;
    font-size: 0.875rem;
  }

  .reports-filter-field select {
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    font-size: 0.875rem;
    color: #374151;
    transition: all 0.2s ease;
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.75rem center;
    background-repeat: no-repeat;
    background-size: 1rem;
    padding-right: 2.5rem;
    appearance: none;
  }

  .reports-filter-field select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  .reports-filter-field select:hover {
    border-color: #9ca3af;
  }

  .reports-filter-actions {
    display: flex;
    align-items: center;
  }

  .reports-filter-actions .btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .reports-filter-actions .btn:hover {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transform: translateY(-1px);
  }

  .reports-filter-actions .btn:active {
    transform: translateY(0);
  }

  .reports-filter-actions .btn i {
    font-size: 0.875rem;
  }

  /* Filter Icon Enhancement */
  .filter-icon-container {
    position: relative;
    display: inline-block;
  }

  .filter-icon-container::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 6px;
    z-index: -1;
    opacity: 0;
    transition: opacity 0.2s ease;
  }

  .reports-filter-actions .btn:hover .filter-icon-container::before {
    opacity: 0.2;
  }

  /* Responsive Design */
  @media (max-width: 768px) {
    .reports-filter-form {
      flex-direction: column;
      align-items: stretch;
      gap: 1rem;
      padding: 1rem;
    }

    .reports-filter-field {
      min-width: unset;
      width: 100%;
    }

    .reports-filter-actions {
      justify-content: center;
    }

    .reports-filter-actions .btn {
      width: 100%;
      justify-content: center;
    }
  }

  @media (max-width: 480px) {
    .materials-toolbar {
      flex-direction: column;
      gap: 1rem;
      align-items: stretch;
    }

    .materials-toolbar__search {
      flex: none;
      max-width: none;
    }
  }

.calendar-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
	gap: 1rem;
	margin-top: 1rem;
}

.calendar-day {
	border: 1px solid #e5e7eb;
	border-radius: 8px;
	padding: 1rem;
	background: white;
	min-height: 140px;
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
	height: 100px;
	color: #9ca3af;
}

.no-orders-message i {
	font-size: 2rem;
	margin-bottom: 0.5rem;
}

/* Year View Styles */
.year-calendar-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
	gap: 1rem;
	margin-top: 1rem;
}

.calendar-month {
	border: 1px solid #e5e7eb;
	border-radius: 8px;
	padding: 1rem;
	background: white;
	min-height: 180px;
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
@endpush

@section('content')
@include('layouts.owner.sidebar')

<main class="materials-page admin-page-shell materials-container owner-dashboard-main" role="main">
	<div class="owner-dashboard-inner">
		<header class="page-header">
			<div>
				<h1 class="page-title">Pickup Calendar</h1>
				<p class="page-subtitle">View scheduled pickups for the selected period.</p>
			</div>
			<div class="page-header__quick-actions">
				<a href="{{ route('owner.order.workflow') }}" class="pill-link">
					<i class="fi fi-rr-arrow-left" aria-hidden="true"></i> Back to Orders
				</a>
			</div>
		</header>

		<section class="materials-toolbar" aria-label="Filter pickup calendar by period">
			<div class="materials-toolbar__search">
				<form method="GET" class="reports-filter-form">
					<div class="reports-filter-field">
						<label for="filterPeriod">
							<i class="fi fi-rr-calendar" aria-hidden="true"></i>
							View Period
						</label>
						<select id="filterPeriod" name="period">
							<option value="day" {{ $period === 'day' ? 'selected' : '' }}>📅 Next Day</option>
							<option value="week" {{ $period === 'week' ? 'selected' : '' }}>📊 Next Week</option>
							<option value="current_month" {{ $period === 'current_month' ? 'selected' : '' }}>📈 Current Month</option>
							<option value="month" {{ $period === 'month' ? 'selected' : '' }}>📉 Next Month</option>
							<option value="year" {{ $period === 'year' ? 'selected' : '' }}>📊 Current Year</option>
						</select>
					</div>
					<div class="reports-filter-actions">
						<button type="submit" class="btn btn-primary">
							<span class="filter-icon-container">
								<i class="fi fi-rr-filter" aria-hidden="true"></i>
							</span>
							Apply Filter
						</button>
					</div>
				</form>
			</div>
			<div class="materials-toolbar__actions">
				<!-- placeholder for future actions -->
			</div>
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
	</div>
</main>

@push('scripts')
<script>
	document.addEventListener('DOMContentLoaded', function() {
		// Add click handlers for order cards
		document.querySelectorAll('.order-card').forEach(card => {
			card.addEventListener('click', function() {
				const orderId = this.dataset.orderId;
				const orderInv = this.querySelector('.order-id')?.textContent || '';
				if (orderId) {
					// For owner, redirect to order workflow with search
					window.location.href = '{{ route("owner.order.workflow") }}?search=' + encodeURIComponent(orderInv);
				}
			});
		});

		// Also handle compact order cards for year view
		document.querySelectorAll('.order-card-compact').forEach(card => {
			card.addEventListener('click', function() {
				const orderId = this.dataset.orderId;
				const orderInv = this.querySelector('.order-id')?.textContent || '';
				if (orderId) {
					// For owner, redirect to order workflow with search
					window.location.href = '{{ route("owner.order.workflow") }}?search=' + encodeURIComponent(orderInv);
				}
			});
		});
	});
</script>
@endpush
@endsection