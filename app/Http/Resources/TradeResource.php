<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TradeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'buyer_id' => $this->buyer_id,
            'seller_id' => $this->seller_id,
            'symbol' => $this->symbol->value,
            'price' => $this->price,
            'amount' => $this->amount,
            'total_value' => $this->total_value,
            'commission' => $this->commission,
            'created_at' => $this->created_at,
        ];
    }
}
