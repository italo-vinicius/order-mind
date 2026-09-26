<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('number', 32)->unique();
            $table->string('status', 32)->index();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->string('carrier', 64)->nullable()->index();
            $table->string('tracking_code', 64)->nullable()->unique();
            $table->json('shipping_address');
            $table->timestampTz('placed_at')->index();
            $table->timestampTz('estimated_delivery_at')->nullable()->index();
            $table->timestampTz('cancellable_until')->nullable();
            $table->timestampTz('delivered_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampsTz();

            $table->index(['user_id', 'placed_at']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 64);
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->timestampsTz();
        });

        Schema::create('tracking_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32)->nullable();
            $table->string('description');
            $table->string('location', 128)->nullable();
            $table->timestampTz('occurred_at')->index();
            $table->timestampsTz();

            $table->index(['order_id', 'occurred_at']);
        });

        Schema::create('conversations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->timestampsTz();
        });

        Schema::create('messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->timestampsTz();

            $table->index(['conversation_id', 'created_at']);
        });

        Schema::create('tool_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('message_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tool_name', 64)->index();
            $table->json('input');
            $table->json('output')->nullable();
            $table->boolean('succeeded');
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_logs');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('tracking_events');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
