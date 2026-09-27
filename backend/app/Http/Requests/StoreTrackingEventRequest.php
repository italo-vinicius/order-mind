<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrackingEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'description' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:128'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}
