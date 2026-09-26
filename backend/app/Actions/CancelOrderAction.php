<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelOrderAction
{
    public function execute(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            if (! in_array($order->status, [OrderStatus::PendingPayment, OrderStatus::Processing], true) || $order->cancellable_until === null || $order->cancellable_until->isPast()) {
                throw ValidationException::withMessages(['order' => ['Este pedido não pode mais ser cancelado.']]);
            }
            $order->update(['status' => OrderStatus::Cancelled, 'cancelled_at' => now()]);
            $order->trackingEvents()->create(['status' => OrderStatus::Cancelled, 'description' => 'Pedido cancelado pelo cliente.', 'location' => 'Loja OrderMind', 'occurred_at' => now()]);

            return $order->fresh(['items', 'trackingEvents']);
        });
    }
}
