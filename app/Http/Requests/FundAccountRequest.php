<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FundAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only authenticated users can fund their accounts
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'integer',
                'min:1', // Minimum amount to fund, preventing zero or negative values
            ],
            'confirmation' => [
                'required',
                'boolean',
                'accepted', // Ensures the checkbox is checked (i.e., true)
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.min' => 'The funding amount must be greater than zero.',
            'confirmation.accepted' => 'You must confirm that you have transferred the funds.',
        ];
    }
}
