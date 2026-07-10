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
import type { Role } from './types';

type Props = {
    open: boolean;
    role: Role | null;
    fallbackLabel: string;
    aiDescription: string;
    error?: string;
    descriptionError?: string;
    updateProcessing: boolean;
    deleteProcessing: boolean;
    canDelete: boolean;
};

defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'update:fallbackLabel': [value: string];
    'update:aiDescription': [value: string];
    submit: [];
    delete: [];
}>();

const { t } = useTranslations();
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ t('Edit role') }}</DialogTitle>
                <DialogDescription>
                    {{ t('Change the role name.') }}
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="emit('submit')">
                <div class="grid gap-2">
                    <Label for="edit-role-name">{{ t('Role name') }}</Label>
                    <Input
                        id="edit-role-name"
                        :model-value="fallbackLabel"
                        name="fallback_label"
                        autocomplete="off"
                        @update:model-value="
                            emit('update:fallbackLabel', String($event))
                        "
                    />
                    <InputError :message="error" />
                </div>

                <div class="grid gap-2">
                    <Label for="edit-role-ai-description">
                        {{ t('AI description') }}
                    </Label>
                    <textarea
                        id="edit-role-ai-description"
                        :value="aiDescription"
                        name="ai_description"
                        rows="4"
                        class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-24 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        :placeholder="
                            t(
                                'Describe what this role is responsible for in English.',
                            )
                        "
                        @input="
                            emit(
                                'update:aiDescription',
                                ($event.target as HTMLTextAreaElement).value,
                            )
                        "
                    />
                    <InputError :message="descriptionError" />
                </div>

                <DialogFooter
                    class="flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                >
                    <Button
                        v-if="canDelete && role && !role.protected"
                        type="button"
                        variant="destructive"
                        :disabled="updateProcessing || deleteProcessing"
                        @click="emit('delete')"
                    >
                        {{ t('Delete role') }}
                    </Button>
                    <span v-else class="hidden sm:block" />

                    <div class="flex flex-col-reverse gap-2 sm:flex-row">
                        <Button
                            type="button"
                            variant="outline"
                            @click="emit('update:open', false)"
                        >
                            {{ t('Cancel') }}
                        </Button>
                        <Button
                            type="submit"
                            :disabled="updateProcessing || deleteProcessing"
                        >
                            {{ t('Save') }}
                        </Button>
                    </div>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
