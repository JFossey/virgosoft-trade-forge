<?php

namespace Tests\Feature;

use App\Enums\AssetSymbol;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Tests\TestCase;

class ActivityFeedTest extends TestCase
{
    public function test_user_can_fetch_recent_activity(): void
    {
        $user = User::factory()->create(['balance' => '10000.00000000']);

        // Create open order
        Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::OPEN,
            'updated_at' => now()->subMinutes(2),
        ]);

        // Create filled order (trade)
        Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::FILLED,
            'updated_at' => now()->subMinutes(1),
        ]);

        $response = $this->actingAs($user)->getJson('/api/activity');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'activity_type',
                    'symbol',
                    'side',
                    'price',
                    'amount',
                    'total_value',
                    'timestamp',
                ],
            ],
        ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_activity_includes_filled_orders_as_trades(): void
    {
        $user = User::factory()->create(['balance' => '10000.00000000']);

        $filledOrder = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::FILLED,
            'side' => OrderSide::BUY,
            'symbol' => AssetSymbol::BTC,
            'price' => '50000.00000000',
            'amount' => '0.10000000',
            'updated_at' => now()->subMinutes(2),
        ]);

        $response = $this->actingAs($user)->getJson('/api/activity');

        $response->assertStatus(200);
        $activity = $response->json('data')[0];

        $this->assertEquals('trade-'.$filledOrder->id, $activity['id']);
        $this->assertEquals('trade', $activity['activity_type']);
        $this->assertEquals('BTC', $activity['symbol']);
        $this->assertEquals('buy', $activity['side']);
        $this->assertEquals('50000.00000000', $activity['price']);
    }

    public function test_activity_includes_open_orders(): void
    {
        $user = User::factory()->create(['balance' => '10000.00000000']);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::OPEN,
            'side' => OrderSide::BUY,
            'symbol' => AssetSymbol::BTC,
            'price' => '50000.00000000',
            'amount' => '0.10000000',
            'updated_at' => now()->subMinutes(1),
        ]);

        $response = $this->actingAs($user)->getJson('/api/activity');

        $response->assertStatus(200);
        $activity = $response->json('data')[0];

        $this->assertEquals('order-created-'.$order->id, $activity['id']);
        $this->assertEquals('order_created', $activity['activity_type']);
        $this->assertEquals('BTC', $activity['symbol']);
        $this->assertEquals('buy', $activity['side']);
    }

    public function test_activity_includes_cancelled_orders(): void
    {
        $user = User::factory()->create(['balance' => '10000.00000000']);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::CANCELLED,
            'side' => OrderSide::SELL,
            'symbol' => AssetSymbol::ETH,
            'price' => '3000.00000000',
            'amount' => '0.50000000',
            'updated_at' => now()->subMinutes(1),
        ]);

        $response = $this->actingAs($user)->getJson('/api/activity');

        $response->assertStatus(200);
        $activity = $response->json('data')[0];

        $this->assertEquals('order-cancelled-'.$order->id, $activity['id']);
        $this->assertEquals('order_cancelled', $activity['activity_type']);
        $this->assertEquals('ETH', $activity['symbol']);
    }

    public function test_activity_filters_by_time_window(): void
    {
        $user = User::factory()->create(['balance' => '10000.00000000']);

        // Create order within 15 minutes (should appear)
        $recentOrder = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::OPEN,
            'updated_at' => now()->subMinutes(10),
        ]);

        // Create order outside 15 minutes (should NOT appear)
        Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::OPEN,
            'updated_at' => now()->subMinutes(20),
        ]);

        $response = $this->actingAs($user)->getJson('/api/activity');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));

        // Should only have the recent order
        $activity = $response->json('data')[0];
        $this->assertEquals('order-created-'.$recentOrder->id, $activity['id']);
    }

    public function test_activity_sorted_by_most_recent_first(): void
    {
        $user = User::factory()->create(['balance' => '10000.00000000']);

        // Create activities with different timestamps
        $oldOrder = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::FILLED,
            'updated_at' => now()->subMinutes(10),
        ]);

        $middleOrder = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::OPEN,
            'updated_at' => now()->subMinutes(5),
        ]);

        $recentOrder = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::CANCELLED,
            'updated_at' => now()->subMinutes(1),
        ]);

        $response = $this->actingAs($user)->getJson('/api/activity');

        $response->assertStatus(200);
        $activities = $response->json('data');

        // Should be sorted most recent first
        $this->assertEquals('order-cancelled-'.$recentOrder->id, $activities[0]['id']);
        $this->assertEquals('order-created-'.$middleOrder->id, $activities[1]['id']);
        $this->assertEquals('trade-'.$oldOrder->id, $activities[2]['id']);
    }

    public function test_activity_shows_platform_wide_activity(): void
    {
        $user1 = User::factory()->create(['balance' => '10000.00000000']);
        $user2 = User::factory()->create(['balance' => '10000.00000000']);

        // Create orders for different users
        $user1Order = Order::factory()->create([
            'user_id' => $user1->id,
            'status' => OrderStatus::OPEN,
            'updated_at' => now()->subMinutes(2),
        ]);

        $user2Order = Order::factory()->create([
            'user_id' => $user2->id,
            'status' => OrderStatus::FILLED,
            'updated_at' => now()->subMinutes(1),
        ]);

        // Both users should see ALL platform activity
        $response = $this->actingAs($user1)->getJson('/api/activity');
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));

        // Verify both orders are present
        $activityIds = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains('order-created-'.$user1Order->id, $activityIds);
        $this->assertContains('trade-'.$user2Order->id, $activityIds);
    }

    public function test_activity_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/activity');

        $response->assertStatus(401);
    }

    public function test_activity_calculates_total_value_for_orders(): void
    {
        $user = User::factory()->create(['balance' => '10000.00000000']);

        Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::OPEN,
            'price' => '50000.00000000',
            'amount' => '0.10000000',
            'updated_at' => now()->subMinutes(1),
        ]);

        $response = $this->actingAs($user)->getJson('/api/activity');

        $response->assertStatus(200);
        $activity = $response->json('data')[0];

        // total_value should be price * amount = 50000 * 0.1 = 5000
        $this->assertEquals('5000.00000000', $activity['total_value']);
    }

    public function test_activity_does_not_include_commission(): void
    {
        $user = User::factory()->create(['balance' => '10000.00000000']);

        Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::FILLED,
            'updated_at' => now()->subMinutes(1),
        ]);

        $response = $this->actingAs($user)->getJson('/api/activity');

        $response->assertStatus(200);
        $activity = $response->json('data')[0];

        // Commission field should not be present
        $this->assertArrayNotHasKey('commission', $activity);
    }

    public function test_matched_trade_shows_both_orders(): void
    {
        $buyer = User::factory()->create(['balance' => '10000.00000000']);
        $seller = User::factory()->create(['balance' => '10000.00000000']);

        // Simulate a matched trade - buy and sell orders both FILLED
        $buyOrder = Order::factory()->create([
            'user_id' => $buyer->id,
            'status' => OrderStatus::FILLED,
            'side' => OrderSide::BUY,
            'symbol' => AssetSymbol::BTC,
            'updated_at' => now()->subMinutes(1),
        ]);

        $sellOrder = Order::factory()->create([
            'user_id' => $seller->id,
            'status' => OrderStatus::FILLED,
            'side' => OrderSide::SELL,
            'symbol' => AssetSymbol::BTC,
            'updated_at' => now()->subMinutes(1),
        ]);

        $response = $this->actingAs($buyer)->getJson('/api/activity');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));

        // Both sides of the trade should appear
        $activityIds = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains('trade-'.$buyOrder->id, $activityIds);
        $this->assertContains('trade-'.$sellOrder->id, $activityIds);
    }
}
