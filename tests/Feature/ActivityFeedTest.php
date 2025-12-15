<?php

namespace Tests\Feature;

use App\Enums\AssetSymbol;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Tests\TestCase;

class ActivityFeedTest extends TestCase
{
    public function test_user_can_fetch_recent_activity(): void
    {
        $user = User::factory()->create(['balance' => '10000.00000000']);

        // Create trade where user is buyer
        $trade = Trade::factory()->create([
            'buyer_id' => $user->id,
            'created_at' => now()->subMinutes(2),
        ]);

        // Create open order
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::OPEN,
            'created_at' => now()->subMinutes(1),
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

    public function test_activity_includes_trades_as_buyer(): void
    {
        $user = User::factory()->create(['balance' => '10000.00000000']);

        $trade = Trade::factory()->create([
            'buyer_id' => $user->id,
            'symbol' => AssetSymbol::BTC,
            'price' => '50000.00000000',
            'amount' => '0.10000000',
            'created_at' => now()->subMinutes(2),
        ]);

        $response = $this->actingAs($user)->getJson('/api/activity');

        $response->assertStatus(200);
        $activity = $response->json('data')[0];

        $this->assertEquals('trade-'.$trade->id, $activity['id']);
        $this->assertEquals('trade', $activity['activity_type']);
        $this->assertEquals('BTC', $activity['symbol']);
        $this->assertEquals('buy', $activity['side']); // User is buyer
        $this->assertEquals('50000.00000000', $activity['price']);
    }

    public function test_activity_includes_trades_as_seller(): void
    {
        $user = User::factory()->create(['balance' => '10000.00000000']);

        $trade = Trade::factory()->create([
            'seller_id' => $user->id,
            'symbol' => AssetSymbol::ETH,
            'price' => '3000.00000000',
            'amount' => '0.50000000',
            'created_at' => now()->subMinutes(1),
        ]);

        $response = $this->actingAs($user)->getJson('/api/activity');

        $response->assertStatus(200);
        $activity = $response->json('data')[0];

        $this->assertEquals('trade-'.$trade->id, $activity['id']);
        $this->assertEquals('trade', $activity['activity_type']);
        $this->assertEquals('ETH', $activity['symbol']);
        $this->assertEquals('sell', $activity['side']); // User is seller
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
            'created_at' => now()->subMinutes(1),
        ]);

        $response = $this->actingAs($user)->getJson('/api/activity');

        $response->assertStatus(200);
        $activity = $response->json('data')[0];

        $this->assertEquals('order-'.$order->id, $activity['id']);
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

        $this->assertEquals('order-'.$order->id, $activity['id']);
        $this->assertEquals('order_cancelled', $activity['activity_type']);
        $this->assertEquals('ETH', $activity['symbol']);
    }

    public function test_activity_excludes_filled_orders(): void
    {
        $user = User::factory()->create(['balance' => '10000.00000000']);

        // Create filled order (should not appear in activity)
        Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::FILLED,
            'created_at' => now()->subMinutes(1),
        ]);

        // Create trade (should appear)
        $trade = Trade::factory()->create([
            'buyer_id' => $user->id,
            'created_at' => now()->subMinutes(1),
        ]);

        $response = $this->actingAs($user)->getJson('/api/activity');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));

        // Should only have the trade
        $activity = $response->json('data')[0];
        $this->assertEquals('trade', $activity['activity_type']);
    }

    public function test_activity_filters_by_time_window(): void
    {
        $user = User::factory()->create(['balance' => '10000.00000000']);

        // Create trade within 5 minutes (should appear)
        $recentTrade = Trade::factory()->create([
            'buyer_id' => $user->id,
            'created_at' => now()->subMinutes(2),
        ]);

        // Create trade outside 5 minutes (should NOT appear)
        $oldTrade = Trade::factory()->create([
            'buyer_id' => $user->id,
            'created_at' => now()->subMinutes(10),
        ]);

        $response = $this->actingAs($user)->getJson('/api/activity');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));

        // Should only have the recent trade
        $activity = $response->json('data')[0];
        $this->assertEquals('trade-'.$recentTrade->id, $activity['id']);
    }

    public function test_activity_sorted_by_most_recent_first(): void
    {
        $user = User::factory()->create(['balance' => '10000.00000000']);

        // Create activities with different timestamps
        $oldTrade = Trade::factory()->create([
            'buyer_id' => $user->id,
            'created_at' => now()->subMinutes(4),
        ]);

        $middleOrder = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::OPEN,
            'created_at' => now()->subMinutes(2),
        ]);

        $recentTrade = Trade::factory()->create([
            'buyer_id' => $user->id,
            'created_at' => now()->subMinutes(1),
        ]);

        $response = $this->actingAs($user)->getJson('/api/activity');

        $response->assertStatus(200);
        $activities = $response->json('data');

        // Should be sorted most recent first
        $this->assertEquals('trade-'.$recentTrade->id, $activities[0]['id']);
        $this->assertEquals('order-'.$middleOrder->id, $activities[1]['id']);
        $this->assertEquals('trade-'.$oldTrade->id, $activities[2]['id']);
    }

    public function test_activity_respects_user_isolation(): void
    {
        $user1 = User::factory()->create(['balance' => '10000.00000000']);
        $user2 = User::factory()->create(['balance' => '10000.00000000']);

        // Create trade for user1
        $user1Trade = Trade::factory()->create([
            'buyer_id' => $user1->id,
            'created_at' => now()->subMinutes(1),
        ]);

        // Create order for user2
        $user2Order = Order::factory()->create([
            'user_id' => $user2->id,
            'status' => OrderStatus::OPEN,
            'created_at' => now()->subMinutes(1),
        ]);

        // User1 should only see their own activity
        $response1 = $this->actingAs($user1)->getJson('/api/activity');
        $response1->assertStatus(200);
        $this->assertCount(1, $response1->json('data'));
        $this->assertEquals('trade-'.$user1Trade->id, $response1->json('data')[0]['id']);

        // User2 should only see their own activity
        $response2 = $this->actingAs($user2)->getJson('/api/activity');
        $response2->assertStatus(200);
        $this->assertCount(1, $response2->json('data'));
        $this->assertEquals('order-'.$user2Order->id, $response2->json('data')[0]['id']);
    }

    public function test_activity_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/activity');

        $response->assertStatus(401);
    }

    public function test_activity_calculates_total_value_for_orders(): void
    {
        $user = User::factory()->create(['balance' => '10000.00000000']);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::OPEN,
            'price' => '50000.00000000',
            'amount' => '0.10000000',
            'created_at' => now()->subMinutes(1),
        ]);

        $response = $this->actingAs($user)->getJson('/api/activity');

        $response->assertStatus(200);
        $activity = $response->json('data')[0];

        // total_value should be price * amount = 50000 * 0.1 = 5000
        $this->assertEquals('5000.00000000', $activity['total_value']);
    }
}
