<?php

namespace App\Http\Controllers;

use App\Enums\AssetSymbol;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Exceptions\OrderCannotBeCancelledException;
use App\Exceptions\OrderNotFoundException;
use App\Exceptions\UnauthorizedOrderAccessException;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderMatchingService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private OrderMatchingService $matchingService
    ) {}

    /**
     * Get orderbook for a symbol.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'symbol' => ['required', Rule::enum(AssetSymbol::class)],
        ]);

        $symbol = $validated['symbol'];

        // Get buy orders (sorted by price DESC - highest first)
        $buyOrders = Order::where('symbol', $symbol)
            ->where('side', OrderSide::BUY->value)
            ->where('status', OrderStatus::OPEN->value)
            ->orderBy('price', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        // Get sell orders (sorted by price ASC - lowest first)
        $sellOrders = Order::where('symbol', $symbol)
            ->where('side', OrderSide::SELL->value)
            ->where('status', OrderStatus::OPEN->value)
            ->orderBy('price', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'symbol' => $symbol,
            'buy_orders' => OrderResource::collection($buyOrders),
            'sell_orders' => OrderResource::collection($sellOrders),
        ]);
    }

    /**
     * Create a new order.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'symbol' => ['required', Rule::enum(AssetSymbol::class)],
            'side' => ['required', Rule::enum(OrderSide::class)],
            'price' => ['required', 'numeric', 'min:0.00000001'],
            'amount' => ['required', 'numeric', 'min:0.00000001'],
        ]);

        $user = $request->user();

        // Create order (locks funds/assets)
        // Exceptions bubble up and are handled by Laravel's exception handler
        if ($validated['side'] === 'buy') {
            $order = $this->orderService->createBuyOrder($user, $validated);
        } else {
            $order = $this->orderService->createSellOrder($user, $validated);
        }

        // Attempt immediate matching
        $trade = $this->matchingService->attemptMatch($order);

        if ($trade) {
            // Order was matched immediately
            return response()->json([
                'message' => 'Order matched successfully',
                'trade' => [
                    'buyer_id' => $trade->buyer_id,
                    'seller_id' => $trade->seller_id,
                    'symbol' => $trade->symbol->value,
                    'price' => $trade->price,
                    'amount' => $trade->amount,
                    'total_value' => $trade->total_value,
                    'commission' => $trade->commission,
                ],
            ], 200);
        } else {
            // Order placed in orderbook
            return response()->json([
                'message' => 'Order placed successfully',
                'order' => new OrderResource($order->fresh()),
            ], 201);
        }
    }

    /**
     * Cancel an order.
     */
    public function cancel(Request $request, int $id)
    {
        $user = $request->user();

        try {
            $order = $this->orderService->cancelOrder($user, $id);

            return response()->json([
                'message' => 'Order cancelled successfully',
                'order' => new OrderResource($order),
            ], 200);
        } catch (OrderNotFoundException $e) {
            return response()->json([
                'message' => 'Order not found',
            ], 404);
        } catch (UnauthorizedOrderAccessException $e) {
            return response()->json([
                'message' => 'Unauthorized to cancel this order',
            ], 403);
        } catch (OrderCannotBeCancelledException $e) {
            return response()->json([
                'message' => 'Order cannot be cancelled',
                'reason' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Match orders (internal/job endpoint).
     */
    public function match(Request $request)
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ]);

        $order = Order::find($validated['order_id']);

        if ($order->status !== OrderStatus::OPEN) {
            return response()->json([
                'message' => 'Order is not open',
                'matched' => false,
            ], 400);
        }

        $trade = $this->matchingService->attemptMatch($order);

        if ($trade) {
            return response()->json([
                'message' => 'Order matched successfully',
                'matched' => true,
                'trade' => [
                    'buyer_id' => $trade->buyer_id,
                    'seller_id' => $trade->seller_id,
                    'symbol' => $trade->symbol->value,
                    'price' => $trade->price,
                    'amount' => $trade->amount,
                    'total_value' => $trade->total_value,
                    'commission' => $trade->commission,
                ],
            ], 200);
        } else {
            return response()->json([
                'message' => 'No matching order found',
                'matched' => false,
            ], 200);
        }
    }
}
