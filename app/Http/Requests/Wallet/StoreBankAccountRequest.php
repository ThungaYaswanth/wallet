<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Prepare data before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'ifsc_code' => strtoupper(trim((string) $this->input('ifsc_code'))),
            'account_number' => trim((string) $this->input('account_number')),
            'account_holder_name' => trim((string) $this->input('account_holder_name')),
            'bank_name' => trim((string) $this->input('bank_name')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'account_holder_name' => ['required', 'string', 'min:3', 'max:100'],
            'account_number' => ['required', 'string', 'regex:/^[0-9]{9,18}$/'],
            'account_number_confirmation' => ['required', 'same:account_number'],
            'ifsc_code' => ['required', 'string', 'size:11', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'bank_name' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'account_holder_name.required' => 'Account holder name is required.',
            'account_number.required' => 'Account number is required.',
            'account_number.regex' => 'The bank account number must be between 9 and 18 digits.',
            'account_number_confirmation.same' => 'The account number confirmation does not match.',
            'ifsc_code.required' => 'IFSC code is required.',
            'ifsc_code.size' => 'The IFSC code must be exactly 11 characters.',
            'ifsc_code.regex' => 'The IFSC code format is invalid (e.g. HDFC0001234). The 5th character must be zero (0).',
        ];
    }
}
