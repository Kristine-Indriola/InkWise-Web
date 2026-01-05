<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentStatusUpdated extends Notification
{
    use Queueable;

    protected int $orderId;
    protected ?string $orderNumber;
    protected string $paymentStatus;
    protected string $paymentStatusLabel;
    protected ?string $paymentNote;

    public function __construct(int $orderId, ?string $orderNumber, string $paymentStatus, string $paymentStatusLabel, ?string $paymentNote = null)
    {
        $this->orderId = $orderId;
        $this->orderNumber = $orderNumber;
        $this->paymentStatus = $paymentStatus;
        $this->paymentStatusLabel = $paymentStatusLabel;
        $this->paymentNote = $paymentNote !== null && $paymentNote !== '' ? $paymentNote : null;
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
                'Your order %s payment status has been updated to: %s',
                $displayNumber,
                $this->paymentStatusLabel
            ),
            'order_id' => $this->orderId,
            'order_number' => $this->orderNumber,
            'payment_status' => $this->paymentStatus,
            'payment_status_label' => $this->paymentStatusLabel,
            'payment_note' => $this->paymentNote,
            'type' => 'payment_status_update',
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $displayNumber = $this->orderNumber ?: (string) $this->orderId;
        $customerName = $notifiable->customer->first_name ?? $notifiable->name ?? $notifiable->email;

        $mail = (new MailMessage)
            ->subject('Payment Status Update')
            ->greeting('Hello ' . $customerName . '!')
            ->line(sprintf(
                'Your order %s payment status has been updated to: %s',
                $displayNumber,
                $this->paymentStatusLabel
            ));

        if ($this->paymentNote) {
            $mail->line('Message from our team:')
                ->line($this->paymentNote);
        }

        return $mail
            ->action('View Order Details', route('customer.my_purchase'))
            ->line('Thank you for trusting InkWise!')
            ->salutation("Regards,\n\nInkWise Management");
    }
}
