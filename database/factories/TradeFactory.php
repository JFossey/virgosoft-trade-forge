<?php

namespace Database\Factories;

use App\Enums\AssetSymbol;
use App\Enums\OrderSide;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trade>
 */
class TradeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $buyOrder = Order::factory()->create([
            'user_id' => $buyer->id,
            'side' => OrderSide::BUY,
        ]);
        $sellOrder = Order::factory()->create([
            'user_id' => $seller->id,
            'side' => OrderSide::SELL,
        ]);

        $amount = $this->faker->randomFloat(8, 0.1, 10);
        $price = $this->faker->randomFloat(8, 100, 1000);
        $totalValue = $amount * $price;
        $commission = $totalValue * 0.015;

        return [
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'symbol' => AssetSymbol::BTC,
            'price' => $price,
            'amount' => $amount,
            'total_value' => $totalValue,
            'commission' => $commission,
        ];
    }
}
