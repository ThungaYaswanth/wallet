<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankAccount>
 */
class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        $accountNum = (string) fake()->numberBetween(1000000000, 9999999999);

        return [
            'user_id' => User::factory(),
            'account_holder_name' => fake()->name(),
            'account_number' => $accountNum,
            'account_number_last4' => substr($accountNum, -4),
            'account_number_hash' => BankAccount::makeAccountHash($accountNum),
            'ifsc_code' => 'HDFC0001234',
            'bank_name' => 'HDFC Bank',
            'branch_name' => 'Mumbai Main',
            'is_verified' => true,
            'verification_status' => 'valid',
            'verification_reference_id' => 'ref_' . fake()->randomNumber(6),
            'name_at_bank' => fake()->name(),
            'cashfree_beneficiary_id' => 'bene_' . fake()->unique()->slug(2),
            'verified_at' => now(),
        ];
    }
}
