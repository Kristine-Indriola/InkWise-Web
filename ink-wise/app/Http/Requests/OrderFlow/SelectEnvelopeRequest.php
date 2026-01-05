<?php

namespace App\Http\Requests\OrderFlow;

use App\Models\ProductEnvelope;
use App\Services\OrderFlowService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;

class SelectEnvelopeRequest extends FormRequest
{
    protected ?ProductEnvelope $resolvedEnvelope = null;

    protected array $resolvedAvailability = [];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'envelope_id' => ['nullable', 'integer', 'exists:product_envelopes,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $envelopeId = (int) $this->input('envelope_id');
            if ($envelopeId <= 0) {
                return;
            }

            $this->resolvedEnvelope = ProductEnvelope::with(['material', 'material.inventory'])->find($envelopeId);

            if (!$this->resolvedEnvelope) {
                $validator->errors()->add('envelope_id', 'The selected envelope is no longer available.');
                return;
            }

            $service = app(OrderFlowService::class);
            $this->resolvedAvailability = $service->resolveEnvelopeAvailability($this->resolvedEnvelope);

            $maxQuantity = Arr::get($this->resolvedAvailability, 'max_quantity');

            if ($maxQuantity !== null && (int) $this->input('quantity', 0) > $maxQuantity) {
                $validator->errors()->add('quantity', "Only {$maxQuantity} envelopes are available for the selected material.");
                return;
            }

            $metadata = $this->input('metadata');
            if (!is_array($metadata)) {
                $metadata = [];
            }

            $materialName = $this->resolvedEnvelope->envelope_material_name;
            if (!$materialName && Arr::get($this->resolvedAvailability, 'material')) {
                $materialName = Arr::get($this->resolvedAvailability, 'material.material_name');
            }

            $metadata = array_merge($metadata, array_filter([
                'material' => $materialName,
                'material_id' => $this->resolvedEnvelope->material_id,
                'product_id' => $this->resolvedEnvelope->product_id,
                'max_qty' => Arr::get($this->resolvedAvailability, 'max_quantity'),
                'available_stock' => Arr::get($this->resolvedAvailability, 'available_stock'),
            ], static fn ($value) => $value !== null));

            $this->merge(['metadata' => $metadata]);
        });
    }

    public function resolvedEnvelope(): ?ProductEnvelope
    {
        return $this->resolvedEnvelope;
    }

    public function resolvedEnvelopeAvailability(): array
    {
        return $this->resolvedAvailability;
    }
}
