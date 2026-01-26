<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewOrderPlaced extends Notification
{
    use Queueable;

    protected int $orderId;
    protected ?string $orderNumber;
    protected string $customerName;
    protected float $totalAmount;
    protected string $orderSummaryUrl;

    public function __construct(int $orderId, ?string $orderNumber, string $customerName, float $totalAmount, string $orderSummaryUrl)
    {
        $this->orderId = $orderId;
        $this->orderNumber = $orderNumber;
        $this->customerName = $customerName;
        $this->totalAmount = $totalAmount;
        $this->orderSummaryUrl = $orderSummaryUrl;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $displayNumber = $this->orderNumber ?: (string) $this->orderId;

        return [
            'message' => sprintf(
                'New order %s placed by %s (Total: ₱%s)',
                $displayNumber,
                $this->customerName,
                number_format($this->totalAmount, 2)
            ),
            'order_id' => $this->orderId,
            'order_number' => $this->orderNumber,
            'total_amount' => $this->totalAmount,
            'url' => $this->orderSummaryUrl,
        ];
    }
}
