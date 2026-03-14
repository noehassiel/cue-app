<?php

namespace App\Notifications;

use App\Models\DebtInstallment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReminderNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly DebtInstallment $installment) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $debtName = $this->installment->debt?->name ?? 'your debt';
        $amount = number_format((float) $this->installment->amount, 2);
        $dueDate = $this->installment->due_date->format('M j, Y');

        return (new MailMessage)
            ->subject("Payment Reminder: {$debtName}")
            ->greeting("Hi {$notifiable->name},")
            ->line('You have an installment payment due in 3 days.')
            ->line("**{$debtName}** — \${$amount} due on {$dueDate}")
            ->action('View in Cue', config('app.url'))
            ->line('Stay on top of your cash flow with Cue!');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'installment_id' => $this->installment->id,
            'debt_name' => $this->installment->debt?->name,
            'amount' => $this->installment->amount,
            'due_date' => $this->installment->due_date->toDateString(),
        ];
    }
}
