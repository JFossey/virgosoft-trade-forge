<?php

namespace App\Services;

use App\Enums\AssetSymbol;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Exceptions\InsufficientAssetsException;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\OrderCannotBeCancelledException;
use App\Exceptions\OrderNotFoundException;
use App\Exceptions\UnauthorizedOrderAccessException;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Create a buy order.
     *
     * @param  User  $user
     * @param  array  $data
     * @return Order
     *
     * @throws InsufficientBalanceException
     */
    public function createBuyOrder(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            // Lock user record
            $user = User::where('id', $user->id)->lockForUpdate()->first();

            // Calculate required balance
            $requiredBalance = bcmul($data['price'], $data['amount'], 8);

            // Validate sufficient balance
            if (bccomp($user->balance, $requiredBalance, 8) < 0) {
                throw new InsufficientBalanceException(
                    required: $requiredBalance,
                    available: $user->balance
                );
            }

            // Deduct balance (lock funds)
            $user->balance = bcsub($user->balance, $requiredBalance, 8);
            $user->save();

            // Create order
            $order = new Order();
            $order->user_id = $user->id;
            $order->symbol = AssetSymbol::from($data['symbol']);
            $order->side = OrderSide::BUY;
            $order->price = $data['price'];
            $order->amount = $data['amount'];
            $order->status = OrderStatus::OPEN;
            $order->save();

            return $order;
        });
    }

    /**
     * Create a sell order.
     *
     * @param  User  $user
     * @param  array  $data
     * @return Order
     *
     * @throws InsufficientAssetsException
     */
    public function createSellOrder(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            // Lock asset record
            $asset = Asset::where('user_id', $user->id)
                ->where('symbol', $data['symbol'])
                ->lockForUpdate()
                ->first();

            // Validate asset exists and has sufficient amount
            if (! $asset || bccomp($asset->amount, $data['amount'], 8) < 0) {
                throw new InsufficientAssetsException(
                    required: $data['amount'],
                    available: $asset?->amount ?? '0.00000000'
                );
            }

            // Lock assets (move to locked_amount)
            $asset->amount = bcsub($asset->amount, $data['amount'], 8);
            $asset->locked_amount = bcadd($asset->locked_amount, $data['amount'], 8);
            $asset->save();

            // Create order
            $order = new Order();
            $order->user_id = $user->id;
            $order->symbol = AssetSymbol::from($data['symbol']);
            $order->side = OrderSide::SELL;
            $order->price = $data['price'];
            $order->amount = $data['amount'];
            $order->status = OrderStatus::OPEN;
            $order->save();

            return $order;
        });
    }

    /**
     * Cancel an order.
     *
     * @param  User  $user
     * @param  int  $orderId
     * @return Order
     *
     * @throws OrderNotFoundException
     * @throws UnauthorizedOrderAccessException
     * @throws OrderCannotBeCancelledException
     */
    public function cancelOrder(User $user, int $orderId): Order
    {
        return DB::transaction(function () use ($user, $orderId) {
            // Lock and fetch order
            $order = Order::where('id', $orderId)
                ->lockForUpdate()
                ->first();

            // Validate order exists
            if (! $order) {
                throw new OrderNotFoundException();
            }

            // Validate ownership
            if ($order->user_id !== $user->id) {
                throw new UnauthorizedOrderAccessException();
            }

            // Validate status is OPEN
            if ($order->status !== OrderStatus::OPEN) {
                throw new OrderCannotBeCancelledException(
                    'Order is already '.$order->status->name
                );
            }

            // Release locked funds/assets
            if ($order->side === OrderSide::BUY) {
                // Refund balance
                $lockedValue = bcmul($order->price, $order->amount, 8);
                $user = User::where('id', $user->id)->lockForUpdate()->first();
                $user->balance = bcadd($user->balance, $lockedValue, 8);
                $user->save();
            } else {
                // Release locked assets
                $asset = Asset::where('user_id', $user->id)
                    ->where('symbol', $order->symbol->value)
                    ->lockForUpdate()
                    ->first();

                $asset->amount = bcadd($asset->amount, $order->amount, 8);
                $asset->locked_amount = bcsub($asset->locked_amount, $order->amount, 8);
                $asset->save();
            }

            // Update order status
            $order->status = OrderStatus::CANCELLED;
            $order->save();

            return $order;
        });
    }
}
