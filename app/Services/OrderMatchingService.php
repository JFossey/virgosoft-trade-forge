<?php

namespace App\Services;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Events\OrderMatched;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderMatchingService
{
    /**
     * Attempt to match an order.
     *
     * @param  Order  $newOrder
     * @return Trade|null
     */
    public function attemptMatch(Order $newOrder): ?Trade
    {
        if ($newOrder->side === OrderSide::BUY) {
            return $this->matchBuyOrder($newOrder);
        } else {
            return $this->matchSellOrder($newOrder);
        }
    }

    /**
     * Match a buy order with a sell order.
     *
     * @param  Order  $buyOrder
     * @return Trade|null
     */
    private function matchBuyOrder(Order $buyOrder): ?Trade
    {
        // Find matching sell order
        $sellOrder = Order::where('symbol', $buyOrder->symbol->value)
            ->where('side', 'sell')
            ->where('status', OrderStatus::OPEN->value)
            ->where('price', '<=', $buyOrder->price)
            ->where('amount', $buyOrder->amount) // Exact match only
            ->where('user_id', '!=', $buyOrder->user_id) // Prevent self-matching
            ->orderBy('price', 'asc')    // Best price first (lowest sell)
            ->orderBy('created_at', 'asc') // FIFO
            ->lockForUpdate()
            ->first();

        if (! $sellOrder) {
            return null; // No match found
        }

        return $this->executeTrade($buyOrder, $sellOrder);
    }

    /**
     * Match a sell order with a buy order.
     *
     * @param  Order  $sellOrder
     * @return Trade|null
     */
    private function matchSellOrder(Order $sellOrder): ?Trade
    {
        // Find matching buy order
        $buyOrder = Order::where('symbol', $sellOrder->symbol->value)
            ->where('side', 'buy')
            ->where('status', OrderStatus::OPEN->value)
            ->where('price', '>=', $sellOrder->price)
            ->where('amount', $sellOrder->amount) // Exact match only
            ->where('user_id', '!=', $sellOrder->user_id) // Prevent self-matching
            ->orderBy('price', 'desc')   // Best price first (highest buy)
            ->orderBy('created_at', 'asc') // FIFO
            ->lockForUpdate()
            ->first();

        if (! $buyOrder) {
            return null; // No match found
        }

        return $this->executeTrade($buyOrder, $sellOrder);
    }

    /**
     * Execute a trade between buy and sell orders.
     *
     * @param  Order  $buyOrder
     * @param  Order  $sellOrder
     * @return Trade
     */
    private function executeTrade(Order $buyOrder, Order $sellOrder): Trade
    {
        return DB::transaction(function () use ($buyOrder, $sellOrder) {
            // 1. Calculate trade values
            $executedPrice = $sellOrder->price; // Seller's price wins
            $executedAmount = $buyOrder->amount; // Same amount (exact match)
            $totalValue = bcmul($executedPrice, $executedAmount, 8);
            $commission = bcmul($totalValue, '0.015', 8); // 1.5%

            // 2. Lock both users
            $buyer = User::where('id', $buyOrder->user_id)->lockForUpdate()->first();
            $seller = User::where('id', $sellOrder->user_id)->lockForUpdate()->first();

            // 3. Deduct commission from buyer
            // (Buy order already deducted price * amount from balance)
            $buyer->balance = bcsub($buyer->balance, $commission, 8);
            $buyer->save();

            // 4. Add crypto to buyer's assets
            $buyerAsset = Asset::where('user_id', $buyer->id)
                ->where('symbol', $buyOrder->symbol->value)
                ->lockForUpdate()
                ->first();

            if (! $buyerAsset) {
                // Create new asset without mass assignment
                $buyerAsset = new Asset();
                $buyerAsset->user_id = $buyer->id;
                $buyerAsset->symbol = $buyOrder->symbol;
                $buyerAsset->amount = '0.00000000';
                $buyerAsset->locked_amount = '0.00000000';
                $buyerAsset->save();

                // Re-lock after creation
                $buyerAsset = Asset::where('id', $buyerAsset->id)->lockForUpdate()->first();
            }

            $buyerAsset->amount = bcadd($buyerAsset->amount, $executedAmount, 8);
            $buyerAsset->save();

            // 5. Release seller's locked assets and add USD to balance
            $sellerAsset = Asset::where('user_id', $seller->id)
                ->where('symbol', $sellOrder->symbol->value)
                ->lockForUpdate()
                ->first();
            $sellerAsset->locked_amount = bcsub($sellerAsset->locked_amount, $executedAmount, 8);
            $sellerAsset->save();

            // Seller receives full trade value (buyer paid commission)
            $seller->balance = bcadd($seller->balance, $totalValue, 8);
            $seller->save();

            // 6. Update order statuses
            $buyOrder->status = OrderStatus::FILLED;
            $buyOrder->save();

            $sellOrder->status = OrderStatus::FILLED;
            $sellOrder->save();

            // 7. Create trade record
            $trade = new Trade();
            $trade->buy_order_id = $buyOrder->id;
            $trade->sell_order_id = $sellOrder->id;
            $trade->buyer_id = $buyer->id;
            $trade->seller_id = $seller->id;
            $trade->symbol = $buyOrder->symbol;
            $trade->price = $executedPrice;
            $trade->amount = $executedAmount;
            $trade->total_value = $totalValue;
            $trade->commission = $commission;
            $trade->save();

            // 8. Broadcast event
            broadcast(new OrderMatched($trade));

            return $trade;
        });
    }
}
