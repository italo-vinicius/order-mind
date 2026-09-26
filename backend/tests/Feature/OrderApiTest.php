<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('orders are isolated, searchable, filterable, and paginated', function (): void {
    $customer = User::factory()->create();
    $other = User::factory()->create();
    $match = Order::factory()->for($customer)->create(['number' => 'OM-MATCH-001', 'status' => OrderStatus::Processing, 'carrier' => 'Correios']);
    OrderItem::factory()->for($match)->create(['product_name' => 'Câmera especial']);
    Order::factory()->count(15)->for($customer)->create(['status' => OrderStatus::Shipped, 'carrier' => 'Loggi']);
    $foreign = Order::factory()->for($other)->create(['number' => 'OM-PRIVATE-001']);

    $this->actingAs($customer, 'sanctum')->getJson('/api/orders?q=Câmera&status=processing&carrier=Correios&per_page=1')
        ->assertOk()->assertJsonPath('data.0.number', $match->number)->assertJsonPath('meta.per_page', 1)
        ->assertJsonMissing(['number' => $foreign->number]);

    $this->getJson('/api/orders?per_page=101')->assertUnprocessable()->assertJsonValidationErrors('per_page');
});

test('dashboard uses customer scope, Sao Paulo month, and excludes cancelled spending', function (): void {
    $customer = User::factory()->create();
    $other = User::factory()->create();
    $month = CarbonImmutable::now('America/Sao_Paulo')->startOfMonth()->utc();
    Order::factory()->for($customer)->create(['placed_at' => $month->addDay(), 'total_amount' => 100, 'status' => OrderStatus::Processing, 'estimated_delivery_at' => now()->subDay()]);
    Order::factory()->for($customer)->create(['placed_at' => $month->addDays(2), 'total_amount' => 200, 'status' => OrderStatus::Cancelled, 'cancelled_at' => now()]);
    Order::factory()->for($customer)->create(['placed_at' => $month->subDay(), 'total_amount' => 300, 'status' => OrderStatus::Delivered, 'delivered_at' => now()]);
    Order::factory()->for($other)->create(['placed_at' => $month->addDay(), 'total_amount' => 400]);

    $this->actingAs($customer, 'sanctum')->getJson('/api/dashboard')->assertOk()
        ->assertJsonPath('data.total_orders', 3)->assertJsonPath('data.monthly_spending', '100.00')
        ->assertJsonPath('data.delayed_orders', 1)->assertJsonCount(3, 'data.orders_by_status');
});

test('order details mask the address and expose simulated tracking', function (): void {
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->create(['carrier' => 'Jadlog', 'shipping_address' => ['street' => 'Rua Secreta, 10', 'zip_code' => '12345-678', 'city' => 'São Paulo', 'state' => 'SP']]);
    OrderItem::factory()->for($order)->create();
    $order->trackingEvents()->create(['status' => OrderStatus::Shipped, 'description' => 'Em trânsito.', 'occurred_at' => now()]);

    $this->actingAs($customer, 'sanctum')->getJson('/api/orders/'.$order->id)->assertOk()
        ->assertJsonPath('data.shipping_address.zip_code', '12345***')->assertJsonPath('data.tracking.carrier', 'Jadlog')
        ->assertJsonCount(1, 'data.items')->assertJsonCount(1, 'data.tracking_events');
});

test('cancellation is atomic and only allowed before the inclusive deadline', function (): void {
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->create(['status' => OrderStatus::Processing, 'cancellable_until' => now()->addMinute()]);
    $expired = Order::factory()->for($customer)->create(['status' => OrderStatus::PendingPayment, 'cancellable_until' => now()->subSecond()]);

    $this->actingAs($customer, 'sanctum')->postJson('/api/orders/'.$order->id.'/cancel')->assertOk()->assertJsonPath('data.status', 'cancelled');
    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)->and($order->trackingEvents()->where('status', 'cancelled')->exists())->toBeTrue();
    $this->postJson('/api/orders/'.$order->id.'/cancel')->assertUnprocessable();
    $this->postJson('/api/orders/'.$expired->id.'/cancel')->assertUnprocessable();
});
