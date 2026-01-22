<?php $__env->startSection('title', 'Customer Reviews'); ?>

<?php $__env->startPush('styles'); ?>
<style>
  .reviews-container {
    max-width: 1200px;
    margin: 0 auto;
  }

  .reviews-header {
    background: linear-gradient(135deg, var(--admin-surface) 0%, rgba(148, 185, 255, 0.05) 100%);
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(148, 185, 255, 0.15);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  }

  .reviews-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
  }

  .stat-card {
    background: rgba(255, 255, 255, 0.8);
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    border: 1px solid rgba(148, 185, 255, 0.2);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
  }

  .stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--admin-accent);
    margin-bottom: 0.25rem;
  }

  .stat-label {
    font-size: 0.85rem;
    color: var(--admin-text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 600;
  }

  .reviews-list {
    display: grid;
    gap: 20px;
  }

  .review-item {
    background: var(--admin-surface);
    border: 1px solid rgba(148, 185, 255, 0.18);
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
  }

  .review-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #6a2ebc, #3cd5c8);
  }

  .review-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
    border-color: rgba(148, 185, 255, 0.3);
  }

  .review-item.needs-reply {
    border-left: 5px solid #f59e0b;
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.02), rgba(245, 158, 11, 0.05));
  }

  .review-item.needs-reply::before {
    background: linear-gradient(90deg, #f59e0b, #fbbf24);
  }

  .review-item.replied {
    border-left: 5px solid #10b981;
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.02), rgba(16, 185, 129, 0.05));
  }

  .review-item.replied::before {
    background: linear-gradient(90deg, #10b981, #34d399);
  }

  .review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 12px;
  }

  .review-rating {
    display: flex;
    align-items: center;
    gap: 12px;
    background: rgba(251, 191, 36, 0.1);
    padding: 8px 12px;
    border-radius: 20px;
    border: 1px solid rgba(251, 191, 36, 0.2);
  }

  .rating-stars {
    display: flex;
    gap: 4px;
    align-items: center;
  }

  .rating-stars i {
    color: #fbbf24;
    font-size: 20px;
    transition: transform 0.2s ease;
    filter: drop-shadow(0 1px 2px rgba(251, 191, 36, 0.3));
  }

  .rating-stars i:hover {
    transform: scale(1.1);
  }

  .rating-number {
    display: flex;
    align-items: baseline;
    gap: 2px;
    font-weight: 700;
    color: #92400e;
  }

  .rating-value {
    font-size: 18px;
    color: #fbbf24;
    text-shadow: 0 1px 2px rgba(251, 191, 36, 0.3);
  }

  .rating-max {
    font-size: 12px;
    color: var(--admin-text-secondary);
    font-weight: 600;
  }

  .review-meta {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
  }

  .review-date {
    font-size: 14px;
    color: var(--admin-text-secondary);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .review-date::before {
    content: '📅';
    font-size: 12px;
  }

  .review-status {
    font-size: 10px;
    font-weight: 700;
    padding: 6px 12px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .review-status::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
  }

  .review-status.replied {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.25));
    color: #065f46;
    border: 1px solid rgba(16, 185, 129, 0.3);
  }

  .review-status.replied::before {
    background: #10b981;
  }

  .review-status.needs-reply {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(245, 158, 11, 0.25));
    color: #92400e;
    border: 1px solid rgba(245, 158, 11, 0.3);
    animation: pulse 2s infinite;
  }

  .review-status.needs-reply::before {
    background: #f59e0b;
  }

  @keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
  }

  .review-content {
    margin-bottom: 16px;
  }

  .review-comment {
    font-size: 16px;
    line-height: 1.6;
    color: var(--admin-text-primary);
    margin-bottom: 12px;
    font-style: italic;
    position: relative;
    padding-left: 20px;
  }

  .review-comment::before {
    content: '"';
    font-size: 3rem;
    color: rgba(148, 185, 255, 0.3);
    position: absolute;
    left: -10px;
    top: -10px;
    font-family: serif;
  }

  .review-author {
    font-size: 14px;
    color: var(--admin-text-secondary);
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  .review-author::before {
    content: '👤';
    font-size: 12px;
  }

  .order-link {
    color: var(--admin-accent);
    font-weight: 600;
    text-decoration: none;
    padding: 4px 8px;
    background: rgba(106, 46, 188, 0.1);
    border-radius: 6px;
    transition: all 0.2s ease;
  }

  .order-link:hover {
    background: rgba(106, 46, 188, 0.2);
    color: var(--admin-accent-strong);
  }

  .staff-reply {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.08), rgba(16, 185, 129, 0.15));
    border: 1px solid rgba(16, 185, 129, 0.2);
    border-left: 5px solid #10b981;
    padding: 20px;
    margin-top: 20px;
    border-radius: 12px;
    position: relative;
  }

  .staff-reply::before {
    content: '💬';
    position: absolute;
    top: 16px;
    right: 16px;
    font-size: 18px;
    opacity: 0.6;
  }

  .staff-reply-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    flex-wrap: wrap;
    gap: 8px;
  }

  .staff-reply-label {
    font-weight: 700;
    color: #065f46;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .staff-reply-label::before {
    content: '✓';
    color: #10b981;
    font-weight: bold;
  }

  .staff-reply-date {
    font-size: 12px;
    color: var(--admin-text-secondary);
    font-weight: 500;
  }

  .staff-reply-text {
    color: var(--admin-text-primary);
    font-size: 15px;
    line-height: 1.5;
    margin-bottom: 8px;
    position: relative;
    padding-left: 16px;
  }

  .staff-reply-text::before {
    content: '"';
    font-size: 2rem;
    color: rgba(16, 185, 129, 0.4);
    position: absolute;
    left: -4px;
    top: -8px;
    font-family: serif;
  }

  .reply-form {
    margin-top: 20px;
    padding: 20px;
    background: linear-gradient(135deg, rgba(148, 185, 255, 0.08), rgba(148, 185, 255, 0.15));
    border-radius: 12px;
    border: 1px solid rgba(148, 185, 255, 0.2);
    position: relative;
  }

  .reply-form::before {
    content: '✍️';
    position: absolute;
    top: 16px;
    right: 16px;
    font-size: 18px;
    opacity: 0.6;
  }

  .reply-form h4 {
    margin: 0 0 16px 0;
    color: var(--admin-text-primary);
    font-size: 16px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .reply-form h4::before {
    content: '💭';
    font-size: 14px;
  }

  .reply-form textarea {
    width: 100%;
    padding: 16px;
    border: 2px solid rgba(148, 185, 255, 0.24);
    border-radius: 10px;
    font-family: inherit;
    resize: vertical;
    background: var(--admin-surface);
    color: var(--admin-text-primary);
    font-size: 14px;
    line-height: 1.5;
    transition: all 0.3s ease;
    min-height: 100px;
  }

  .reply-form textarea:focus {
    outline: none;
    border-color: var(--admin-accent);
    box-shadow: 0 0 0 3px rgba(106, 46, 188, 0.1);
    background: rgba(106, 46, 188, 0.02);
  }

  .reply-form textarea::placeholder {
    color: var(--admin-text-secondary);
    opacity: 0.7;
  }

  .reply-form .btn {
    margin-top: 12px;
    padding: 12px 24px;
    background: linear-gradient(135deg, #6a2ebc, #3cd5c8);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  .reply-form .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(106, 46, 188, 0.3);
  }

  .reply-form .btn:active {
    transform: translateY(0);
  }

  .reply-form .btn::before {
    content: '📤';
    font-size: 14px;
  }

  .no-reviews {
    text-align: center;
    padding: 60px 20px;
    color: var(--admin-text-secondary);
    font-size: 18px;
    background: var(--admin-surface);
    border-radius: 16px;
    border: 2px dashed rgba(148, 185, 255, 0.3);
    margin: 2rem 0;
  }

  .no-reviews::before {
    content: '📝';
    font-size: 3rem;
    display: block;
    margin-bottom: 16px;
    opacity: 0.5;
  }

  .pagination {
    margin-top: 32px;
    display: flex;
    justify-content: center;
    padding: 20px;
  }

  .pagination .page-link {
    border-radius: 8px;
    border: 1px solid rgba(148, 185, 255, 0.3);
    color: var(--admin-text-primary);
    padding: 8px 16px;
    margin: 0 2px;
    transition: all 0.2s ease;
  }

  .pagination .page-link:hover {
    background: var(--admin-accent);
    color: white;
    border-color: var(--admin-accent);
  }

  .pagination .active .page-link {
    background: linear-gradient(135deg, #6a2ebc, #3cd5c8);
    border-color: transparent;
    color: white;
  }

  /* Success alert styling */
  .alert-success {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.25));
    color: #065f46;
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    font-weight: 600;
    border: 1px solid rgba(16, 185, 129, 0.3);
    display: flex;
    align-items: center;
    gap: 12px;
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
    color: var(--admin-text-secondary);
    font-weight: 500;
    margin-left: 4px;
    font-family: 'Courier New', monospace;
  }

  /* Responsive design */
  @media (max-width: 768px) {
    .reviews-header {
      padding: 1.5rem;
    }

    .review-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 8px;
    }

    .review-meta {
      width: 100%;
      justify-content: space-between;
    }

    .staff-reply-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 4px;
    }

    .reviews-stats {
      grid-template-columns: repeat(2, 1fr);
      gap: 1rem;
    }
  }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<main class="admin-page-shell">
  <div class="reviews-container">
    <header class="reviews-header">
      <div>
        <h1 class="page-title">Customer Reviews</h1>
        <p class="page-subtitle">View and manage customer feedback and ratings.</p>
      </div>
      <div class="page-header__quick-actions">
        <a href="<?php echo e(route('admin.dashboard')); ?>" class="pill-link">← Back to Dashboard</a>
      </div>
    </header>

    <?php if(session('success')): ?>
      <div class="alert alert-success">
        <?php echo e(session('success')); ?>

      </div>
    <?php endif; ?>

    <section class="reviews-list">
      <?php $__empty_1 = true; $__currentLoopData = $reviews; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $review): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="review-item <?php echo e($review->staff_reply ? 'replied' : 'needs-reply'); ?>">
          <div class="review-header">
            <div class="review-rating">
              <div class="rating-stars">
                <?php for($i = 1; $i <= 5; $i++): ?>
                  <i class="fi fi-<?php echo e($i <= $review->rating ? 'rs' : 'rr'); ?>-star" aria-hidden="true"></i>
                <?php endfor; ?>
              </div>
              <div class="rating-number">
                <span class="rating-value"><?php echo e($review->rating); ?></span>
                <span class="rating-max">/5</span>
              </div>
            </div>
            <div class="review-meta">
              <span class="review-date"><?php echo e($review->submitted_at ? \Carbon\Carbon::parse($review->submitted_at)->format('M d, Y') : \Carbon\Carbon::parse($review->created_at)->format('M d, Y')); ?></span>
              <?php if($review->staff_reply): ?>
                <span class="review-status replied">✓ Replied</span>
              <?php else: ?>
                <span class="review-status needs-reply">⚡ Needs Reply</span>
              <?php endif; ?>
            </div>
          </div>

          <div class="review-content">
            <p class="review-comment"><?php echo e($review->review); ?></p>
            <small class="review-author">
              By <?php echo e($review->customer ? $review->customer->name : 'Anonymous'); ?>

              <?php if($review->order): ?>
                • <a href="<?php echo e(route('admin.ordersummary.show', ['order' => $review->order_id])); ?>" class="order-link">Order #<?php echo e($review->order->order_number); ?></a>
              <?php endif; ?>
            </small>
          </div>

          <?php if($review->staff_reply): ?>
            <div class="staff-reply">
              <div class="staff-reply-header">
                <span class="staff-reply-label">Staff Reply</span>
                <span class="staff-reply-date"><?php echo e(\Carbon\Carbon::parse($review->staff_reply_at)->format('M d, Y \a\t g:i A')); ?></span>
              </div>
              <p class="staff-reply-text"><?php echo e($review->staff_reply); ?></p>
              <?php if($review->staffReplyBy): ?>
                <small style="color: var(--admin-text-secondary); font-weight: 500;">
                  Replied by: <?php echo e($review->staffReplyBy->name); ?>

                  <?php if($review->staffReplyBy->staff): ?>
                    <span class="staff-id">(ID: <?php echo e($review->staffReplyBy->staff->staff_id); ?>)</span>
                  <?php endif; ?>
                  <span class="user-role <?php echo e($review->staffReplyBy->role); ?>">
                    (<?php echo e(ucfirst($review->staffReplyBy->role)); ?>)
                  </span>
                </small>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <div class="reply-form">
              <h4>Reply to this review</h4>
              <form action="<?php echo e(route('admin.reviews.reply', $review)); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <textarea name="staff_reply" rows="4" placeholder="Enter your thoughtful reply to this customer review..." required></textarea>
                <button type="submit" class="btn btn-primary">Send Reply & Notify Customer</button>
              </form>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <p class="no-reviews">No reviews available yet.<br>Customer feedback will appear here once orders are completed.</p>
      <?php endif; ?>
    </section>

    <?php if($reviews->hasPages()): ?>
      <div class="pagination">
        <?php echo e($reviews->links()); ?>

      </div>
    <?php endif; ?>
  </div>
</main>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\leanne\xampp\htdocs\InkWise-Web\ink-wise\resources\views/admin/reviews/index.blade.php ENDPATH**/ ?>