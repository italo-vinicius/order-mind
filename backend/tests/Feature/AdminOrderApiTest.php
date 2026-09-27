<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function adminToken(User $user): string
{
    return $user->createToken('browser-session', ['*'], now()->addMinutes(120))->plainTextToken;
}

function adminOrderPayload(User $customer): array
{
    return [
        'user_id' => $customer->id,
        'number' => 'OM-ADMIN-0001',
        'shipping_amount' => '14.90',
        'discount_amount' => '10.00',
        'carrier' => 'Correios',
        'tracking_code' => 'ADMIN00000001BR',
        'shipping_address' => [
            'street' => 'Rua da Administração, 10',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zip_code' => '01000-000',
        ],
        'placed_at' => now()->toISOString(),
        'estimated_delivery_at' => now()->addDays(5)->toISOString(),
        'cancellable_until' => now()->addDay()->toISOString(),
        'items' => [
            ['sku' => 'SKU-001', 'product_name' => 'Produto de teste', 'quantity' => 2, 'unit_price' => '50.00'],
        ],
    ];
}

test('only administrators can manage orders and customers', function (): void {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    $this->withToken(adminToken($customer))->getJson('/api/admin/orders')->assertForbidden();
    app('auth')->forgetGuards();
    $this->withToken(adminToken($admin))->getJson('/api/admin/customers')
        ->assertOk()->assertJsonPath('data.0.id', $customer->id);
});

test('an administrator creates and updates an order with calculated totals', function (): void {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();
    $token = adminToken($admin);

    $created = $this->withToken($token)->postJson('/api/admin/orders', adminOrderPayload($customer))
        ->assertCreated()->assertJsonPath('data.status', OrderStatus::PendingPayment->value)
        ->assertJsonPath('data.subtotal', '100.00')->assertJsonPath('data.total_amount', '104.90')
        ->assertJsonPath('data.shipping_address.zip_code', '01000-000')
        ->assertJsonCount(1, 'data.tracking_events');

    $id = $created->json('data.id');
    $this->withToken($token)->patchJson('/api/admin/orders/'.$id, [
        'shipping_amount' => '20.00',
        'discount_amount' => '5.00',
    ])->assertOk()->assertJsonPath('data.total_amount', '115.00');

    $this->withToken($token)->patchJson('/api/admin/orders/'.$id, [
        'items' => [['sku' => 'SKU-002', 'product_name' => 'Produto atualizado', 'quantity' => 1, 'unit_price' => '50.00']],
    ])->assertOk()->assertJsonPath('data.total_amount', '65.00');

    expect(Order::query()->findOrFail($id)->items()->first()->total_amount)->toBe('50.00');
});

test('status changes obey transitions and create an event with terminal dates', function (): void {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->create(['status' => OrderStatus::PendingPayment]);
    $token = adminToken($admin);

    $this->withToken($token)->patchJson('/api/admin/orders/'.$order->id.'/status', ['status' => OrderStatus::Delivered->value])
        ->assertUnprocessable()->assertJsonValidationErrors('status');
    expect($order->fresh()->status)->toBe(OrderStatus::PendingPayment);

    $this->withToken($token)->patchJson('/api/admin/orders/'.$order->id.'/status', ['status' => OrderStatus::Processing->value])
        ->assertOk()->assertJsonPath('data.status', OrderStatus::Processing->value)
        ->assertJsonCount(1, 'data.tracking_events');
    $this->withToken($token)->patchJson('/api/admin/orders/'.$order->id.'/status', ['status' => OrderStatus::Shipped->value])->assertOk();
    $this->withToken($token)->patchJson('/api/admin/orders/'.$order->id.'/status', ['status' => OrderStatus::OutForDelivery->value])->assertOk();
    $this->withToken($token)->patchJson('/api/admin/orders/'.$order->id.'/status', ['status' => OrderStatus::Delivered->value])
        ->assertOk()->assertJsonPath('data.status', OrderStatus::Delivered->value);

    expect($order->fresh()->delivered_at)->not->toBeNull()
        ->and($order->trackingEvents()->where('status', OrderStatus::Delivered->value)->exists())->toBeTrue();

    app('auth')->forgetGuards();
    $this->actingAs($customer, 'sanctum')->getJson('/api/orders/'.$order->id)
        ->assertOk()->assertJsonPath('data.status', OrderStatus::Delivered->value);
});

test('an administrator can append a tracking event and invalid payloads are rejected', function (): void {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->for(User::factory())->create();
    $token = adminToken($admin);

    $this->withToken($token)->postJson('/api/admin/orders/'.$order->id.'/tracking-events', [
        'status' => OrderStatus::Shipped->value,
        'description' => 'Pedido recebido na unidade local.',
        'location' => 'São Paulo',
    ])->assertCreated()->assertJsonPath('data.tracking_events.0.description', 'Pedido recebido na unidade local.');
    $this->withToken($token)->postJson('/api/admin/orders/'.$order->id.'/tracking-events', [])
        ->assertUnprocessable()->assertJsonValidationErrors('description');
});
