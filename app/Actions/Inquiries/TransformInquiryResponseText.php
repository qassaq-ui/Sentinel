<?php

namespace App\Actions\Inquiries;

use App\Services\AIAssistant\AIAssistantClient;
use App\Services\AIAssistant\AIAssistantPromptBuilder;

class TransformInquiryResponseText
{
    public function __construct(
        private AIAssistantPromptBuilder $promptBuilder,
        private AIAssistantClient $client,
    ) {}

    public function handle(string $action, string $body, ?string $locale): string
    {
        $messages = $action === 'translate'
            ? $this->promptBuilder->responseTranslationMessages($body, (string) $locale)
            : $this->promptBuilder->responsePolishingMessages($body);

        return $this->client->chat($messages);
    }
}
