<?php

namespace App\Http\Requests\OrderFlow;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SelectGiveawayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                'numeric',
            ],
            'quantity' => ['required', 'numeric', 'min:1'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
