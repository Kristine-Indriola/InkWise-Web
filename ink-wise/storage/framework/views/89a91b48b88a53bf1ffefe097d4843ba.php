<?php $__env->startSection('title', 'Payment Transactions'); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .payments-shell {
        max-width: 1600px;
        margin: 0 auto;
        padding: 32px 16px 72px;
    }

    .payments-shell > * + * {
        margin-top: 24px;
    }

    @media (max-width: 600px) {
        .payments-shell {
            padding: 24px 12px 64px;
        }
    }

    .payments-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 24px;
    }

    @media (max-width: 720px) {
        .payments-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }

    .payments-header h1 {
        font-size: 28px;
        font-weight: 700;
        margin: 0;
        color: #111827;
    }

    .filter-actions {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .filter-actions-row {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    @media (max-width: 640px) {
        .filter-actions {
            align-items: stretch;
            gap: 16px;
        }

        .filter-actions-row {
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
        }

        .filter-actions-row .btn-export {
            justify-content: center;
        }
    }

    .btn-export {
        background: linear-gradient(135deg, #059669, #047857);
        color: white;
        border: none;
        padding: 10px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        text-decoration: none;
    }

    .btn-export:hover {
        background: linear-gradient(135deg, #047857, #065f46);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        transform: translateY(-1px);
    }

    .btn-export i {
        font-size: 14px;
    }

    .payments-filters-section {
        margin-bottom: 24px;
        padding: 24px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .payments-filters {
        display: flex;
        align-items: flex-start;
        gap: 24px;
        flex-wrap: wrap;
    }

    @media (max-width: 1024px) {
        .payments-filters {
            flex-direction: column;
            align-items: stretch;
            gap: 16px;
        }
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
        flex: 1;
    }

    .filter-group label {
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .filter-group label i {
        color: #6b7280;
        font-size: 14px;
    }

    .filter-input {
        padding: 10px 12px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        color: #374151;
        background: white;
        transition: all 0.2s ease;
    }

    .filter-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .filter-input::placeholder {
        color: #9ca3af;
    }

    .date-range-group {
        display: flex;
        gap: 8px;
        align-items: flex-end;
    }

    @media (max-width: 640px) {
        .date-range-group {
            flex-direction: column;
            gap: 12px;
        }
    }

    .date-input-wrapper {
        display: flex;
        flex-direction: column;
        gap: 8px;
        flex: 1;
    }

    .date-input-wrapper label {
        font-size: 12px;
        font-weight: 500;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .filter-actions {
        display: flex;
        align-items: flex-end;
        gap: 8px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #2563eb, #1e40af);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        transform: translateY(-1px);
    }

    .btn-secondary {
        background: white;
        color: #374151;
        border: 2px solid #e5e7eb;
        padding: 10px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-secondary:hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }

    .btn-icon-only {
        width: 40px;
        height: 40px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .payments-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
        display: flex;
        align-items: center;
        gap: 16px;
        text-decoration: none;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12);
        border-color: #3b82f6;
    }

    .stat-card.active {
        border-color: #3b82f6;
        border-width: 2px;
        background: #eff6ff;
    }

    .stat-card__icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .stat-card__icon--total {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
    }

    .stat-card__icon--paid {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .stat-card__icon--pending {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
    }

    .stat-card__icon--overdue {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

    .stat-card__icon--partial {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
    }

    .stat-card__content h3 {
        font-size: 24px;
        font-weight: 700;
        margin: 0 0 4px;
        color: #111827;
    }

    .stat-card__content p {
        font-size: 14px;
        color: #6b7280;
        margin: 0;
    }

    .payments-table-container {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
    }

    .payments-table {
        width: 100%;
        border-collapse: collapse;
    }

    .payments-table th {
        background: #f9fafb;
        padding: 16px;
        text-align: left;
        font-weight: 600;
        font-size: 14px;
        color: #374151;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
    }

    .payments-table td {
        padding: 16px;
        border-bottom: 1px solid #f3f4f6;
        font-size: 14px;
        color: #374151;
    }

    .payments-table tbody tr:hover {
        background: #f9fafb;
    }

    .order-number {
        font-weight: 600;
        color: #111827;
        text-decoration: none;
    }

    .order-number:hover {
        text-decoration: underline;
    }

    .customer-name {
        font-weight: 500;
        color: #374151;
    }

    .payment-amount {
        font-weight: 600;
        font-size: 16px;
        color: #111827;
    }

    .payment-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .payment-status--paid {
        background: #dcfce7;
        color: #166534;
    }

    .payment-status--pending {
        background: #fef3c7;
        color: #92400e;
    }

    .payment-status--partial {
        background: #ede9fe;
        color: #7c3aed;
    }

    .payment-status--failed {
        background: #fee2e2;
        color: #991b1b;
    }

    .payment-status--refunded {
        background: #e0f2fe;
        color: #0c4a6e;
    }

    .actions-cell {
        text-align: right;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-outline {
        background: transparent;
        border-color: #d1d5db;
        color: #374151;
    }

    .btn-outline:hover {
        background: #f9fafb;
        border-color: #9ca3af;
    }

    .btn-sm {
        padding: 6px 10px;
        font-size: 12px;
    }

    .btn-icon {
        width: 36px;
        height: 36px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .pagination-container {
        display: flex;
        justify-content: center;
        margin-top: 24px;
    }

    .pagination {
        display: flex;
        gap: 2px;
        align-items: center;
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .pagination a,
    .pagination span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        text-decoration: none;
        border: 1px solid #d1d5db;
        background: white;
        color: #374151;
        transition: all 0.2s ease;
    }

    .pagination a svg,
    .pagination span svg {
        width: 12px;
        height: 12px;
    }

    .pagination .active {
        background: #3b82f6;
        border-color: #3b82f6;
        color: white;
    }

    .pagination a:hover:not(.active) {
        background: #f3f4f6;
        border-color: #9ca3af;
    }

    .pagination-disabled {
        opacity: 0.4;
        pointer-events: none;
    }

    .pagination-ellipsis {
        border: none;
        background: transparent;
        color: #9ca3af;
        cursor: default;
    }

    .empty-state {
        text-align: center;
        padding: 48px 24px;
        color: #6b7280;
    }

    .empty-state h3 {
        font-size: 18px;
        font-weight: 600;
        margin: 0 0 8px;
        color: #374151;
    }

    .empty-state p {
        font-size: 14px;
        margin: 0;
    }

    @media (max-width: 768px) {
        .payments-table {
            font-size: 12px;
        }

        .payments-table th,
        .payments-table td {
            padding: 12px 8px;
        }

        .stat-card {
            padding: 16px;
        }

        .stat-card__icon {
            width: 40px;
            height: 40px;
            font-size: 16px;
        }

        .stat-card__content h3 {
            font-size: 20px;
        }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php
    $totalTransactions = $summary['total_transactions'] ?? 0;
    $totalAmount = $summary['total_amount'] ?? 0;
    $paidTransactions = $summary['paid_count'] ?? 0;
    $paidAmount = $summary['paid_amount'] ?? 0;
    $pendingTransactions = $summary['pending_count'] ?? 0;
    $pendingAmount = $summary['pending_amount'] ?? 0;
    $partialTransactions = $summary['partial_count'] ?? 0;
    $partialAmount = $summary['partial_amount'] ?? 0;
    $failedTransactions = $summary['failed_count'] ?? 0;
    $failedAmount = $summary['failed_amount'] ?? 0;
    $currentFilter = $filter !== 'all' ? $filter : null;
    $baseQuery = request()->except(['page', 'filter']);
    $searchQuery = request('search', '');
    $dateFrom = request('date_from', '');
    $dateTo = request('date_to', '');
?>

<main class="payments-shell">
    <header class="payments-header">
        <h1>Payment Transactions</h1>
    </header>

    <section class="payments-stats">
        <a href="<?php echo e(route('admin.payments.index', $baseQuery)); ?>" class="stat-card <?php echo e(!$currentFilter ? 'active' : ''); ?>">
            <div class="stat-card__icon stat-card__icon--total">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-card__content">
                <h3><?php echo e(number_format($totalTransactions)); ?></h3>
                <p>Total Transactions · ₱<?php echo e(number_format($totalAmount, 2)); ?></p>
            </div>
        </a>

        <a href="<?php echo e(route('admin.payments.index', array_merge($baseQuery, ['filter' => 'paid']))); ?>" class="stat-card <?php echo e($currentFilter === 'paid' ? 'active' : ''); ?>">
            <div class="stat-card__icon stat-card__icon--paid">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-card__content">
                <h3><?php echo e(number_format($paidTransactions)); ?></h3>
                <p>Paid Transactions · ₱<?php echo e(number_format($paidAmount, 2)); ?></p>
            </div>
        </a>

        <a href="<?php echo e(route('admin.payments.index', array_merge($baseQuery, ['filter' => 'pending']))); ?>" class="stat-card <?php echo e($currentFilter === 'pending' ? 'active' : ''); ?>">
            <div class="stat-card__icon stat-card__icon--pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-card__content">
                <h3><?php echo e(number_format($pendingTransactions)); ?></h3>
                <p>Pending Transactions · ₱<?php echo e(number_format($pendingAmount, 2)); ?></p>
            </div>
        </a>

        <a href="<?php echo e(route('admin.payments.index', array_merge($baseQuery, ['filter' => 'partial']))); ?>" class="stat-card <?php echo e($currentFilter === 'partial' ? 'active' : ''); ?>">
            <div class="stat-card__icon stat-card__icon--partial">
                <i class="fas fa-coins"></i>
            </div>
            <div class="stat-card__content">
                <h3><?php echo e(number_format($partialTransactions)); ?></h3>
                <p>Partial Transactions · ₱<?php echo e(number_format($partialAmount, 2)); ?></p>
            </div>
        </a>

        <a href="<?php echo e(route('admin.payments.index', array_merge($baseQuery, ['filter' => 'failed']))); ?>" class="stat-card <?php echo e($currentFilter === 'failed' ? 'active' : ''); ?>">
            <div class="stat-card__icon stat-card__icon--overdue">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-card__content">
                <h3><?php echo e(number_format($failedTransactions)); ?></h3>
                <p>Failed Transactions · ₱<?php echo e(number_format($failedAmount, 2)); ?></p>
            </div>
        </a>
    </section>

    <section class="payments-filters-section">
        <form method="GET" class="payments-filters" action="<?php echo e(route('admin.payments.index')); ?>">
            <?php if($currentFilter): ?>
                <input type="hidden" name="filter" value="<?php echo e($currentFilter); ?>">
            <?php endif; ?>

            <div class="filter-group">
                <label for="search">
                    <i class="fas fa-search"></i>
                    Search Transactions
                </label>
                <input
                    type="text"
                    id="search"
                    name="search"
                    value="<?php echo e($searchQuery); ?>"
                    placeholder="Order #, Transaction ID, or Customer name..."
                    class="filter-input"
                >
            </div>

            <div class="date-range-group">
                <div class="date-input-wrapper">
                    <label for="date_from">From Date</label>
                    <input
                        type="date"
                        id="date_from"
                        name="date_from"
                        value="<?php echo e($dateFrom); ?>"
                        class="filter-input"
                    >
                </div>
                <div class="date-input-wrapper">
                    <label for="date_to">To Date</label>
                    <input
                        type="date"
                        id="date_to"
                        name="date_to"
                        value="<?php echo e($dateTo); ?>"
                        class="filter-input"
                    >
                </div>
            </div>

            <div class="filter-actions">
                <div class="filter-actions-row">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-filter"></i>
                        Apply Filters
                    </button>
                    <?php if($searchQuery || $dateFrom || $dateTo): ?>
                        <a href="<?php echo e(route('admin.payments.index', $currentFilter ? ['filter' => $currentFilter] : [])); ?>" class="btn-secondary btn-icon-only" title="Clear Filters">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="filter-actions-row">
                    <a href="<?php echo e(route('admin.payments.export', request()->query())); ?>" class="btn-export" title="Export to CSV">
                        <i class="fas fa-download"></i>
                        Export CSV
                    </a>
                    <a href="<?php echo e(route('admin.payments.archived')); ?>" class="btn-secondary" title="Show archived payments">
                        <i class="fas fa-archive"></i>
                        Archived
                    </a>
                </div>
            </div>
        </form>
    </section>

    <section class="payments-table-container">
        <?php if($transactions->count() > 0): ?>
            <table class="payments-table">
                <thead>
                    <tr>
                        <th>Transaction</th>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Balance</th>
                        <th>Payment Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $transformedRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $payment = $row['raw'];
                            $order = $payment?->order;
                            $transactionIdDisplay = $row['transaction_id'] ?? '—';
                            $orderDisplay = $order?->order_number ?? $row['order_id'] ?? '—';
                            $customerName = $row['customer_name'] ?? '—';
                            $method = $row['payment_method'] ?? '—';
                            $dateDisplay = $row['display_date'] ?? '—';
                            $amountDisplay = $row['amount_display'] ?? '—';
                            $balanceDisplay = $row['remaining_balance_display'] ?? '—';
                            $statusLabel = $row['status_label'] ?? '—';
                            $statusClass = $row['status_class'] ?? '';
                            $statusRaw = $row['status_raw'] ?? '';

                            if ($statusClass === 'stock-ok') {
                                $badgeClass = 'payment-status--paid';
                            } elseif ($statusClass === 'stock-critical') {
                                $badgeClass = 'payment-status--failed';
                            } elseif (\Illuminate\Support\Str::contains($statusRaw, 'partial')) {
                                $badgeClass = 'payment-status--partial';
                            } else {
                                $badgeClass = 'payment-status--pending';
                            }
                        ?>
                        <tr>
                            <td>
                                <span class="order-number"><?php echo e($transactionIdDisplay); ?></span>
                            </td>
                            <td>
                                <?php if($order): ?>
                                    <a href="<?php echo e(route('admin.ordersummary.show', ['order' => $order->id])); ?>" class="order-number">
                                        #<?php echo e($order->order_number); ?>

                                    </a>
                                <?php else: ?>
                                    <span class="order-number"><?php echo e($orderDisplay); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="customer-name"><?php echo e($customerName); ?></span>
                            </td>
                            <td><?php echo e($method); ?></td>
                            <td><?php echo e($dateDisplay); ?></td>
                            <td class="payment-amount"><?php echo e($amountDisplay); ?></td>
                            <td class="payment-amount"><?php echo e($balanceDisplay); ?></td>
                            <td>
                                <span class="payment-status <?php echo e($badgeClass); ?>">
                                    <?php echo e($statusLabel); ?>

                                </span>
                            </td>
                            <td class="actions-cell">
                                <?php if($order): ?>
                                    <a href="<?php echo e(route('admin.ordersummary.show', ['order' => $order->id])); ?>" class="btn btn-outline btn-sm" title="View Order">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?php echo e(route('admin.orders.payment.edit', ['order' => $order->id])); ?>" class="btn btn-outline btn-sm" title="Manage Payment">
                                        <i class="fas fa-credit-card"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <h3>No payment transactions found</h3>
                <p>Try adjusting your filters to find a different set of transactions.</p>
            </div>
        <?php endif; ?>
    </section>

    <?php if($transactions->hasPages()): ?>
        <div class="pagination-container">
            <?php echo e($transactions->appends(request()->query())->links('admin.payments.pagination')); ?>

        </div>
    <?php endif; ?>
</main>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-submit form when status filter is clicked (for stat cards)
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach(card => {
            card.addEventListener('click', function(e) {
                // Allow normal link behavior
            });
        });

        // Enhanced search functionality
        const searchInput = document.getElementById('search');
        const dateFromInput = document.getElementById('date_from');
        const dateToInput = document.getElementById('date_to');
        const filterForm = document.querySelector('.payments-filters');

        // Auto-submit on Enter key in search
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    filterForm.submit();
                }
            });
        }

        // Date validation
        function validateDates() {
            const fromDate = dateFromInput?.value;
            const toDate = dateToInput?.value;

            if (fromDate && toDate && fromDate > toDate) {
                alert('From date cannot be later than To date');
                dateToInput.value = '';
                return false;
            }
            return true;
        }

        if (dateFromInput && dateToInput) {
            dateFromInput.addEventListener('change', validateDates);
            dateToInput.addEventListener('change', validateDates);
        }

        // Clear filters functionality
        const clearFiltersBtn = document.querySelector('.btn-secondary[title="Clear Filters"]');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function(e) {
                // Clear form inputs
                if (searchInput) searchInput.value = '';
                if (dateFromInput) dateFromInput.value = '';
                if (dateToInput) dateToInput.value = '';
            });
        }

        // Add loading state to filter button
        const filterBtn = document.querySelector('.btn-primary');
        if (filterBtn && filterForm) {
            filterForm.addEventListener('submit', function() {
                filterBtn.disabled = true;
                filterBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Filtering...';
            });
        }

        // Quick date range buttons (optional enhancement)
        function setDateRange(days) {
            const today = new Date();
            const fromDate = new Date(today);
            fromDate.setDate(today.getDate() - days);

            if (dateFromInput) {
                dateFromInput.value = fromDate.toISOString().split('T')[0];
            }
            if (dateToInput) {
                dateToInput.value = today.toISOString().split('T')[0];
            }
        }

        // You can add quick date buttons in the future if needed
        // Example: Last 7 days, Last 30 days, etc.
    });
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\leanne\xampp\htdocs\InkWise-Web\ink-wise\resources\views/admin/payments/index.blade.php ENDPATH**/ ?>