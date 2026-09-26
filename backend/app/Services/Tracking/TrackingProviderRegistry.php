<?php

namespace App\Services\Tracking;

use App\Models\Order;

class TrackingProviderRegistry
{
    /** @return array{carrier: string, tracking_code: string|null, latest_event_at: string|null}|null */
    public function summary(Order $order): ?array
    {
        return match ($order->carrier) {
            'Correios' => app(CorreiosTrackingProvider::class)->summary($order),
            'Jadlog' => app(JadlogTrackingProvider::class)->summary($order),
            'Loggi' => app(LoggiTrackingProvider::class)->summary($order),
            default => null,
        };
    }
}
