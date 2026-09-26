<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['sku' => $this->sku, 'product_name' => $this->product_name, 'quantity' => $this->quantity, 'unit_price' => $this->unit_price, 'total_amount' => $this->total_amount];
    }
}
