<?php

namespace App\Modules\Billing\Notifications;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Queued payment failure alert (PRD 5.5/5.7).
 */
class PaymentFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Organization $organization,
        private ?int $attemptCount,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Payment failed — {$this->organization->name}")
            ->greeting('Hi '.$notifiable->name)
            ->line('A payment for '.$this->organization->name.' could not be processed.')
            ->line(
                $this->attemptCount
                    ? "This was attempt #{$this->attemptCount}. Stripe will retry automatically — in the meantime you can update the payment method from billing settings."
                    : 'Please update the payment method from billing settings to keep your subscription active.'
            )
            ->action('Update payment method', route('billing.portal'))
            ->line('If you keep seeing this email, contact support.');
    }
}
