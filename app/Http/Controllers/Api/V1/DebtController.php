<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Debt\StoreDebtRequest;
use App\Http\Requests\Debt\UpdateDebtRequest;
use App\Http\Resources\Api\DebtInstallmentResource;
use App\Http\Resources\Api\DebtResource;
use App\Models\Debt;
use App\Models\DebtInstallment;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DebtController extends Controller
{
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $debts = $workspace->debts()
            ->with('installments')
            ->orderBy('start_date')
            ->get();

        return response()->json(['data' => DebtResource::collection($debts)]);
    }

    public function store(StoreDebtRequest $request, Workspace $workspace): JsonResponse
    {
        $debt = $workspace->debts()->create($request->validated());

        $debt->refresh();
        $debt->load('installments');

        return response()->json(['data' => new DebtResource($debt)], 201);
    }

    public function show(Request $request, Workspace $workspace, Debt $debt): JsonResponse
    {
        abort_if($debt->workspace_id !== $workspace->id, 404);

        $debt->load('installments');

        return response()->json(['data' => new DebtResource($debt)]);
    }

    public function update(UpdateDebtRequest $request, Workspace $workspace, Debt $debt): JsonResponse
    {
        abort_if($debt->workspace_id !== $workspace->id, 404);

        $debt->update($request->validated());

        $debt->load('installments');

        return response()->json(['data' => new DebtResource($debt)]);
    }

    public function destroy(Request $request, Workspace $workspace, Debt $debt): JsonResponse
    {
        abort_if($debt->workspace_id !== $workspace->id, 404);

        $debt->installments()
            ->whereNull('paid_at')
            ->each(function (DebtInstallment $installment) {
                if ($installment->transaction_id) {
                    $installment->transaction?->delete();
                }
            });

        $debt->delete();

        return response()->json(['message' => 'Debt deleted']);
    }

    public function upcoming(Request $request, Workspace $workspace): JsonResponse
    {
        $days = min((int) $request->query('days', 30), 365);

        $installments = DebtInstallment::query()
            ->whereHas('debt', fn ($q) => $q->where('workspace_id', $workspace->id))
            ->whereNull('paid_at')
            ->where('due_date', '<=', now()->addDays($days)->toDateString())
            ->with('debt')
            ->orderBy('due_date')
            ->get();

        return response()->json(['data' => DebtInstallmentResource::collection($installments)]);
    }

    public function payInstallment(Request $request, Workspace $workspace, Debt $debt, DebtInstallment $installment): JsonResponse
    {
        abort_if($debt->workspace_id !== $workspace->id, 404);
        abort_if($installment->debt_id !== $debt->id, 404);

        if ($installment->isPaid()) {
            return response()->json(['message' => 'Installment is already paid.'], 422);
        }

        $paidAt = $request->input('paid_at') ? now()->parse($request->input('paid_at')) : now();

        $installment->update(['paid_at' => $paidAt]);

        if ($installment->transaction_id) {
            $installment->transaction?->update(['confirmed_at' => $paidAt]);
        }

        $debt->increment('paid_installments');

        return response()->json(['data' => new DebtInstallmentResource($installment->fresh())]);
    }
}
