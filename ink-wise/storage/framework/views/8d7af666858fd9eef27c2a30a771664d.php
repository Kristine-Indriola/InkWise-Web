<?php $__env->startSection('title', 'Customer Reviews'); ?>

<?php $__env->startPush('styles'); ?>
<link rel="stylesheet" href="<?php echo e(asset('css/admin-css/materials.css')); ?>">
<link rel="stylesheet" href="<?php echo e(asset('css/staff-css/dashboard.css')); ?>">
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
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<main class="materials-page admin-page-shell staff-dashboard-page">
  <?php if(session('success')): ?>
    <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.1); color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-weight: 600;">
      <?php echo e(session('success')); ?>

    </div>
  <?php endif; ?>

  <header class="page-header">
    <div>
      <h1 class="page-title">Customer Reviews</h1>
      <p class="page-subtitle">View and manage customer feedback and ratings.</p>
    </div>
    <div class="page-header__quick-actions">
      <a href="<?php echo e(route('staff.dashboard')); ?>" class="pill-link">Back to Dashboard</a>
    </div>
  </header>

  <section class="reviews-list">
    <?php $__empty_1 = true; $__currentLoopData = $reviews; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $review): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
      <div class="review-item">
        <div class="review-header">
          <div class="review-rating">
            <?php for($i = 1; $i <= 5; $i++): ?>
              <i class="fi fi-<?php echo e($i <= $review->rating ? 'rs' : 'rr'); ?>-star" aria-hidden="true"></i>
            <?php endfor; ?>
          </div>
          <span class="review-date"><?php echo e($review->submitted_at ? \Carbon\Carbon::parse($review->submitted_at)->format('M d, Y') : \Carbon\Carbon::parse($review->created_at)->format('M d, Y')); ?></span>
        </div>
        <p class="review-comment"><?php echo e($review->review); ?></p>
        <small class="review-author">
          By <?php echo e($review->customer ? $review->customer->name : 'Anonymous'); ?>

          <?php if($review->order): ?>
            for Order #<?php echo e($review->order->order_number); ?>

          <?php endif; ?>
        </small>

        <?php if($review->staff_reply): ?>
          <div class="staff-reply">
            <div class="staff-reply-header">
              <span class="staff-reply-label">Staff Reply</span>
              <span class="staff-reply-date"><?php echo e(\Carbon\Carbon::parse($review->staff_reply_at)->format('M d, Y \a\t g:i A')); ?></span>
            </div>
            <p class="staff-reply-text">"<?php echo e($review->staff_reply); ?>"</p>
            <?php if($review->staffReplyBy): ?>
              <small>Replied by: <?php echo e($review->staffReplyBy->name); ?> <?php if($review->staffReplyBy->staff): ?><span class="staff-id">(ID: <?php echo e($review->staffReplyBy->staff->staff_id); ?>)</span><?php endif; ?> <span class="user-role <?php echo e($review->staffReplyBy->role); ?>">(<?php echo e(ucfirst($review->staffReplyBy->role)); ?>)</span></small>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="reply-form">
            <h4>Reply to this review</h4>
            <form action="<?php echo e(route('staff.reviews.reply', $review)); ?>" method="POST">
              <?php echo csrf_field(); ?>
              <textarea name="staff_reply" rows="3" placeholder="Enter your reply to this customer review..." required></textarea>
              <button type="submit" class="btn btn-primary">Send Reply & Notify Customer</button>
            </form>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
      <p class="no-reviews">No reviews available.</p>
    <?php endif; ?>
  </section>

  <?php echo e($reviews->links()); ?>

</main>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.staffapp', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\leanne\xampp\htdocs\InkWise-Web\ink-wise\resources\views/staff/reviews/index.blade.php ENDPATH**/ ?>