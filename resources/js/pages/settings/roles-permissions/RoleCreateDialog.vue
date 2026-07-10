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
    name: string;
    error?: string;
    processing: boolean;
    canCreate: boolean;
};

defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'update:name': [value: string];
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
                        :model-value="name"
                        name="name"
                        autocomplete="off"
                        :placeholder="t('For example, Manager')"
                        @update:model-value="emit('update:name', String($event))"
                    />
                    <InputError :message="error" />
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
