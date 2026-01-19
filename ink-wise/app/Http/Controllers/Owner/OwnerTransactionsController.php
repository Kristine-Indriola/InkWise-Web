<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\Reports\TransactionReportService;
use App\Models\Payment;
use App\Support\Owner\TransactionPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OwnerTransactionsController extends Controller
{
    public function __construct(protected TransactionReportService $transactionReportService)
    {
    }

    public function index(Request $request)
    {
        $statusGroups = TransactionPresenter::statusGroups();

        $summaryQuery = $this->transactionReportService->buildTransactionsQuery($request, $statusGroups, false);
        $summary = $this->transactionReportService->summarize($summaryQuery, $statusGroups);

        $transactionsQuery = $this->transactionReportService->buildTransactionsQuery($request, $statusGroups);

        $transactions = $this->transactionReportService->paginate($transactionsQuery, $request, 25);

        $transformedRows = $this->transactionReportService->transform($transactions->items());

        return view('owner.transactions-view', [
            'transactions' => $transactions,
            'summary' => $summary,
            'statusGroups' => $statusGroups,
            'transformedRows' => $transformedRows,
        ]);
    }

    public function export(Request $request)
    {
        $format = Str::lower((string) $request->query('format', 'csv'));
        $statusGroups = TransactionPresenter::statusGroups();

        $query = $this->transactionReportService->buildTransactionsQuery($request, $statusGroups);
        $transformed = $this->transactionReportService->transformAll($query);

        $exportRows = $transformed->map(function (array $row) {
            return [
                'transaction_id' => $row['transaction_id'],
                'order_id' => $row['order_id'],
                'customer' => $row['customer_name'],
                'payment_method' => $row['payment_method'],
                'date' => $row['display_date'],
                'amount' => $row['amount_display'],
                'remaining_balance' => $row['remaining_balance_display'] ?? '—',
                'status' => $row['status_label'],
            ];
        });

        if ($format !== 'csv') {
            abort(400, 'Unsupported export format.');
        }

        $fileName = 'transactions-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $callback = function () use ($exportRows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Transaction ID', 'Order ID', 'Customer', 'Payment Method', 'Date', 'Amount', 'Remaining Balance', 'Status']);

            foreach ($exportRows as $row) {
                fputcsv($handle, array_values($row));
            }

            fclose($handle);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    public function archive(Request $request, Payment $payment)
    {
        // Only archive payments that exist; owner middleware controls access.
        $payment->archived = true;
        $payment->save();

        return back()->with('success', 'Payment transaction archived.');
    }

    public function archived(Request $request)
    {
        $query = Payment::query()
            ->with([
                'customer.user',
                'order.customer.user',
                'order.customerOrder.customer.user',
            ])
            ->where('archived', true)
            ->orderByDesc('recorded_at')
            ->orderByDesc('created_at');

        $transactions = $query->paginate(25)->withQueryString();
        $transformedRows = $this->transactionReportService->transform($transactions->items());

        return view('owner.transactions-archived', [
            'transactions' => $transactions,
            'transformedRows' => $transformedRows,
        ]);
    }
}
