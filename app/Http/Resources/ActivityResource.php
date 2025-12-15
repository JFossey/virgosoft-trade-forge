<?php

namespace App\Http\Resources;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Trade;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof Trade) {
            return $this->transformTrade($request);
        } elseif ($this->resource instanceof Order) {
            return $this->transformOrder($request);
        }

        return [];
    }

    /**
     * Transform a Trade model into activity format.
     */
    private function transformTrade(Request $request): array
    {
        return [
            'id' => "trade-{$this->id}",
            'activity_type' => 'trade',
            'symbol' => $this->symbol->value,
            'side' => 'trade', // Platform-wide view, not user-specific
            'price' => $this->price,
            'amount' => $this->amount,
            'total_value' => $this->total_value,
            'commission' => $this->commission,
            'timestamp' => $this->created_at->toISOString(),
        ];
    }

    /**
     * Transform an Order model into activity format.
     */
    private function transformOrder(Request $request): array
    {
        $isCancelled = $this->status === OrderStatus::CANCELLED;
        $activityType = $isCancelled ? 'order_cancelled' : 'order_created';
        $idPrefix = $isCancelled ? 'order-cancelled' : 'order-created';

        return [
            'id' => "{$idPrefix}-{$this->id}",
            'activity_type' => $activityType,
            'symbol' => $this->symbol->value,
            'side' => $this->side->value,
            'price' => $this->price,
            'amount' => $this->amount,
            'total_value' => bcmul($this->price, $this->amount, 8),
            'timestamp' => ($isCancelled ? $this->updated_at : $this->created_at)->toISOString(),
        ];
    }
}
