<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 40, 500);
        $shipping = fake()->randomFloat(2, 0, 35);
        $discount = fake()->randomFloat(2, 0, min(20, $subtotal));

        return [
            'user_id' => User::factory(),
            'number' => 'OM-'.fake()->unique()->numerify('########'),
            'status' => OrderStatus::Processing,
            'subtotal' => $subtotal,
            'shipping_amount' => $shipping,
            'discount_amount' => $discount,
            'total_amount' => $subtotal + $shipping - $discount,
            'carrier' => fake()->randomElement(['Correios', 'Jadlog', 'Loggi']),
            'tracking_code' => fake()->unique()->bothify('BR##########??'),
            'shipping_address' => ['city' => fake()->city(), 'state' => fake()->stateAbbr()],
            'placed_at' => now()->subDays(fake()->numberBetween(1, 20)),
            'estimated_delivery_at' => now()->addDays(fake()->numberBetween(1, 10)),
            'cancellable_until' => now()->addDay(),
        ];
    }
}
