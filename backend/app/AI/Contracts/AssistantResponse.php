<?php

namespace App\AI\Contracts;

final readonly class AssistantResponse
{
    /**
     * @param  array<int, array{id: string, name: string, arguments: array<string, mixed>}>  $toolCalls
     * @param  array<string, mixed>|null  $modelContent
     */
    public function __construct(
        public string $text,
        public array $toolCalls = [],
        public ?string $interactionId = null,
        public ?array $modelContent = null,
    ) {}
}
