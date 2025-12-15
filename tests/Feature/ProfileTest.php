<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

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

        $response->assertOk()
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
        \App\Models\Asset::factory()
            ->for($user)
            ->symbol('BTC')
            ->withAmount('0.50000000')
            ->withLockedAmount('0.10000000')
            ->create();

        // Create ETH asset
        \App\Models\Asset::factory()
            ->for($user)
            ->symbol('ETH')
            ->withAmount('5.00000000')
            ->withLockedAmount('0.00000000')
            ->create();

        $response = $this->actingAs($user)->getJson('/api/profile');

        $response->assertOk()
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

        $response->assertOk()
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

        \App\Models\Asset::factory()
            ->for($user)
            ->symbol('BTC')
            ->withAmount('0.00000001')
            ->withLockedAmount('0.99999999')
            ->create();

        $response = $this->actingAs($user)->getJson('/api/profile');

        $response->assertOk()
            ->assertJsonPath('user.balance', '12345.67890123')
            ->assertJsonPath('assets.0.amount', '0.00000001')
            ->assertJsonPath('assets.0.locked_amount', '0.99999999');
    }

    public function test_profile_includes_all_assets_even_with_zero_balance(): void
    {
        $user = User::factory()->create();

        // Create asset with zero balance
        \App\Models\Asset::factory()
            ->for($user)
            ->symbol('BTC')
            ->withAmount('0.00000000')
            ->withLockedAmount('0.00000000')
            ->create();

        // Create asset with non-zero balance
        \App\Models\Asset::factory()
            ->for($user)
            ->symbol('ETH')
            ->withAmount('1.00000000')
            ->withLockedAmount('0.00000000')
            ->create();

        $response = $this->actingAs($user)->getJson('/api/profile');

        $response->assertOk()
            ->assertJsonCount(2, 'assets');
    }
}
