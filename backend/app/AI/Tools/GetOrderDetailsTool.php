<?php

namespace App\AI\Tools;

use App\AI\Tools\Concerns\FormatsOrder;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class GetOrderDetailsTool implements AssistantTool
{
    use FormatsOrder;

    public function name(): string
    {
        return 'get_order_details';
    }

    public function definition(): array
    {
        return ['name' => $this->name(), 'description' => 'Consulta detalhes de um pedido pelo número.', 'parameters' => ['type' => 'OBJECT', 'properties' => ['order_number' => ['type' => 'STRING', 'description' => 'Número do pedido, por exemplo OM-2026-0001.']], 'required' => ['order_number']]];
    }

    public function execute(User $user, array $arguments): array
    {
        $data = Validator::validate($arguments, ['order_number' => ['required', 'string', 'max:32']]);
        $order = Order::query()->where('user_id', $user->id)->where('number', $data['order_number'])->with(['items', 'trackingEvents'])->first();
        if ($order === null) {
            return ['order' => null];
        }

        return ['order' => [...$this->orderSummary($order), 'items' => $order->items->map(fn ($item): array => ['product_name' => $item->product_name, 'quantity' => $item->quantity, 'total_amount' => $item->total_amount])->all(), 'tracking_events' => $order->trackingEvents->take(10)->map(fn ($event): array => ['status' => $event->status?->value, 'description' => $event->description, 'location' => $event->location, 'occurred_at' => $event->occurred_at?->toISOString()])->all()]];
    }
}
