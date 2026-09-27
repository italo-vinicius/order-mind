<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ToolLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'tool_name' => $this->tool_name, 'input' => $this->input, 'output' => $this->output, 'succeeded' => $this->succeeded, 'created_at' => $this->created_at?->toISOString(), 'user' => $this->whenLoaded('user', fn (): array => ['id' => $this->user->id, 'name' => $this->user->name])];
    }
}
