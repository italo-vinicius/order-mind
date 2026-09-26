<?php

namespace App\Services\Tracking;

class JadlogTrackingProvider extends SimulatedTrackingProvider
{
    public function carrier(): string
    {
        return 'Jadlog';
    }
}
