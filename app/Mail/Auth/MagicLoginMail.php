<?php

namespace App\Mail\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Queued magic-link login email (one click, expires quickly).
 */
class MagicLoginMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        private string $token,
        private Carbon $expiresAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your '.config('app.name').' login link',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.magic-login',
            with: [
                'loginUrl' => route('auth.magic.consume', ['token' => $this->token]),
                'expiresAt' => $this->expiresAt->format('H:i'),
            ],
        );
    }
}
