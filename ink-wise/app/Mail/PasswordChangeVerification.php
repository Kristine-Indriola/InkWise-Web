<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\PasswordChangeAttempt;

class PasswordChangeVerification extends Mailable
{
    use Queueable, SerializesModels;

    public PasswordChangeAttempt $attempt;

    /**
     * Create a new message instance.
     */
    public function __construct(PasswordChangeAttempt $attempt)
    {
        $this->attempt = $attempt;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verification Required: New Password Change Attempt on Your InkWise Account',
            from: 'security@otp.inkwise.ph'
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.password-change-verification',
            with: [
                'attempt' => $this->attempt,
                'user' => $this->attempt->user,
                'customer' => $this->attempt->user->customer,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
