<?php

namespace Tests\Feature;

use App\Enums\AssetSymbol;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Tests\TestCase;

class OrderModelTest extends TestCase
{
    public function test_order_can_be_created_with_factory(): void
    {
        $order = Order::factory()->create();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'symbol' => $order->symbol->value,
            'side' => $order->side->value,
        ]);
    }

    public function test_order_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create();

        $this->assertEquals($user->id, $order->user->id);
        $this->assertEquals($user->name, $order->user->name);
    }

    public function test_order_decimal_precision_is_preserved(): void
    {
        $order = Order::factory()
            ->withPrice('65432.12345678')
            ->withAmount('3.87654321')
            ->create();

        $this->assertEquals('65432.12345678', $order->price);
        $this->assertEquals('3.87654321', $order->amount);
    }

    public function test_order_has_correct_default_status(): void
    {
        $order = Order::factory()->create();

        $this->assertEquals(OrderStatus::OPEN, $order->status);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 1,
        ]);
    }

    public function test_symbol_is_cast_to_enum(): void
    {
        $order = Order::factory()->create();

        $this->assertInstanceOf(AssetSymbol::class, $order->symbol);
    }

    public function test_side_is_cast_to_enum(): void
    {
        $order = Order::factory()->create();

        $this->assertInstanceOf(OrderSide::class, $order->side);
    }

    public function test_status_is_cast_to_enum(): void
    {
        $order = Order::factory()->create();

        $this->assertInstanceOf(OrderStatus::class, $order->status);
    }

    public function test_orders_are_deleted_when_user_is_deleted(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create();

        $orderId = $order->id;

        $user->delete();

        $this->assertDatabaseMissing('orders', ['id' => $orderId]);
    }

    public function test_factory_symbol_method_creates_specific_symbol(): void
    {
        $btcOrder = Order::factory()->symbol(AssetSymbol::BTC)->create();
        $ethOrder = Order::factory()->symbol(AssetSymbol::ETH)->create();

        $this->assertEquals(AssetSymbol::BTC, $btcOrder->symbol);
        $this->assertEquals(AssetSymbol::ETH, $ethOrder->symbol);
    }

    public function test_factory_symbol_method_accepts_string(): void
    {
        $btcOrder = Order::factory()->symbol('BTC')->create();
        $ethOrder = Order::factory()->symbol('eth')->create();

        $this->assertEquals(AssetSymbol::BTC, $btcOrder->symbol);
        $this->assertEquals(AssetSymbol::ETH, $ethOrder->symbol);
    }

    public function test_factory_buy_method_creates_buy_order(): void
    {
        $order = Order::factory()->buy()->create();

        $this->assertEquals(OrderSide::BUY, $order->side);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'side' => 'buy',
        ]);
    }

    public function test_factory_sell_method_creates_sell_order(): void
    {
        $order = Order::factory()->sell()->create();

        $this->assertEquals(OrderSide::SELL, $order->side);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'side' => 'sell',
        ]);
    }

    public function test_factory_with_price_sets_correct_price(): void
    {
        $order = Order::factory()->withPrice('75000.12345678')->create();

        $this->assertEquals('75000.12345678', $order->price);
    }

    public function test_factory_with_amount_sets_correct_amount(): void
    {
        $order = Order::factory()->withAmount('5.50000000')->create();

        $this->assertEquals('5.50000000', $order->amount);
    }

    public function test_factory_filled_method_creates_filled_order(): void
    {
        $order = Order::factory()->filled()->create();

        $this->assertEquals(OrderStatus::FILLED, $order->status);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 2,
        ]);
    }

    public function test_factory_cancelled_method_creates_cancelled_order(): void
    {
        $order = Order::factory()->cancelled()->create();

        $this->assertEquals(OrderStatus::CANCELLED, $order->status);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 3,
        ]);
    }

    public function test_factory_open_method_creates_open_order(): void
    {
        $order = Order::factory()->open()->create();

        $this->assertEquals(OrderStatus::OPEN, $order->status);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 1,
        ]);
    }

    public function test_factory_method_chaining_works(): void
    {
        $order = Order::factory()
            ->symbol('BTC')
            ->buy()
            ->withPrice('68000.00000000')
            ->withAmount('2.50000000')
            ->filled()
            ->create();

        $this->assertEquals(AssetSymbol::BTC, $order->symbol);
        $this->assertEquals(OrderSide::BUY, $order->side);
        $this->assertEquals('68000.00000000', $order->price);
        $this->assertEquals('2.50000000', $order->amount);
        $this->assertEquals(OrderStatus::FILLED, $order->status);
    }

    public function test_manual_order_creation_without_mass_assignment(): void
    {
        $user = User::factory()->create();

        $order = new Order;
        $order->user_id = $user->id;
        $order->symbol = AssetSymbol::ETH;
        $order->side = OrderSide::SELL;
        $order->price = '3500.50000000';
        $order->amount = '10.00000000';
        $order->status = OrderStatus::OPEN;
        $order->save();

        $order->refresh();

        $this->assertEquals($user->id, $order->user_id);
        $this->assertEquals(AssetSymbol::ETH, $order->symbol);
        $this->assertEquals(OrderSide::SELL, $order->side);
        $this->assertEquals('3500.50000000', $order->price);
        $this->assertEquals('10.00000000', $order->amount);
        $this->assertEquals(OrderStatus::OPEN, $order->status);
    }
}
