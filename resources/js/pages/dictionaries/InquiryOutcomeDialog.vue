<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import type { InquiryOutcome, InquiryOutcomeFormData } from './types';

type Errors = Partial<Record<string, string>>;

type Props = {
    open: boolean;
    outcome: InquiryOutcome | null;
    form: InquiryOutcomeFormData;
    errors: Errors;
    processing: boolean;
};

defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'update:form': [value: InquiryOutcomeFormData];
    submit: [];
}>();

const { t } = useTranslations();

function updateField<K extends keyof InquiryOutcomeFormData>(
    form: InquiryOutcomeFormData,
    field: K,
    value: InquiryOutcomeFormData[K],
) {
    emit('update:form', {
        ...form,
        [field]: value,
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[calc(100vh-2rem)] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>{{ t('Edit review outcome') }}</DialogTitle>
                <DialogDescription>
                    {{ t('Configure how this review outcome is shown and used by AI.') }}
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="emit('submit')">
                <div class="grid gap-2">
                    <Label for="outcome-fallback-name">
                        {{ t('Outcome name') }}
                    </Label>
                    <Input
                        id="outcome-fallback-name"
                        :model-value="form.fallback_name"
                        autocomplete="off"
                        :placeholder="t('For example, Confirmed')"
                        @update:model-value="
                            updateField(form, 'fallback_name', String($event))
                        "
                    />
                    <InputError :message="errors.fallback_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="outcome-ai-instruction">
                        {{ t('AI instruction') }}
                    </Label>
                    <textarea
                        id="outcome-ai-instruction"
                        class="border-input placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 min-h-36 w-full min-w-0 resize-y rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                        :value="form.ai_instruction"
                        :placeholder="
                            t(
                                'Explain how AI should generate the response for this outcome.',
                            )
                        "
                        @input="
                            updateField(
                                form,
                                'ai_instruction',
                                ($event.target as HTMLTextAreaElement).value,
                            )
                        "
                    />
                    <InputError :message="errors.ai_instruction" />
                </div>

                <div class="grid gap-2">
                    <Label for="outcome-sort-order">
                        {{ t('Sort order') }}
                    </Label>
                    <Input
                        id="outcome-sort-order"
                        type="number"
                        min="0"
                        :model-value="form.sort_order"
                        @update:model-value="
                            updateField(form, 'sort_order', Number($event) || 0)
                        "
                    />
                    <InputError :message="errors.sort_order" />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                    >
                        {{ t('Cancel') }}
                    </Button>
                    <Button type="submit" :disabled="processing">
                        {{ t('Save') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
