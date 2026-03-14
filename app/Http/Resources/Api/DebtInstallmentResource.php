<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DebtInstallmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'debt_id' => $this->debt_id,
            'installment_number' => $this->installment_number,
            'amount' => $this->amount,
            'due_date' => $this->due_date->toDateString(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'transaction_id' => $this->transaction_id,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
