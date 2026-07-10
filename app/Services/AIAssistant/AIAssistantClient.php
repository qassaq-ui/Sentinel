<?php

namespace App\Services\AIAssistant;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AIAssistantClient
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     *
     * @throws ConnectionException
     */
    public function chat(array $messages): string
    {
        $baseUrl = rtrim((string) config('services.ai_assistant.base_url'), '/');
        $model = (string) config('services.ai_assistant.model');
        $timeout = (int) config('services.ai_assistant.timeout', 120);

        $response = Http::acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout($timeout)
            ->post("{$baseUrl}/chat/completions", [
                'model' => $model,
                'messages' => $messages,
                'stream' => false,
            ])
            ->throw()
            ->json();

        $content = data_get($response, 'choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('AI assistant returned an empty response.');
        }

        return trim($content);
    }
}
