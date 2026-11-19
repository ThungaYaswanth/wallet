<?php

namespace App\Models;

use Database\Factories\BankAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    /** @use HasFactory<BankAccountFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'account_holder_name',
        'account_number',
        'account_number_last4',
        'account_number_hash',
        'ifsc_code',
        'bank_name',
        'branch_name',
        'is_verified',
        'verification_status',
        'verification_reference_id',
        'name_at_bank',
        'cashfree_beneficiary_id',
        'verified_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'account_number' => 'encrypted',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * Compute deterministic HMAC-SHA256 hash of the bank account number for uniqueness checks.
     */
    public static function makeAccountHash(string $accountNumber): string
    {
        return hash_hmac('sha256', trim($accountNumber), (string) config('app.key'));
    }

    /**
     * Masked account number representation (e.g., XXXXXX1234).
     */
    public function getMaskedAccountNumberAttribute(): string
    {
        $last4 = $this->account_number_last4;
        if (empty($last4) && !empty($this->account_number)) {
            $last4 = substr((string) $this->account_number, -4);
        }

        return 'XXXXXX' . ($last4 ?: '0000');
    }

    /**
     * User who owns this bank account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Withdrawals made to this bank account.
     */
    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }
}
