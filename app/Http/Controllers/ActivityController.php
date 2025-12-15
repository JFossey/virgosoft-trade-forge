<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Resources\ActivityResource;
use App\Models\Order;
use App\Models\Trade;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ActivityController extends Controller
{
    /**
     * Get recent activity for all users on the platform.
     *
     * Returns trades, order creations, and order cancellations from the last 15 minutes.
     */
    public function index(Request $request)
    {
        $fifteenMinutesAgo = now()->subMinutes(15);

        // Get all trades from last 15 minutes
        $trades = Trade::where('created_at', '>=', $fifteenMinutesAgo)
            ->get()
            ->map(fn ($trade) => [
                'type' => 'trade',
                'model' => $trade,
                'timestamp' => $trade->created_at,
            ]);

        // Get newly created orders (OPEN status)
        $orderCreations = Order::where('status', OrderStatus::OPEN)
            ->where('created_at', '>=', $fifteenMinutesAgo)
            ->get()
            ->map(fn ($order) => [
                'type' => 'order_created',
                'model' => $order,
                'timestamp' => $order->created_at,
            ]);

        // Get cancelled orders (CANCELLED status)
        $orderCancellations = Order::where('status', OrderStatus::CANCELLED)
            ->where('updated_at', '>=', $fifteenMinutesAgo)
            ->get()
            ->map(fn ($order) => [
                'type' => 'order_cancelled',
                'model' => $order,
                'timestamp' => $order->updated_at,
            ]);

        // Merge all activities and sort by timestamp (most recent first)
        $allActivity = $trades
            ->concat($orderCreations)
            ->concat($orderCancellations)
            ->sortByDesc('timestamp')
            ->take(50)
            ->map(fn ($item) => $item['model']);

        return ActivityResource::collection($allActivity);
    }
}
