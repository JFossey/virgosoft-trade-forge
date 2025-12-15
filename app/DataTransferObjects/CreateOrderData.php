<?php

namespace App\DataTransferObjects;

use App\Enums\AssetSymbol;
use App\Enums\OrderSide;

readonly class CreateOrderData
{
    public function __construct(
        public AssetSymbol $symbol,
        public OrderSide $side,
        public string $price,
        public string $amount,
    ) {}

    /**
     * Create from validated array (e.g., from controller validation)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            symbol: AssetSymbol::from($data['symbol']),
            side: OrderSide::from($data['side']),
            price: $data['price'],
            amount: $data['amount'],
        );
    }
}
