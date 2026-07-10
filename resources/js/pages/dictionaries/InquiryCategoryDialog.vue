<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import type { InquiryCategory, InquiryCategoryFormData } from './types';

type Errors = Partial<Record<string, string>>;

type Props = {
    open: boolean;
    mode: 'create' | 'edit';
    category: InquiryCategory | null;
    form: InquiryCategoryFormData;
    errors: Errors;
    processing: boolean;
};

defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'update:form': [value: InquiryCategoryFormData];
    submit: [];
}>();

const { t } = useTranslations();

function updateField<K extends keyof InquiryCategoryFormData>(
    form: InquiryCategoryFormData,
    field: K,
    value: InquiryCategoryFormData[K],
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
                <DialogTitle>
                    {{
                        mode === 'create'
                            ? t('Add inquiry category')
                            : t('Edit inquiry category')
                    }}
                </DialogTitle>
                <DialogDescription>
                    {{ t('Category translations are resolved from JSON files by key.') }}
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="emit('submit')">
                <div class="grid gap-2">
                    <Label for="category-fallback-name">
                        {{ t('Category name') }}
                    </Label>
                    <Input
                        id="category-fallback-name"
                        :model-value="form.fallback_name"
                        autocomplete="off"
                        :placeholder="t('For example, Corruption reports')"
                        @update:model-value="
                            updateField(form, 'fallback_name', String($event))
                        "
                    />
                    <InputError :message="errors.fallback_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="category-fallback-description">
                        {{ t('Category description') }}
                    </Label>
                    <textarea
                        id="category-fallback-description"
                        class="border-input placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 min-h-32 w-full min-w-0 resize-y rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                        :value="form.fallback_description"
                        :placeholder="t('Describe when this category should be used.')"
                        @input="
                            updateField(
                                form,
                                'fallback_description',
                                ($event.target as HTMLTextAreaElement).value,
                            )
                        "
                    />
                    <InputError :message="errors.fallback_description" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="category-review-days">
                            {{ t('Review period') }}
                        </Label>
                        <div class="flex items-center gap-2">
                            <Input
                                id="category-review-days"
                                type="number"
                                min="1"
                                max="365"
                                :model-value="form.review_days"
                                @update:model-value="
                                    updateField(
                                        form,
                                        'review_days',
                                        Number($event) || 1,
                                    )
                                "
                            />
                            <span class="shrink-0 text-sm text-muted-foreground">
                                {{ t('days') }}
                            </span>
                        </div>
                        <InputError :message="errors.review_days" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="category-sort-order">{{ t('Sort order') }}</Label>
                        <Input
                            id="category-sort-order"
                            type="number"
                            min="0"
                            :model-value="form.sort_order"
                            @update:model-value="
                                updateField(form, 'sort_order', Number($event) || 0)
                            "
                        />
                        <InputError :message="errors.sort_order" />
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-md border border-border px-3 py-2.5">
                    <Checkbox
                        id="category-active"
                        :model-value="form.is_active"
                        @update:model-value="
                            updateField(form, 'is_active', $event === true)
                        "
                    />
                    <Label for="category-active" class="text-sm">
                        {{ t('Active') }}
                    </Label>
                </div>

                <div
                    v-if="category"
                    class="grid gap-2 rounded-md border border-border bg-muted/30 p-3 text-xs text-muted-foreground"
                >
                    <div class="font-medium text-foreground">
                        {{ t('JSON translation keys') }}
                    </div>
                    <div class="grid gap-1">
                        <code class="break-all rounded bg-background px-2 py-1">
                            {{ category.name_key }}
                        </code>
                        <code class="break-all rounded bg-background px-2 py-1">
                            {{ category.description_key }}
                        </code>
                    </div>
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
