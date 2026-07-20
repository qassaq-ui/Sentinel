<script setup lang="ts">
import { InfiniteScroll, router } from '@inertiajs/vue3';
import { Ban, Pencil, Trash2, UsersRound } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useTranslations } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import { destroy as destroyUser, update as updateUser } from '@/routes/users';
import UsersTableSkeletonRows from './UsersTableSkeletonRows.vue';

export type UsersTableUser = {
    id: number;
    name: string;
    email: string;
    status: 'active' | 'blocked';
    role_id: number | null;
    roles: string[];
    created_at: string | null;
    last_login_at?: string | null;
};

type Props = {
    scrollData: string;
    users: UsersTableUser[];
    emptyLabel: string;
    canEdit: boolean;
    canToggleStatus: boolean;
    canDelete: boolean;
    loading?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

const emit = defineEmits<{
    edit: [user: UsersTableUser];
}>();

const { t } = useTranslations();
const processingKey = ref<string | null>(null);
const tableViewport = ref<HTMLElement | null>(null);
const tableViewportHeight = ref(0);
const tableBodyId = computed(() => `users-table-body-${props.scrollData}`);
const hasActions = computed(
    () => props.canEdit || props.canToggleStatus || props.canDelete,
);
const loadingSkeletonRows = computed(() => {
    const headerHeight = 41;
    const rowHeight = 61;
    const availableHeight = Math.max(
        0,
        tableViewportHeight.value - headerHeight,
    );

    return Math.max(4, Math.ceil(availableHeight / rowHeight));
});
let tableViewportObserver: ResizeObserver | null = null;

function updateTableViewportHeight() {
    tableViewportHeight.value = tableViewport.value?.clientHeight ?? 0;
}

function statusLabel(status: UsersTableUser['status']) {
    return status === 'blocked' ? t('Blocked') : t('Active');
}

function toggleStatus(user: UsersTableUser) {
    if (!props.canToggleStatus) {
        return;
    }

    processingKey.value = `status:${user.id}`;

    router.patch(
        updateUser(user).url,
        {
            status: user.status === 'blocked' ? 'active' : 'blocked',
            name: user.name,
            email: user.email,
            password: '',
            role_id: user.role_id,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                processingKey.value = null;
            },
        },
    );
}

function deleteUserById(user: UsersTableUser) {
    if (!props.canDelete) {
        return;
    }

    processingKey.value = `delete:${user.id}`;

    router.delete(destroyUser(user).url, {
        preserveScroll: true,
        onFinish: () => {
            processingKey.value = null;
        },
    });
}

onMounted(() => {
    updateTableViewportHeight();

    if (tableViewport.value === null) {
        return;
    }

    tableViewportObserver = new ResizeObserver(updateTableViewportHeight);
    tableViewportObserver.observe(tableViewport.value);
});

onBeforeUnmount(() => {
    tableViewportObserver?.disconnect();
    tableViewportObserver = null;
});
</script>

<template>
    <div
        class="flex min-h-0 flex-1 flex-col overflow-hidden bg-white dark:bg-[#111113]"
    >
        <div ref="tableViewport" class="relative min-h-0 flex-1 overflow-auto">
            <InfiniteScroll
                :data="scrollData"
                :items-element="`#${tableBodyId}`"
                :buffer="160"
                only-next
                preserve-url
                class="block min-h-full"
                #default="{ loadingNext }"
            >
                <Table class="w-full table-fixed">
                    <TableHeader
                        class="sticky top-0 z-10 bg-[#f7f7f8] dark:bg-[#1a1a1c]"
                    >
                        <TableRow>
                            <TableHead class="w-[34%]">
                                {{ t('Full name') }}
                            </TableHead>
                            <TableHead class="w-[18%]">{{
                                t('Role')
                            }}</TableHead>
                            <TableHead class="w-[14%]">{{
                                t('Status')
                            }}</TableHead>
                            <TableHead class="w-[16%]">
                                {{ t('Created at') }}
                            </TableHead>
                            <TableHead class="w-[16%]">
                                {{ t('Last login') }}
                            </TableHead>
                            <TableHead
                                v-if="hasActions"
                                class="w-[12%] text-right"
                            >
                                {{ t('Actions') }}
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody :id="tableBodyId">
                        <UsersTableSkeletonRows
                            v-if="loading"
                            :loading="true"
                            :count="loadingSkeletonRows"
                            :delay="0"
                            :show-actions="hasActions"
                        />

                        <TableRow v-else-if="users.length === 0">
                            <TableCell
                                :colspan="hasActions ? 6 : 5"
                                class="h-56 overflow-hidden text-center"
                            >
                                <div
                                    class="flex h-full flex-col items-center justify-center gap-4"
                                >
                                    <UsersRound
                                        class="size-32 text-muted-foreground opacity-[0.08]"
                                        :stroke-width="1.25"
                                    />
                                    <div
                                        class="text-sm font-medium text-muted-foreground"
                                    >
                                        {{ emptyLabel }}
                                    </div>
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow
                            v-for="user in loading ? [] : users"
                            :key="user.id"
                            class="h-16 border-black/7 transition-colors hover:bg-[#f7f7f8] dark:border-white/8 dark:hover:bg-white/[0.045]"
                        >
                            <TableCell>
                                <div class="min-w-0">
                                    <div class="truncate font-medium">
                                        {{ user.name }}
                                    </div>
                                    <div
                                        class="truncate text-sm text-muted-foreground"
                                    >
                                        {{ user.email }}
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <span class="text-muted-foreground">
                                    {{
                                        user.roles.length > 0
                                            ? user.roles.join(', ')
                                            : t('User')
                                    }}
                                </span>
                            </TableCell>
                            <TableCell>
                                <span
                                    :class="
                                        cn(
                                            'inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium',
                                            user.status === 'blocked'
                                                ? 'border-destructive/20 bg-destructive/10 text-destructive'
                                                : 'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
                                        )
                                    "
                                >
                                    {{ statusLabel(user.status) }}
                                </span>
                            </TableCell>
                            <TableCell class="text-muted-foreground">
                                {{ user.created_at ?? '-' }}
                            </TableCell>
                            <TableCell class="text-muted-foreground">
                                {{ user.last_login_at ?? '-' }}
                            </TableCell>
                            <TableCell v-if="hasActions">
                                <div class="flex justify-end gap-1">
                                    <Button
                                        v-if="canEdit"
                                        type="button"
                                        variant="ghost"
                                        size="icon-sm"
                                        :aria-label="t('Edit')"
                                        @click="emit('edit', user)"
                                    >
                                        <Pencil class="size-4" />
                                    </Button>

                                    <ConfirmActionDialog
                                        v-if="canToggleStatus"
                                        :title="
                                            user.status === 'blocked'
                                                ? t('Unblock user')
                                                : t('Block user')
                                        "
                                        :description="
                                            user.status === 'blocked'
                                                ? t(
                                                      'This user will be unblocked and will regain access to the system.',
                                                  )
                                                : t(
                                                      'This user will be blocked and will lose access to the system.',
                                                  )
                                        "
                                        :confirm-label="
                                            user.status === 'blocked'
                                                ? t('Unblock')
                                                : t('Block')
                                        "
                                        :confirm-variant="
                                            user.status === 'blocked'
                                                ? 'secondary'
                                                : 'destructive'
                                        "
                                        @confirm="toggleStatus(user)"
                                    >
                                        <template #trigger>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon-sm"
                                                :disabled="
                                                    processingKey ===
                                                    `status:${user.id}`
                                                "
                                                :aria-label="
                                                    user.status === 'blocked'
                                                        ? t('Unblock')
                                                        : t('Block')
                                                "
                                            >
                                                <Ban class="size-4" />
                                            </Button>
                                        </template>
                                    </ConfirmActionDialog>

                                    <ConfirmActionDialog
                                        v-if="canDelete"
                                        :title="t('Delete user')"
                                        :description="
                                            t(
                                                'This user and all of their data will be permanently deleted.',
                                            )
                                        "
                                        :confirm-label="t('Delete')"
                                        @confirm="deleteUserById(user)"
                                    >
                                        <template #trigger>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon-sm"
                                                class="text-destructive hover:text-destructive"
                                                :disabled="
                                                    processingKey ===
                                                    `delete:${user.id}`
                                                "
                                                :aria-label="t('Delete')"
                                            >
                                                <Trash2 class="size-4" />
                                            </Button>
                                        </template>
                                    </ConfirmActionDialog>
                                </div>
                            </TableCell>
                        </TableRow>

                        <UsersTableSkeletonRows
                            v-if="!loading"
                            :loading="loadingNext"
                            :show-actions="hasActions"
                        />
                    </TableBody>
                </Table>
            </InfiniteScroll>
        </div>
    </div>
</template>
