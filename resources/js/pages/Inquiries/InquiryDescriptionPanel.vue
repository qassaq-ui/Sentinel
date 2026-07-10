<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslations } from '@/composables/useTranslations';
import { Languages, LoaderCircle, RotateCcw } from '@lucide/vue';

const supportedLanguages: { code: string; label: string }[] = [
    { code: 'ru', label: 'Русский' },
    { code: 'kk', label: 'Қазақша' },
    { code: 'en', label: 'English' },
    { code: 'es', label: 'Español' },
    { code: 'fr', label: 'Français' },
    { code: 'de', label: 'Deutsch' },
    { code: 'zh', label: '中文' },
    { code: 'ar', label: 'العربية' },
    { code: 'pt', label: 'Português' },
    { code: 'tr', label: 'Türkçe' },
];

type Props = {
    description: string | null;
    selectedLanguage: string;
    translatedDescription: string | null;
    isTranslating: boolean;
    translationError: string;
};

defineProps<Props>();

const emit = defineEmits<{
    'select-language': [language: string];
    'show-original': [];
}>();

const { t } = useTranslations();

function selectLanguage(language: string) {
    emit('select-language', language);
}
</script>

<template>
    <section class="rounded-lg bg-muted/40 px-4 py-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-sm font-semibold">{{ t('Inquiry description') }}</h2>

            <div class="flex items-center gap-2">
                <Button
                    v-if="selectedLanguage !== '' || translatedDescription !== null"
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="h-8 text-xs"
                    :disabled="isTranslating"
                    @click="emit('show-original')"
                >
                    <RotateCcw class="size-3.5" />
                    {{ t('Show original') }}
                </Button>

                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            type="button"
                            variant="secondary"
                            size="sm"
                            class="h-8 text-xs text-[var(--color-tab)]"
                            :disabled="isTranslating"
                        >
                            <LoaderCircle v-if="isTranslating" class="size-3.5 animate-spin" />
                            <Languages v-else class="size-3.5" />
                            {{ t('Translate') }}
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-48">
                        <DropdownMenuItem
                            v-for="language in supportedLanguages"
                            :key="language.code"
                            :disabled="isTranslating"
                            @select="selectLanguage(language.code)"
                        >
                            {{ language.label }}
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>

        <p
            v-if="translationError !== ''"
            class="mt-4 rounded-lg bg-destructive/10 px-3 py-2 text-sm text-destructive"
        >
            {{ translationError }}
        </p>

        <p
            v-if="isTranslating"
            class="mt-4 max-w-none animate-pulse whitespace-pre-line rounded-sm bg-primary/10 text-sm leading-6 text-transparent"
        >
            {{ description || t('No description provided') }}
        </p>

        <p
            v-else
            class="mt-4 max-w-none whitespace-pre-line text-sm leading-6 text-foreground"
        >
            <template v-if="translatedDescription !== null">
                {{ translatedDescription || t('No description provided') }}
            </template>
            <template v-else>
                {{ description || t('No description provided') }}
            </template>
        </p>
    </section>
</template>
