<?php

namespace App\AI\Tools\Concerns;

use App\Models\Order;

trait FormatsOrder
{
    /** @return array<string, mixed> */
    private function orderSummary(Order $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status->value,
            'total_amount' => $order->total_amount,
            'carrier' => $order->carrier,
            'tracking_code' => $order->tracking_code,
            'placed_at' => $order->placed_at?->toISOString(),
            'estimated_delivery_at' => $order->estimated_delivery_at?->toISOString(),
            'is_delayed' => $order->estimated_delivery_at?->isPast() && $order->delivered_at === null && $order->cancelled_at === null,
        ];
    }
}
