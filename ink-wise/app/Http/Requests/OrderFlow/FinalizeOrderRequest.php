<?php

namespace App\Http\Requests\OrderFlow;

use Illuminate\Foundation\Http\FormRequest;

class FinalizeOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1'],
            'paper_stock_id' => ['nullable', 'integer', 'exists:product_paper_stocks,id'],
            'paper_stock_price' => ['nullable', 'numeric', 'min:0'],
            'paper_stock_name' => ['nullable', 'string', 'max:255'],
            'estimated_date' => ['nullable', 'date', 'after:today'],
            'addons' => ['nullable', 'array'],
            'addons.*' => ['integer', 'exists:product_addons,id'],
            'addon_quantities' => ['nullable', 'array'],
            'addon_quantities.*' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
            'preview_selections' => ['nullable', 'array'],
        ];
    }
}
