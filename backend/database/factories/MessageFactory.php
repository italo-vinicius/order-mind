<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Message> */
class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'role' => 'user',
            'content' => fake()->sentence(),
            'metadata' => null,
        ];
    }
}
