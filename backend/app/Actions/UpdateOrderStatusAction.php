<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateOrderStatusAction
{
    public function execute(Order $order, OrderStatus $status): Order
    {
        return DB::transaction(function () use ($order, $status): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            if (! $order->status->canTransitionTo($status)) {
                throw ValidationException::withMessages(['status' => ['A transição de status solicitada não é permitida.']]);
            }

            $attributes = ['status' => $status];
            if ($status === OrderStatus::Delivered) {
                $attributes['delivered_at'] = now();
            }
            if ($status === OrderStatus::Cancelled) {
                $attributes['cancelled_at'] = now();
            }
            $order->update($attributes);
            $order->trackingEvents()->create([
                'status' => $status,
                'description' => 'Status atualizado para '.str($status->value)->replace('_', ' ').'.',
                'location' => 'Administração OrderMind',
                'occurred_at' => now(),
            ]);

            return $order->fresh(['user', 'items', 'trackingEvents']);
        });
    }
}
