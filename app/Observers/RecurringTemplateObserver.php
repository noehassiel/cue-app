<?php

namespace App\Observers;

use App\Models\RecurringTemplate;
use App\Services\ProjectionService;

class RecurringTemplateObserver
{
    public function __construct(private readonly ProjectionService $projectionService) {}

    /**
     * Generate projected transactions immediately after template creation.
     */
    public function created(RecurringTemplate $recurringTemplate): void
    {
        $this->projectionService->generateFromTemplate($recurringTemplate);
    }

    public function updated(RecurringTemplate $recurringTemplate): void {}

    public function deleted(RecurringTemplate $recurringTemplate): void {}

    public function restored(RecurringTemplate $recurringTemplate): void {}

    public function forceDeleted(RecurringTemplate $recurringTemplate): void {}
}
