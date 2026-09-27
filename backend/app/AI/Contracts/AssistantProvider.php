<?php

namespace App\AI\Contracts;

interface AssistantProvider
{
    /**
     * @param  array<int, array<string, mixed>>  $contents
     * @param  array<int, array<string, mixed>>  $tools
     * @param  array<int, array{id: string, name: string, result: array<string, mixed>}>  $toolResults
     */
    public function respond(array $contents, array $tools, ?string $previousInteractionId = null, array $toolResults = []): AssistantResponse;

    public function generateTitle(string $firstMessage): string;

    public function isAvailable(): bool;
}
