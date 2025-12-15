<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Tests\TestCase;

class OrderbookTest extends TestCase
{
    public function test_orderbook_returns_only_open_orders(): void
    {
        $user1 = User::factory()->create(['balance' => '10000.00000000']);
        $user2 = User::factory()->create(['balance' => '10000.00000000']);
        $seller = User::factory()->create(['balance' => '0.00000000']);

        Asset::factory()
            ->for($seller)
            ->symbol('BTC')
            ->withAmount('10.00000000')
            ->create();

        // Create sell order
        $this->actingAs($seller)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => '50000.00000000',
            'amount' => '0.01000000',
        ]);

        // Create buy order that will be matched
        $this->actingAs($user1)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '50000.00000000',
            'amount' => '0.01000000',
        ]);

        // Create another open buy order
        $this->actingAs($user2)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '49000.00000000',
            'amount' => '0.01000000',
        ]);

        $response = $this->actingAs($user1)->getJson('/api/orders?symbol=BTC');

        $response->assertOk();
        // Only the unfilled buy order should be in orderbook
        $this->assertCount(1, $response->json('buy_orders'));
        $this->assertCount(0, $response->json('sell_orders'));
    }

    public function test_orderbook_filters_by_symbol(): void
    {
        $user = User::factory()->create(['balance' => '10000.00000000']);

        // Create BTC order
        $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '50000.00000000',
            'amount' => '0.01000000',
        ]);

        // Create ETH order
        $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'ETH',
            'side' => 'buy',
            'price' => '3000.00000000',
            'amount' => '0.10000000',
        ]);

        // Get BTC orderbook
        $response = $this->actingAs($user)->getJson('/api/orders?symbol=BTC');

        $response->assertOk();
        $response->assertJson(['symbol' => 'BTC']);
        $this->assertCount(1, $response->json('buy_orders'));

        // Get ETH orderbook
        $response = $this->actingAs($user)->getJson('/api/orders?symbol=ETH');

        $response->assertOk();
        $response->assertJson(['symbol' => 'ETH']);
        $this->assertCount(1, $response->json('buy_orders'));
    }

    public function test_buy_orders_sorted_by_price_descending(): void
    {
        $user1 = User::factory()->create(['balance' => '10000.00000000']);
        $user2 = User::factory()->create(['balance' => '10000.00000000']);
        $user3 = User::factory()->create(['balance' => '10000.00000000']);

        // Create buy orders at different prices
        $this->actingAs($user1)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '50000.00000000',
            'amount' => '0.01000000',
        ]);

        $this->actingAs($user2)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '52000.00000000', // Highest
            'amount' => '0.01000000',
        ]);

        $this->actingAs($user3)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '49000.00000000', // Lowest
            'amount' => '0.01000000',
        ]);

        $response = $this->actingAs($user1)->getJson('/api/orders?symbol=BTC');

        $buyOrders = $response->json('buy_orders');
        $this->assertEquals('52000.00000000', $buyOrders[0]['price']);
        $this->assertEquals('50000.00000000', $buyOrders[1]['price']);
        $this->assertEquals('49000.00000000', $buyOrders[2]['price']);
    }

    public function test_sell_orders_sorted_by_price_ascending(): void
    {
        $user1 = User::factory()->create(['balance' => '0.00000000']);
        $user2 = User::factory()->create(['balance' => '0.00000000']);
        $user3 = User::factory()->create(['balance' => '0.00000000']);

        Asset::factory()->for($user1)->symbol('BTC')->withAmount('1.00000000')->create();
        Asset::factory()->for($user2)->symbol('BTC')->withAmount('1.00000000')->create();
        Asset::factory()->for($user3)->symbol('BTC')->withAmount('1.00000000')->create();

        // Create sell orders at different prices
        $this->actingAs($user1)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => '50000.00000000',
            'amount' => '0.01000000',
        ]);

        $this->actingAs($user2)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => '48000.00000000', // Lowest
            'amount' => '0.01000000',
        ]);

        $this->actingAs($user3)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => '52000.00000000', // Highest
            'amount' => '0.01000000',
        ]);

        $response = $this->actingAs($user1)->getJson('/api/orders?symbol=BTC');

        $sellOrders = $response->json('sell_orders');
        $this->assertEquals('48000.00000000', $sellOrders[0]['price']);
        $this->assertEquals('50000.00000000', $sellOrders[1]['price']);
        $this->assertEquals('52000.00000000', $sellOrders[2]['price']);
    }

    public function test_orderbook_excludes_filled_orders(): void
    {
        $buyer = User::factory()->create(['balance' => '1000.00000000']);
        $seller = User::factory()->create(['balance' => '0.00000000']);

        Asset::factory()
            ->for($seller)
            ->symbol('BTC')
            ->withAmount('1.00000000')
            ->create();

        $this->actingAs($seller)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => '50000.00000000',
            'amount' => '0.01000000',
        ]);

        $this->actingAs($buyer)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '50000.00000000',
            'amount' => '0.01000000',
        ]);

        $response = $this->actingAs($buyer)->getJson('/api/orders?symbol=BTC');

        $response->assertOk();
        $this->assertCount(0, $response->json('buy_orders'));
        $this->assertCount(0, $response->json('sell_orders'));
    }

    public function test_orderbook_excludes_cancelled_orders(): void
    {
        $user = User::factory()->create(['balance' => '1000.00000000']);

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '50000.00000000',
            'amount' => '0.01000000',
        ]);

        $orderId = $response->json('order.id');

        // Cancel the order
        $this->actingAs($user)->postJson("/api/orders/{$orderId}/cancel");

        $response = $this->actingAs($user)->getJson('/api/orders?symbol=BTC');

        $response->assertOk();
        $this->assertCount(0, $response->json('buy_orders'));
    }

    public function test_orderbook_requires_authentication(): void
    {
        $response = $this->getJson('/api/orders?symbol=BTC');

        $response->assertUnauthorized();
    }

    public function test_orderbook_validates_symbol_enum(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/orders?symbol=INVALID');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['symbol']);
    }
}
