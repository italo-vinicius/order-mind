<?php

namespace App\AI\Tools;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class CheckCancellationEligibilityTool implements AssistantTool
{
    public function name(): string
    {
        return 'check_cancellation_eligibility';
    }

    public function definition(): array
    {
        return ['name' => $this->name(), 'description' => 'Verifica se um pedido pode ser cancelado. Não cancela pedidos.', 'parameters' => ['type' => 'OBJECT', 'properties' => ['order_number' => ['type' => 'STRING']], 'required' => ['order_number']]];
    }

    public function execute(User $user, array $arguments): array
    {
        $data = Validator::validate($arguments, ['order_number' => ['required', 'string', 'max:32']]);
        $order = Order::query()->where('user_id', $user->id)->where('number', $data['order_number'])->first();
        if ($order === null) {
            return ['found' => false, 'eligible' => false];
        }

        $eligible = in_array($order->status, [OrderStatus::PendingPayment, OrderStatus::Processing], true) && $order->cancellable_until?->greaterThanOrEqualTo(now());

        return ['found' => true, 'eligible' => $eligible, 'cancellable_until' => $order->cancellable_until?->toISOString(), 'reason' => $eligible ? 'O pedido pode ser cancelado pela tela de pedidos.' : 'O pedido não atende às regras de cancelamento.'];
    }
}
