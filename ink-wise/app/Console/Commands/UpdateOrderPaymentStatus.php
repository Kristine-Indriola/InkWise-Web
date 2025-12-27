<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;

class UpdateOrderPaymentStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:update-payment-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update payment status for all orders based on payments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orders = Order::with('payments')->get();

        $bar = $this->output->createProgressBar($orders->count());
        $bar->start();

        foreach ($orders as $order) {
            $totalPaid = $order->totalPaid();
            $grandTotal = (float) $order->total_amount;

            if ($totalPaid >= $grandTotal && $grandTotal > 0) {
                $status = 'paid';
            } elseif ($totalPaid > 0) {
                $status = 'partial';
            } else {
                $status = 'pending';
            }

            $order->update(['payment_status' => $status]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Payment statuses updated successfully.');
    }
}
