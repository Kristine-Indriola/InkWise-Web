<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewStaffCreated extends Notification
{
    use Queueable;

    protected $user;
    

    public function __construct($user)
    {
        $this->user = $user;
    }

    // ✅ Store notification in DB
    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => 'A new staff account has been created.',
            'first_name'   => $this->user->staff->first_name,
            'last_name'    => $this->user->staff->last_name,
            'role'         => $this->user->role,
            'email'   => $this->user->email,
        ];
    }
}
