<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FundResource extends JsonResource
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
            'description' => $this->description,
            'target_amount' => $this->target_amount,
            'current_balance' => $this->current_balance,
            'progress_percentage' => $this->progressPercentage(),
            'currency' => $this->currency,
            'target_date' => $this->target_date?->toDateString(),
            'color' => $this->color,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
