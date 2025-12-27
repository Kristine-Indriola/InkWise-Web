<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaterialShortageAlert extends Notification implements ShouldQueue
{
    use Queueable;

    protected $orderId;
    protected $orderNumber;
    protected $shortages;
    protected $message;

    /**
     * Create a new notification instance.
     */
    public function __construct($orderId, $orderNumber, array $shortages, string $message)
    {
        $this->orderId = $orderId;
        $this->orderNumber = $orderNumber;
        $this->shortages = $shortages;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $shortageDetails = collect($this->shortages)->map(function ($shortage) {
            return "• {$shortage['material']}: {$shortage['shortage']} units short (need {$shortage['required']}, have {$shortage['available']})";
        })->implode("\n");

        return (new MailMessage)
            ->subject("🚨 Material Shortage Alert - Order #{$this->orderNumber}")
            ->greeting('Material Shortage Alert')
            ->line("Order #{$this->orderNumber} has been automatically marked as 'Pending – Awaiting Materials' due to insufficient inventory.")
            ->line('')
            ->line('Material Shortages:')
            ->line($shortageDetails)
            ->line('')
            ->action('View Order Details', route('admin.ordersummary.index', $this->orderId))
            ->line('Please restock the required materials before proceeding with this order.')
            ->salutation('Best regards, InkWise System');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->orderId,
            'order_number' => $this->orderNumber,
            'type' => 'material_shortage',
            'title' => 'Material Shortage Alert',
            'message' => $this->message,
            'shortages' => $this->shortages,
            'action_url' => route('admin.ordersummary.index', $this->orderId),
            'action_text' => 'View Order',
        ];
    }
}