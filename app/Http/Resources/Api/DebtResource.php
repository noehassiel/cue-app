<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DebtResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'name' => $this->name,
            'total_amount' => $this->total_amount,
            'installment_amount' => $this->installment_amount,
            'total_installments' => $this->total_installments,
            'paid_installments' => $this->paid_installments,
            'remaining_installments' => $this->remainingInstallments(),
            'remaining_amount' => $this->remainingAmount(),
            'currency' => $this->currency,
            'start_date' => $this->start_date->toDateString(),
            'payment_day' => $this->payment_day,
            'notes' => $this->notes,
            'next_installment' => $this->whenLoaded('installments', fn () => $this->nextInstallment()
                ? new DebtInstallmentResource($this->nextInstallment())
                : null),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
