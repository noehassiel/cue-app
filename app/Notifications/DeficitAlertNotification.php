<?php

namespace App\Notifications;

use App\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeficitAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Workspace $workspace,
        private readonly string $projectedBalance,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $workspaceName = $this->workspace->name;
        $balance = number_format((float) $this->projectedBalance, 2);
        $currency = $this->workspace->currency;

        return (new MailMessage)
            ->subject("Cash Flow Alert: {$workspaceName}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your workspace **{$workspaceName}** is projected to go into deficit within the next 30 days.")
            ->line("Projected balance: **{$currency} {$balance}**")
            ->action('Review in Cue', config('app.url'))
            ->line('Review your upcoming expenses and income to stay on track.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'workspace_id' => $this->workspace->id,
            'workspace_name' => $this->workspace->name,
            'projected_balance' => $this->projectedBalance,
        ];
    }
}
