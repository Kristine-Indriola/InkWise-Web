<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\Order;

class PaymentObserver
{
    public function created(Payment $payment)
    {
        $this->updateOrderPaymentStatus($payment->order);
    }

    public function updated(Payment $payment)
    {
        $this->updateOrderPaymentStatus($payment->order);
    }

    public function deleted(Payment $payment)
    {
        $this->updateOrderPaymentStatus($payment->order);
    }

    protected function updateOrderPaymentStatus(Order $order)
    {
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
    }
}