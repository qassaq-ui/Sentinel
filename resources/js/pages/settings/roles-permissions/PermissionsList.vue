<script setup lang="ts">
import { computed } from 'vue';
import { useTranslations } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import type { Permission, Role } from './types';

type Props = {
    permissions: Permission[];
    selectedRole: Role | null;
    processingKey: string | null;
    canUpdate: boolean;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    toggle: [permission: Permission];
}>();

const { t } = useTranslations();

const selectedPermissionNames = computed(
    () => new Set(props.selectedRole?.permissions ?? []),
);

const groupedPermissions = computed(() => {
    const groups = new Map<string, Permission[]>();

    props.permissions.forEach((permission) => {
        const permissions = groups.get(permission.group) ?? [];
        permissions.push(permission);
        groups.set(permission.group, permissions);
    });

    return Array.from(groups.entries()).map(([group, permissions]) => ({
        group,
        permissions,
    }));
});

function hasPermission(permission: Permission) {
    return selectedPermissionNames.value.has(permission.name);
}
</script>

<template>
    <div
        class="flex min-h-64 min-w-0 flex-col overflow-hidden border-y border-black/8 bg-[#f7f7f8] dark:border-white/10 dark:bg-[#1a1a1c]"
    >
        <div class="border-b border-border px-4 py-3">
            <div class="text-sm font-medium">{{ t('Permissions') }}</div>
        </div>

        <div class="max-h-[calc(100vh-14rem)] overflow-y-auto">
            <section
                v-for="group in groupedPermissions"
                :key="group.group"
                class="border-b border-border last:border-b-0"
            >
                <div class="border-b border-border bg-muted/30 px-4 py-2.5">
                    <div class="text-sm font-medium">{{ t(group.group) }}</div>
                </div>

                <div
                    v-for="permission in group.permissions"
                    :key="permission.name"
                    class="flex min-h-16 items-center justify-between gap-4 px-4 py-3"
                >
                    <div class="min-w-0">
                        <div class="truncate text-sm font-medium">
                            {{ permission.label }}
                        </div>
                    </div>

                    <button
                        type="button"
                        role="switch"
                        :aria-checked="hasPermission(permission)"
                        :disabled="
                            !canUpdate ||
                            selectedRole?.protected ||
                            processingKey ===
                                `${selectedRole?.id}:${permission.name}`
                        "
                        :class="
                            cn(
                                'relative inline-flex h-5 w-9 shrink-0 items-center rounded-full border border-border px-0.5 transition-colors disabled:cursor-not-allowed disabled:opacity-60',
                                hasPermission(permission)
                                    ? 'border-[var(--color-tab)] bg-[var(--color-tab)]'
                                    : 'bg-muted',
                            )
                        "
                        @click="emit('toggle', permission)"
                    >
                        <span class="sr-only">{{ permission.label }}</span>
                        <span
                            :class="
                                cn(
                                    'size-4 rounded-full bg-background shadow-sm transition-transform',
                                    hasPermission(permission)
                                        ? 'translate-x-4'
                                        : 'translate-x-0',
                                )
                            "
                        />
                    </button>
                </div>
            </section>
        </div>
    </div>
</template>
