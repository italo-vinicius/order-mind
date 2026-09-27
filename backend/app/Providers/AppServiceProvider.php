<?php

namespace App\Providers;

use App\AI\Contracts\AssistantProvider;
use App\AI\GeminiAssistantProvider;
use App\AI\Tools\CalculateMonthlySpendingTool;
use App\AI\Tools\CheckCancellationEligibilityTool;
use App\AI\Tools\GetLatestOrderTool;
use App\AI\Tools\GetOrderDetailsTool;
use App\AI\Tools\ListDelayedOrdersTool;
use App\AI\Tools\ToolRegistry;
use App\AI\UnavailableAssistantProvider;
use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\ToolLog;
use App\Models\User;
use App\Policies\ConversationPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ToolLogPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AssistantProvider::class, fn (): AssistantProvider => filled(config('services.gemini.api_key')) && filled(config('services.gemini.model')) ? new GeminiAssistantProvider : new UnavailableAssistantProvider);
        $this->app->singleton(ToolRegistry::class, fn (): ToolRegistry => new ToolRegistry([
            new GetLatestOrderTool,
            new GetOrderDetailsTool,
            new ListDelayedOrdersTool,
            new CalculateMonthlySpendingTool,
            new CheckCancellationEligibilityTool,
        ]));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Conversation::class, ConversationPolicy::class);
        Gate::policy(ToolLog::class, ToolLogPolicy::class);
        Gate::define('access-admin', fn (User $user): bool => $user->role === UserRole::Admin);
    }
}
