<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class SiteContentUpdated extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  string       $actorName   Display name for the admin who performed the update.
     * @param  string|null  $actorEmail  Contact email for the admin who performed the update.
     * @param  int|null     $actorId     Identifier for the admin who performed the update.
     * @param  array<string, mixed> $changes Key/value pairs describing the updated fields and their new values.
     */
    public function __construct(
        public readonly string $actorName,
        public readonly ?string $actorEmail,
        public readonly ?int $actorId,
        public readonly array $changes
    ) {
    }

    /**
     * Get the notification's delivery channels.
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
        $mail = (new MailMessage)
            ->subject('Site Content Updated')
            ->greeting('Hello Owner,')
            ->line(sprintf('%s updated the public site content.', $this->actorName ?: 'An administrator'));

        if ($this->actorEmail) {
            $mail->line('Contact email: '.$this->actorEmail);
        }

        $fieldList = $this->formatChangedFields();

        if ($fieldList !== '') {
            $mail->line('Fields changed: '.$fieldList);
        }

        return $mail
            ->action('Review Site Content', route('admin.settings.site-content.edit'))
            ->line('You are receiving this because you are registered as an InkWise owner.');
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => 'fa-solid fa-globe',
            'message' => sprintf('%s updated the site content.', $this->actorName ?: 'An administrator'),
            'url' => route('admin.settings.site-content.edit'),
            'changed_fields' => array_keys($this->changes),
            'changes' => $this->changes,
            'updated_by' => [
                'id' => $this->actorId,
                'name' => $this->actorName,
                'email' => $this->actorEmail,
            ],
        ];
    }

    /**
     * Format the changed fields for human consumption.
     */
    protected function formatChangedFields(): string
    {
        if (empty($this->changes)) {
            return '';
        }

        return collect(array_keys($this->changes))
            ->map(fn (string $field) => Str::headline($field))
            ->join(', ');
    }
}
