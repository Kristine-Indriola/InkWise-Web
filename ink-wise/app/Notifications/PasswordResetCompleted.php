<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetCompleted extends Notification
{
	use Queueable;

	public function __construct(public User $user)
	{
	}

	public function via(object $notifiable): array
	{
		return ['mail', 'database'];
	}

	public function toMail(object $notifiable): MailMessage
	{
		return (new MailMessage)
			->subject('Password Reset Completed')
			->greeting('Hello Admin,')
			->line('A user has successfully changed their password.')
			->line('User: '.$this->user->email)
			->line('Role: '.$this->user->role)
			->line('If this action was unexpected, please review the account activity immediately.')
			->salutation('Regards, InkWise System');
	}

	public function toDatabase(object $notifiable): array
	{
		return [
			'icon' => 'fa-solid fa-lock',
			'message' => sprintf('🔐 %s just updated their password.', $this->user->email),
			'url' => route('admin.users.index'),
		];
	}
}
