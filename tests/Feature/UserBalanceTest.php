<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_balance_decimal_precision_is_maintained(): void
    {
        // Create a user with a precise balance value (without mass assignment)
        $user = new User();
        $user->name = 'Test User';
        $user->email = 'balance.test@example.com';
        $user->password = bcrypt('password');
        $user->balance = '1234.56789012';
        $user->save();

        // Refresh the model from the database
        $user->refresh();

        // Assert the balance is cast as a string with correct precision
        $this->assertIsString($user->balance);
        $this->assertEquals('1234.56789012', $user->balance);

        // Test updating balance with different precision values
        $user->balance = '9999.99999999';
        $user->save();
        $user->refresh();

        $this->assertEquals('9999.99999999', $user->balance);

        // Test that decimal precision is maintained with trailing zeros
        $user->balance = '100.00000000';
        $user->save();
        $user->refresh();

        $this->assertEquals('100.00000000', $user->balance);

        // Test very small decimal values
        $user->balance = '0.00000001';
        $user->save();
        $user->refresh();

        $this->assertEquals('0.00000001', $user->balance);
    }

    public function test_balance_defaults_to_zero_with_correct_precision(): void
    {
        // Create a user without specifying balance (without mass assignment)
        $user = new User();
        $user->name = 'Default Balance User';
        $user->email = 'default.balance@example.com';
        $user->password = bcrypt('password');
        $user->save();

        $user->refresh();

        // Assert balance defaults to zero with 8 decimal places
        $this->assertIsString($user->balance);
        $this->assertEquals('0.00000000', $user->balance);
    }
}
