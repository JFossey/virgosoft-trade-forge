<?php

namespace App\Services;

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
use App\Values\CreateOrderValue;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Cancel an order.
     *
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
            if ($order === null) {
                throw new OrderNotFoundException;
            }

            // Validate ownership
            if ($order->user_id !== $user->id) {
                throw new UnauthorizedOrderAccessException;
            }

            // Validate status is OPEN
            if ($order->status !== OrderStatus::OPEN) {
                throw new OrderCannotBeCancelledException(
                    'Order is already '.$order->status->name
                );
            }

            // Release locked funds
            if ($order->side === OrderSide::BUY) {
                // Refund balance
                $lockedValue = bcmul($order->price, $order->amount, 8);
                $user = User::where('id', $user->id)->lockForUpdate()->first();
                $user->balance = bcadd($user->balance, $lockedValue, 8);
                $user->save();
            }

            // Release locked assets
            if ($order->side === OrderSide::SELL) {
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

    /**
     * Create an order (buy or sell).
     */
    public function createOrder(User $user, CreateOrderValue $orderData): Order
    {
        return DB::transaction(function () use ($user, $orderData) {
            if ($orderData->side === OrderSide::BUY) {
                $this->lockAndValidateBalance($user, $orderData);
            }

            if ($orderData->side === OrderSide::SELL) {
                $this->lockAndValidateAsset($user, $orderData);
            }

            // Create order (common code)
            $order = new Order;
            $order->user_id = $user->id;
            $order->symbol = $orderData->symbol;
            $order->side = $orderData->side;
            $order->price = $orderData->price;
            $order->amount = $orderData->amount;
            $order->status = OrderStatus::OPEN;
            $order->save();

            return $order;
        });
    }

    /**
     * Lock user and validate sufficient balance for buy order.
     */
    protected function lockAndValidateBalance(User $user, CreateOrderValue $orderData): void
    {
        $user = User::where('id', $user->id)->lockForUpdate()->first();
        $requiredBalance = bcmul($orderData->price, $orderData->amount, 8);

        if (bccomp($user->balance, $requiredBalance, 8) < 0) {
            throw new InsufficientBalanceException(
                required: $requiredBalance,
                available: $user->balance
            );
        }

        $user->balance = bcsub($user->balance, $requiredBalance, 8);
        $user->save();
    }

    /**
     * Lock asset and validate sufficient amount for sell order.
     */
    protected function lockAndValidateAsset(User $user, CreateOrderValue $orderData): void
    {
        $asset = Asset::where('user_id', $user->id)
            ->where('symbol', $orderData->symbol->value)
            ->lockForUpdate()
            ->first();

        if (! $asset || bccomp($asset->amount, $orderData->amount, 8) < 0) {
            throw new InsufficientAssetsException(
                required: $orderData->amount,
                available: $asset?->amount ?? '0.00000000'
            );
        }

        $asset->amount = bcsub($asset->amount, $orderData->amount, 8);
        $asset->locked_amount = bcadd($asset->locked_amount, $orderData->amount, 8);
        $asset->save();
    }
}
