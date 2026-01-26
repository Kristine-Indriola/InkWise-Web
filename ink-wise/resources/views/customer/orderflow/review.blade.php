<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<title>Design Review &mdash; InkWise</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="{{ asset('css/customer/review.css') }}">
</head>
<body>
@php
	// Prepare data for React component
	$reviewData = [
		'product' => $product ?? null,
		'finalArtworkFront' => $finalArtworkFront ?? $frontImage ?? null,
		'finalArtworkBack' => $finalArtworkBack ?? $backImage ?? null,
		'orderSummary' => $orderSummary ?? [],
		'customerReview' => $customerReview ?? null,
		'lastEditedAt' => $lastEditedAt ?? null,
		'continueHref' => $continueHref ?? route('order.finalstep'),
		'editHref' => $editHref ?? route('design.edit'),
	];
@endphp

<script>
	window.reviewData = @json($reviewData);
</script>

<div id="review-react-root"></div>

    <script type="module" src="{{ asset('build/assets/Review-ddcrIrrV.js') }}"></script>
</body>
</html>
