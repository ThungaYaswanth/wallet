<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class CreateDepositOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $min = config('cashfree.limits.min_deposit_rupees', 1.00);
        $max = config('cashfree.limits.max_deposit_rupees', 500000.00);

        return [
            'amount' => ['required', 'numeric', "min:{$min}", "max:{$max}"],
            'customer_phone' => ['nullable', 'string', 'regex:/^[6-9][0-9]{9}$/'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Please enter the amount to add.',
            'amount.numeric' => 'The amount must be a valid number.',
            'amount.min' => 'Minimum deposit amount is ₹' . config('cashfree.limits.min_deposit_rupees', 1.00),
            'amount.max' => 'Maximum deposit amount is ₹' . number_format(config('cashfree.limits.max_deposit_rupees', 500000.00)),
            'customer_phone.regex' => 'Please enter a valid 10-digit Indian mobile number.',
        ];
    }
}
