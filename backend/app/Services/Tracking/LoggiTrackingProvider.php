<?php

namespace App\Services\Tracking;

class LoggiTrackingProvider extends SimulatedTrackingProvider
{
    public function carrier(): string
    {
        return 'Loggi';
    }
}
