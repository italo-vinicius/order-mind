<?php

namespace App\AI\Tools;

use App\AI\Tools\Concerns\FormatsOrder;
use App\Models\Order;
use App\Models\User;

class GetLatestOrderTool implements AssistantTool
{
    use FormatsOrder;

    public function name(): string
    {
        return 'get_latest_order';
    }

    public function definition(): array
    {
        return ['name' => $this->name(), 'description' => 'Consulta o pedido mais recente do cliente autenticado.', 'parameters' => ['type' => 'OBJECT']];
    }

    public function execute(User $user, array $arguments): array
    {
        $order = Order::query()->where('user_id', $user->id)->latest('placed_at')->first();

        return ['order' => $order ? $this->orderSummary($order) : null];
    }
}
