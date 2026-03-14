<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransactionsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly Collection $transactions) {}

    public function collection(): Collection
    {
        return $this->transactions;
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return [
            'ID',
            'Type',
            'Concept',
            'Amount',
            'Currency',
            'Category',
            'Projected Date',
            'Confirmed At',
            'Notes',
        ];
    }

    /** @return array<int, mixed> */
    public function map(mixed $transaction): array
    {
        return [
            $transaction->id,
            $transaction->type,
            $transaction->concept,
            $transaction->amount,
            $transaction->currency,
            $transaction->category ?? '',
            $transaction->projected_date->toDateString(),
            $transaction->confirmed_at?->toIso8601String() ?? '',
            $transaction->notes ?? '',
        ];
    }
}
