<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TemplateReturnedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $template;
    protected $admin;
    protected $note;

    public function __construct($template, $admin, string $note = null)
    {
        $this->template = $template;
        $this->admin = $admin;
        $this->note = $note;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $adminName = $this->admin?->name ?? 'Admin';
        $noteLine = $this->note ? "Admin note: {$this->note}" : null;

        $mail = (new MailMessage)
            ->subject('Template Returned for Revisions')
            ->line("{$adminName} returned your template for revisions.")
            ->line('Template: ' . ($this->template->name ?? 'Untitled'))
            ->line('Type: ' . ($this->template->product_type ?? 'N/A'))
            ->action('Open Templates', route('staff.templates.index'))
            ->line('Please review the feedback and re-upload the updated template.');

        if ($noteLine) {
            $mail->line($noteLine);
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Template returned for revisions',
            'message' => ($this->admin?->name ?? 'Admin') . ' returned your template: ' . ($this->template->name ?? 'Untitled'),
            'template_id' => $this->template->id ?? null,
            'template_name' => $this->template->name ?? null,
            'template_type' => $this->template->product_type ?? null,
            'admin_name' => $this->admin?->name ?? 'Admin',
            'note' => $this->note,
            'action_url' => route('staff.templates.index'),
            'icon' => 'fas fa-undo',
            'type' => 'template_returned',
        ];
    }
}
