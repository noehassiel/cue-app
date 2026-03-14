<?php

namespace App\Jobs;

use App\Models\Workspace;
use App\Notifications\DeficitAlertNotification;
use App\Services\CashFlowService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendDeficitAlerts implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly CashFlowService $cashFlowService) {}

    /**
     * Alert workspace owners when the 30-day projected balance goes negative.
     */
    public function handle(): void
    {
        $targetDate = Carbon::now()->addDays(30)->endOfDay();

        Workspace::query()
            ->with('owner')
            ->each(function (Workspace $workspace) use ($targetDate): void {
                $projectedBalance = $this->cashFlowService->getProjectedBalance($workspace, $targetDate);

                if (bccomp($projectedBalance, '0.0000', 4) >= 0) {
                    return;
                }

                $owner = $workspace->owner;

                if ($owner === null) {
                    return;
                }

                $owner->notify(new DeficitAlertNotification($workspace, $projectedBalance));
            });
    }
}
