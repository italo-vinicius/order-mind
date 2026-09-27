<?php

namespace App\AI\Tools;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;

class CalculateMonthlySpendingTool implements AssistantTool
{
    public function name(): string
    {
        return 'calculate_monthly_spending';
    }

    public function definition(): array
    {
        return ['name' => $this->name(), 'description' => 'Calcula os gastos mensais do cliente, sem pedidos cancelados.', 'parameters' => ['type' => 'OBJECT', 'properties' => ['month' => ['type' => 'STRING', 'description' => 'Mês no formato YYYY-MM; omita para o mês atual em São Paulo.']]]];
    }

    public function execute(User $user, array $arguments): array
    {
        $data = Validator::validate($arguments, ['month' => ['nullable', 'date_format:Y-m']]);
        $month = isset($data['month']) ? CarbonImmutable::createFromFormat('Y-m', $data['month'], 'America/Sao_Paulo')->startOfMonth() : CarbonImmutable::now('America/Sao_Paulo')->startOfMonth();
        $start = $month->utc();

        return ['month' => $month->format('Y-m'), 'total_amount' => (string) Order::query()->where('user_id', $user->id)->whereBetween('placed_at', [$start, $start->addMonth()])->where('status', '!=', OrderStatus::Cancelled->value)->sum('total_amount')];
    }
}
