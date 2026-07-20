<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Bot, CheckCircle2, FileSearch, Hash, Info, Save } from '@lucide/vue';
import { computed } from 'vue';
import { update } from '@/actions/App/Http/Controllers/Settings/InquirySettingsController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import type { InquirySettings } from '@/types/ui';

const props = defineProps<{
    settings: InquirySettings;
}>();

const { t } = useTranslations();

const form = useForm({
    number_prefix: props.settings.numberPrefix,
    sequence_padding: props.settings.sequencePadding,
    ai_screening_enabled: props.settings.aiScreeningEnabled,
    ai_screening_instructions: props.settings.aiScreeningInstructions,
});

const numberPreview = computed(() => {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const year = String(now.getFullYear()).slice(-2);
    const sequence = '1'.padStart(Number(form.sequence_padding) || 4, '0');
    const prefix = form.number_prefix.trim().toUpperCase() || 'KAZM';

    return `${prefix}-${month}${year}-${sequence}`;
});

function submit(): void {
    form.transform((data) => ({
        ...data,
        number_prefix: data.number_prefix.trim().toUpperCase(),
        sequence_padding: Number(data.sequence_padding),
    })).patch(update.url(), {
        preserveScroll: true,
        onSuccess: () => form.defaults(),
    });
}
</script>

<template>
    <form class="space-y-5" @submit.prevent="submit">
        <section
            class="border-y border-black/8 bg-[#f7f7f8] dark:border-white/10 dark:bg-[#1a1a1c]"
        >
            <div class="border-b border-black/8 px-4 py-4 dark:border-white/10">
                <div class="flex items-start gap-3">
                    <div
                        class="flex size-9 shrink-0 items-center justify-center rounded-md bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300"
                    >
                        <Hash class="size-4" />
                    </div>
                    <div>
                        <h2 class="text-base font-semibold">
                            {{ t('Inquiry numbering') }}
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{
                                t(
                                    'Configure how registration numbers are generated for new inquiries.',
                                )
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-5 p-4 md:grid-cols-[minmax(0,1fr)_12rem]">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="inquiry-number-prefix">{{
                            t('Number prefix')
                        }}</Label>
                        <Input
                            id="inquiry-number-prefix"
                            v-model="form.number_prefix"
                            maxlength="12"
                            autocomplete="off"
                            class="h-10 uppercase"
                            placeholder="KAZM"
                            @input="form.clearErrors('number_prefix')"
                        />
                        <InputError :message="form.errors.number_prefix" />
                        <p class="text-xs leading-5 text-muted-foreground">
                            {{ t('Use only Latin letters and numbers.') }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="inquiry-sequence-padding">{{
                            t('Sequence digits')
                        }}</Label>
                        <Input
                            id="inquiry-sequence-padding"
                            v-model.number="form.sequence_padding"
                            type="number"
                            min="3"
                            max="8"
                            class="h-10"
                            @input="form.clearErrors('sequence_padding')"
                        />
                        <InputError :message="form.errors.sequence_padding" />
                        <p class="text-xs leading-5 text-muted-foreground">
                            {{ t('From 3 to 8 digits in the running number.') }}
                        </p>
                    </div>
                </div>

                <div
                    class="rounded-lg border border-dashed border-blue-200 bg-blue-50/60 p-3 dark:border-blue-900 dark:bg-blue-950/20"
                >
                    <p class="text-xs font-medium text-muted-foreground">
                        {{ t('Number preview') }}
                    </p>
                    <p
                        class="mt-2 font-mono text-sm font-semibold break-all text-blue-700 dark:text-blue-300"
                    >
                        {{ numberPreview }}
                    </p>
                    <p class="mt-2 text-xs leading-5 text-muted-foreground">
                        {{ t('The sequence restarts every month.') }}
                    </p>
                </div>
            </div>
        </section>

        <section
            class="border-y border-black/8 bg-[#f7f7f8] dark:border-white/10 dark:bg-[#1a1a1c]"
        >
            <div
                class="flex flex-col gap-4 border-b border-black/8 px-4 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-white/10"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="flex size-9 shrink-0 items-center justify-center rounded-md bg-violet-50 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300"
                    >
                        <Bot class="size-4" />
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-base font-semibold">
                                {{ t('AI inquiry screening') }}
                            </h2>
                            <span
                                class="text-xs font-medium"
                                :class="
                                    form.ai_screening_enabled
                                        ? 'text-emerald-700 dark:text-emerald-300'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{
                                    form.ai_screening_enabled
                                        ? t('Enabled')
                                        : t('Disabled')
                                }}
                            </span>
                        </div>
                        <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                            {{
                                t(
                                    'When enabled, AI checks whether a submission matches the purpose of the Speak Up channel before registration.',
                                )
                            }}
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    role="switch"
                    :aria-checked="form.ai_screening_enabled"
                    class="relative h-7 w-12 shrink-0 rounded-full transition-colors outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-tab)] focus-visible:ring-offset-2"
                    :class="
                        form.ai_screening_enabled
                            ? 'bg-[var(--color-tab)]'
                            : 'bg-slate-300 dark:bg-slate-600'
                    "
                    @click="
                        form.ai_screening_enabled = !form.ai_screening_enabled
                    "
                >
                    <span
                        class="absolute top-1 left-1 size-5 rounded-full bg-white shadow-sm transition-transform"
                        :class="
                            form.ai_screening_enabled
                                ? 'translate-x-5'
                                : 'translate-x-0'
                        "
                    />
                    <span class="sr-only">{{ t('AI inquiry screening') }}</span>
                </button>
            </div>

            <div class="space-y-4 p-4">
                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-3">
                        <Label for="ai-screening-instructions">{{
                            t('Admission criteria')
                        }}</Label>
                        <span class="text-xs text-muted-foreground">
                            {{ form.ai_screening_instructions.length }}/5000
                        </span>
                    </div>
                    <textarea
                        id="ai-screening-instructions"
                        v-model="form.ai_screening_instructions"
                        rows="9"
                        maxlength="5000"
                        class="min-h-52 w-full resize-y rounded-md border border-input bg-transparent px-3 py-2 text-sm leading-6 shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus:border-[var(--color-tab)] focus:ring-2 focus:ring-[color-mix(in_srgb,var(--color-tab)_18%,transparent)] disabled:cursor-not-allowed disabled:opacity-60"
                        :placeholder="
                            t(
                                'Describe which submissions should be accepted and which should be rejected.',
                            )
                        "
                        @input="form.clearErrors('ai_screening_instructions')"
                    />
                    <InputError
                        :message="form.errors.ai_screening_instructions"
                    />
                    <p class="text-xs leading-5 text-muted-foreground">
                        {{
                            t(
                                'AI receives only the category, subject, and description. Contact details and attachments are not sent for screening.',
                            )
                        }}
                    </p>
                </div>

                <div class="grid gap-3 lg:grid-cols-2">
                    <div
                        class="flex gap-3 rounded-lg border border-border bg-muted/35 p-3"
                    >
                        <FileSearch
                            class="mt-0.5 size-4 shrink-0 text-[var(--color-tab)]"
                        />
                        <div>
                            <p class="text-sm font-medium">
                                {{ t('Conservative screening') }}
                            </p>
                            <p
                                class="mt-1 text-xs leading-5 text-muted-foreground"
                            >
                                {{
                                    t(
                                        'Only a clear mismatch with high AI confidence is rejected. Uncertain submissions are accepted for human review.',
                                    )
                                }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex gap-3 rounded-lg border border-border bg-muted/35 p-3"
                    >
                        <CheckCircle2
                            class="mt-0.5 size-4 shrink-0 text-emerald-600"
                        />
                        <div>
                            <p class="text-sm font-medium">
                                {{ t('Safe fallback') }}
                            </p>
                            <p
                                class="mt-1 text-xs leading-5 text-muted-foreground"
                            >
                                {{
                                    t(
                                        'If AI is unavailable or returns an invalid result, the inquiry is accepted instead of being lost.',
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="flex gap-3 rounded-lg border border-amber-200 bg-amber-50/70 p-3 text-amber-950 dark:border-amber-900/60 dark:bg-amber-950/20 dark:text-amber-200"
                >
                    <Info class="mt-0.5 size-4 shrink-0" />
                    <p class="text-xs leading-5">
                        {{
                            t(
                                'Do not use screening to suppress criticism or reports about management. Criteria should only exclude clearly irrelevant submissions and spam.',
                            )
                        }}
                    </p>
                </div>
            </div>
        </section>

        <div
            class="sticky bottom-0 z-10 flex justify-end border-t border-black/8 bg-white/90 py-3 backdrop-blur-xl dark:border-white/10 dark:bg-[#111113]/90"
        >
            <Button
                type="submit"
                class="min-w-36 gap-2"
                :disabled="form.processing"
            >
                <Save class="size-4" />
                {{ form.processing ? t('Saving...') : t('Save changes') }}
            </Button>
        </div>
    </form>
</template>
