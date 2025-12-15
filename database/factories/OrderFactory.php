<?php

namespace Database\Factories;

use App\Enums\AssetSymbol;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'symbol' => fake()->randomElement(AssetSymbol::cases()),
            'side' => fake()->randomElement(OrderSide::cases()),
            'price' => '50000.00000000',
            'amount' => '1.00000000',
            'status' => OrderStatus::OPEN,
        ];
    }

    /**
     * Create an order with a specific symbol.
     */
    public function symbol(AssetSymbol|string $symbol): static
    {
        $enumSymbol = is_string($symbol)
            ? AssetSymbol::from(strtoupper($symbol))
            : $symbol;

        return $this->state(fn (array $attributes) => [
            'symbol' => $enumSymbol,
        ]);
    }

    /**
     * Create a buy order.
     */
    public function buy(): static
    {
        return $this->state(fn (array $attributes) => [
            'side' => OrderSide::BUY,
        ]);
    }

    /**
     * Create a sell order.
     */
    public function sell(): static
    {
        return $this->state(fn (array $attributes) => [
            'side' => OrderSide::SELL,
        ]);
    }

    /**
     * Create an order with a specific price.
     */
    public function withPrice(string $price): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $price,
        ]);
    }

    /**
     * Create an order with a specific amount.
     */
    public function withAmount(string $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }

    /**
     * Create a filled order.
     */
    public function filled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::FILLED,
        ]);
    }

    /**
     * Create a cancelled order.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::CANCELLED,
        ]);
    }

    /**
     * Create an open order (default state).
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::OPEN,
        ]);
    }
}
