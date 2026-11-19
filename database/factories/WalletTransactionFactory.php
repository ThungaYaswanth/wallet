<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WalletTransaction>
 */
class WalletTransactionFactory extends Factory
{
    protected $model = WalletTransaction::class;

    public function definition(): array
    {
        return [
            'wallet_id' => Wallet::factory(),
            'user_id' => fn (array $attributes) => Wallet::find($attributes['wallet_id'])->user_id ?? User::factory(),
            'type' => 'credit',
            'amount' => 10000, // ₹100.00
            'reference_id' => 'ord_' . Str::lower(Str::random(16)),
            'gateway_reference_id' => 'pay_' . Str::lower(Str::random(16)),
            'payment_session_id' => 'session_' . Str::lower(Str::random(16)),
            'transaction_type' => 'deposit',
            'status' => 'completed',
            'description' => 'Test deposit',
            'metadata' => [],
        ];
    }
}
