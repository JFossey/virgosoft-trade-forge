<?php

namespace App\Http\Resources;

use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * All activities now come from Order model with different statuses:
     * - OPEN = order created
     * - FILLED = trade executed
     * - CANCELLED = order cancelled
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Determine activity type and ID prefix based on order status
        $activityType = match ($this->status) {
            OrderStatus::OPEN => 'order_created',
            OrderStatus::FILLED => 'trade',
            OrderStatus::CANCELLED => 'order_cancelled',
        };

        $idPrefix = match ($this->status) {
            OrderStatus::OPEN => 'order-created',
            OrderStatus::FILLED => 'trade',
            OrderStatus::CANCELLED => 'order-cancelled',
        };

        return [
            'id' => "{$idPrefix}-{$this->id}",
            'activity_type' => $activityType,
            'symbol' => $this->symbol->value,
            'side' => $this->side->value,
            'price' => $this->price,
            'amount' => $this->amount,
            'total_value' => bcmul($this->price, $this->amount, 8),
            'timestamp' => $this->updated_at->toISOString(),
        ];
    }
}
