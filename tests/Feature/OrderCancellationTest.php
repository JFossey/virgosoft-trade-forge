<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    public function test_user_can_cancel_own_open_order(): void
    {
        $user = User::factory()->create(['balance' => '1000.00000000']);

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '50000.00000000',
            'amount' => '0.01000000',
        ]);

        $orderId = $response->json('order.id');

        $response = $this->actingAs($user)->postJson("/api/orders/{$orderId}/cancel");

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Order cancelled successfully',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'status' => OrderStatus::CANCELLED->value,
        ]);
    }

    public function test_user_cannot_cancel_another_users_order(): void
    {
        $user1 = User::factory()->create(['balance' => '1000.00000000']);
        $user2 = User::factory()->create(['balance' => '1000.00000000']);

        $response = $this->actingAs($user1)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '50000.00000000',
            'amount' => '0.01000000',
        ]);

        $orderId = $response->json('order.id');

        // User2 tries to cancel user1's order
        $response = $this->actingAs($user2)->postJson("/api/orders/{$orderId}/cancel");

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Unauthorized to cancel this order',
        ]);

        // Order still open
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'status' => OrderStatus::OPEN->value,
        ]);
    }

    public function test_user_cannot_cancel_filled_order(): void
    {
        $buyer = User::factory()->create(['balance' => '1000.00000000']);
        $seller = User::factory()->create(['balance' => '0.00000000']);

        Asset::factory()
            ->for($seller)
            ->symbol('BTC')
            ->withAmount('1.00000000')
            ->create();

        $sellResponse = $this->actingAs($seller)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => '50000.00000000',
            'amount' => '0.01000000',
        ]);

        $sellOrderId = $sellResponse->json('order.id');

        // Match the order
        $this->actingAs($buyer)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '50000.00000000',
            'amount' => '0.01000000',
        ]);

        // Try to cancel filled order
        $response = $this->actingAs($seller)->postJson("/api/orders/{$sellOrderId}/cancel");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['order']);
    }

    public function test_user_cannot_cancel_already_cancelled_order(): void
    {
        $user = User::factory()->create(['balance' => '1000.00000000']);

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '50000.00000000',
            'amount' => '0.01000000',
        ]);

        $orderId = $response->json('order.id');

        // Cancel once
        $this->actingAs($user)->postJson("/api/orders/{$orderId}/cancel");

        // Try to cancel again
        $response = $this->actingAs($user)->postJson("/api/orders/{$orderId}/cancel");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['order']);
    }

    public function test_cancelling_buy_order_refunds_full_balance(): void
    {
        $user = User::factory()->create(['balance' => '1000.00000000']);

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '50000.00000000',
            'amount' => '0.01000000', // Locks $500
        ]);

        $orderId = $response->json('order.id');

        // Balance after order: 500
        $user->refresh();
        $this->assertEquals('500.00000000', $user->balance);

        // Cancel order
        $this->actingAs($user)->postJson("/api/orders/{$orderId}/cancel");

        // Balance restored
        $user->refresh();
        $this->assertEquals('1000.00000000', $user->balance);
    }

    public function test_cancelling_sell_order_unlocks_assets(): void
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

        $orderId = $response->json('order.id');

        // Assets locked
        $asset = $user->assets()->where('symbol', 'BTC')->first();
        $this->assertEquals('0.50000000', $asset->amount);
        $this->assertEquals('0.50000000', $asset->locked_amount);

        // Cancel order
        $this->actingAs($user)->postJson("/api/orders/{$orderId}/cancel");

        // Assets unlocked
        $asset->refresh();
        $this->assertEquals('1.00000000', $asset->amount);
        $this->assertEquals('0.00000000', $asset->locked_amount);
    }

    public function test_cancelled_order_status_is_updated_to_3(): void
    {
        $user = User::factory()->create(['balance' => '1000.00000000']);

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '50000.00000000',
            'amount' => '0.01000000',
        ]);

        $orderId = $response->json('order.id');

        $this->actingAs($user)->postJson("/api/orders/{$orderId}/cancel");

        $order = Order::find($orderId);
        $this->assertEquals(OrderStatus::CANCELLED, $order->status);
        $this->assertEquals(3, $order->status->value);
    }

    public function test_cancel_nonexistent_order_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/orders/99999/cancel');

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Order not found',
        ]);
    }
}
