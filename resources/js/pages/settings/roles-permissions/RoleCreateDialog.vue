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
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';

type Props = {
    open: boolean;
    fallbackLabel: string;
    aiDescription: string;
    error?: string;
    descriptionError?: string;
    processing: boolean;
    canCreate: boolean;
};

defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'update:fallbackLabel': [value: string];
    'update:aiDescription': [value: string];
    submit: [];
}>();

const { t } = useTranslations();
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <div class="mb-4 flex items-center justify-between gap-4">
            <h1 class="text-lg font-semibold">{{ t('Roles and permissions') }}</h1>
            <DialogTrigger v-if="canCreate" as-child>
                <Button variant="link" class="h-auto px-0 py-0">
                    {{ t('+ Add role') }}
                </Button>
            </DialogTrigger>
        </div>

        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ t('Add role') }}</DialogTitle>
                <DialogDescription>
                    {{ t('Create a new role to configure permissions.') }}
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="emit('submit')">
                <div class="grid gap-2">
                    <Label for="role-name">{{ t('Role name') }}</Label>
                    <Input
                        id="role-name"
                        :model-value="fallbackLabel"
                        name="fallback_label"
                        autocomplete="off"
                        :placeholder="t('For example, Manager')"
                        @update:model-value="
                            emit('update:fallbackLabel', String($event))
                        "
                    />
                    <InputError :message="error" />
                </div>

                <div class="grid gap-2">
                    <Label for="role-ai-description">
                        {{ t('AI description') }}
                    </Label>
                    <textarea
                        id="role-ai-description"
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

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                    >
                        {{ t('Cancel') }}
                    </Button>
                    <Button type="submit" :disabled="processing">
                        {{ t('Add') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
