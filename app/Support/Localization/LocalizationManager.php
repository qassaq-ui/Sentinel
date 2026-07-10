<?php

namespace App\Support\Localization;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LocalizationManager
{
    private const Directory = 'localizations';

    private const LabelsFile = 'localizations/labels.json';

    public function availableLocales(): array
    {
        $locales = collect(config('localization.locales', []))
            ->map(fn (string $label, string $locale): array => [
                'code' => $locale,
                'label' => $label,
                'uploaded' => false,
            ]);

        foreach ($this->uploadedLocaleCodes() as $locale) {
            $locales->put($locale, [
                'code' => $locale,
                'label' => $this->labels()[$locale] ?? Str::upper($locale),
                'uploaded' => true,
            ]);
        }

        return $locales->sortKeys()->values()->all();
    }

    public function hasLocale(string $locale): bool
    {
        return collect($this->availableLocales())->contains(
            fn (array $availableLocale): bool => $availableLocale['code'] === $locale,
        );
    }

    public function currentLocale(): string
    {
        $locale = session('locale') ?? request()->cookie('locale') ?? config('app.locale');

        if (is_string($locale) && $this->hasLocale($locale)) {
            return $locale;
        }

        return config('app.fallback_locale');
    }

    public function messages(string $locale): array
    {
        return array_replace_recursive(
            $this->baseMessages($locale),
            $this->uploadedMessages($locale),
        );
    }

    public function storeUploadedLocale(string $locale, string $label, array $messages): void
    {
        $this->disk()->put(
            self::Directory.'/'.$locale.'.json',
            json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        );

        $labels = $this->labels();
        $labels[$locale] = $label;

        $this->disk()->put(
            self::LabelsFile,
            json_encode($labels, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        );
    }

    private function baseMessages(string $locale): array
    {
        $path = lang_path($locale.'.json');

        if (! File::exists($path)) {
            return [];
        }

        $messages = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

        return is_array($messages) ? $messages : [];
    }

    private function uploadedMessages(string $locale): array
    {
        $path = self::Directory.'/'.$locale.'.json';

        if (! $this->disk()->exists($path)) {
            return [];
        }

        $messages = json_decode($this->disk()->get($path) ?: '{}', true, 512, JSON_THROW_ON_ERROR);

        return is_array($messages) ? Arr::except($messages, ['_meta']) : [];
    }

    private function uploadedLocaleCodes(): array
    {
        return collect($this->disk()->files(self::Directory))
            ->filter(fn (string $path): bool => Str::endsWith($path, '.json') && $path !== self::LabelsFile)
            ->map(fn (string $path): string => Str::beforeLast(basename($path), '.json'))
            ->values()
            ->all();
    }

    private function labels(): array
    {
        if (! $this->disk()->exists(self::LabelsFile)) {
            return [];
        }

        $labels = json_decode($this->disk()->get(self::LabelsFile) ?: '{}', true, 512, JSON_THROW_ON_ERROR);

        return is_array($labels) ? $labels : [];
    }

    private function disk(): Filesystem
    {
        return Storage::disk('local');
    }
}
