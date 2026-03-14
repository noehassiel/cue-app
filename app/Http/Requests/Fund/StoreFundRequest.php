<?php

namespace App\Http\Requests\Fund;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFundRequest extends FormRequest
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
            'description' => ['sometimes', 'nullable', 'string'],
            'target_amount' => ['sometimes', 'nullable', 'numeric', 'gte:0'],
            'currency' => ['required', 'string', 'size:3'],
            'target_date' => ['sometimes', 'nullable', 'date'],
            'color' => ['sometimes', 'nullable', 'string', 'max:7'],
        ];
    }
}
