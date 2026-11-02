<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Queued welcome email — sent on registration, after the personal
 * workspace bootstrap (PRD 5.7).
 */
class Welcome extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to '.config('app.name'))
            ->greeting('Hi '.$notifiable->name)
            ->line('Your account is ready. You can start right away — your workspace was created automatically.')
            ->action('Open your dashboard', route('dashboard'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Welcome aboard',
            'body' => 'Your workspace is ready. Start exploring your dashboard.',
            'url' => route('dashboard'),
        ];
    }
}
