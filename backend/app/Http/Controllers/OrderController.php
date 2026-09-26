<?php

namespace App\Http\Controllers;

use App\Actions\CancelOrderAction;
use App\Enums\OrderStatus;
use App\Http\Requests\IndexOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
use App\Services\Tracking\TrackingProviderRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function index(IndexOrderRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Order::class);
        $query = $this->visibleOrders($request->user());
        $data = $request->validated();
        if (! empty($data['q'])) {
            $term = '%'.$data['q'].'%';
            $query->where(fn (Builder $q) => $q->whereRaw('number ILIKE ?', [$term])->orWhereHas('items', fn (Builder $items) => $items->whereRaw('product_name ILIKE ?', [$term])));
        }
        foreach (['status', 'carrier'] as $filter) {
            if (! empty($data[$filter])) {
                $query->where($filter, $data[$filter]);
            }
        }
        if (! empty($data['date_from'])) {
            $query->where('placed_at', '>=', CarbonImmutable::parse($data['date_from'], 'America/Sao_Paulo')->startOfDay()->utc());
        }
        if (! empty($data['date_to'])) {
            $query->where('placed_at', '<', CarbonImmutable::parse($data['date_to'], 'America/Sao_Paulo')->addDay()->startOfDay()->utc());
        }
        $query->orderBy($data['sort'] ?? 'placed_at', $data['direction'] ?? 'desc');

        return OrderResource::collection($query->paginate($data['per_page'] ?? 15)->withQueryString());
    }

    public function show(Request $request, Order $order, TrackingProviderRegistry $tracking): OrderResource
    {
        $this->authorize('view', $order);
        $order->load(['items', 'trackingEvents']);
        $order->setAttribute('tracking_summary', $tracking->summary($order));

        return new OrderResource($order);
    }

    public function cancel(Request $request, Order $order, CancelOrderAction $action): OrderResource
    {
        $this->authorize('cancel', $order);

        return new OrderResource($action->execute($order));
    }

    public function dashboard(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);
        $query = $this->visibleOrders($request->user());
        $monthStart = CarbonImmutable::now('America/Sao_Paulo')->startOfMonth()->utc();
        $nextMonth = $monthStart->addMonth();
        $delayed = fn (Builder $q) => $q->whereNotNull('estimated_delivery_at')->where('estimated_delivery_at', '<', now())->whereNotIn('status', [OrderStatus::Delivered->value, OrderStatus::Cancelled->value]);

        return response()->json(['data' => [
            'total_orders' => (clone $query)->count(),
            'active_orders' => (clone $query)->whereNotIn('status', [OrderStatus::Delivered->value, OrderStatus::Cancelled->value])->count(),
            'delayed_orders' => (clone $query)->where($delayed)->count(),
            'monthly_spending' => (clone $query)->whereBetween('placed_at', [$monthStart, $nextMonth])->where('status', '!=', OrderStatus::Cancelled->value)->sum('total_amount'),
            'orders_by_status' => (clone $query)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'recent_orders' => OrderResource::collection((clone $query)->latest('placed_at')->limit(5)->get()),
        ]]);
    }

    private function visibleOrders(User $user): Builder
    {
        return Order::query()->with('items')->when($user->role->value !== 'admin', fn (Builder $query) => $query->where('user_id', $user->id));
    }
}
