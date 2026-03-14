<?php

namespace App\Console\Commands;

use App\Models\RecurringTemplate;
use App\Services\ProjectionService;
use Illuminate\Console\Command;

class GenerateProjectedTransactions extends Command
{
    protected $signature = 'cue:generate-projected-transactions';

    protected $description = 'Generate projected transactions from active recurring templates';

    public function handle(ProjectionService $projectionService): int
    {
        $templates = RecurringTemplate::query()
            ->where('is_active', true)
            ->whereDate('next_date', '<=', now()->addDays(365)->toDateString())
            ->get();

        $count = 0;

        foreach ($templates as $template) {
            $projectionService->generateFromTemplate($template);
            $count++;
        }

        $this->info("Processed {$count} recurring template(s).");

        return self::SUCCESS;
    }
}
