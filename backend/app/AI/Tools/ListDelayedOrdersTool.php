<?php

namespace App\AI\Tools;

use App\AI\Tools\Concerns\FormatsOrder;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

class ListDelayedOrdersTool implements AssistantTool
{
    use FormatsOrder;

    public function name(): string
    {
        return 'list_delayed_orders';
    }

    public function definition(): array
    {
        return ['name' => $this->name(), 'description' => 'Lista os pedidos atrasados do cliente autenticado.', 'parameters' => ['type' => 'OBJECT']];
    }

    public function execute(User $user, array $arguments): array
    {
        $orders = Order::query()->where('user_id', $user->id)->whereNotNull('estimated_delivery_at')->where('estimated_delivery_at', '<', now())->whereNotIn('status', [OrderStatus::Delivered->value, OrderStatus::Cancelled->value])->latest('estimated_delivery_at')->limit(20)->get();

        return ['count' => $orders->count(), 'orders' => $orders->map(fn (Order $order): array => $this->orderSummary($order))->all()];
    }
}
