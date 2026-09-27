<?php

namespace App\AI;

use App\AI\Contracts\AssistantProvider;
use App\AI\Contracts\AssistantResponse;
use App\AI\Exceptions\AssistantUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GeminiAssistantProvider implements AssistantProvider
{
    public function respond(array $contents, array $tools, ?string $previousInteractionId = null, array $toolResults = []): AssistantResponse
    {
        $response = $this->request([
            'systemInstruction' => ['parts' => [['text' => 'Você é o assistente OrderMind. Responda em português do Brasil. Para dados de pedidos, use apenas as ferramentas fornecidas. Nunca invente informações nem tente executar ações de alteração. Se uma ferramenta não encontrar dados, informe isso claramente.']]],
            'contents' => $contents,
            'tools' => [['functionDeclarations' => $tools]],
            'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 600],
        ]);
        $modelContent = data_get($response, 'candidates.0.content');
        if (is_array($modelContent)) {
            $modelContent = $this->normalizeModelContent($modelContent);
        }
        $parts = data_get($modelContent, 'parts', []);
        $text = collect($parts)->pluck('text')->filter()->implode("\n");
        $calls = collect($parts)->pluck('functionCall')->filter()->map(fn (array $call): array => ['id' => (string) ($call['id'] ?? ''), 'name' => (string) ($call['name'] ?? ''), 'arguments' => is_array($call['args'] ?? null) ? $call['args'] : []])->values()->all();

        if ($text === '' && $calls === []) {
            throw new AssistantUnavailableException('O provedor de IA retornou uma resposta inválida.');
        }

        return new AssistantResponse($text, $calls, null, is_array($modelContent) ? $modelContent : null);
    }

    public function generateTitle(string $firstMessage): string
    {
        $payload = $this->request(['contents' => [['role' => 'user', 'parts' => [['text' => "Crie um título curto, com no máximo 60 caracteres, em português, para esta conversa. Responda somente o título:\n\n".$firstMessage]]]], 'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 30]]);
        $title = trim((string) data_get($payload, 'candidates.0.content.parts.0.text'));

        if ($title === '') {
            throw new AssistantUnavailableException('O provedor não gerou título.');
        }

        return mb_substr(preg_replace('/\s+/', ' ', $title) ?? '', 0, 60);
    }

    public function isAvailable(): bool
    {
        return filled(config('services.gemini.api_key')) && filled(config('services.gemini.model'));
    }

    /** @return array<string, mixed> */
    private function request(array $payload): array
    {
        if (! $this->isAvailable()) {
            throw new AssistantUnavailableException('O assistente está indisponível porque não foi configurado.');
        }

        try {
            return Http::baseUrl('https://generativelanguage.googleapis.com/v1beta')
                ->acceptJson()
                ->asJson()
                ->withHeaders(['x-goog-api-key' => config('services.gemini.api_key')])
                ->timeout((int) config('services.gemini.timeout', 10))
                ->post('models/'.config('services.gemini.model').':generateContent', $payload)
                ->throw()
                ->json();
        } catch (ConnectionException $exception) {
            throw new AssistantUnavailableException('O assistente demorou para responder.', previous: $exception);
        } catch (\Throwable $exception) {
            throw new AssistantUnavailableException('O assistente está indisponível no momento.', previous: $exception);
        }
    }

    /** @param  array<string, mixed>  $content */
    private function normalizeModelContent(array $content): array
    {
        foreach ($content['parts'] ?? [] as $index => $part) {
            if (is_array($part) && isset($part['functionCall']['args']) && $part['functionCall']['args'] === []) {
                $content['parts'][$index]['functionCall']['args'] = (object) [];
            }
        }

        return $content;
    }
}
