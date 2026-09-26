<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\TrackingEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'status', 'description', 'location', 'occurred_at'])]
class TrackingEvent extends Model
{
    /** @use HasFactory<TrackingEventFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'occurred_at' => 'immutable_datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
