<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Order Summary — InkWise</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="{{ asset('css/customer/orderflow-ordersummary.css') }}">
	<script src="{{ asset('js/customer/orderflow-ordersummary.js') }}" defer></script>
</head>
<body>
@php
	try {
		$envelopeUrl = route('order.envelope');
	} catch (\Throwable $eEnvelope) {
		$envelopeUrl = url('/order/envelope');
	}

	try {
		$finalStepUrl = route('order.finalstep');
	} catch (\Throwable $eFinal) {
		$finalStepUrl = url('/order/final-step');
	}

	try {
		$giveawaysUrl = route('order.giveaways');
	} catch (\Throwable $eGiveaways) {
		$giveawaysUrl = url('/order/giveaways');
	}

	try {
		$checkoutUrl = url('/checkout');
	} catch (\Throwable $eCheckout) {
		$checkoutUrl = '/checkout';
	}
@endphp
	<main
		class="os-shell ordersummary-shell"
		data-storage-key="inkwise-finalstep"
		data-envelopes-url="{{ $envelopeUrl }}"
		data-checkout-url="{{ $checkoutUrl }}"
		data-edit-url="{{ $finalStepUrl }}"
		data-giveaways-url="{{ $giveawaysUrl }}"
	>
		<header class="ordersummary-header">
			<div class="ordersummary-header__content">
				<a href="{{ $envelopeUrl }}" class="ordersummary-header__back" aria-label="Back to envelope options">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
					Back to envelopes
				</a>
				<h1>Review your order</h1>
				<p>Confirm your invitations, envelopes, and giveaways before heading to checkout.</p>
			</div>
		</header>

		<div class="os-empty" data-empty-state hidden>
			<div class="os-empty-card">
				<h2>No order selections found</h2>
				<p>We couldn’t find any saved selections for this order. Start again from the final step to configure your invitation.</p>
				<a href="{{ $finalStepUrl }}" class="btn primary">Return to final step</a>
			</div>
		</div>

		<div class="os-layout" data-summary-wrapper>
			<section class="os-detail-column" data-summary-grid>
				<article class="os-card os-product-preview">
					<header class="os-card-header">
						<h2>Invitation selection</h2>
						<a href="#" class="os-preview-remove" id="osRemoveProductBtn">Remove</a>
					</header>
					<div class="os-preview-content">
						<div class="os-preview-left">
							<div class="os-preview-frame" data-preview-frame>
								<button type="button" class="os-preview-nav os-preview-nav--prev" data-preview-prev aria-label="Previous preview image" disabled>
									<span aria-hidden="true">◀</span>
								</button>
								<img src="{{ asset('images/placeholder.png') }}" alt="Invitation preview" data-preview-image>
								<button type="button" class="os-preview-nav os-preview-nav--next" data-preview-next aria-label="Next preview image" disabled>
									<span aria-hidden="true">▶</span>
								</button>
							</div>
							<div class="os-preview-meta">
								<a href="{{ route('order.finalstep') }}" class="os-preview-link" data-preview-edit>Edit design / Edit options</a>
							</div>
						</div>
						<div class="os-preview-right">
							<h3 class="os-preview-title" data-preview-name>Custom invitation</h3>
							<div class="os-preview-quantity">
								<label for="osPreviewQuantity">Quantity</label>
								<select id="osPreviewQuantity" data-preview-quantity disabled>
									<option value="10">10</option>
									<option value="20">20</option>
									<option value="30">30</option>
									<option value="40">40</option>
									<option value="50">50</option>
									<option value="60">60</option>
									<option value="70">70</option>
									<option value="80">80</option>
									<option value="90">90</option>
									<option value="100">100</option>
									<option value="110">110</option>
									<option value="120">120</option>
									<option value="130">130</option>
									<option value="140">140</option>
									<option value="150">150</option>
									<option value="160">160</option>
									<option value="170">170</option>
									<option value="180">180</option>
									<option value="190">190</option>
									<option value="200">200</option>
								</select>
							</div>
							<details class="os-preview-options">
								<summary>Selected options</summary>
								<dl class="os-options-list">
									<div class="os-option">
										<dt>Orientation</dt>
										<dd data-option="orientation">—</dd>
									</div>
									<div class="os-option">
										<dt>Foil color</dt>
										<dd data-option="foil-color">Included</dd>
									</div>
									<div class="os-option">
										<dt>Backside option</dt>
										<dd data-option="backside">—</dd>
									</div>
									<div class="os-option">
										<dt>Trim</dt>
										<dd data-option="trim">—</dd>
									</div>
									<div class="os-option">
										<dt>Size</dt>
										<dd data-option="size">—</dd>
									</div>
									<div class="os-option">
										<dt>Paper stock</dt>
										<dd data-option="paper-stock">—</dd>
									</div>
								</dl>
							</details>
							<div class="os-preview-pricing">
								<div class="os-preview-total">
									<span>Item total</span>
									<div class="os-preview-amount">
										<span class="os-price-old" data-preview-old-total>₱0.00</span>
										<span class="os-price-new" data-preview-new-total>₱0.00</span>
									</div>
								</div>
								<p class="os-preview-savings" data-preview-savings hidden>You saved ₱0.00</p>
							</div>
						</div>
					</div>
				</article>

				<article class="os-card os-product-preview">
					<header class="os-card-header">
						<h2>Envelope selection</h2>
						<a href="#" class="os-preview-remove" id="osRemoveEnvelopeBtn">Remove</a>
					</header>
					<div class="os-preview-content">
						<div class="os-preview-left">
							<div class="os-preview-frame" data-envelope-preview-frame>
								<button type="button" class="os-preview-nav os-preview-nav--prev" data-envelope-preview-prev aria-label="Previous envelope preview image" disabled>
									<span aria-hidden="true">◀</span>
								</button>
								<img src="{{ asset('images/placeholder.png') }}" alt="Envelope preview" data-envelope-preview-image>
								<button type="button" class="os-preview-nav os-preview-nav--next" data-envelope-preview-next aria-label="Next envelope preview image" disabled>
									<span aria-hidden="true">▶</span>
								</button>
							</div>
							<div class="os-preview-meta">
								<a href="{{ route('order.envelope') }}" class="os-preview-link" data-envelope-edit>Edit envelope</a>
							</div>
						</div>
						<div class="os-preview-right">
							<h3 class="os-preview-title" data-envelope-name>Envelope</h3>
							<div class="os-preview-quantity">
								<label for="osEnvelopeQuantity">Quantity</label>
								<select id="osEnvelopeQuantity" data-envelope-quantity disabled>
									<option value="10">10</option>
									<option value="20">20</option>
									<option value="30">30</option>
									<option value="40">40</option>
									<option value="50">50</option>
									<option value="60">60</option>
									<option value="70">70</option>
									<option value="80">80</option>
									<option value="90">90</option>
									<option value="100">100</option>
									<option value="110">110</option>
									<option value="120">120</option>
									<option value="130">130</option>
									<option value="140">140</option>
									<option value="150">150</option>
									<option value="160">160</option>
									<option value="170">170</option>
									<option value="180">180</option>
									<option value="190">190</option>
									<option value="200">200</option>
								</select>
							</div>
							<details class="os-preview-options">
								<summary>Selected options</summary>
								<dl class="os-options-list">
									<div class="os-option">
										<dt>Envelope type</dt>
										<dd data-envelope-option="type">—</dd>
									</div>
									<div class="os-option">
										<dt>Color</dt>
										<dd data-envelope-option="color">—</dd>
									</div>
									<div class="os-option">
										<dt>Size</dt>
										<dd data-envelope-option="size">—</dd>
									</div>
									<div class="os-option">
										<dt>Printing</dt>
										<dd data-envelope-option="printing">Included</dd>
									</div>
								</dl>
							</details>
							<div class="os-preview-pricing">
								<div class="os-preview-total">
									<span>Item total</span>
									<div class="os-preview-amount">
										<span class="os-price-old" data-envelope-old-total>₱0.00</span>
										<span class="os-price-new" data-envelope-new-total>₱0.00</span>
									</div>
								</div>
								<p class="os-preview-savings" data-envelope-savings hidden>You saved ₱0.00</p>
							</div>
						</div>
					</div>
				</article>

				<article class="os-card os-product-preview">
					<header class="os-card-header">
						<h2>Giveaways selection</h2>
						<a href="#" class="os-preview-remove" id="osRemoveGiveawaysBtn">Remove</a>
					</header>
					<div class="os-preview-content">
						<div class="os-preview-left">
							<div class="os-preview-frame" data-giveaways-preview-frame>
								<button type="button" class="os-preview-nav os-preview-nav--prev" data-giveaways-preview-prev aria-label="Previous giveaways preview image" disabled>
									<span aria-hidden="true">◀</span>
								</button>
								<img src="{{ asset('images/placeholder.png') }}" alt="Giveaways preview" data-giveaways-preview-image>
								<button type="button" class="os-preview-nav os-preview-nav--next" data-giveaways-preview-next aria-label="Next giveaways preview image" disabled>
									<span aria-hidden="true">▶</span>
								</button>
							</div>
							<div class="os-preview-meta">
								<a href="#" class="os-preview-link" data-giveaways-edit>Edit giveaways</a>
							</div>
						</div>
						<div class="os-preview-right">
							<h3 class="os-preview-title" data-giveaways-name>Giveaways</h3>
							<div class="os-preview-quantity">
								<label for="osGiveawaysQuantity">Quantity</label>
								<select id="osGiveawaysQuantity" data-giveaways-quantity disabled>
									<option value="10">10</option>
									<option value="20">20</option>
									<option value="30">30</option>
									<option value="40">40</option>
									<option value="50">50</option>
									<option value="60">60</option>
									<option value="70">70</option>
									<option value="80">80</option>
									<option value="90">90</option>
									<option value="100">100</option>
									<option value="110">110</option>
									<option value="120">120</option>
									<option value="130">130</option>
									<option value="140">140</option>
									<option value="150">150</option>
									<option value="160">160</option>
									<option value="170">170</option>
									<option value="180">180</option>
									<option value="190">190</option>
									<option value="200">200</option>
								</select>
							</div>
							<details class="os-preview-options">
								<summary>Selected options</summary>
								<dl class="os-options-list">
									<div class="os-option">
										<dt>Giveaway type</dt>
										<dd data-giveaways-option="type">—</dd>
									</div>
									<div class="os-option">
										<dt>Material</dt>
										<dd data-giveaways-option="material">—</dd>
									</div>
									<div class="os-option">
										<dt>Customization</dt>
										<dd data-giveaways-option="customization">Included</dd>
									</div>
								</dl>
							</details>
							<div class="os-preview-pricing">
								<div class="os-preview-total">
									<span>Item total</span>
									<div class="os-preview-amount">
										<span class="os-price-old" data-giveaways-old-total>₱0.00</span>
										<span class="os-price-new" data-giveaways-new-total>₱0.00</span>
									</div>
								</div>
								<p class="os-preview-savings" data-giveaways-savings hidden>You saved ₱0.00</p>
							</div>
						</div>
					</div>
				</article>
			</section>

			<aside class="os-summary-column" data-summary-card>
				<div class="os-summary-card">
					<header class="os-summary-header">
						<h2>Order summary</h2>
						<span class="os-summary-secure">Secure checkout</span>
					</header>

					<div class="os-summary-pricing">
						<div class="os-summary-line os-summary-line--subtotal">
							<span>Subtotal</span>
							<div class="os-summary-amount">
								<span class="os-price-old" data-summary="subtotal-original">₱0.00</span>
								<span class="os-price-new" data-summary="subtotal-discounted">₱0.00</span>
								<span class="os-price-savings" data-summary="subtotal-savings" hidden>You saved ₱0.00</span>
							</div>
						</div>
						<div class="os-summary-total">
							<span>Total due today</span>
							<span data-summary="grand-total">₱0.00</span>
						</div>
					</div>

					<div class="os-checkout-buttons">
						<button type="button" class="btn primary os-checkout-primary" id="osCheckoutBtn">Checkout</button>
					</div>
				</div>
			</aside>
		</div>
	</main>

	<div id="osToast" class="os-toast" aria-live="polite" role="status" hidden></div>
@if(!empty($orderSummary))
	<script>
		document.addEventListener('DOMContentLoaded', () => {
			const summaryData = {!! \Illuminate\Support\Js::from($orderSummary) !!};
			window.sessionStorage.setItem('inkwise-finalstep', JSON.stringify(summaryData));
		});
	</script>
@endif
</body>
</html>
