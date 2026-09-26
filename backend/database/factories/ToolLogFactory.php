<?php

namespace Database\Factories;

use App\Models\ToolLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ToolLog> */
class ToolLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'conversation_id' => null,
            'message_id' => null,
            'tool_name' => 'get_latest_order',
            'input' => [],
            'output' => [],
            'succeeded' => true,
            'created_at' => now(),
        ];
    }
}
