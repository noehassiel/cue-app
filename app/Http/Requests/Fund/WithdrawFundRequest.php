<?php

namespace App\Http\Requests\Fund;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class WithdrawFundRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'gt:0'],
            'note' => ['sometimes', 'nullable', 'string', 'max:255'],
            'movement_date' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
