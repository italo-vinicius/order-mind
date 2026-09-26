<?php

namespace App\Services\Tracking;

use App\Models\Order;

interface TrackingProvider
{
    public function carrier(): string;

    /** @return array{carrier: string, tracking_code: string|null, latest_event_at: string|null} */
    public function summary(Order $order): array;
}
