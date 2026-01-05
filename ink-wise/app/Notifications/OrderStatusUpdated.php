<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OrderStatusUpdated extends Notification
{
    use Queueable;

    protected int $orderId;
    protected ?string $orderNumber;
    protected string $oldStatus;
    protected string $newStatus;
    protected string $statusLabel;

    public function __construct(int $orderId, ?string $orderNumber, string $oldStatus, string $newStatus, string $statusLabel)
    {
        $this->orderId = $orderId;
        $this->orderNumber = $orderNumber;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->statusLabel = $statusLabel;
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray($notifiable): array
    {
        $displayNumber = $this->orderNumber ?: (string) $this->orderId;

        return [
            'message' => sprintf(
                'Your order %s status has been updated to: %s',
                $displayNumber,
                $this->statusLabel
            ),
            'order_id' => $this->orderId,
            'order_number' => $this->orderNumber,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'status_label' => $this->statusLabel,
            'type' => 'order_status_update',
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $displayNumber = $this->orderNumber ?: (string) $this->orderId;
        $customerName = $notifiable->customer->first_name ?? $notifiable->name ?? $notifiable->email;

        return (new MailMessage)
            ->subject('Order Status Update')
            ->greeting('Hello ' . $customerName . '!')
            ->line(sprintf(
                'Your order %s status has been updated to: %s',
                $displayNumber,
                $this->statusLabel
            ))
            ->action('View Order Details', route('customer.my_purchase'))
            ->line('Thank you for using our service!')
            ->salutation("Regards,\n\nInkWise Management");
    }
}
