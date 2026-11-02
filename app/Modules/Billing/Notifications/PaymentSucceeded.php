<?php

namespace App\Modules\Billing\Notifications;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Queued payment confirmation (PRD 5.7 core transactional email).
 */
class PaymentSucceeded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Organization $organization,
        private int $amountCents,
        private Carbon $chargedAt,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->wantsEmail('payment_success')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Payment received',
            'body' => number_format($this->amountCents / 100, 2).' USD was charged for '.$this->organization->name.'.',
            'url' => route('billing.show'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Payment received — {$this->organization->name}")
            ->greeting('Hi '.$notifiable->name)
            ->line('We received a payment of **'.number_format($this->amountCents / 100, 2).' USD** for '.$this->organization->name.' on '.$this->chargedAt->toDateString().'.')
            ->line('Thank you for your business!')
            ->action('View billing', route('billing.show'));
    }
}
