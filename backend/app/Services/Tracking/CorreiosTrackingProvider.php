<?php

namespace App\Services\Tracking;

class CorreiosTrackingProvider extends SimulatedTrackingProvider
{
    public function carrier(): string
    {
        return 'Correios';
    }
}
