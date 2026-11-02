<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class BusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * An active currency, or the one the record already has so an unrelated edit still saves after it is retired.
     */
    private function selectableCurrency(?int $currentCurrencyId): Exists
    {
        return Rule::exists('currencies', 'id')->where(
            fn (Builder $query) => $query->where('is_active', true)->orWhere('id', $currentCurrencyId),
        );
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'currency_id' => ['required', 'integer', $this->selectableCurrency($this->user()?->business?->currency_id)],
            'customers_per_station' => ['boolean'],
        ];
    }
}
