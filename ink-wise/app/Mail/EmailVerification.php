<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerification extends Mailable
{
    use Queueable, SerializesModels;

    public $user; // Pass the user into the email

    /**
     * Create a new message instance.
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Verify Your Email Address')
                    ->markdown('emails.verify')
                    ->with([
                        'user' => $this->user,
                        'verificationUrl' => url('/verify-email/'.$this->user->email_verification_token),
                    ]);
    }
}
