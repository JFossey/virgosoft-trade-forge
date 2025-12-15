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
     */
    public function attemptMatch(Order $newOrder): ?Trade
    {
        return DB::transaction(function () use ($newOrder) {
            if ($newOrder->side === OrderSide::BUY) {
                $buyOrder = $newOrder;
                $sellOrder = $this->matchBuyOrder($newOrder);
            }

            if ($newOrder->side === OrderSide::SELL) {
                $sellOrder = $newOrder;
                $buyOrder = $this->matchSellOrder($newOrder);
            }

            // If no match return early
            if ($buyOrder === null || $sellOrder === null) {
                return null;
            }

            return $this->executeTrade($buyOrder, $sellOrder);
        });
    }

    /**
     * Match a buy order with a sell order.
     */
    protected function matchBuyOrder(Order $buyOrder): ?Order
    {
        return $this->findMatchingOrder($buyOrder, OrderSide::SELL, '<=', 'asc');
    }

    /**
     * Match a sell order with a buy order.
     */
    protected function matchSellOrder(Order $sellOrder): ?Order
    {
        return $this->findMatchingOrder($sellOrder, OrderSide::BUY, '>=', 'desc');
    }

    /**
     * Finds a matching order for the given new order.
     */
    protected function findMatchingOrder(Order $newOrder, OrderSide $oppositeSide, string $priceComparisonOperator, string $priceOrdering): ?Order
    {
        return Order::where('symbol', $newOrder->symbol->value)
            ->where('side', $oppositeSide->value)
            ->where('status', OrderStatus::OPEN->value)
            ->where('price', $priceComparisonOperator, $newOrder->price)

            // Exact match only
            ->where('amount', $newOrder->amount)

            // Prevent self-matching
            ->where('user_id', '!=', $newOrder->user_id)
            ->orderBy('price', $priceOrdering)

            // FIFO
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->first();
    }

    /**
     * Execute a trade between buy and sell orders.
     */
    protected function executeTrade(Order $buyOrder, Order $sellOrder): Trade
    {
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

        if ($buyerAsset === null) {
            // Create new asset without mass assignment
            $buyerAsset = new Asset;
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
        $trade = new Trade;
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
    }
}
