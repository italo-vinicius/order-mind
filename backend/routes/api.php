<?php

use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AdminToolLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)->name('health');

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('/dashboard', [OrderController::class, 'dashboard'])->name('dashboard');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::post('/conversations', [ConversationController::class, 'store']);
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);
    Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'storeMessage']);

    Route::prefix('admin')->middleware('can:access-admin')->group(function (): void {
        Route::get('/access', fn () => response()->noContent());
        Route::get('/customers', [AdminOrderController::class, 'customers']);
        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::post('/orders', [AdminOrderController::class, 'store']);
        Route::get('/orders/{order}', [AdminOrderController::class, 'show']);
        Route::patch('/orders/{order}', [AdminOrderController::class, 'update']);
        Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus']);
        Route::post('/orders/{order}/tracking-events', [AdminOrderController::class, 'storeTrackingEvent']);
        Route::get('/ai-tool-logs', [AdminToolLogController::class, 'index']);
    });
});
