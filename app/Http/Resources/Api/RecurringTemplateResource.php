<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecurringTemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'concept' => $this->concept,
            'type' => $this->type,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'category' => $this->category,
            'frequency' => $this->frequency,
            'frequency_day' => $this->frequency_day,
            'next_date' => $this->next_date->toDateString(),
            'generate_ahead_days' => $this->generate_ahead_days,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
