<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\ToolLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /** @var array<int, array{sku: string, name: string, price: float}> */
    private array $products = [
        ['sku' => 'OM-CABO-01', 'name' => 'Cabo USB-C trançado 2 m', 'price' => 39.90],
        ['sku' => 'OM-SUP-02', 'name' => 'Suporte articulado para notebook', 'price' => 159.90],
        ['sku' => 'OM-FONE-03', 'name' => 'Fone sem fio compacto', 'price' => 189.90],
        ['sku' => 'OM-TECL-04', 'name' => 'Teclado mecânico compacto', 'price' => 299.90],
        ['sku' => 'OM-MOUSE-05', 'name' => 'Mouse ergonômico sem fio', 'price' => 129.90],
        ['sku' => 'OM-WEB-06', 'name' => 'Webcam Full HD', 'price' => 219.90],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->user('Marina Admin', 'admin@ordermind.test', UserRole::Admin);
            $ana = $this->user('Ana Cliente', 'ana@ordermind.test', UserRole::Customer);
            $bruno = $this->user('Bruno Cliente', 'bruno@ordermind.test', UserRole::Customer);

            foreach (range(1, 30) as $index) {
                $this->seedOrder($index, $index % 2 === 0 ? $ana : $bruno);
            }

            $this->seedConversations($ana, $bruno);
        });
    }

    private function user(string $name, string $email, UserRole $role): User
    {
        return User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'role' => $role,
                'email_verified_at' => CarbonImmutable::now('UTC'),
                'password' => Hash::make('ordermind-demo'),
            ],
        );
    }

    private function seedOrder(int $index, User $user): void
    {
        $status = OrderStatus::cases()[($index - 1) % count(OrderStatus::cases())];
        $items = $this->itemsFor($index);
        $subtotal = array_sum(array_column($items, 'total_amount'));
        $shipping = $index % 3 === 0 ? 0.0 : 14.90;
        $discount = $index % 5 === 0 ? 20.0 : 0.0;
        $now = CarbonImmutable::now('UTC');
        $placedAt = $now->subDays($index);
        $estimatedDelivery = $status === OrderStatus::Delayed
            ? $now->subDays(1)
            : $placedAt->addDays(5);
        $isCancellable = in_array($status, [OrderStatus::PendingPayment, OrderStatus::Processing], true);

        $order = Order::query()->firstOrCreate(
            ['number' => sprintf('OM-2026-%04d', $index)],
            [
                'user_id' => $user->id,
                'status' => $status,
                'subtotal' => $subtotal,
                'shipping_amount' => $shipping,
                'discount_amount' => $discount,
                'total_amount' => $subtotal + $shipping - $discount,
                'carrier' => ['Correios', 'Jadlog', 'Loggi'][($index - 1) % 3],
                'tracking_code' => sprintf('OM%010dBR', $index),
                'shipping_address' => [
                    'street' => 'Rua da Demonstração, '.(100 + $index),
                    'neighborhood' => 'Centro',
                    'city' => $index % 2 === 0 ? 'São Paulo' : 'Campinas',
                    'state' => 'SP',
                    'zip_code' => '01000-000',
                ],
                'placed_at' => $placedAt,
                'estimated_delivery_at' => $estimatedDelivery,
                'cancellable_until' => $isCancellable
                    ? ($index % 3 === 0 ? $now->subHour() : $now->addHours(12))
                    : null,
                'delivered_at' => $status === OrderStatus::Delivered ? $placedAt->addDays(4) : null,
                'cancelled_at' => $status === OrderStatus::Cancelled ? $placedAt->addHours(3) : null,
            ],
        );

        if (! $order->wasRecentlyCreated) {
            return;
        }

        $order->items()->createMany($items);
        $order->trackingEvents()->createMany($this->eventsFor($status, $placedAt));
    }

    /** @return array<int, array{sku: string, product_name: string, quantity: int, unit_price: float, total_amount: float}> */
    private function itemsFor(int $index): array
    {
        $items = [];

        foreach (range(0, 2 + ($index % 4)) as $position) {
            $product = $this->products[($index + $position) % count($this->products)];
            $quantity = 1 + (($index + $position) % 2);

            $items[] = [
                'sku' => $product['sku'],
                'product_name' => $product['name'],
                'quantity' => $quantity,
                'unit_price' => $product['price'],
                'total_amount' => $product['price'] * $quantity,
            ];
        }

        return $items;
    }

    /** @return array<int, array{status: OrderStatus, description: string, location: string, occurred_at: CarbonImmutable}> */
    private function eventsFor(OrderStatus $status, CarbonImmutable $placedAt): array
    {
        $events = [[
            'status' => OrderStatus::PendingPayment,
            'description' => 'Pedido recebido.',
            'location' => 'Loja OrderMind',
            'occurred_at' => $placedAt,
        ]];

        if ($status === OrderStatus::PendingPayment) {
            return $events;
        }

        $events[] = [
            'status' => OrderStatus::Processing,
            'description' => 'Pagamento confirmado e pedido em separação.',
            'location' => 'Centro de distribuição',
            'occurred_at' => $placedAt->addHour(),
        ];

        if ($status === OrderStatus::Processing || $status === OrderStatus::Cancelled) {
            if ($status === OrderStatus::Cancelled) {
                $events[] = [
                    'status' => OrderStatus::Cancelled,
                    'description' => 'Pedido cancelado dentro do prazo.',
                    'location' => 'Loja OrderMind',
                    'occurred_at' => $placedAt->addHours(3),
                ];
            }

            return $events;
        }

        $events[] = [
            'status' => OrderStatus::Shipped,
            'description' => 'Pedido entregue à transportadora.',
            'location' => 'Centro de distribuição',
            'occurred_at' => $placedAt->addDay(),
        ];

        if ($status === OrderStatus::Shipped) {
            return $events;
        }

        if ($status === OrderStatus::Delayed) {
            $events[] = [
                'status' => OrderStatus::Delayed,
                'description' => 'Entrega atrasada; nova atualização em breve.',
                'location' => 'Unidade de distribuição',
                'occurred_at' => $placedAt->addDays(6),
            ];

            return $events;
        }

        $events[] = [
            'status' => OrderStatus::OutForDelivery,
            'description' => 'Pedido saiu para entrega.',
            'location' => 'Unidade local',
            'occurred_at' => $placedAt->addDays(3),
        ];

        if ($status === OrderStatus::OutForDelivery) {
            return $events;
        }

        $events[] = [
            'status' => OrderStatus::Delivered,
            'description' => 'Pedido entregue.',
            'location' => 'Endereço de entrega',
            'occurred_at' => $placedAt->addDays(4),
        ];

        return $events;
    }

    private function seedConversations(User $ana, User $bruno): void
    {
        $conversation = Conversation::query()->firstOrCreate(
            ['user_id' => $ana->id, 'title' => 'Acompanhar pedido mais recente'],
        );
        $message = $conversation->messages()->firstOrCreate(
            ['role' => 'user', 'content' => 'Onde está meu pedido mais recente?'],
        );
        $conversation->messages()->firstOrCreate(
            ['role' => 'assistant', 'content' => 'Vou consultar o rastreamento do seu pedido.'],
        );
        ToolLog::query()->firstOrCreate(
            ['user_id' => $ana->id, 'conversation_id' => $conversation->id, 'tool_name' => 'get_latest_order'],
            ['message_id' => $message->id, 'input' => [], 'output' => ['order_number' => 'OM-2026-0030'], 'succeeded' => true],
        );

        $conversation = Conversation::query()->firstOrCreate(
            ['user_id' => $bruno->id, 'title' => 'Verificar pedidos atrasados'],
        );
        $message = $conversation->messages()->firstOrCreate(
            ['role' => 'user', 'content' => 'Tenho algum pedido atrasado?'],
        );
        ToolLog::query()->firstOrCreate(
            ['user_id' => $bruno->id, 'conversation_id' => $conversation->id, 'tool_name' => 'list_delayed_orders'],
            ['message_id' => $message->id, 'input' => [], 'output' => ['count' => 2], 'succeeded' => true],
        );
    }
}
