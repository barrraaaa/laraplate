<?php

namespace App\Modules\Entitlements\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Usage-alert (PRD 5.6 P1): fired once per (metric, period) when usage
 * crosses the configured threshold, to the organization's admins.
 */
class UsageAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $metric,
        private int $used,
        private ?int $limit,
        private string $organizationName,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->wantsEmail('usage_alert')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Usage approaching your plan limit — {$this->organizationName}")
            ->greeting('Hi '.$notifiable->name)
            ->line("**{$this->metric}** has reached {$this->used}".($this->limit !== null ? " of {$this->limit}" : '').'.')
            ->line('Consider upgrading the plan before actions start being blocked.')
            ->action('View billing', route('billing.show'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => "Usage alert: {$this->metric}",
            'body' => "{$this->used}".($this->limit !== null ? " of {$this->limit}" : '').' used — upgrade to keep going.',
            'url' => route('billing.show'),
        ];
    }
}
