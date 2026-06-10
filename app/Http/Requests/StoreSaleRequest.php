<?php

namespace App\Http\Requests;

use App\Models\Pump;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
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
            'payment_method' => ['required', Rule::in(['cash', 'card'])],
            'sold_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Ensure the pump's tank holds enough fuel for the requested volume.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
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
