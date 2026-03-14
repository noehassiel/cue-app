<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\StoreWorkspaceRequest;
use App\Http\Requests\Workspace\UpdateWorkspaceRequest;
use App\Http\Resources\Api\WorkspaceResource;
use App\Models\Workspace;
use App\Services\CashFlowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function __construct(private readonly CashFlowService $cashFlowService) {}

    public function index(Request $request): JsonResponse
    {
        $workspaces = $request->user()->workspaces()->get();

        return response()->json([
            'data' => WorkspaceResource::collection($workspaces),
        ]);
    }

    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        $workspace = Workspace::create([
            'owner_id' => $request->user()->id,
            'name' => $request->name,
            'currency' => strtoupper($request->currency),
            'opening_balance' => $request->opening_balance ?? '0.0000',
        ]);

        $workspace->members()->attach($request->user()->id, ['role' => 'owner']);

        return response()->json(['data' => new WorkspaceResource($workspace)], 201);
    }

    public function show(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorizeWorkspaceAccess($request, $workspace);

        return response()->json(['data' => new WorkspaceResource($workspace)]);
    }

    public function update(UpdateWorkspaceRequest $request, Workspace $workspace): JsonResponse
    {
        $this->authorizeWorkspaceAccess($request, $workspace);

        $workspace->update($request->validated());

        return response()->json(['data' => new WorkspaceResource($workspace)]);
    }

    public function destroy(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorizeWorkspaceAccess($request, $workspace);

        abort_if($workspace->owner_id !== $request->user()->id, 403, 'Only the owner can delete a workspace.');

        $workspace->delete();

        return response()->json(['message' => 'Workspace deleted']);
    }

    public function dashboard(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorizeWorkspaceAccess($request, $workspace);

        $operationalBalance = $this->cashFlowService->getOperationalBalance($workspace);
        $confirmedBalance = bcadd(
            (string) $workspace->opening_balance,
            (string) $workspace->transactions()->where('type', 'income')->whereNotNull('confirmed_at')->sum('amount'),
            4
        );
        $confirmedBalance = bcsub(
            $confirmedBalance,
            (string) $workspace->transactions()->where('type', 'expense')->whereNotNull('confirmed_at')->sum('amount'),
            4
        );

        $projectedBalance = $this->cashFlowService->getProjectedBalance($workspace, now()->endOfMonth());
        $totalFundsAllocated = $workspace->funds()->sum('current_balance');

        $currentMonth = now()->format('Y-m');
        $monthlyIncome = $workspace->transactions()
            ->where('type', 'income')
            ->where('projected_date', 'like', $currentMonth . '%')
            ->sum('amount');
        $monthlyExpenses = $workspace->transactions()
            ->where('type', 'expense')
            ->where('projected_date', 'like', $currentMonth . '%')
            ->sum('amount');

        return response()->json([
            'data' => [
                'operational_balance' => $operationalBalance,
                'confirmed_balance' => $confirmedBalance,
                'projected_balance' => $projectedBalance,
                'total_funds_allocated' => number_format((float) $totalFundsAllocated, 4, '.', ''),
                'active_debts_count' => $workspace->debts()->count(),
                'monthly_summary' => [
                    'income' => number_format((float) $monthlyIncome, 4, '.', ''),
                    'expenses' => number_format((float) $monthlyExpenses, 4, '.', ''),
                    'net' => bcsub((string) $monthlyIncome, (string) $monthlyExpenses, 4),
                ],
            ],
        ]);
    }

    public function cashflow(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorizeWorkspaceAccess($request, $workspace);

        $months = (int) $request->query('months', $workspace->projection_months);
        $months = in_array($months, [3, 6, 12]) ? $months : $workspace->projection_months;

        $periods = $this->cashFlowService->getMonthlyPeriods($workspace, $months);

        return response()->json(['data' => ['periods' => $periods]]);
    }

    private function authorizeWorkspaceAccess(Request $request, Workspace $workspace): void
    {
        abort_unless(
            $workspace->members()->where('user_id', $request->user()->id)->exists(),
            403,
            'You do not have access to this workspace.'
        );
    }
}
