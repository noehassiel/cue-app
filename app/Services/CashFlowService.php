<?php

namespace App\Services;

use App\Models\Workspace;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;

class CashFlowService
{
    /**
     * Operational balance = opening_balance + confirmed_income - confirmed_expense - funds_allocated
     */
    public function getOperationalBalance(Workspace $workspace): string
    {
        $confirmedIncome = $workspace->transactions()
            ->where('type', 'income')
            ->whereNotNull('confirmed_at')
            ->sum('amount');

        $confirmedExpenses = $workspace->transactions()
            ->where('type', 'expense')
            ->whereNotNull('confirmed_at')
            ->sum('amount');

        $fundsAllocated = $workspace->funds()
            ->sum('current_balance');

        $balance = bcadd((string) $workspace->opening_balance, (string) $confirmedIncome, 4);
        $balance = bcsub($balance, (string) $confirmedExpenses, 4);
        $balance = bcsub($balance, (string) $fundsAllocated, 4);

        return $balance;
    }

    /**
     * Projected balance at a given date includes all unconfirmed (projected) transactions up to that date.
     */
    public function getProjectedBalance(Workspace $workspace, CarbonInterface $targetDate): string
    {
        $operationalBalance = $this->getOperationalBalance($workspace);

        $projectedIncome = $workspace->transactions()
            ->where('type', 'income')
            ->whereNull('confirmed_at')
            ->whereDate('projected_date', '<=', $targetDate)
            ->sum('amount');

        $projectedExpenses = $workspace->transactions()
            ->where('type', 'expense')
            ->whereNull('confirmed_at')
            ->whereDate('projected_date', '<=', $targetDate)
            ->sum('amount');

        $balance = bcadd($operationalBalance, (string) $projectedIncome, 4);
        $balance = bcsub($balance, (string) $projectedExpenses, 4);

        return $balance;
    }

    /**
     * Returns monthly period summaries from the current month forward.
     *
     * @return array<int, array{
     *     month: string,
     *     projected_income: string,
     *     projected_expenses: string,
     *     projected_net: string,
     *     confirmed_income: string,
     *     confirmed_expenses: string,
     *     balance_start: string,
     *     balance_end: string,
     *     deficit: bool
     * }>
     */
    public function getMonthlyPeriods(Workspace $workspace, int $months, ?CarbonInterface $fromDate = null): array
    {
        $fromDate = $fromDate ?? Carbon::now()->startOfMonth();
        $periods = [];
        $runningBalance = $this->getOperationalBalance($workspace);

        foreach (CarbonPeriod::create($fromDate, '1 month', $fromDate->copy()->addMonths($months - 1)) as $periodStart) {
            $periodEnd = $periodStart->copy()->endOfMonth();
            $monthKey = $periodStart->format('Y-m');

            $projectedIncome = $workspace->transactions()
                ->where('type', 'income')
                ->whereNull('confirmed_at')
                ->whereBetween('projected_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->sum('amount');

            $projectedExpenses = $workspace->transactions()
                ->where('type', 'expense')
                ->whereNull('confirmed_at')
                ->whereBetween('projected_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->sum('amount');

            $confirmedIncome = $workspace->transactions()
                ->where('type', 'income')
                ->whereNotNull('confirmed_at')
                ->whereBetween('projected_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->sum('amount');

            $confirmedExpenses = $workspace->transactions()
                ->where('type', 'expense')
                ->whereNotNull('confirmed_at')
                ->whereBetween('projected_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->sum('amount');

            $projectedNet = bcsub((string) $projectedIncome, (string) $projectedExpenses, 4);
            $balanceStart = $runningBalance;
            $totalInflow = bcadd((string) $projectedIncome, (string) $confirmedIncome, 4);
            $totalOutflow = bcadd((string) $projectedExpenses, (string) $confirmedExpenses, 4);
            $balanceEnd = bcadd(bcsub($runningBalance, $totalOutflow, 4), $totalInflow, 4);

            $periods[] = [
                'month' => $monthKey,
                'projected_income' => number_format((float) $projectedIncome, 4, '.', ''),
                'projected_expenses' => number_format((float) $projectedExpenses, 4, '.', ''),
                'projected_net' => $projectedNet,
                'confirmed_income' => number_format((float) $confirmedIncome, 4, '.', ''),
                'confirmed_expenses' => number_format((float) $confirmedExpenses, 4, '.', ''),
                'balance_start' => $balanceStart,
                'balance_end' => $balanceEnd,
                'deficit' => bccomp($balanceEnd, '0.0000', 4) < 0,
            ];

            $runningBalance = $balanceEnd;
        }

        return $periods;
    }
}
