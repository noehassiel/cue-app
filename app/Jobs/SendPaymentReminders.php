<?php

namespace App\Jobs;

use App\Models\DebtInstallment;
use App\Notifications\PaymentReminderNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendPaymentReminders implements ShouldQueue
{
    use Queueable;

    public function __construct() {}

    /**
     * Send push/email reminders for unpaid installments due in exactly 3 days.
     */
    public function handle(): void
    {
        $targetDate = Carbon::now()->addDays(3)->toDateString();

        DebtInstallment::query()
            ->with(['debt.workspace.owner'])
            ->whereNull('paid_at')
            ->whereDate('due_date', $targetDate)
            ->each(function (DebtInstallment $installment): void {
                $owner = $installment->debt?->workspace?->owner;

                if ($owner === null) {
                    return;
                }

                $owner->notify(new PaymentReminderNotification($installment));
            });
    }
}
