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
     * Get recent activity for the authenticated user.
     *
     * Returns trades, order creations, and order cancellations from the last 5 minutes.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $fiveMinutesAgo = now()->subMinutes(5);

        // Get trades where user is buyer or seller
        $trades = Trade::where(function ($query) use ($user) {
            $query->where('buyer_id', $user->id)
                ->orWhere('seller_id', $user->id);
        })
            ->where('created_at', '>=', $fiveMinutesAgo)
            ->get()
            ->map(fn ($trade) => [
                'type' => 'trade',
                'model' => $trade,
                'timestamp' => $trade->created_at,
            ]);

        // Get newly created orders (OPEN status)
        $orderCreations = Order::where('user_id', $user->id)
            ->where('status', OrderStatus::OPEN)
            ->where('created_at', '>=', $fiveMinutesAgo)
            ->get()
            ->map(fn ($order) => [
                'type' => 'order_created',
                'model' => $order,
                'timestamp' => $order->created_at,
            ]);

        // Get cancelled orders (CANCELLED status)
        $orderCancellations = Order::where('user_id', $user->id)
            ->where('status', OrderStatus::CANCELLED)
            ->where('updated_at', '>=', $fiveMinutesAgo)
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
