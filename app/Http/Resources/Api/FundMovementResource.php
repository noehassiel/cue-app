<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FundMovementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fund_id' => $this->fund_id,
            'transaction_id' => $this->transaction_id,
            'type' => $this->type,
            'amount' => $this->amount,
            'note' => $this->note,
            'movement_date' => $this->movement_date->toDateString(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
