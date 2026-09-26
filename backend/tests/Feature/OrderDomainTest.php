<?php

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ToolLog;
use App\Models\TrackingEvent;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('persists the order domain relationships and casts', function (): void {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $order = Order::factory()->for($customer)->create(['status' => OrderStatus::Processing]);
    $item = OrderItem::factory()->for($order)->create();
    $event = TrackingEvent::factory()->for($order)->create(['status' => OrderStatus::Shipped]);
    $conversation = Conversation::factory()->for($customer)->create();
    $message = Message::factory()->for($conversation)->create(['metadata' => ['source' => 'demo']]);
    $log = ToolLog::factory()->create([
        'user_id' => $customer->id,
        'conversation_id' => $conversation->id,
        'message_id' => $message->id,
        'input' => ['order_number' => $order->number],
    ]);

    expect($order->fresh())
        ->user->id->toBe($customer->id)
        ->items->first()->id->toBe($item->id)
        ->trackingEvents->first()->id->toBe($event->id)
        ->status->toBe(OrderStatus::Processing)
        ->total_amount->toBeString()
        ->shipping_address->toBeArray()
        ->and($conversation->fresh()->messages->first()->metadata)->toBe(['source' => 'demo'])
        ->and($log->fresh()->user->id)->toBe($customer->id)
        ->and($log->fresh()->message->id)->toBe($message->id);
});

test('demo seed data is coherent and can run again without duplicates', function (): void {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(3)
        ->and(User::query()->where('role', UserRole::Admin)->count())->toBe(1)
        ->and(User::query()->where('role', UserRole::Customer)->count())->toBe(2)
        ->and(Order::query()->count())->toBe(30)
        ->and(Order::query()->distinct('number')->count('number'))->toBe(30)
        ->and(Order::query()->whereIn('carrier', ['Correios', 'Jadlog', 'Loggi'])->count())->toBe(30)
        ->and(Order::query()->whereNotNull('cancellable_until')->count())->toBeGreaterThan(0)
        ->and(OrderItem::query()->count())->toBeGreaterThanOrEqual(90)
        ->and(OrderItem::query()->count())->toBeLessThanOrEqual(180)
        ->and(TrackingEvent::query()->count())->toBeGreaterThan(30)
        ->and(Conversation::query()->count())->toBe(2)
        ->and(ToolLog::query()->count())->toBe(2);

    expect(Order::query()->get()->every(
        fn (Order $order): bool => $order->items()->count() >= 3 && $order->items()->count() <= 6,
    ))->toBeTrue();
});

dataset('approved order transitions', [
    [OrderStatus::PendingPayment, [OrderStatus::Processing, OrderStatus::Cancelled]],
    [OrderStatus::Processing, [OrderStatus::Shipped, OrderStatus::Cancelled]],
    [OrderStatus::Shipped, [OrderStatus::OutForDelivery, OrderStatus::Delayed]],
    [OrderStatus::OutForDelivery, [OrderStatus::Delivered, OrderStatus::Delayed]],
    [OrderStatus::Delayed, [OrderStatus::OutForDelivery, OrderStatus::Delivered]],
    [OrderStatus::Delivered, []],
    [OrderStatus::Cancelled, []],
]);

test('order statuses expose only the approved transitions', function (OrderStatus $status, array $next): void {
    expect($status->nextStatuses())->toBe($next);

    foreach (OrderStatus::cases() as $candidate) {
        expect($status->canTransitionTo($candidate))->toBe(in_array($candidate, $next, true));
    }
})->with('approved order transitions');
