<script setup lang="ts">
import { Pencil } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import type { Role } from './types';

type Props = {
    roles: Role[];
    selectedRoleId: number | null;
    canEdit: boolean;
};

defineProps<Props>();

const emit = defineEmits<{
    'update:selectedRoleId': [value: number];
    edit: [role: Role];
}>();

const { t } = useTranslations();
</script>

<template>
    <div
        class="flex min-h-64 flex-col overflow-hidden rounded-lg border border-border bg-background"
    >
        <div class="border-b border-border px-4 py-3">
            <div class="text-sm font-medium">{{ t('Roles') }}</div>
        </div>

        <div class="max-h-[calc(100vh-14rem)] space-y-2 overflow-y-auto p-3">
            <div
                v-for="role in roles"
                :key="role.id"
                class="flex items-center rounded-md border border-border transition-colors"
                :class="{ 'bg-muted': role.id === selectedRoleId }"
            >
                <Button
                    variant="ghost"
                    class="h-auto min-w-0 flex-1 justify-start rounded-md bg-transparent px-3 py-3 text-left hover:bg-transparent"
                    @click="emit('update:selectedRoleId', role.id)"
                >
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-medium">
                            {{ role.label }}
                        </span>
                    </span>
                </Button>
                <Button
                    v-if="canEdit && !role.protected"
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    class="mr-1 text-muted-foreground hover:bg-background/80 hover:text-foreground"
                    :aria-label="t('Edit role')"
                    @click.stop="emit('edit', role)"
                >
                    <Pencil class="size-4" />
                </Button>
            </div>
        </div>
    </div>
</template>
