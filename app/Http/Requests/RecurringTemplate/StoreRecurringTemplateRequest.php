<?php

namespace App\Http\Requests\RecurringTemplate;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRecurringTemplateRequest extends FormRequest
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
            'concept' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency' => ['required', 'string', 'size:3'],
            'category' => ['sometimes', 'nullable', 'string', 'max:100'],
            'frequency' => ['required', 'in:weekly,biweekly,monthly,custom'],
            'frequency_day' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:31'],
            'next_date' => ['required', 'date'],
            'generate_ahead_days' => ['sometimes', 'integer', 'min:1', 'max:365'],
        ];
    }
}
