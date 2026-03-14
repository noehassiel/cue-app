<?php

namespace App\Services;

use App\Models\RecurringTemplate;
use App\Models\Transaction;
use Carbon\Carbon;

class ProjectionService
{
    /**
     * Generate projected transactions from template.next_date up to now() + generate_ahead_days.
     */
    public function generateFromTemplate(RecurringTemplate $template): void
    {
        if (! $template->is_active) {
            return;
        }

        $endDate = Carbon::now()->addDays($template->generate_ahead_days);
        $current = $template->next_date->copy();

        while ($current->lte($endDate)) {
            $alreadyExists = Transaction::query()
                ->where('workspace_id', $template->workspace_id)
                ->where('recurring_template_id', $template->id)
                ->where('projected_date', $current->toDateString())
                ->exists();

            if (! $alreadyExists) {
                Transaction::create([
                    'workspace_id' => $template->workspace_id,
                    'recurring_template_id' => $template->id,
                    'type' => $template->type,
                    'concept' => $template->concept,
                    'amount' => $template->amount,
                    'currency' => $template->currency,
                    'category' => $template->category,
                    'projected_date' => $current->toDateString(),
                ]);
            }

            $current = $this->nextOccurrence($template, $current);

            if ($current === null) {
                break;
            }
        }

        $template->update(['next_date' => $current ?? $endDate->addDay()]);
    }

    /**
     * Delete all unconfirmed future transactions for this template and regenerate.
     */
    public function regenerateFuture(RecurringTemplate $template): void
    {
        Transaction::query()
            ->where('recurring_template_id', $template->id)
            ->whereNull('confirmed_at')
            ->whereDate('projected_date', '>=', Carbon::now()->toDateString())
            ->delete();

        $template->update(['next_date' => Carbon::now()]);
        $template->refresh();

        $this->generateFromTemplate($template);
    }

    private function nextOccurrence(RecurringTemplate $template, Carbon $current): ?Carbon
    {
        return match ($template->frequency) {
            'weekly' => $current->copy()->addWeek(),
            'biweekly' => $current->copy()->addWeeks(2),
            'monthly' => $current->copy()->addMonth(),
            'custom' => null,
        };
    }
}
