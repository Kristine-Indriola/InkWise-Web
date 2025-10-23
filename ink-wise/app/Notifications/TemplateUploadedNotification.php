<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class TemplateUploadedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $template;
    protected $staff;

    /**
     * Create a new notification instance.
     */
    public function __construct($template, $staff)
    {
        $this->template = $template;
        $this->staff = $staff;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Template Uploaded')
            ->line('A new template has been uploaded by ' . $this->staff->name . '.')
            ->line('Template: ' . $this->template->name)
            ->line('Type: ' . $this->template->product_type)
            ->action('View Template', route('admin.templates.uploaded'))
            ->line('Please review the uploaded template.');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'New Template Uploaded',
            'message' => $this->staff->name . ' uploaded a new template: ' . $this->template->name,
            'template_id' => $this->template->id,
            'template_name' => $this->template->name,
            'template_type' => $this->template->product_type,
            'staff_name' => $this->staff->name,
            'action_url' => route('admin.templates.uploaded'),
            'icon' => 'fas fa-upload',
            'type' => 'template_uploaded'
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'template_id' => $this->template->id,
            'template_name' => $this->template->name,
            'staff_name' => $this->staff->name,
        ];
    }
}
