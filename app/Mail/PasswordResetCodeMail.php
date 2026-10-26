<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetCodeMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public string $code,
        public int $minutes = 10,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Password Reset Code');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.password-reset-code');
    }
}