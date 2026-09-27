<?php

namespace App\AI;

use App\AI\Contracts\AssistantProvider;
use App\AI\Exceptions\AssistantLimitExceededException;
use App\AI\Tools\ToolRegistry;
use App\Jobs\GenerateConversationTitleJob;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ToolLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

class OrderAssistant
{
    public function __construct(private AssistantProvider $provider, private ToolRegistry $tools) {}

    public function respond(User $user, Conversation $conversation, string $content): Message
    {
        $userMessage = $conversation->messages()->create(['role' => 'user', 'content' => $content]);
        $contents = $this->history($conversation);
        for ($iteration = 0; $iteration < (int) config('services.gemini.max_tool_iterations', 3); $iteration++) {
            $response = $this->provider->respond($contents, $this->tools->definitions());
            if ($response->toolCalls === []) {
                $message = $conversation->messages()->create(['role' => 'assistant', 'content' => $response->text]);
                $this->scheduleTitle($conversation, $content);

                return $message;
            }

            $contents[] = $response->modelContent ?? ['role' => 'model', 'parts' => array_map(fn (array $call): array => ['functionCall' => ['name' => $call['name'], 'args' => $call['arguments']]], $response->toolCalls)];
            $responses = [];
            foreach ($response->toolCalls as $call) {
                $result = $this->runTool($user, $conversation, $userMessage, $call['name'], $call['arguments']);
                $responses[] = ['functionResponse' => ['name' => $call['name'], 'id' => $call['id'], 'response' => $result]];
            }
            $contents[] = ['role' => 'user', 'parts' => $responses];
        }

        throw new AssistantLimitExceededException('O assistente atingiu o limite de consultas desta mensagem. Tente reformular a pergunta.');
    }

    /** @return array<int, array<string, mixed>> */
    private function history(Conversation $conversation): array
    {
        return $conversation->messages()->latest('id')->limit((int) config('services.gemini.history_messages', 10))->get()->reverse()->map(fn (Message $message): array => ['role' => $message->role === 'assistant' ? 'model' : 'user', 'parts' => [['text' => mb_substr($message->content, 0, (int) config('services.gemini.history_characters', 1200))]]])->values()->all();
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function runTool(User $user, Conversation $conversation, Message $message, string $name, array $arguments): array
    {
        $safeName = mb_substr($name, 0, 64);
        $safeArguments = is_array($arguments) ? $arguments : [];
        $startedAt = hrtime(true);
        try {
            $result = $this->tools->execute($user, $safeName, $safeArguments);
            ToolLog::query()->create(['user_id' => $user->id, 'conversation_id' => $conversation->id, 'message_id' => $message->id, 'tool_name' => $safeName, 'input' => $safeArguments, 'output' => $result, 'succeeded' => true]);
            Log::info('Assistant tool completed.', ['tool_name' => $safeName, 'succeeded' => true, 'duration_ms' => $this->duration($startedAt)]);

            return $result;
        } catch (InvalidArgumentException|ValidationException $exception) {
            $result = ['error' => 'Não foi possível executar esta consulta.'];
            ToolLog::query()->create(['user_id' => $user->id, 'conversation_id' => $conversation->id, 'message_id' => $message->id, 'tool_name' => $safeName ?: 'unknown', 'input' => $safeArguments, 'output' => $result, 'succeeded' => false]);
            Log::warning('Assistant tool rejected.', ['tool_name' => $safeName ?: 'unknown', 'succeeded' => false, 'duration_ms' => $this->duration($startedAt)]);

            return $result;
        } catch (Throwable $exception) {
            Log::error('Assistant tool failed.', ['tool_name' => $safeName ?: 'unknown', 'succeeded' => false, 'duration_ms' => $this->duration($startedAt)]);

            throw $exception;
        }
    }

    private function duration(int $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1_000_000);
    }

    private function scheduleTitle(Conversation $conversation, string $firstMessage): void
    {
        if ($conversation->title !== 'Nova conversa' || ! $this->provider->isAvailable() || $conversation->messages()->where('role', 'user')->count() !== 1) {
            return;
        }

        if (app()->environment('production')) {
            GenerateConversationTitleJob::dispatchSync($conversation->id, $firstMessage);

            return;
        }

        GenerateConversationTitleJob::dispatch($conversation->id, $firstMessage);
    }
}
