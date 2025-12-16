<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Resources\ActivityResource;
use App\Models\Order;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    /**
     * Time window in minutes considered recent activity.
     */
    public const ACTIVITY_TIME_WINDOW = 15;

    /**
     * Get recent activity for all users on the platform.
     *
     * Returns order activity (created, filled, cancelled) from the last 15 minutes.
     * Uses order status to determine activity type:
     * - OPEN = order created
     * - FILLED = trade executed
     * - CANCELLED = order cancelled
     */
    public function index(Request $request)
    {
        $fifteenMinutesAgo = now()->subMinutes(self::ACTIVITY_TIME_WINDOW);

        $orders = Order::where('updated_at', '>=', $fifteenMinutesAgo)
            ->whereIn('status', [OrderStatus::OPEN, OrderStatus::FILLED, OrderStatus::CANCELLED])
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        return ActivityResource::collection($orders);
    }
}
