<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $detail = $request->routeIs('orders.show', 'orders.cancel');

        return array_filter([
            'id' => $this->id,
            'number' => $this->number,
            'status' => $this->status->value,
            'subtotal' => $this->subtotal,
            'shipping_amount' => $this->shipping_amount,
            'discount_amount' => $this->discount_amount,
            'total_amount' => $this->total_amount,
            'carrier' => $this->carrier,
            'tracking_code' => $this->tracking_code,
            'placed_at' => $this->placed_at?->toISOString(),
            'estimated_delivery_at' => $this->estimated_delivery_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'is_delayed' => $this->estimated_delivery_at?->isPast() && $this->delivered_at === null && $this->cancelled_at === null,
            'shipping_address' => $detail ? $this->maskedAddress() : null,
            'tracking' => $detail ? $this->tracking_summary : null,
            'items' => $detail ? OrderItemResource::collection($this->whenLoaded('items')) : null,
            'tracking_events' => $detail ? TrackingEventResource::collection($this->whenLoaded('trackingEvents')) : null,
        ], static fn (mixed $value): bool => $value !== null);
    }

    private function maskedAddress(): array
    {
        $address = $this->shipping_address;

        return ['street' => $address['street'] ?? null, 'neighborhood' => $address['neighborhood'] ?? null, 'city' => $address['city'] ?? null, 'state' => $address['state'] ?? null, 'zip_code' => isset($address['zip_code']) ? substr($address['zip_code'], 0, 5).'***' : null];
    }
}
