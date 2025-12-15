<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Brick\Math\BigDecimal;

class ProfileTest extends TestCase
{
    public function test_unauthenticated_user_cannot_view_profile(): void
    {
        $response = $this->getJson('/api/profile');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_view_own_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'balance' => '1000.50000000',
        ]);

        $response = $this->actingAs($user)->getJson('/api/profile');

        $response
            ->assertOk()
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'balance' => '1000.50000000',
                ],
            ]);
    }

    public function test_profile_includes_user_assets(): void
    {
        $user = User::factory()->create();

        // Create BTC asset
        Asset::factory()
            ->for($user)
            ->symbol('BTC')
            ->withAmount('0.50000000')
            ->withLockedAmount('0.10000000')
            ->create();

        // Create ETH asset
        Asset::factory()
            ->for($user)
            ->symbol('ETH')
            ->withAmount('5.00000000')
            ->withLockedAmount('0.00000000')
            ->create();

        $response = $this->actingAs($user)->getJson('/api/profile');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'assets')
            ->assertJsonPath('assets.0.symbol', 'BTC')
            ->assertJsonPath('assets.0.amount', '0.50000000')
            ->assertJsonPath('assets.0.locked_amount', '0.10000000')
            ->assertJsonPath('assets.1.symbol', 'ETH')
            ->assertJsonPath('assets.1.amount', '5.00000000')
            ->assertJsonPath('assets.1.locked_amount', '0.00000000');
    }

    public function test_profile_returns_empty_assets_for_new_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/profile');

        $response
            ->assertOk()
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'assets' => [],
            ]);
    }

    public function test_profile_preserves_decimal_precision(): void
    {
        $user = User::factory()->create([
            'balance' => '12345.67890123',
        ]);

        Asset::factory()
            ->for($user)
            ->symbol('BTC')
            ->withAmount('0.00000001')
            ->withLockedAmount('0.99999999')
            ->create();

        $response = $this->actingAs($user)->getJson('/api/profile');

        $response
            ->assertOk()
            ->assertJsonPath('user.balance', '12345.67890123')
            ->assertJsonPath('assets.0.amount', '0.00000001')
            ->assertJsonPath('assets.0.locked_amount', '0.99999999');
    }

    public function test_profile_includes_all_assets_even_with_zero_balance(): void
    {
        $user = User::factory()->create();

        // Create asset with zero balance
        Asset::factory()
            ->for($user)
            ->symbol('BTC')
            ->withAmount('0.00000000')
            ->withLockedAmount('0.00000000')
            ->create();

        // Create asset with non-zero balance
        Asset::factory()
            ->for($user)
            ->symbol('ETH')
            ->withAmount('1.00000000')
            ->withLockedAmount('0.00000000')
            ->create();

        $response = $this->actingAs($user)->getJson('/api/profile');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'assets');
    }

    // New tests for funding account functionality
    public function test_authenticated_user_can_fund_their_account_successfully(): void
    {
        $user = User::factory()->create(['balance' => '100.00']);
        $initialBalance = BigDecimal::of($user->balance);
        $fundAmount = BigDecimal::of('50.50');

        $response = $this->actingAs($user)->postJson('/api/account/fund', [
            'amount' => $fundAmount->toScale(8)->__toString(),
            'confirmation' => true,
        ]);

        $response->assertOk()
                 ->assertJson([
                     'message' => 'Account funded successfully.',
                     'user' => [
                         'id' => $user->id,
                         'balance' => $initialBalance->plus($fundAmount)->toScale(8)->__toString(),
                     ],
                 ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'balance' => $initialBalance->plus($fundAmount)->toScale(8)->__toString(),
        ]);
    }

    public function test_unauthenticated_user_cannot_fund_account(): void
    {
        $response = $this->postJson('/api/account/fund', [
            'amount' => '100.00',
            'confirmation' => true,
        ]);

        $response->assertUnauthorized();
    }

    public function test_funding_account_requires_amount(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/account/fund', [
            'confirmation' => true,
        ]);

        $response->assertJsonValidationErrors('amount');
    }

    public function test_funding_account_requires_amount_greater_than_zero(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/account/fund', [
            'amount' => '0.00',
            'confirmation' => true,
        ]);

        $response->assertJsonValidationErrors('amount');

        $response = $this->actingAs($user)->postJson('/api/account/fund', [
            'amount' => '-10.00',
            'confirmation' => true,
        ]);

        $response->assertJsonValidationErrors('amount');
    }

    public function test_funding_account_requires_valid_amount_format(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/account/fund', [
            'amount' => 'not-a-number',
            'confirmation' => true,
        ]);

        $response->assertJsonValidationErrors('amount');
    }

    public function test_funding_account_requires_confirmation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/account/fund', [
            'amount' => '100.00',
            'confirmation' => false,
        ]);

        $response->assertJsonValidationErrors('confirmation');

        $response = $this->actingAs($user)->postJson('/api/account/fund', [
            'amount' => '100.00',
            // 'confirmation' is missing
        ]);

        $response->assertJsonValidationErrors('confirmation');
    }

    public function test_funding_account_updates_user_balance_with_precision(): void
    {
        $user = User::factory()->create(['balance' => '0.00000001']);
        $initialBalance = BigDecimal::of($user->balance);
        $fundAmount = BigDecimal::of('9999.99999999');
        $expectedBalance = $initialBalance->plus($fundAmount)->toScale(8)->__toString();

        $response = $this->actingAs($user)->postJson('/api/account/fund', [
            'amount' => $fundAmount->toScale(8)->__toString(),
            'confirmation' => true,
        ]);

        $response->assertOk()
                 ->assertJsonPath('user.balance', $expectedBalance);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'balance' => $expectedBalance,
        ]);
    }

    public function test_concurrent_funding_requests_handle_race_conditions_correctly(): void
    {
        $user = User::factory()->create(['balance' => '100.00']);
        $initialBalance = BigDecimal::of($user->balance);
        $fundAmount = BigDecimal::of('10.00');

        $this->withoutExceptionHandling(); // Disable exception handling to see real errors

        // Simulate concurrent requests
        $promises = [];
        for ($i = 0; $i < 5; $i++) {
            $promises[] = $this->actingAs($user)->postJson('/api/account/fund', [
                'amount' => $fundAmount->toScale(8)->__toString(),
                'confirmation' => true,
            ]);
        }

        // Wait for all promises to resolve
        foreach ($promises as $response) {
            $response->assertOk();
        }

        $user->refresh(); // Reload user to get the latest balance

        $expectedFinalBalance = $initialBalance->plus($fundAmount->multipliedBy(5))->toScale(8)->__toString();
        $this->assertEquals($expectedFinalBalance, $user->balance);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'balance' => $expectedFinalBalance,
        ]);
    }
}