<?php

namespace App\Models;

use Database\Factories\WithdrawalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    /** @use HasFactory<WithdrawalFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'wallet_id',
        'bank_account_id',
        'wallet_transaction_id',
        'amount',
        'transfer_id',
        'idempotency_key',
        'cashfree_reference_id',
        'utr',
        'status',
        'failure_reason',
        'processed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'integer',
        'processed_at' => 'datetime',
    ];

    /**
     * Formatted amount with Rupee symbol.
     */
    public function getFormattedAmountAttribute(): string
    {
        return Wallet::formatPaise($this->amount);
    }

    /**
     * Amount in decimal rupees string.
     */
    public function getAmountRupeesAttribute(): string
    {
        return Wallet::paiseToRupees($this->amount);
    }

    /**
     * User who requested the withdrawal.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Wallet from which the funds were reserved and deducted.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Destination bank account.
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * Associated ledger transaction.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'wallet_transaction_id');
    }
}
