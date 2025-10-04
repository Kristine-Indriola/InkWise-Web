<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetCompleted extends Notification implements ShouldQueue
{
	use Queueable;

	public function __construct(public User $user)
	{
	}

	public function via(object $notifiable): array
	{
		return ['mail'];
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
}
