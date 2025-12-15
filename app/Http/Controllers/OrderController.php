<?php

namespace App\Http\Controllers;

use App\DataTransferObjects\CreateOrderData;
use App\Enums\AssetSymbol;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Http\Resources\OrderResource;
use App\Http\Resources\TradeResource;
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
        $orderData = CreateOrderData::fromArray($validated);
        $order = $this->orderService->createOrder($user, $orderData);

        // Attempt immediate matching
        $trade = $this->matchingService->attemptMatch($order);

        if ($trade === null) {
            // Order placed in orderbook
            return response()->json([
                'message' => 'Order placed successfully',
                'order' => new OrderResource($order->fresh()),
            ], 201);
        }

        // Order placed and matched immediately
        return response()->json([
            'message' => 'Order matched successfully',
            'trade' => new TradeResource($trade),
        ], 200);
    }

    /**
     * Cancel an order.
     */
    public function cancel(Request $request, int $id)
    {
        $user = $request->user();
        $order = $this->orderService->cancelOrder($user, $id);

        return response()->json([
            'message' => 'Order cancelled successfully',
            'order' => new OrderResource($order),
        ], 200);
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

        if ($trade === null) {
            return response()->json([
                'message' => 'No matching order found',
                'matched' => false,
            ], 200);
        }

        return response()->json([
            'message' => 'Order matched successfully',
            'matched' => true,
            'trade' => new TradeResource($trade),
        ], 200);
    }
}
