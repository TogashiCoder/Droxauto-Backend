<?php

namespace App\Http\Requests\Daparto;

use App\Models\Daparto;
use Illuminate\Foundation\Http\FormRequest;

class StoreDapartoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // No authentication needed for now
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return Daparto::validationRules();
    }

    public function messages(): array
    {
        return [
            'tiltle.max' => 'Title must not exceed 255 characters.',
            'teilemarke_teilenummer.required' => 'Part brand and number is required.',
            'teilemarke_teilenummer.max' => 'Part brand and number must not exceed 255 characters.',
            'preis.required' => 'Price is required.',
            'preis.numeric' => 'Price must be a valid number.',
            'preis.min' => 'Price must be at least 0.',
            'interne_artikelnummer.required' => 'Internal article number is required.',
            'interne_artikelnummer.max' => 'Internal article number must not exceed 100 characters.',
            'interne_artikelnummer.unique' => 'This internal article number is already registered.',
            'zustand.required' => 'Condition is required.',
            'zustand.integer' => 'Condition must be a whole number.',
            'zustand.min' => 'Condition must be at least 0.',
            'pfand.required' => 'Deposit is required.',
            'pfand.integer' => 'Deposit must be a whole number.',
            'pfand.min' => 'Deposit must be at least 0.',
            'versandklasse.required' => 'Shipping class is required.',
            'versandklasse.integer' => 'Shipping class must be a whole number.',
            'versandklasse.min' => 'Shipping class must be at least 0.',
            'lieferzeit.required' => 'Delivery time is required.',
            'lieferzeit.integer' => 'Delivery time must be a whole number.',
            'lieferzeit.min' => 'Delivery time must be at least 0.',
        ];
    }
}
