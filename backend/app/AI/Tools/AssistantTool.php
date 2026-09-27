<?php

namespace App\AI\Tools;

use App\Models\User;

interface AssistantTool
{
    public function name(): string;

    /** @return array<string, mixed> */
    public function definition(): array;

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function execute(User $user, array $arguments): array;
}
