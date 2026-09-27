<?php

namespace App\AI;

use App\AI\Contracts\AssistantProvider;
use App\AI\Contracts\AssistantResponse;
use App\AI\Exceptions\AssistantUnavailableException;

class UnavailableAssistantProvider implements AssistantProvider
{
    public function respond(array $contents, array $tools, ?string $previousInteractionId = null, array $toolResults = []): AssistantResponse
    {
        throw new AssistantUnavailableException('O assistente está indisponível no momento. Tente novamente mais tarde.');
    }

    public function generateTitle(string $firstMessage): string
    {
        throw new AssistantUnavailableException('O assistente está indisponível.');
    }

    public function isAvailable(): bool
    {
        return false;
    }
}
