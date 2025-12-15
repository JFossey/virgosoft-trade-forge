<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Asset;
use App\Models\User;
use Tests\TestCase;

class OrderCreationTest extends TestCase
{
    public function test_user_can_create_buy_order_with_sufficient_balance(): void
    {
        $user = User::factory()->create(['balance' => '1000.00000000']);

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '50000.00000000',
            'amount' => '0.01000000', // Total: $500
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Order placed successfully',
        ]);

        // Verify balance was locked
        $user->refresh();
        $this->assertEquals('500.00000000', $user->balance);

        // Verify order exists
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '50000.00000000',
            'amount' => '0.01000000',
            'status' => OrderStatus::OPEN->value,
        ]);
    }

    public function test_user_cannot_create_buy_order_with_insufficient_balance(): void
    {
        $user = User::factory()->create(['balance' => '100.00000000']);

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '50000.00000000',
            'amount' => '0.01000000', // Requires $500
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['balance']);
        $response->assertJsonFragment([
            'balance' => ['Insufficient balance. Required: 500.00000000, Available: 100.00000000'],
        ]);

        // Balance unchanged
        $user->refresh();
        $this->assertEquals('100.00000000', $user->balance);
    }

    public function test_buy_order_deducts_exact_amount_from_balance(): void
    {
        $user = User::factory()->create(['balance' => '10000.50000000']);

        $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'ETH',
            'side' => 'buy',
            'price' => '3000.12345678',
            'amount' => '2.00000000',
        ]);

        $user->refresh();
        // 10000.50000000 - (3000.12345678 * 2) = 4000.25308644
        $this->assertEquals('4000.25308644', $user->balance);
    }

    public function test_user_can_create_sell_order_with_sufficient_assets(): void
    {
        $user = User::factory()->create();

        Asset::factory()
            ->for($user)
            ->symbol('BTC')
            ->withAmount('1.00000000')
            ->withLockedAmount('0.00000000')
            ->create();

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => '50000.00000000',
            'amount' => '0.50000000',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Order placed successfully',
        ]);

        // Verify assets were locked
        $asset = $user->assets()->where('symbol', 'BTC')->first();
        $this->assertEquals('0.50000000', $asset->amount);
        $this->assertEquals('0.50000000', $asset->locked_amount);

        // Verify order exists
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => '50000.00000000',
            'amount' => '0.50000000',
            'status' => OrderStatus::OPEN->value,
        ]);
    }

    public function test_user_cannot_create_sell_order_with_insufficient_assets(): void
    {
        $user = User::factory()->create();

        Asset::factory()
            ->for($user)
            ->symbol('BTC')
            ->withAmount('0.25000000')
            ->create();

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => '50000.00000000',
            'amount' => '0.50000000', // Requires 0.5 BTC
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['assets']);
        $response->assertJsonFragment([
            'assets' => ['Insufficient assets. Required: 0.50000000, Available: 0.25000000'],
        ]);
    }

    public function test_sell_order_moves_amount_to_locked_amount(): void
    {
        $user = User::factory()->create();

        Asset::factory()
            ->for($user)
            ->symbol('ETH')
            ->withAmount('10.00000000')
            ->withLockedAmount('2.00000000')
            ->create();

        $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'ETH',
            'side' => 'sell',
            'price' => '3000.00000000',
            'amount' => '3.00000000',
        ]);

        $asset = $user->assets()->where('symbol', 'ETH')->first();
        $this->assertEquals('7.00000000', $asset->amount);
        $this->assertEquals('5.00000000', $asset->locked_amount);
    }

    public function test_order_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/orders', [
            // Missing all fields
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['symbol', 'side', 'price', 'amount']);
    }

    public function test_order_validates_positive_price(): void
    {
        $user = User::factory()->create(['balance' => '1000.00000000']);

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '0',
            'amount' => '0.01000000',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['price']);
    }

    public function test_order_validates_positive_amount(): void
    {
        $user = User::factory()->create(['balance' => '1000.00000000']);

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '50000.00000000',
            'amount' => '0',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);
    }

    public function test_order_validates_symbol_enum(): void
    {
        $user = User::factory()->create(['balance' => '1000.00000000']);

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'INVALID',
            'side' => 'buy',
            'price' => '50000.00000000',
            'amount' => '0.01000000',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['symbol']);
    }

    public function test_order_validates_side_enum(): void
    {
        $user = User::factory()->create(['balance' => '1000.00000000']);

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'invalid',
            'price' => '50000.00000000',
            'amount' => '0.01000000',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['side']);
    }

    public function test_decimal_precision_is_preserved_in_orders(): void
    {
        $user = User::factory()->create(['balance' => '10000.00000000']);

        $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '12345.67890123',
            'amount' => '0.00000001',
        ]);

        $this->assertDatabaseHas('orders', [
            'price' => '12345.67890123',
            'amount' => '0.00000001',
        ]);
    }

    public function test_unauthenticated_user_cannot_create_order(): void
    {
        $response = $this->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '50000.00000000',
            'amount' => '0.01000000',
        ]);

        $response->assertUnauthorized();
    }
}
