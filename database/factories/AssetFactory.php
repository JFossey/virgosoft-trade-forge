<?php

namespace Database\Factories;

use App\Enums\AssetSymbol;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asset>
 */
class AssetFactory extends Factory
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
            'amount' => '1.00000000',
            'locked_amount' => '0.00000000',
        ];
    }

    /**
     * Create an asset with a specific symbol.
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
     * Create an asset with a specific amount.
     */
    public function withAmount(string $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }

    /**
     * Create an asset with locked amount.
     */
    public function withLockedAmount(string $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'locked_amount' => $amount,
        ]);
    }
}
