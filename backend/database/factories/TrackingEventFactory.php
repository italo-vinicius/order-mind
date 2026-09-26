<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\TrackingEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TrackingEvent> */
class TrackingEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'status' => OrderStatus::Shipped,
            'description' => 'Pedido encaminhado para a transportadora.',
            'location' => fake()->city().', '.fake()->stateAbbr(),
            'occurred_at' => now()->subDay(),
        ];
    }
}
