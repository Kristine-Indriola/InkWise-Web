<section class="summary-grid" aria-label="Staff summary">
    <div class="summary-card">
        <div class="summary-card-header">
            <span class="summary-card-label">Total Staff</span>
            <span class="summary-card-chip accent">Directory</span>
        </div>
        <div class="summary-card-body">
            <span class="summary-card-value">{{ number_format($totalStaff ?? 0) }}</span>
            <span class="summary-card-icon" aria-hidden="true"><i class="fa-solid fa-users"></i></span>
        </div>
        <span class="summary-card-meta">All registered accounts</span>
    </div>
    <div class="summary-card">
        <div class="summary-card-header">
            <span class="summary-card-label">Active</span>
            <span class="summary-card-chip success">Approved</span>
        </div>
        <div class="summary-card-body">
            <span class="summary-card-value">{{ number_format($activeStaff ?? 0) }}</span>
            <span class="summary-card-icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
        </div>
        <span class="summary-card-meta">Currently approved staff</span>
    </div>
    <div class="summary-card">
        <div class="summary-card-header">
            <span class="summary-card-label">Pending</span>
            <span class="summary-card-chip warning">Review</span>
        </div>
        <div class="summary-card-body">
            <span class="summary-card-value">{{ number_format($pendingStaff ?? 0) }}</span>
            <span class="summary-card-icon" aria-hidden="true"><i class="fa-solid fa-clock"></i></span>
        </div>
        <span class="summary-card-meta">Awaiting approval</span>
    </div>
</section>
