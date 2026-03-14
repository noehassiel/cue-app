<?php

namespace App\Http\Controllers\Api\V1;

use App\Exports\TransactionsExport;
use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Services\CashFlowService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(private readonly CashFlowService $cashFlowService) {}

    /**
     * Monthly summary for a workspace. Defaults to current month.
     */
    public function monthly(Request $request, Workspace $workspace): JsonResponse
    {
        $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'months' => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);

        $monthInput = $request->query('month', Carbon::now()->format('Y-m'));
        $months = (int) $request->query('months', 1);
        $fromDate = Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth();

        $periods = $this->cashFlowService->getMonthlyPeriods($workspace, $months, $fromDate);

        return response()->json(['data' => $periods]);
    }

    /**
     * Multi-month cash flow projection from today.
     */
    public function projection(Request $request, Workspace $workspace): JsonResponse
    {
        $request->validate([
            'months' => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);

        $months = (int) $request->query('months', (int) $workspace->projection_months);

        $periods = $this->cashFlowService->getMonthlyPeriods($workspace, $months);

        $targetDate = Carbon::now()->addMonths($months)->endOfMonth();
        $projectedBalance = $this->cashFlowService->getProjectedBalance($workspace, $targetDate);
        $operationalBalance = $this->cashFlowService->getOperationalBalance($workspace);

        return response()->json([
            'data' => [
                'operational_balance' => $operationalBalance,
                'projected_balance' => $projectedBalance,
                'months' => $months,
                'periods' => $periods,
            ],
        ]);
    }

    /**
     * Export workspace transactions as CSV.
     */
    public function exportCsv(Request $request, Workspace $workspace): Response
    {
        $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $query = $workspace->transactions()->orderBy('projected_date');

        if ($request->has('month')) {
            $query->where('projected_date', 'like', $request->query('month').'%');
        }

        $transactions = $query->get();

        $rows = [];
        $rows[] = implode(',', ['id', 'type', 'concept', 'amount', 'currency', 'category', 'projected_date', 'confirmed_at', 'notes']);

        foreach ($transactions as $tx) {
            $rows[] = implode(',', [
                $tx->id,
                $tx->type,
                '"'.str_replace('"', '""', (string) $tx->concept).'"',
                $tx->amount,
                $tx->currency,
                $tx->category ?? '',
                $tx->projected_date->toDateString(),
                $tx->confirmed_at?->toIso8601String() ?? '',
                '"'.str_replace('"', '""', (string) ($tx->notes ?? '')).'"',
            ]);
        }

        $filename = 'transactions-'.($request->query('month') ?? 'all').'.csv';

        return response(implode("\n", $rows), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Export workspace transactions as XLSX using maatwebsite/excel.
     */
    public function exportXlsx(Request $request, Workspace $workspace): BinaryFileResponse
    {
        $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $query = $workspace->transactions()->orderBy('projected_date');

        if ($request->has('month')) {
            $query->where('projected_date', 'like', $request->query('month').'%');
        }

        $transactions = $query->get();

        $export = new TransactionsExport($transactions);

        $filename = 'transactions-'.($request->query('month') ?? 'all').'.xlsx';

        return Excel::download($export, $filename);
    }
}
