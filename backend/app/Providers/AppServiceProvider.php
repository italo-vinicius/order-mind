<?php

namespace App\Providers;

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
        //
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
