<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', UserRole::Customer->value)],
            'number' => ['required', 'string', 'max:32', 'unique:orders,number'],
            'shipping_amount' => ['nullable', 'decimal:0,2', 'min:0'],
            'discount_amount' => ['nullable', 'decimal:0,2', 'min:0'],
            'carrier' => ['nullable', 'string', 'max:64'],
            'tracking_code' => ['nullable', 'string', 'max:64', 'unique:orders,tracking_code'],
            'shipping_address' => ['required', 'array:street,neighborhood,city,state,zip_code'],
            'shipping_address.street' => ['required', 'string', 'max:255'],
            'shipping_address.neighborhood' => ['required', 'string', 'max:128'],
            'shipping_address.city' => ['required', 'string', 'max:128'],
            'shipping_address.state' => ['required', 'string', 'size:2'],
            'shipping_address.zip_code' => ['required', 'string', 'max:16'],
            'placed_at' => ['nullable', 'date'],
            'estimated_delivery_at' => ['nullable', 'date', 'after_or_equal:placed_at'],
            'cancellable_until' => ['nullable', 'date', 'after_or_equal:placed_at'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.sku' => ['required', 'string', 'max:64'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.unit_price' => ['required', 'decimal:0,2', 'min:0'],
        ];
    }
}
