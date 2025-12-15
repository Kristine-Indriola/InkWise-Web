<?php

namespace App\Http\Requests\OrderFlow;

use Illuminate\Foundation\Http\FormRequest;

class SelectEnvelopeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'envelope_id' => ['nullable', 'integer', 'exists:product_envelopes,id'],
            'quantity' => ['required', 'integer', 'min:20'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
