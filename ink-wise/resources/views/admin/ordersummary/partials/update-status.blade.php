	<section class="status-progress-card" data-status-card>
		<header class="status-progress-card__header">
			<div>
				<h2>Order progress</h2>
			</div>
			<div class="status-progress-card__actions">
				<span class="order-stage-chip order-stage-chip--{{ $currentChipModifier }}" data-status-chip>
					{{ $currentStatusLabel }}
				</span>
				@if($statusManageUrl)
					<a href="{{ $statusManageUrl }}" class="status-progress-manage-link">Update status</a>
				@endif
			</div>
		</header>
		<ol class="status-tracker" aria-label="Order progress">
			@foreach($statusFlow as $index => $statusKey)
				@php
					$stateClass = 'status-tracker__item--upcoming';
					if ($currentStatus === 'cancelled') {
						$stateClass = 'status-tracker__item--disabled';
					} elseif ($flowIndex !== false) {
						if ($index < $flowIndex) {
							$stateClass = 'status-tracker__item--done';
						} elseif ($index === $flowIndex) {
							$stateClass = $currentStatus === 'completed'
								? 'status-tracker__item--done'
								: 'status-tracker__item--current';
						}
					}
				@endphp
				<li class="status-tracker__item {{ $stateClass }}" data-status-step="{{ $statusKey }}">
					<div class="status-tracker__marker">
						@if($stateClass === 'status-tracker__item--done')
							<span class="status-tracker__icon">✓</span>
						@else
							<span class="status-tracker__number">{{ $index + 1 }}</span>
						@endif
					</div>
					@if(!$loop->last)
						<span class="status-tracker__line" aria-hidden="true"></span>
					@endif
					<div class="status-tracker__content">
						<p class="status-tracker__title">
							{{ $statusOptions[$statusKey] ?? ucfirst(str_replace('_', ' ', $statusKey)) }}
						</p>
						<p class="status-tracker__subtitle">
							@switch($statusKey)
								@case('pending')
									Order received and awaiting confirmation.
									@break
								@case('processing')
									Team is preparing assets before full production starts.
									@break
								@case('in_production')
									Production team is preparing the items.
									@break
								@case('confirmed')
									Packaged and ready for courier hand-off.
									@break
								@case('to_receive')
									Order is in transit to the customer.
									@break
								@case('completed')
									Delivered and closed out.
									@break
								@default
									Status update in progress.
									@break
							@endswitch
						</p>
					</div>
				</li>
			@endforeach
		</ol>

		<div class="status-info-grid">
			<article class="status-info-card">
				<h2 class="status-info-card__title">Customer-facing update</h2>
				<dl>
					<div>
						<dt>Tracking number</dt>
						<dd>{{ $trackingNumber ?: '— Not provided yet' }}</dd>
					</div>
					<div>
						<dt>Next milestone</dt>
						<dd data-next-status>{{ $nextStatusLabel ?? 'All steps complete' }}</dd>
					</div>
					<div>
						<dt>Last updated</dt>
						<dd>{{ $lastUpdatedDisplay ?? 'Not available' }}</dd>
					</div>
				</dl>
			</article>
			<article class="status-info-card">
				<h2 class="status-info-card__title">Internal note</h2>
				@if(filled($statusNote))
					<p class="status-info-card__text">{{ $statusNote }}</p>
				@else
					<p class="status-info-card__empty">
						No notes yet. Hit "Update status" to leave instructions for the team.
					</p>
				@endif
			</article>
		</div>
	</section>