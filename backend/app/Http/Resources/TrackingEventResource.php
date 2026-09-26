<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackingEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['status' => $this->status?->value, 'description' => $this->description, 'location' => $this->location, 'occurred_at' => $this->occurred_at?->toISOString()];
    }
}
