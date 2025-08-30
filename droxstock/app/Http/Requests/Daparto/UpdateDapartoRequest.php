<?php

namespace App\Http\Requests\Daparto;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDapartoRequest extends FormRequest
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
        // For updates, create specific rules that don't require fields
        return [
            'tiltle' => 'nullable|string|max:255',
            'teilemarke_teilenummer' => 'nullable|string|max:255',
            'preis' => 'nullable|numeric|min:0.01',
            'interne_artikelnummer' => 'prohibited', // Cannot be changed
            'zustand' => 'nullable|integer|min:0|max:5',
            'pfand' => 'nullable|integer|min:0',
            'versandklasse' => 'nullable|integer|min:1|max:5',
            'lieferzeit' => 'nullable|integer|min:1',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Custom validation logic
            $data = $this->input();

            // Check if at least one field is being updated
            if (empty(array_filter($data))) {
                $validator->errors()->add('general', 'At least one field must be provided for update');
            }

            // Business rule: Price cannot be changed to 0 if it was previously non-zero
            if (isset($data['preis']) && $data['preis'] == 0) {
                $validator->errors()->add('preis', 'Price cannot be set to 0. Use a minimum value of 0.01');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'tiltle.max' => 'Title must not exceed 255 characters.',
            'teilemarke_teilenummer.max' => 'Part brand and number must not exceed 255 characters.',
            'preis.numeric' => 'Price must be a valid number.',
            'preis.min' => 'Price must be at least 0.01.',
            'interne_artikelnummer.prohibited' => 'Internal article number cannot be changed once created.',
            'zustand.integer' => 'Condition must be a whole number.',
            'zustand.min' => 'Condition must be at least 0.',
            'zustand.max' => 'Condition must not exceed 5.',
            'pfand.integer' => 'Deposit must be a whole number.',
            'pfand.min' => 'Deposit must be at least 0.',
            'versandklasse.integer' => 'Shipping class must be a whole number.',
            'versandklasse.min' => 'Shipping class must be at least 1.',
            'versandklasse.max' => 'Shipping class must not exceed 5.',
            'lieferzeit.integer' => 'Delivery time must be a whole number.',
            'lieferzeit.min' => 'Delivery time must be at least 1 day.',
        ];
    }
}
