@extends('layouts.staffapp')

@section('title', 'Customer Reviews')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
<link rel="stylesheet" href="{{ asset('css/staff-css/dashboard.css') }}">
<style>
  .reviews-list {
    display: grid;
    gap: 16px;
  }

  .review-item {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
    margin-bottom: 20px;
  }

  .review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
  }

  .review-rating {
    display: flex;
    gap: 4px;
  }

  .review-rating i {
    color: #fbbf24;
  }

  .review-date {
    font-size: 14px;
    color: #6b7280;
  }

  .review-comment {
    font-size: 16px;
    line-height: 1.5;
    color: #374151;
    margin-bottom: 12px;
  }

  .review-author {
    font-size: 14px;
    color: #6b7280;
    font-style: italic;
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
  }

  .reply-form {
    margin-top: 16px;
    padding: 16px;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
  }

  .reply-form textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-family: inherit;
    resize: vertical;
  }

  .reply-form .btn {
    margin-top: 8px;
  }

  .no-reviews {
    text-align: center;
    padding: 40px;
    color: #6b7280;
    font-size: 16px;
  }

  .pagination {
    margin-top: 20px;
    display: flex;
    justify-content: center;
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
</style>
@endpush

@section('content')
<main class="materials-page admin-page-shell staff-dashboard-page">
  @if(session('success'))
    <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.1); color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-weight: 600;">
      {{ session('success') }}
    </div>
  @endif

  <header class="page-header">
    <div>
      <h1 class="page-title">Customer Reviews</h1>
      <p class="page-subtitle">View and manage customer feedback and ratings.</p>
    </div>
    <div class="page-header__quick-actions">
      <a href="{{ route('staff.dashboard') }}" class="pill-link">Back to Dashboard</a>
    </div>
  </header>

  <section class="reviews-list">
    @forelse($reviews as $review)
      <div class="review-item">
        <div class="review-header">
          <div class="review-rating">
            @for($i = 1; $i <= 5; $i++)
              <i class="fi fi-{{ $i <= $review->rating ? 'rs' : 'rr' }}-star" aria-hidden="true"></i>
            @endfor
          </div>
          <span class="review-date">{{ $review->submitted_at ? \Carbon\Carbon::parse($review->submitted_at)->format('M d, Y') : \Carbon\Carbon::parse($review->created_at)->format('M d, Y') }}</span>
        </div>
        <p class="review-comment">{{ $review->review }}</p>
        <small class="review-author">
          By {{ $review->customer ? $review->customer->name : 'Anonymous' }}
          @if($review->order)
            for Order #{{ $review->order->order_number }}
          @endif
        </small>

        @if($review->staff_reply)
          <div class="staff-reply">
            <div class="staff-reply-header">
              <span class="staff-reply-label">Staff Reply</span>
              <span class="staff-reply-date">{{ \Carbon\Carbon::parse($review->staff_reply_at)->format('M d, Y \a\t g:i A') }}</span>
            </div>
            <p class="staff-reply-text">"{{ $review->staff_reply }}"</p>
            @if($review->staffReplyBy)
              <small>Replied by: {{ $review->staffReplyBy->name }} @if($review->staffReplyBy->staff)<span class="staff-id">(ID: {{ $review->staffReplyBy->staff->staff_id }})</span>@endif <span class="user-role {{ $review->staffReplyBy->role }}">({{ ucfirst($review->staffReplyBy->role) }})</span></small>
            @endif
          </div>
        @else
          <div class="reply-form">
            <h4>Reply to this review</h4>
            <form action="{{ route('staff.reviews.reply', $review) }}" method="POST">
              @csrf
              <textarea name="staff_reply" rows="3" placeholder="Enter your reply to this customer review..." required></textarea>
              <button type="submit" class="btn btn-primary">Send Reply & Notify Customer</button>
            </form>
          </div>
        @endif
      </div>
    @empty
      <p class="no-reviews">No reviews available.</p>
    @endforelse
  </section>

  {{ $reviews->links() }}
</main>
@endsection