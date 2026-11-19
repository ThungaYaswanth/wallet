<?php

namespace App\Http\Resources;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletBalanceResource extends JsonResource
{
    /**
     * Disable resource wrapping.
     *
     * @var string|null
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'available_balance_paise' => (int) $this->balance,
            'available_balance_rupees' => (float) Wallet::paiseToRupees($this->balance),
            'formatted_available_balance' => Wallet::formatPaise($this->balance),
            'reserved_balance_paise' => (int) $this->reserved_balance,
            'reserved_balance_rupees' => (float) Wallet::paiseToRupees($this->reserved_balance),
            'formatted_reserved_balance' => Wallet::formatPaise($this->reserved_balance),
            'currency' => 'INR',
        ];
    }
}
