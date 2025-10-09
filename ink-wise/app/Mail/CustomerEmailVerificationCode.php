<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerEmailVerificationCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code)
    {
    }

    public function build()
    {
        return $this->subject('InkWise Email Verification Code')
            ->markdown('emails.customer-email-verification-code', [
                'code' => $this->code,
            ]);
    }
}
