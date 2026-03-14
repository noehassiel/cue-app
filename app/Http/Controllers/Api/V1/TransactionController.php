<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\ConfirmTransactionRequest;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Http\Resources\Api\TransactionResource;
use App\Imports\TransactionsImport;
use App\Models\Transaction;
use App\Models\Workspace;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TransactionController extends Controller
{
    public function __construct(private readonly CurrencyService $currencyService) {}

    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $query = $workspace->transactions()->orderBy('projected_date', 'desc');

        if ($request->has('month')) {
            $query->where('projected_date', 'like', $request->query('month').'%');
        }

        if ($request->has('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->has('confirmed')) {
            $confirmed = filter_var($request->query('confirmed'), FILTER_VALIDATE_BOOLEAN);
            $confirmed ? $query->whereNotNull('confirmed_at') : $query->whereNull('confirmed_at');
        }

        if ($request->has('category')) {
            $query->where('category', $request->query('category'));
        }

        $perPage = min((int) $request->query('per_page', 25), 100);
        $transactions = $query->cursorPaginate($perPage);

        return response()->json([
            'data' => TransactionResource::collection($transactions),
            'meta' => [
                'next_cursor' => $transactions->nextCursor()?->encode(),
                'prev_cursor' => $transactions->previousCursor()?->encode(),
            ],
        ]);
    }

    public function store(StoreTransactionRequest $request, Workspace $workspace): JsonResponse
    {
        $data = $request->validated();

        $exchangeRate = null;
        if ($data['currency'] !== $workspace->currency) {
            $exchangeRate = $this->currencyService->getRate($data['currency'], $workspace->currency);
        }

        $transaction = $workspace->transactions()->create([
            'type' => $data['type'],
            'concept' => $data['concept'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'exchange_rate' => $exchangeRate,
            'category' => $data['category'] ?? null,
            'projected_date' => $data['projected_date'],
            'confirmed_at' => ($data['confirm'] ?? false) ? now() : null,
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json(['data' => new TransactionResource($transaction)], 201);
    }

    public function show(Request $request, Workspace $workspace, Transaction $transaction): JsonResponse
    {
        abort_if($transaction->workspace_id !== $workspace->id, 404);

        return response()->json(['data' => new TransactionResource($transaction)]);
    }

    public function update(UpdateTransactionRequest $request, Workspace $workspace, Transaction $transaction): JsonResponse
    {
        abort_if($transaction->workspace_id !== $workspace->id, 404);

        $transaction->update($request->validated());

        return response()->json(['data' => new TransactionResource($transaction)]);
    }

    public function destroy(Request $request, Workspace $workspace, Transaction $transaction): JsonResponse
    {
        abort_if($transaction->workspace_id !== $workspace->id, 404);

        $transaction->delete();

        return response()->json(['message' => 'Transaction deleted']);
    }

    public function confirm(ConfirmTransactionRequest $request, Workspace $workspace, Transaction $transaction): JsonResponse
    {
        abort_if($transaction->workspace_id !== $workspace->id, 404);

        $confirmedAt = $request->confirmed_at ? now()->parse($request->confirmed_at) : now();
        $transaction->update(['confirmed_at' => $confirmedAt]);

        return response()->json(['data' => new TransactionResource($transaction)]);
    }

    /**
     * Import transactions from an uploaded Excel / CSV file.
     */
    public function import(Request $request, Workspace $workspace): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        Excel::import(new TransactionsImport($workspace), $request->file('file'));

        return response()->json(['message' => 'Transactions imported successfully'], 201);
    }
}
