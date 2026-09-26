<?php

namespace App\Services\Tracking;

use App\Models\Order;

abstract class SimulatedTrackingProvider implements TrackingProvider
{
    public function summary(Order $order): array
    {
        return ['carrier' => $this->carrier(), 'tracking_code' => $order->tracking_code, 'latest_event_at' => $order->trackingEvents->last()?->occurred_at?->toISOString()];
    }
}
