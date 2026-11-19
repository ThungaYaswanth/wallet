<?php

namespace Tests\Unit;

use App\Models\Wallet;
use PHPUnit\Framework\TestCase;

class WalletPaiseCalculationTest extends TestCase
{
    /**
     * Test exact string-based integer paise conversion without floating point errors.
     */
    public function test_converts_rupees_to_paise_accurately(): void
    {
        // Crucial precision test: 19.99 in standard float multiplication can yield 1998 in PHP
        $this->assertSame(1999, Wallet::rupeesToPaise(19.99));
        $this->assertSame(1999, Wallet::rupeesToPaise('19.99'));
        $this->assertSame(100, Wallet::rupeesToPaise(1.00));
        $this->assertSame(100, Wallet::rupeesToPaise('1'));
        $this->assertSame(5, Wallet::rupeesToPaise('0.05'));
        $this->assertSame(50, Wallet::rupeesToPaise('0.5'));
        $this->assertSame(10050, Wallet::rupeesToPaise(100.5));
        $this->assertSame(1000000, Wallet::rupeesToPaise(10000));
        $this->assertSame(0, Wallet::rupeesToPaise(0));
        $this->assertSame(0, Wallet::rupeesToPaise(''));
        $this->assertSame(0, Wallet::rupeesToPaise('invalid'));
        $this->assertSame(-1999, Wallet::rupeesToPaise(-19.99));
    }

    /**
     * Test conversion from integer paise back to decimal rupee strings.
     */
    public function test_converts_paise_to_rupees_string(): void
    {
        $this->assertSame('19.99', Wallet::paiseToRupees(1999));
        $this->assertSame('1.00', Wallet::paiseToRupees(100));
        $this->assertSame('0.05', Wallet::paiseToRupees(5));
        $this->assertSame('100.50', Wallet::paiseToRupees(10050));
        $this->assertSame('0.00', Wallet::paiseToRupees(0));
    }

    /**
     * Test formatted Rupee strings with currency symbol.
     */
    public function test_formats_paise_with_rupee_symbol(): void
    {
        $this->assertSame('₹19.99', Wallet::formatPaise(1999));
        $this->assertSame('₹1.00', Wallet::formatPaise(100));
        $this->assertSame('₹1,000.00', Wallet::formatPaise(100000));
        $this->assertSame('₹50,000.00', Wallet::formatPaise(5000000));
    }
}
