<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Withdrawal>
 */
class WithdrawalFactory extends Factory
{
    protected $model = Withdrawal::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'wallet_id' => fn (array $attributes) => Wallet::where('user_id', $attributes['user_id'])->first()?->id ?? Wallet::factory(['user_id' => $attributes['user_id']]),
            'bank_account_id' => fn (array $attributes) => BankAccount::factory(['user_id' => $attributes['user_id']]),
            'wallet_transaction_id' => null,
            'amount' => 50000, // ₹500.00
            'transfer_id' => 'tr_' . Str::lower(Str::random(16)),
            'idempotency_key' => (string) Str::uuid(),
            'cashfree_reference_id' => 'cf_' . Str::lower(Str::random(12)),
            'utr' => 'UTR' . fake()->numerify('##########'),
            'status' => 'completed',
            'failure_reason' => null,
            'processed_at' => now(),
        ];
    }
}
