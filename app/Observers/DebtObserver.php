<?php

namespace App\Observers;

use App\Models\Debt;
use App\Models\DebtInstallment;
use App\Models\Transaction;
use Carbon\Carbon;

class DebtObserver
{
    /**
     * Auto-generate all debt installments and their projected transactions on creation.
     */
    public function created(Debt $debt): void
    {
        $installmentDate = Carbon::parse($debt->start_date);

        for ($i = 1; $i <= $debt->total_installments; $i++) {
            $dueDate = $this->calculateDueDate($installmentDate, $debt->payment_day, $i);

            $transaction = Transaction::create([
                'workspace_id' => $debt->workspace_id,
                'type' => 'expense',
                'concept' => $debt->name . ' - Installment ' . $i . '/' . $debt->total_installments,
                'amount' => $debt->installment_amount,
                'currency' => $debt->currency,
                'category' => 'debt_payment',
                'projected_date' => $dueDate->toDateString(),
            ]);

            DebtInstallment::create([
                'debt_id' => $debt->id,
                'installment_number' => $i,
                'amount' => $debt->installment_amount,
                'due_date' => $dueDate->toDateString(),
                'transaction_id' => $transaction->id,
            ]);
        }
    }

    public function updated(Debt $debt): void {}

    public function deleted(Debt $debt): void {}

    public function restored(Debt $debt): void {}

    public function forceDeleted(Debt $debt): void {}

    private function calculateDueDate(Carbon $startDate, ?int $paymentDay, int $installmentNumber): Carbon
    {
        $date = $startDate->copy()->addMonths($installmentNumber - 1);

        if ($paymentDay !== null) {
            $daysInMonth = $date->daysInMonth;
            $date->setDay(min($paymentDay, $daysInMonth));
        }

        return $date;
    }
}
