<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TankRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'fuel_type_id' => ['required', 'exists:fuel_types,id'],
            'capacity' => ['required', 'numeric', 'min:0'],
            'current_volume' => ['required', 'numeric', 'min:0', 'lte:capacity'],
        ];
    }
}
