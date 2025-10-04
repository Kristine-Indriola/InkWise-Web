<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Staff;

class StaffApprovedNotification extends Notification
{
    use Queueable;

    protected $staff;

    public function __construct(Staff $staff)
    {
        $this->staff = $staff;
    }

    public function via($notifiable)
    {
        return ['mail', 'database']; // ✅ Sends email + stores in DB
    }

    public function toMail($notifiable)
    {
        $targetUrl = $this->resolveUrlFor($notifiable);

        return (new MailMessage)
            ->subject('Staff Account Approved')
            ->line("The staff account for {$this->staff->first_name} {$this->staff->last_name} has been approved by the owner.")
            ->action('View Staff Member', $targetUrl)
            ->line('Thank you for using the system!');
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "✅ Staff {$this->staff->first_name} {$this->staff->last_name} has been approved by the owner.",
            'staff_id' => $this->staff->staff_id,
            'url' => $this->resolveUrlFor($notifiable),
        ];
    }

    protected function resolveUrlFor($notifiable): string
    {
        $userId = $this->staff->user->user_id ?? $this->staff->user_id;

        if (method_exists($notifiable, 'getAttribute') && $notifiable->getAttribute('role') === 'admin') {
            return route('admin.users.show', ['user_id' => $userId, 'highlight' => 1]);
        }

        return route('owner.staff.index', ['highlight' => $this->staff->staff_id]);
    }
}
