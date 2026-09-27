<?php

namespace App\Jobs;

use App\AI\Contracts\AssistantProvider;
use App\Models\Conversation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateConversationTitleJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 10;

    public function __construct(private int $conversationId, private string $firstMessage) {}

    public function handle(AssistantProvider $provider): void
    {
        try {
            $title = $provider->generateTitle($this->firstMessage);
        } catch (\Throwable) {
            return;
        }

        Conversation::query()->whereKey($this->conversationId)->where('title', 'Nova conversa')->update(['title' => $title]);
    }
}
