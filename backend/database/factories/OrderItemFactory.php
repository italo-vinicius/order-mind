<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrderItem> */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 3);
        $unitPrice = fake()->randomFloat(2, 15, 250);

        return [
            'order_id' => Order::factory(),
            'sku' => fake()->unique()->bothify('SKU-####??'),
            'product_name' => fake()->words(3, true),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_amount' => $quantity * $unitPrice,
        ];
    }
}
