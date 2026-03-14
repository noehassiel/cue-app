<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecurringTemplate\StoreRecurringTemplateRequest;
use App\Http\Requests\RecurringTemplate\UpdateRecurringTemplateRequest;
use App\Http\Resources\Api\RecurringTemplateResource;
use App\Models\RecurringTemplate;
use App\Models\Workspace;
use App\Services\ProjectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecurringTemplateController extends Controller
{
    public function __construct(private readonly ProjectionService $projectionService) {}

    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $templates = $workspace->recurringTemplates()->orderBy('concept')->get();

        return response()->json([
            'data' => RecurringTemplateResource::collection($templates),
        ]);
    }

    public function store(StoreRecurringTemplateRequest $request, Workspace $workspace): JsonResponse
    {
        $template = $workspace->recurringTemplates()->create($request->validated());

        $this->projectionService->generateFromTemplate($template);

        return response()->json(['data' => new RecurringTemplateResource($template)], 201);
    }

    public function show(Request $request, Workspace $workspace, RecurringTemplate $recurringTemplate): JsonResponse
    {
        abort_if($recurringTemplate->workspace_id !== $workspace->id, 404);

        return response()->json(['data' => new RecurringTemplateResource($recurringTemplate)]);
    }

    public function update(UpdateRecurringTemplateRequest $request, Workspace $workspace, RecurringTemplate $recurringTemplate): JsonResponse
    {
        abort_if($recurringTemplate->workspace_id !== $workspace->id, 404);

        $regenerate = $request->hasAny(['amount', 'next_date', 'frequency', 'frequency_day', 'concept', 'type']);
        $recurringTemplate->update($request->validated());

        if ($regenerate) {
            $this->projectionService->regenerateFuture($recurringTemplate);
        }

        return response()->json(['data' => new RecurringTemplateResource($recurringTemplate)]);
    }

    public function destroy(Request $request, Workspace $workspace, RecurringTemplate $recurringTemplate): JsonResponse
    {
        abort_if($recurringTemplate->workspace_id !== $workspace->id, 404);

        $deleteFuture = filter_var($request->input('delete_future_transactions', true), FILTER_VALIDATE_BOOLEAN);

        if ($deleteFuture) {
            $recurringTemplate->transactions()
                ->whereNull('confirmed_at')
                ->whereDate('projected_date', '>=', now()->toDateString())
                ->delete();
        }

        $recurringTemplate->delete();

        return response()->json(['message' => 'Template deleted']);
    }
}
