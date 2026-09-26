<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case OutForDelivery = 'out_for_delivery';
    case Delayed = 'delayed';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    /**
     * @return array<OrderStatus>
     */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::PendingPayment => [self::Processing, self::Cancelled],
            self::Processing => [self::Shipped, self::Cancelled],
            self::Shipped => [self::OutForDelivery, self::Delayed],
            self::OutForDelivery => [self::Delivered, self::Delayed],
            self::Delayed => [self::OutForDelivery, self::Delivered],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->nextStatuses(), true);
    }
}
