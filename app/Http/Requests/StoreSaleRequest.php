<?php

namespace App\Http\Requests;

use App\Models\Pump;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pump_id' => ['required', 'exists:pumps,id'],
            'volume' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', Rule::in(['cash', 'card', 'credit'])],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'sold_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Ensure the pump's tank holds enough fuel for the requested volume,
     * and that credit sales always have a customer.
     */
    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator): void {
            if ($this->input('payment_method') === 'credit' && ! $this->input('customer_id')) {
                $validator->errors()->add('customer_id', __('credit.customer_required'));
            }
            $pump = Pump::with('tank')->find($this->input('pump_id'));

            if ($pump === null) {
                return;
            }

            $volume = (float) $this->input('volume');

            if ($volume > (float) $pump->tank->current_volume) {
                $validator->errors()->add(
                    'volume',
                    "Only {$pump->tank->current_volume} {$pump->tank->fuelType?->unit} available in {$pump->tank->name}.",
                );
            }
        });
    }
}
