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
        return (new MailMessage)
            ->subject('Staff Account Approved')
            ->line("The staff account for {$this->staff->first_name} {$this->staff->last_name} has been approved by the owner.")
            ->action('View Notifications', url('/admin/notifications')) // adjust route
            ->line('Thank you for using the system!');
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "✅ Staff {$this->staff->first_name} {$this->staff->last_name} has been approved by the owner.",
            'staff_id' => $this->staff->staff_id,
        ];
    }
}
