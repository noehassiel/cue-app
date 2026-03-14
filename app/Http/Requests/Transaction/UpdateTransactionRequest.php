<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
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
            'type' => ['sometimes', 'in:income,expense'],
            'concept' => ['sometimes', 'string', 'max:255'],
            'amount' => ['sometimes', 'numeric', 'gt:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'projected_date' => ['sometimes', 'date'],
            'category' => ['sometimes', 'nullable', 'string', 'max:100'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
