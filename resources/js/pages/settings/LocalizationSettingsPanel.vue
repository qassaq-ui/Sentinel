<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3';
import { Check, FileJson, Languages, Upload } from '@lucide/vue';
import {
    store,
    update,
} from '@/actions/App/Http/Controllers/Settings/LocalizationController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import type { LocalizationSettings } from '@/types/ui';

defineProps<{
    settings: LocalizationSettings;
}>();

const { t } = useTranslations();

const uploadForm = useForm<{
    locale: string;
    label: string;
    messages: File | null;
}>({
    locale: '',
    label: '',
    messages: null,
});

function selectMessages(event: Event): void {
    const input = event.target as HTMLInputElement;
    uploadForm.messages = input.files?.[0] ?? null;
}

function uploadLanguage(): void {
    uploadForm.post(store.url(), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            uploadForm.reset();
        },
    });
}

function toggleLocale(code: string, enabled: boolean): void {
    router.patch(
        update.url({ locale: code }),
        { enabled: !enabled },
        { preserveScroll: true },
    );
}
</script>

<template>
    <div class="space-y-5">
        <section
            class="border-y border-black/8 bg-[#f7f7f8] text-card-foreground dark:border-white/10 dark:bg-[#1a1a1c]"
        >
            <div
                class="flex items-center justify-between gap-3 border-b border-border px-4 py-3"
            >
                <div class="min-w-0">
                    <h2 class="text-base font-semibold">
                        {{ t('Languages') }}
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{
                            t(
                                'Manage languages available in the site switcher.',
                            )
                        }}
                    </p>
                </div>
            </div>

            <div class="divide-y divide-border">
                <div
                    v-for="locale in settings.locales"
                    :key="locale.code"
                    class="grid gap-3 px-4 py-3 md:grid-cols-[1fr_auto]"
                >
                    <div class="flex min-w-0 items-start gap-3">
                        <div
                            class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground"
                        >
                            <Languages class="size-4" />
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-foreground">
                                    {{ locale.label }}
                                </p>
                                <Badge variant="secondary" class="font-mono">
                                    {{ locale.code }}
                                </Badge>
                                <Badge
                                    v-if="locale.fallback"
                                    variant="secondary"
                                    class="bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300"
                                >
                                    {{ t('Fallback') }}
                                </Badge>
                            </div>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{
                                    locale.uploaded
                                        ? t('Uploaded language')
                                        : t('Base language')
                                }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between gap-3 md:justify-end"
                    >
                        <span
                            class="inline-flex items-center gap-1.5 text-sm font-medium"
                            :class="
                                locale.enabled
                                    ? 'text-emerald-700 dark:text-emerald-300'
                                    : 'text-muted-foreground'
                            "
                        >
                            <Check v-if="locale.enabled" class="size-4" />
                            {{ locale.enabled ? t('Enabled') : t('Disabled') }}
                        </span>

                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="locale.fallback"
                            @click="toggleLocale(locale.code, locale.enabled)"
                        >
                            {{ locale.enabled ? t('Disable') : t('Enable') }}
                        </Button>
                    </div>
                </div>
            </div>
        </section>

        <form
            class="border-y border-black/8 bg-[#f7f7f8] p-4 text-card-foreground dark:border-white/10 dark:bg-[#1a1a1c]"
            @submit.prevent="uploadLanguage"
        >
            <div class="mb-4 flex items-center gap-2">
                <FileJson class="size-5 text-[var(--color-tab)]" />
                <h2 class="text-base font-semibold">
                    {{ t('Upload language') }}
                </h2>
            </div>

            <div class="grid gap-4 md:grid-cols-[10rem_1fr_1.2fr_auto]">
                <div class="space-y-2">
                    <Label for="locale-code">{{ t('Language code') }}</Label>
                    <Input
                        id="locale-code"
                        v-model="uploadForm.locale"
                        placeholder="mn"
                    />
                    <InputError :message="uploadForm.errors.locale" />
                </div>

                <div class="space-y-2">
                    <Label for="locale-label">{{ t('Language label') }}</Label>
                    <Input
                        id="locale-label"
                        v-model="uploadForm.label"
                        :placeholder="t('Mongolian')"
                    />
                    <InputError :message="uploadForm.errors.label" />
                </div>

                <div class="space-y-2">
                    <Label for="locale-messages">{{
                        t('JSON translation file')
                    }}</Label>
                    <Input
                        id="locale-messages"
                        type="file"
                        accept="application/json,.json"
                        @change="selectMessages"
                    />
                    <InputError :message="uploadForm.errors.messages" />
                </div>

                <div class="flex items-end">
                    <Button
                        type="submit"
                        class="w-full gap-2"
                        :disabled="uploadForm.processing"
                    >
                        <Upload class="size-4" />
                        {{ t('Upload') }}
                    </Button>
                </div>
            </div>
        </form>
    </div>
</template>
