<?php

namespace App\Http\Requests\Debt;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDebtRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'total_amount' => ['required', 'numeric', 'gt:0'],
            'installment_amount' => ['required', 'numeric', 'gt:0'],
            'total_installments' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
            'start_date' => ['required', 'date'],
            'payment_day' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:31'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
