<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PersistAdminOrderAction
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Order
    {
        return DB::transaction(function () use ($attributes): Order {
            $order = Order::query()->create($this->orderAttributes($attributes) + [
                'user_id' => $attributes['user_id'],
                'status' => OrderStatus::PendingPayment,
                'placed_at' => $attributes['placed_at'] ?? now(),
            ]);
            $this->replaceItems($order, $attributes['items']);
            $order->trackingEvents()->create([
                'status' => OrderStatus::PendingPayment,
                'description' => 'Pedido criado pela administração.',
                'location' => 'Administração OrderMind',
                'occurred_at' => $order->placed_at,
            ]);

            return $order->fresh(['user', 'items', 'trackingEvents']);
        });
    }

    /** @param array<string, mixed> $attributes */
    public function update(Order $order, array $attributes): Order
    {
        return DB::transaction(function () use ($order, $attributes): Order {
            if (array_key_exists('items', $attributes)) {
                $attributes['shipping_amount'] ??= $order->shipping_amount;
                $attributes['discount_amount'] ??= $order->discount_amount;
            }
            $data = $this->orderAttributes($attributes);
            if (! array_key_exists('items', $attributes) && (array_key_exists('shipping_amount', $attributes) || array_key_exists('discount_amount', $attributes))) {
                $shipping = (float) ($attributes['shipping_amount'] ?? $order->shipping_amount);
                $discount = (float) ($attributes['discount_amount'] ?? $order->discount_amount);
                $data['total_amount'] = max(0, (float) $order->subtotal + $shipping - $discount);
            }
            $order->update($data);
            if (array_key_exists('items', $attributes)) {
                $this->replaceItems($order, $attributes['items']);
            }

            return $order->fresh(['user', 'items', 'trackingEvents']);
        });
    }

    /** @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function orderAttributes(array $attributes): array
    {
        $data = Arr::only($attributes, [
            'number', 'shipping_amount', 'discount_amount', 'carrier', 'tracking_code',
            'shipping_address', 'placed_at', 'estimated_delivery_at', 'cancellable_until',
        ]);
        $items = $attributes['items'] ?? null;
        if ($items === null) {
            return $data;
        }
        $subtotal = collect($items)->sum(fn (array $item): float => $item['quantity'] * $item['unit_price']);
        $shipping = (float) ($attributes['shipping_amount'] ?? 0);
        $discount = (float) ($attributes['discount_amount'] ?? 0);

        return $data + [
            'subtotal' => $subtotal,
            'total_amount' => max(0, $subtotal + $shipping - $discount),
        ];
    }

    /** @param array<int, array<string, mixed>> $items */
    private function replaceItems(Order $order, array $items): void
    {
        $order->items()->delete();
        $order->items()->createMany(array_map(fn (array $item): array => [
            'sku' => $item['sku'],
            'product_name' => $item['product_name'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'total_amount' => $item['quantity'] * $item['unit_price'],
        ], $items));
    }
}
