<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fund\DepositFundRequest;
use App\Http\Requests\Fund\StoreFundRequest;
use App\Http\Requests\Fund\UpdateFundRequest;
use App\Http\Requests\Fund\WithdrawFundRequest;
use App\Http\Resources\Api\FundMovementResource;
use App\Http\Resources\Api\FundResource;
use App\Models\Fund;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FundController extends Controller
{
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $funds = $workspace->funds()->orderBy('name')->get();

        return response()->json(['data' => FundResource::collection($funds)]);
    }

    public function store(StoreFundRequest $request, Workspace $workspace): JsonResponse
    {
        $fund = $workspace->funds()->create([
            ...$request->validated(),
            'current_balance' => '0.0000',
        ]);

        return response()->json(['data' => new FundResource($fund)], 201);
    }

    public function show(Request $request, Workspace $workspace, Fund $fund): JsonResponse
    {
        abort_if($fund->workspace_id !== $workspace->id, 404);

        return response()->json(['data' => new FundResource($fund)]);
    }

    public function update(UpdateFundRequest $request, Workspace $workspace, Fund $fund): JsonResponse
    {
        abort_if($fund->workspace_id !== $workspace->id, 404);

        $fund->update($request->validated());

        return response()->json(['data' => new FundResource($fund)]);
    }

    public function destroy(Request $request, Workspace $workspace, Fund $fund): JsonResponse
    {
        abort_if($fund->workspace_id !== $workspace->id, 404);

        if (bccomp($fund->current_balance, '0', 4) !== 0) {
            return response()->json([
                'message' => 'Cannot delete a fund with a non-zero balance. Withdraw the remaining balance first.',
            ], 422);
        }

        $fund->delete();

        return response()->json(['message' => 'Fund deleted']);
    }

    public function deposit(DepositFundRequest $request, Workspace $workspace, Fund $fund): JsonResponse
    {
        abort_if($fund->workspace_id !== $workspace->id, 404);

        $data = $request->validated();
        $amount = $data['amount'];
        $movementDate = $data['movement_date'] ?? now()->toDateString();

        $transaction = $workspace->transactions()->create([
            'type' => 'expense',
            'concept' => 'Fund allocation: '.$fund->name,
            'amount' => $amount,
            'currency' => $fund->currency,
            'category' => 'fund_allocation',
            'projected_date' => $movementDate,
            'confirmed_at' => now(),
        ]);

        $movement = $fund->movements()->create([
            'transaction_id' => $transaction->id,
            'type' => 'deposit',
            'amount' => $amount,
            'note' => $data['note'] ?? null,
            'movement_date' => $movementDate,
        ]);

        $fund->update([
            'current_balance' => bcadd($fund->current_balance, $amount, 4),
        ]);

        return response()->json([
            'data' => [
                'fund' => new FundResource($fund->fresh()),
                'movement' => new FundMovementResource($movement),
            ],
        ]);
    }

    public function withdraw(WithdrawFundRequest $request, Workspace $workspace, Fund $fund): JsonResponse
    {
        abort_if($fund->workspace_id !== $workspace->id, 404);

        $data = $request->validated();
        $amount = $data['amount'];

        if (bccomp($fund->current_balance, $amount, 4) < 0) {
            return response()->json([
                'message' => 'Insufficient fund balance.',
                'current_balance' => $fund->current_balance,
                'requested' => $amount,
            ], 422);
        }

        $movementDate = $data['movement_date'] ?? now()->toDateString();

        $transaction = $workspace->transactions()->create([
            'type' => 'income',
            'concept' => 'Fund withdrawal: '.$fund->name,
            'amount' => $amount,
            'currency' => $fund->currency,
            'category' => 'fund_allocation',
            'projected_date' => $movementDate,
            'confirmed_at' => now(),
        ]);

        $movement = $fund->movements()->create([
            'transaction_id' => $transaction->id,
            'type' => 'withdrawal',
            'amount' => $amount,
            'note' => $data['note'] ?? null,
            'movement_date' => $movementDate,
        ]);

        $fund->update([
            'current_balance' => bcsub($fund->current_balance, $amount, 4),
        ]);

        return response()->json([
            'data' => [
                'fund' => new FundResource($fund->fresh()),
                'movement' => new FundMovementResource($movement),
            ],
        ]);
    }

    public function movements(Request $request, Workspace $workspace, Fund $fund): JsonResponse
    {
        abort_if($fund->workspace_id !== $workspace->id, 404);

        $movements = $fund->movements()->orderBy('movement_date', 'desc')->get();

        return response()->json(['data' => FundMovementResource::collection($movements)]);
    }
}
