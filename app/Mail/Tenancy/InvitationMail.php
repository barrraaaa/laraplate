<?php

namespace App\Mail\Tenancy;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Queued organization invitation email (PRD 5.3). The plain token never
 * leaves this mailable's construction — at rest it is stored hashed.
 */
class InvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        private Invitation $invitation,
        private string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're invited to {$this->invitation->organization->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invitation',
            with: [
                'organizationName' => $this->invitation->organization->name,
                'inviterName' => $this->invitation->inviter?->name,
                'role' => ucfirst($this->invitation->role->value),
                'acceptUrl' => route('invitations.accept', ['token' => $this->token]),
                'expiresAt' => $this->invitation->expires_at->format('M j, Y'),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
