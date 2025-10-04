<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Envelope Options — InkWise</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/customer/orderflow-envelope.css') }}">
    <script src="{{ asset('js/customer/orderflow-envelope.js') }}" defer></script>
</head>
<body>
@php
    try {
        $summaryUrl = route('order.summary');
    } catch (\Throwable $eSummary) {
        $summaryUrl = url('/order/summary');
    }

    try {
        $envelopesApiUrl = route('api.envelopes');
    } catch (\Throwable $eApi) {
        $envelopesApiUrl = url('/api/envelopes');
    }

    try {
        $giveawaysUrl = route('order.giveaways');
    } catch (\Throwable $eGiveaways) {
        $giveawaysUrl = url('/order/giveaways');
    }

    try {
        $finalStepUrl = route('order.finalstep');
    } catch (\Throwable $eFinal) {
        $finalStepUrl = url('/order/final-step');
    }

    try {
        $envelopeSyncUrl = route('order.envelope.store');
    } catch (\Throwable $eSync) {
        $envelopeSyncUrl = url('/order/envelope');
    }
@endphp
    <main
        class="envelope-shell"
        data-summary-url="{{ $summaryUrl }}"
        data-summary-api="{{ $summaryUrl }}"
        data-envelopes-url="{{ $envelopesApiUrl }}"
        data-giveaways-url="{{ $giveawaysUrl }}"
        data-sync-url="{{ $envelopeSyncUrl }}"
    >
        <header class="envelope-header">
            <div class="envelope-header__content">
                <a href="{{ $finalStepUrl }}" class="envelope-header__back" aria-label="Back to final step">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
                    Back to final step
                </a>
                <h1>Pick the perfect envelope</h1>
                <p>Match your invitations with envelopes that complement the style, finish, and tone you’ve created.</p>
            </div>
        </header>

        <div class="envelope-layout">
            <section class="envelope-options">
                <div class="envelope-card">
                    <header class="envelope-card__header">
                        <span class="envelope-card__badge">Envelope catalog</span>
                    </header>
                    <div id="envelopeGrid" class="envelope-grid" aria-live="polite"></div>
                </div>
            </section>

            <aside class="envelope-summary">
                <div class="summary-card">
                    <header class="summary-card__header">
                        <div>
                            <h2>Your envelope</h2>
                            <p>We’ll keep your choice in sync with the rest of your order.</p>
                        </div>
                        <span id="envSelectionBadge" class="summary-card__badge">Pending</span>
                    </header>
                    <div id="envelopeSummaryBody" class="summary-card__body">
                        <p class="summary-empty">Choose an envelope to see the details here.</p>
                    </div>
                </div>

                <div class="summary-actions">
                    <button type="button" class="btn btn-secondary" id="skipEnvelopeBtn" data-summary-url="{{ $giveawaysUrl }}">Skip envelopes</button>
                    <button type="button" class="btn btn-primary" id="envContinueBtn" data-summary-url="{{ $giveawaysUrl }}" disabled>Continue to giveaways</button>
                </div>
                <p class="summary-note">You can revisit this step before finalizing your order. Your progress is saved automatically.</p>
            </aside>
        </div>

        <div id="envToast" class="envelope-toast" aria-live="polite" hidden></div>
    </main>
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
