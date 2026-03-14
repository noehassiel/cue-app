<?php

namespace App\Http\Requests\RecurringTemplate;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRecurringTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'concept' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:income,expense'],
            'amount' => ['sometimes', 'numeric', 'gt:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'category' => ['sometimes', 'nullable', 'string', 'max:100'],
            'frequency' => ['sometimes', 'in:weekly,biweekly,monthly,custom'],
            'frequency_day' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:31'],
            'next_date' => ['sometimes', 'date'],
            'generate_ahead_days' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
