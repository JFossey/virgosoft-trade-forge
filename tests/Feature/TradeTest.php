<?php

namespace Tests\Feature;

use App\Enums\AssetSymbol;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TradeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a trade can be created and has the correct relationships.
     */
    public function test_trade_creation_and_relationships(): void
    {
        // Create a trade using the factory
        $trade = Trade::factory()->create();

        // Assert that the trade exists in the database
        $this->assertDatabaseHas('trades', ['id' => $trade->id]);

        // Assert the relationships
        $this->assertInstanceOf(Order::class, $trade->buyOrder);
        $this->assertInstanceOf(Order::class, $trade->sellOrder);
        $this->assertInstanceOf(User::class, $trade->buyer);
        $this->assertInstanceOf(User::class, $trade->seller);

        // Assert the foreign keys are set correctly
        $this->assertEquals($trade->buy_order_id, $trade->buyOrder->id);
        $this->assertEquals($trade->sell_order_id, $trade->sellOrder->id);
        $this->assertEquals($trade->buyer_id, $trade->buyer->id);
        $this->assertEquals($trade->seller_id, $trade->seller->id);
    }

    /**
     * Test that the trade's attributes are cast correctly.
     */
    public function test_trade_casts_correctly(): void
    {
        $trade = Trade::factory()->create([
            'price' => '12345.67890123',
            'amount' => '1.23456789',
            'total_value' => '15241.55677490',
            'commission' => '228.62335162',
            'symbol' => AssetSymbol::BTC,
        ]);

        $retrievedTrade = Trade::find($trade->id);

        $this->assertInstanceOf(AssetSymbol::class, $retrievedTrade->symbol);
        $this->assertEquals(AssetSymbol::BTC, $retrievedTrade->symbol);

        $this->assertEquals('12345.67890123', $retrievedTrade->price);
        $this->assertEquals('1.23456789', $retrievedTrade->amount);
        $this->assertEquals('15241.55677490', $retrievedTrade->total_value);
        $this->assertEquals('228.62335162', $retrievedTrade->commission);
    }
}
