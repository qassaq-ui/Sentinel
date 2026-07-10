<?php

namespace App\Services\AIAssistant;

use App\Models\Inquiry;
use Illuminate\Support\Facades\Cache;

class InquiryTranslationService
{
    /** @var array<int, array{code: string, label: string}> */
    private const SupportedLanguages = [
        ['code' => 'ru', 'label' => 'Russian'],
        ['code' => 'kk', 'label' => 'Kazakh'],
        ['code' => 'en', 'label' => 'English'],
        ['code' => 'es', 'label' => 'Spanish'],
        ['code' => 'fr', 'label' => 'French'],
        ['code' => 'de', 'label' => 'German'],
        ['code' => 'zh', 'label' => 'Chinese'],
        ['code' => 'ar', 'label' => 'Arabic'],
        ['code' => 'pt', 'label' => 'Portuguese'],
        ['code' => 'tr', 'label' => 'Turkish'],
    ];

    /**
     * @return array<int, array{code: string, label: string}>
     */
    public function supportedLanguages(): array
    {
        return self::SupportedLanguages;
    }

    public function isSupportedLanguage(string $locale): bool
    {
        return collect(self::SupportedLanguages)
            ->contains(fn (array $language): bool => $language['code'] === $locale);
    }

    /**
     * @return array{description: string|null, language: string, from_cache: bool}
     */
    public function translateDescription(Inquiry $inquiry, string $locale): array
    {
        $description = $inquiry->description;
        $languageLabel = $this->languageLabel($locale);

        if ($description === null || trim($description) === '') {
            return ['description' => null, 'language' => $languageLabel, 'from_cache' => true];
        }

        $key = $this->cacheKey($inquiry, $locale);

        /** @var string|null $cached */
        $cached = Cache::get($key);

        if (is_string($cached) && trim($cached) !== '') {
            return ['description' => $cached, 'language' => $languageLabel, 'from_cache' => true];
        }

        $translation = $this->client()->chat([
            [
                'role' => 'system',
                'content' => implode("\n", [
                    'You are a professional translator.',
                    "Reply only with the translated text in {$languageLabel}, no commentary, no quotes.",
                    'Preserve meaning, names, dates, numbers, and official terms.',
                    'Keep the original formatting and line breaks.',
                ]),
            ],
            [
                'role' => 'user',
                'content' => $description,
            ],
        ]);

        $translation = $this->sanitize($translation);

        Cache::put($key, $translation, now()->addMonth());

        return ['description' => $translation, 'language' => $languageLabel, 'from_cache' => false];
    }

    public function forget(Inquiry $inquiry): void
    {
        foreach (self::SupportedLanguages as $language) {
            Cache::forget($this->cacheKey($inquiry, $language['code']));
        }
    }

    private function cacheKey(Inquiry $inquiry, string $locale): string
    {
        return "inquiry.{$inquiry->id}.translation.{$locale}";
    }

    private function client(): AIAssistantClient
    {
        return app(AIAssistantClient::class);
    }

    private function sanitize(string $translation): string
    {
        $trimmed = trim($translation);

        $withoutWrappers = preg_replace('/^\s*```[a-zA-Z]*\s*|\s*```\s*$/u', '', $trimmed) ?? $trimmed;

        return trim($withoutWrappers);
    }

    private function languageLabel(string $locale): string
    {
        $match = collect(self::SupportedLanguages)
            ->first(fn (array $language): bool => $language['code'] === $locale);

        return $match['label'] ?? ucfirst($locale);
    }
}
