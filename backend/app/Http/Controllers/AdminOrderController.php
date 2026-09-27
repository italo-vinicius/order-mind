<?php

namespace App\Http\Controllers;

use App\Actions\PersistAdminOrderAction;
use App\Actions\UpdateOrderStatusAction;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Requests\IndexOrderRequest;
use App\Http\Requests\StoreAdminOrderRequest;
use App\Http\Requests\StoreTrackingEventRequest;
use App\Http\Requests\UpdateAdminOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\AdminCustomerResource;
use App\Http\Resources\AdminOrderResource;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminOrderController extends Controller
{
    public function index(IndexOrderRequest $request): AnonymousResourceCollection
    {
        $query = Order::query()->with(['user', 'items', 'trackingEvents']);
        $data = $request->validated();
        if (! empty($data['q'])) {
            $term = '%'.$data['q'].'%';
            $query->where(fn (Builder $builder) => $builder->whereRaw('number ILIKE ?', [$term])->orWhereHas('user', fn (Builder $users) => $users->whereRaw('name ILIKE ?', [$term])->orWhereRaw('email ILIKE ?', [$term])));
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

        return AdminOrderResource::collection($query->paginate($data['per_page'] ?? 15)->withQueryString());
    }

    public function customers(): AnonymousResourceCollection
    {
        return AdminCustomerResource::collection(User::query()->where('role', UserRole::Customer)->orderBy('name')->limit(100)->get());
    }

    public function show(Order $order): AdminOrderResource
    {
        return new AdminOrderResource($order->load(['user', 'items', 'trackingEvents']));
    }

    public function store(StoreAdminOrderRequest $request, PersistAdminOrderAction $action): JsonResponse
    {
        return (new AdminOrderResource($action->create($request->validated())))->response()->setStatusCode(201);
    }

    public function update(UpdateAdminOrderRequest $request, Order $order, PersistAdminOrderAction $action): AdminOrderResource
    {
        return new AdminOrderResource($action->update($order, $request->validated()));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order, UpdateOrderStatusAction $action): AdminOrderResource
    {
        return new AdminOrderResource($action->execute($order, OrderStatus::from($request->validated('status'))));
    }

    public function storeTrackingEvent(StoreTrackingEventRequest $request, Order $order): JsonResponse
    {
        $order->trackingEvents()->create($request->validated() + ['occurred_at' => $request->validated('occurred_at') ?? now()]);

        return (new AdminOrderResource($order->fresh(['user', 'items', 'trackingEvents'])))->response()->setStatusCode(201);
    }
}
