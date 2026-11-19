<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class StoreWithdrawalRequest extends FormRequest
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
        $min = config('cashfree.limits.min_withdrawal_rupees', 1.00);
        $max = config('cashfree.limits.max_withdrawal_rupees', 100000.00);

        return [
            'bank_account_id' => ['required', 'integer', 'exists:bank_accounts,id'],
            'amount' => ['required', 'numeric', "min:{$min}", "max:{$max}"],
            'idempotency_key' => ['required', 'string', 'uuid'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'bank_account_id.required' => 'Please select a bank account for withdrawal.',
            'bank_account_id.exists' => 'The selected bank account is invalid.',
            'amount.required' => 'Please enter the withdrawal amount.',
            'amount.min' => 'Minimum withdrawal amount is ₹' . config('cashfree.limits.min_withdrawal_rupees', 1.00),
            'amount.max' => 'Maximum withdrawal amount is ₹' . number_format(config('cashfree.limits.max_withdrawal_rupees', 100000.00)),
            'idempotency_key.required' => 'Missing security idempotency key.',
            'idempotency_key.uuid' => 'Invalid idempotency token.',
        ];
    }
}
