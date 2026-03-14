<?php

namespace App\Imports;

use App\Models\Transaction;
use App\Models\Workspace;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class TransactionsImport implements ToCollection, WithHeadingRow, WithValidation
{
    public function __construct(private readonly Workspace $workspace) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            Transaction::create([
                'workspace_id' => $this->workspace->id,
                'type' => $row['type'],
                'concept' => $row['concept'],
                'amount' => $row['amount'],
                'currency' => $row['currency'] ?? $this->workspace->currency,
                'category' => $row['category'] ?? null,
                'projected_date' => $row['projected_date'],
                'confirmed_at' => ! empty($row['confirmed_at']) ? $row['confirmed_at'] : null,
                'notes' => $row['notes'] ?? null,
            ]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:income,expense'],
            'concept' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'projected_date' => ['required', 'date'],
        ];
    }
}
