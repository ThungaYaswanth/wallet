<?php

namespace App\Models;

use Database\Factories\WalletTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    /** @use HasFactory<WalletTransactionFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'wallet_id',
        'user_id',
        'type',
        'amount',
        'reference_id',
        'gateway_reference_id',
        'payment_session_id',
        'transaction_type',
        'status',
        'description',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Amount formatted in rupees.
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
     * Wallet that this transaction belongs to.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * User who initiated or owns this transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
