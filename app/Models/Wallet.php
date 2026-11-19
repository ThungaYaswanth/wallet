<?php

namespace App\Models;

use Database\Factories\WalletFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    /** @use HasFactory<WalletFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'balance',
        'reserved_balance',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'balance' => 'integer',
        'reserved_balance' => 'integer',
    ];

    /**
     * Convert Rupees to exact integer Paise without floating-point errors.
     */
    public static function rupeesToPaise(float|int|string $rupees): int
    {
        $rupees = trim((string) $rupees);
        if ($rupees === '' || !is_numeric($rupees)) {
            return 0;
        }

        $isNegative = str_starts_with($rupees, '-');
        if ($isNegative) {
            $rupees = substr($rupees, 1);
        }

        if (!str_contains($rupees, '.')) {
            $paise = ((int) $rupees) * 100;
        } else {
            [$whole, $decimal] = explode('.', $rupees, 2);
            $whole = (int) $whole;
            // Pad or truncate decimal to exactly 2 digits
            $decimal = substr(str_pad($decimal, 2, '0'), 0, 2);
            $paise = ($whole * 100) + (int) $decimal;
        }

        return $isNegative ? -$paise : $paise;
    }

    /**
     * Convert integer Paise to formatted decimal Rupees string (e.g., "19.99").
     */
    public static function paiseToRupees(int $paise): string
    {
        return number_format($paise / 100, 2, '.', '');
    }

    /**
     * Format integer Paise with Indian Rupee symbol (e.g., "₹19.99" or "₹1,000.00").
     */
    public static function formatPaise(int $paise): string
    {
        return '₹' . number_format($paise / 100, 2);
    }

    /**
     * Get available balance in decimal rupees string.
     */
    public function getAvailableBalanceRupeesAttribute(): string
    {
        return self::paiseToRupees($this->balance);
    }

    /**
     * Get reserved balance in decimal rupees string.
     */
    public function getReservedBalanceRupeesAttribute(): string
    {
        return self::paiseToRupees($this->reserved_balance);
    }

    /**
     * Get formatted available balance.
     */
    public function getFormattedAvailableBalanceAttribute(): string
    {
        return self::formatPaise($this->balance);
    }

    /**
     * Get formatted reserved balance.
     */
    public function getFormattedReservedBalanceAttribute(): string
    {
        return self::formatPaise($this->reserved_balance);
    }

    /**
     * User who owns this wallet.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Transactions on this wallet.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
