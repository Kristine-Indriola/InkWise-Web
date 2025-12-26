@extends('layouts.owner.app')

@section('content')
@include('layouts.owner.sidebar')

<link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">

<style>
  .ratings-page-shell {
    padding-right: 0;
    padding-bottom: 0;
    padding-left: 0;
  }

  .ratings-main {
    max-width: var(--owner-content-shell-max, 1440px);
    margin: 0;
    padding: 0 28px 36px 12px;
    width: 100%;
  }

  .ratings-inner {
    max-width: var(--owner-content-shell-max, 1390px);
    width: 100%;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 24px;
  }

  .ratings-summary-card {
    background: #ffffff;
    border-radius: 18px;
    padding: 28px;
    box-shadow: 0 20px 42px rgba(15, 23, 42, 0.12);
    display: grid;
    grid-template-columns: minmax(200px, 260px) 1fr;
    gap: 32px;
  }

  .ratings-summary-card__score {
    display: grid;
    gap: 10px;
    text-align: center;
  }

  .ratings-summary-card__value {
    font-size: 3.4rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1;
  }

  .ratings-summary-card__stars {
    display: inline-flex;
    justify-content: center;
    gap: 6px;
    font-size: 1.4rem;
    color: #f59e0b;
  }

  .ratings-summary-card__meta {
    margin: 0;
    font-size: 0.95rem;
    color: #6b7280;
  }

  .ratings-breakdown {
    display: grid;
    gap: 10px;
  }

  .ratings-breakdown-row {
    display: grid;
    gap: 12px;
    grid-template-columns: 70px 1fr minmax(36px, auto);
    align-items: center;
    font-size: 0.9rem;
    color: #475569;
  }

  .ratings-breakdown-row__bar {
    position: relative;
    height: 10px;
    border-radius: 999px;
    background: rgba(148, 185, 255, 0.2);
    overflow: hidden;
  }

  .ratings-breakdown-row__fill {
    position: absolute;
    inset: 0;
    border-radius: 999px;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.6), rgba(37, 99, 235, 0.8));
    transform-origin: left;
  }

  .ratings-breakdown-row__count {
    font-weight: 600;
    text-align: right;
  }

  .ratings-list {
    display: grid;
    gap: 18px;
  }

  .rating-entry {
    background: #ffffff;
    border-radius: 16px;
    padding: 20px 24px;
    box-shadow: 0 14px 32px rgba(15, 23, 42, 0.10);
    display: grid;
    gap: 14px;
  }

  .rating-entry__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    flex-wrap: wrap;
  }

  .rating-entry__title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: #0f172a;
  }

  .rating-entry__stars {
    display: inline-flex;
    gap: 4px;
    font-size: 1.2rem;
    color: #f59e0b;
  }

  .rating-entry__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 0.88rem;
    color: #64748b;
  }

  .rating-entry__review {
    margin: 0;
    font-size: 0.96rem;
    color: #334155;
    line-height: 1.6;
  }

  .staff-reply {
    background: #f0f9ff;
    border-left: 4px solid #3b82f6;
    padding: 16px;
    margin-top: 16px;
    border-radius: 8px;
  }

  .staff-reply-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
  }

  .staff-reply-label {
    font-weight: 600;
    color: #1e40af;
  }

  .staff-reply-date {
    font-size: 12px;
    color: #6b7280;
  }

  .staff-reply-text {
    color: #1e40af;
    font-style: italic;
    margin: 0;
  }

  .user-role {
    font-size: 11px;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 10px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-left: 6px;
  }

  .user-role.admin {
    background: linear-gradient(135deg, rgba(106, 46, 188, 0.15), rgba(106, 46, 188, 0.25));
    color: #6a2ebc;
    border: 1px solid rgba(106, 46, 188, 0.3);
  }

  .user-role.staff {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(59, 130, 246, 0.25));
    color: #3b82f6;
    border: 1px solid rgba(59, 130, 246, 0.3);
  }

  .staff-id {
    font-size: 11px;
    color: #6b7280;
    font-weight: 500;
    margin-left: 4px;
    font-family: 'Courier New', monospace;
  }

  .ratings-empty {
    text-align: center;
    padding: 48px 24px;
    border-radius: 16px;
    background: rgba(148, 185, 255, 0.08);
    border: 1px dashed rgba(148, 185, 255, 0.26);
    color: #475569;
    font-size: 0.95rem;
  }

  .ratings-pagination {
    display: flex;
    justify-content: flex-end;
  }

  .dark-mode .ratings-summary-card,
  .dark-mode .rating-entry {
    background: #1f2937;
    color: #f9fafb;
    box-shadow: 0 18px 38px rgba(15, 23, 42, 0.45);
  }

  .dark-mode .ratings-summary-card__value,
  .dark-mode .rating-entry__title {
    color: #f9fafb;
  }

  .dark-mode .ratings-summary-card__meta,
  .dark-mode .rating-entry__meta,
  .dark-mode .rating-entry__review {
    color: #cbd5f5;
  }

  .dark-mode .ratings-breakdown-row__bar {
    background: rgba(148, 185, 255, 0.18);
  }

  .dark-mode .ratings-empty {
    background: rgba(59, 130, 246, 0.12);
    border-color: rgba(59, 130, 246, 0.32);
    color: #cbd5f5;
  }

  .dark-mode .staff-reply {
    background: rgba(59, 130, 246, 0.1);
    border-left-color: #60a5fa;
  }

  .dark-mode .staff-reply-label {
    color: #60a5fa;
  }

  .dark-mode .staff-reply-text {
    color: #93c5fd;
  }

  .dark-mode .staff-reply-date {
    color: #9ca3af;
  }

  @media (max-width: 900px) {
    .ratings-summary-card {
      grid-template-columns: 1fr;
      gap: 20px;
      padding: 24px;
    }
  }
</style>

<section class="main-content ratings-page-shell">
  <main class="ratings-main admin-page-shell" role="main">
    <header class="page-header" style="margin-bottom:24px;">
      <div>
        <h1 class="page-title">Customer Ratings</h1>
        <p class="page-subtitle">Consolidated feedback across completed orders.</p>
      </div>
    </header>

    <div class="ratings-inner">
      @php
        $totalReviews = $totalRatings ?? 0;
        $averageScore = $averageRating !== null ? number_format($averageRating, 2) : '—';
        $ratingMap = $ratingCounts ?? [];
      @endphp

      <section class="ratings-summary-card" aria-label="Ratings summary">
        <div class="ratings-summary-card__score" role="presentation">
          <span class="ratings-summary-card__value">{{ $averageScore }}</span>
          <span class="ratings-summary-card__stars" aria-hidden="true">
            @for($i = 1; $i <= 5; $i++)
              <span>{{ $averageRating !== null && $i <= floor($averageRating) ? '★' : '☆' }}</span>
            @endfor
          </span>
          <p class="ratings-summary-card__meta">
            @if($totalReviews === 1)
              1 customer review
            @else
              {{ number_format($totalReviews) }} customer reviews
            @endif
          </p>
        </div>

        <div class="ratings-breakdown" role="list">
          @for($score = 5; $score >= 1; $score--)
            @php
              $count = $ratingMap[$score] ?? 0;
              $percent = $totalReviews > 0 ? round(($count / $totalReviews) * 100) : 0;
            @endphp
            <div class="ratings-breakdown-row" role="listitem">
              <span>{{ $score }} star{{ $score === 1 ? '' : 's' }}</span>
              <div class="ratings-breakdown-row__bar" aria-hidden="true">
                <span class="ratings-breakdown-row__fill" style="width: {{ $percent }}%;"></span>
              </div>
              <span class="ratings-breakdown-row__count">{{ $count }}</span>
            </div>
          @endfor
        </div>
      </section>

      <section class="ratings-list" aria-label="Customer reviews">
        @forelse($ratings as $rating)
          <article class="rating-entry">
            <div class="rating-entry__header">
              <div>
                <h2 class="rating-entry__title">Order {{ $rating['order_number'] ?? ('#' . $rating['id']) }}</h2>
                <div class="rating-entry__meta">
                  <span>{{ $rating['customer_name'] ?? 'Customer' }}</span>
                  <span>Rated {{ optional($rating['submitted_at'])->diffForHumans() ?? '—' }}</span>
                </div>
              </div>
              <div class="rating-entry__stars" aria-label="Rating: {{ $rating['rating'] ?? 'N/A' }} out of 5">
                @php
                  $score = (int) ($rating['rating'] ?? 0);
                @endphp
                @for($i = 1; $i <= 5; $i++)
                  <span>{{ $i <= $score ? '★' : '☆' }}</span>
                @endfor
              </div>
            </div>
            @if(!empty($rating['review']))
              <p class="rating-entry__review">{{ $rating['review'] }}</p>
            @else
              <p class="rating-entry__review" style="color:#94a3b8;">No written review provided.</p>
            @endif

            @if(!empty($rating['staff_reply']))
              <div class="staff-reply">
                <div class="staff-reply-header">
                  <span class="staff-reply-label">Staff Reply</span>
                  <span class="staff-reply-date">{{ $rating['staff_reply_at'] ? \Carbon\Carbon::parse($rating['staff_reply_at'])->format('M d, Y \a\t g:i A') : '' }}</span>
                </div>
                <p class="staff-reply-text">"{{ $rating['staff_reply'] }}"</p>
                @if($rating['staff_reply_by'])
                  <small>Replied by: {{ $rating['staff_reply_by']->name }} @if($rating['staff_reply_by']->staff)<span class="staff-id">(ID: {{ $rating['staff_reply_by']->staff->staff_id }})</span>@endif <span class="user-role {{ $rating['staff_reply_by']->role }}">({{ ucfirst($rating['staff_reply_by']->role) }})</span></small>
                @endif
              </div>
             @endif
          </article>
        @empty
          <div class="ratings-empty">
            No customer ratings have been submitted yet.
          </div>
        @endforelse
      </section>

      @if($ratings->hasPages())
        <div class="ratings-pagination">
          {{ $ratings->links() }}
        </div>
      @endif
    </div>
  </main>
</section>
@endsection
