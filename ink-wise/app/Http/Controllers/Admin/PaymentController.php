<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Reports\TransactionReportService;
use App\Models\Payment;
use App\Support\Owner\TransactionPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(protected TransactionReportService $transactionReportService)
    {
    }

    public function index(Request $request)
    {
        $allowed = [10, 20, 25, 50, 100];
        $default = 20;
        $perPage = (int) $request->query('per_page', $default);
        if (!in_array($perPage, $allowed, true)) {
            $perPage = $default;
        }

        $statusGroups = TransactionPresenter::statusGroups();

        $statusFilter = Str::lower((string) $request->query('filter', ''));
        if ($statusFilter === 'all') {
            $statusFilter = '';
        }

        if ($statusFilter !== '' && !array_key_exists($statusFilter, $statusGroups)) {
            $statusFilter = '';
        }

        $summaryQuery = $this->transactionReportService->buildTransactionsQuery(
            $request,
            $statusGroups,
            false,
            $statusFilter
        );
        $summary = $this->transactionReportService->summarize($summaryQuery, $statusGroups);

        $transactionsQuery = $this->transactionReportService->buildTransactionsQuery(
            $request,
            $statusGroups,
            true,
            $statusFilter
        );

        $transactions = $this->transactionReportService->paginate($transactionsQuery, $request, $perPage);
        $transformedRows = $this->transactionReportService->transform($transactions->items());

        return view('admin.payments.index', [
            'transactions' => $transactions,
            'summary' => $summary,
            'statusGroups' => $statusGroups,
            'transformedRows' => $transformedRows,
            'filter' => $statusFilter !== '' ? $statusFilter : 'all',
            'perPage' => $perPage,
        ]);
    }

    public function export(Request $request)
    {
        $allowed = [10, 20, 25, 50, 100];
        $default = 20;
        $perPage = (int) $request->query('per_page', $default);
        if (!in_array($perPage, $allowed, true)) {
            $perPage = $default;
        }

        $statusGroups = TransactionPresenter::statusGroups();

        $statusFilter = Str::lower((string) $request->query('filter', ''));
        if ($statusFilter === 'all') {
            $statusFilter = '';
        }

        if ($statusFilter !== '' && !array_key_exists($statusFilter, $statusGroups)) {
            $statusFilter = '';
        }

        $transactionsQuery = $this->transactionReportService->buildTransactionsQuery(
            $request,
            $statusGroups,
            true,
            $statusFilter
        );

        // Get all records for export (no pagination)
        $allTransactions = $this->transactionReportService->transformAll($transactionsQuery);

        // Generate CSV
        $filename = 'payment_transactions_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        $callback = function() use ($allTransactions) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Transaction ID',
                'Order Number',
                'Customer Name',
                'Payment Method',
                'Amount',
                'Balance',
                'Status',
                'Date',
                'Provider',
                'Currency'
            ]);

            // CSV data
            foreach ($allTransactions as $transaction) {
                fputcsv($file, [
                    $transaction['transaction_id'] ?? '',
                    $transaction['order_id'] ?? '',
                    $transaction['customer_name'] ?? '',
                    $transaction['payment_method'] ?? '',
                    $transaction['amount_display'] ?? '',
                    $transaction['remaining_balance_display'] ?? '',
                    $transaction['status_label'] ?? '',
                    $transaction['display_date'] ?? '',
                    $transaction['provider'] ?? '',
                    $transaction['currency'] ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function archived(Request $request)
    {
        $allowed = [10, 20, 25, 50, 100];
        $default = 20;
        $perPage = (int) $request->query('per_page', $default);
        if (!in_array($perPage, $allowed, true)) {
            $perPage = $default;
        }

        $query = Payment::query()
            ->with([
                'customer.user',
                'order.customer.user',
                'order.customerOrder.customer.user',
            ])
            ->where('archived', true)
            ->orderByDesc('recorded_at')
            ->orderByDesc('created_at');

        $transactions = $query->paginate($perPage)->withQueryString();
        $transformedRows = $this->transactionReportService->transform($transactions->items());

        return view('admin.payments.archived', [
            'transactions' => $transactions,
            'transformedRows' => $transformedRows,
        ]);
    }
}