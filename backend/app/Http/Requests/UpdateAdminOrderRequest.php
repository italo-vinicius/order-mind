<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $orderId = $this->route('order')?->id;

        return [
            'number' => ['sometimes', 'string', 'max:32', Rule::unique('orders', 'number')->ignore($orderId)],
            'shipping_amount' => ['sometimes', 'decimal:0,2', 'min:0'],
            'discount_amount' => ['sometimes', 'decimal:0,2', 'min:0'],
            'carrier' => ['nullable', 'string', 'max:64'],
            'tracking_code' => ['nullable', 'string', 'max:64', Rule::unique('orders', 'tracking_code')->ignore($orderId)],
            'shipping_address' => ['sometimes', 'array:street,neighborhood,city,state,zip_code'],
            'shipping_address.street' => ['required_with:shipping_address', 'string', 'max:255'],
            'shipping_address.neighborhood' => ['required_with:shipping_address', 'string', 'max:128'],
            'shipping_address.city' => ['required_with:shipping_address', 'string', 'max:128'],
            'shipping_address.state' => ['required_with:shipping_address', 'string', 'size:2'],
            'shipping_address.zip_code' => ['required_with:shipping_address', 'string', 'max:16'],
            'placed_at' => ['sometimes', 'date'],
            'estimated_delivery_at' => ['nullable', 'date'],
            'cancellable_until' => ['nullable', 'date'],
            'items' => ['sometimes', 'array', 'min:1', 'max:50'],
            'items.*.sku' => ['required', 'string', 'max:64'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.unit_price' => ['required', 'decimal:0,2', 'min:0'],
        ];
    }
}
