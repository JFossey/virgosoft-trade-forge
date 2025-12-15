<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Brick\Math\BigDecimal; // Import BigDecimal

class UserBalanceTest extends TestCase
{
    public function test_balance_decimal_precision_is_maintained(): void
    {
        // Create a user with a precise balance value (without mass assignment)
        $user = new User;
        $user->name = 'Test User';
        $user->email = 'balance.test@example.com';
        $user->password = bcrypt('password');
        $user->balance = BigDecimal::of('1234.56789012'); // Assign as BigDecimal
        $user->save();

        // Refresh the model from the database
        $user->refresh();

        // Assert the balance is a BigDecimal object and its string representation is correct
        $this->assertInstanceOf(BigDecimal::class, $user->balance);
        $this->assertEquals('1234.56789012', $user->balance->toScale(8)->__toString());

        // Test updating balance with different precision values
        $user->balance = BigDecimal::of('9999.99999999'); // Assign as BigDecimal
        $user->save();
        $user->refresh();

        $this->assertEquals('9999.99999999', $user->balance->toScale(8)->__toString());

        // Test that decimal precision is maintained with trailing zeros
        $user->balance = BigDecimal::of('100.00000000'); // Assign as BigDecimal
        $user->save();
        $user->refresh();

        $this->assertEquals('100.00000000', $user->balance->toScale(8)->__toString());

        // Test very small decimal values
        $user->balance = BigDecimal::of('0.00000001'); // Assign as BigDecimal
        $user->save();
        $user->refresh();

        $this->assertEquals('0.00000001', $user->balance->toScale(8)->__toString());
    }

    public function test_balance_defaults_to_zero_with_correct_precision(): void
    {
        // Create a user without specifying balance (without mass assignment)
        $user = new User;
        $user->name = 'Default Balance User';
        $user->email = 'default.balance@example.com';
        $user->password = bcrypt('password');
        $user->save();

        $user->refresh();

        // Assert balance defaults to zero with 8 decimal places
        $this->assertInstanceOf(BigDecimal::class, $user->balance);
        $this->assertEquals('0.00000000', $user->balance->toScale(8)->__toString());
    }
}