<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
import type { Auth } from '@/types/auth';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import type { UserRoleOption } from './users/UserCreateSheet.vue';
import UserCreateSheet from './users/UserCreateSheet.vue';
import UserEditSheet from './users/UserEditSheet.vue';
import type { UsersTableUser } from './users/UsersTable.vue';
import UsersTable from './users/UsersTable.vue';

type ScrollUsers = {
    data: UsersTableUser[];
    nextPage?: number | null;
    previousPage?: number | null;
    currentPage?: number | null;
};

type Props = {
    regularUsers: ScrollUsers;
    systemUsers: ScrollUsers;
    roles: UserRoleOption[];
};

defineProps<Props>();

const { t } = useTranslations();
const page = usePage<{ auth: Auth }>();
const can = computed(() => page.props.auth.can);
const activeTab = ref<'regular' | 'system'>('regular');
const isTabLoading = ref(true);
const isEditSheetOpen = ref(false);
const editingUser = ref<UsersTableUser | null>(null);
let tabLoadingTimer: ReturnType<typeof window.setTimeout> | null = null;

function clearTabLoadingTimer() {
    if (tabLoadingTimer === null) {
        return;
    }

    window.clearTimeout(tabLoadingTimer);
    tabLoadingTimer = null;
}

function showTabSkeleton() {
    clearTabLoadingTimer();
    isTabLoading.value = true;

    tabLoadingTimer = window.setTimeout(() => {
        isTabLoading.value = false;
        tabLoadingTimer = null;
    }, 900);
}

function editUser(user: UsersTableUser) {
    editingUser.value = user;
    isEditSheetOpen.value = true;
}

watch(activeTab, showTabSkeleton, { immediate: true });

onBeforeUnmount(clearTabLoadingTimer);
</script>

<template>
    <Head :title="t('Users')" />

    <div class="flex min-h-0 flex-1 flex-col gap-4 overflow-hidden p-4">
        <div class="flex shrink-0 flex-col gap-4">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-lg font-semibold">{{ t('Users') }}</h1>
                <UserCreateSheet v-if="can.usersCreate" :roles="roles" />
                <UserEditSheet
                    v-if="can.usersUpdate"
                    v-model:open="isEditSheetOpen"
                    :user="editingUser"
                    :roles="roles"
                />
            </div>

            <div
                class="relative grid h-10 w-full max-w-md grid-cols-2 rounded-lg bg-muted p-1"
                role="tablist"
                aria-label="Users tabs"
            >
                <span
                    class="pointer-events-none absolute inset-y-1 left-1 w-[calc(50%-0.25rem)] rounded-md bg-[var(--color-tab)] shadow-sm transition-transform duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
                    :class="
                        activeTab === 'system'
                            ? 'translate-x-full'
                            : 'translate-x-0'
                    "
                />

                <button
                    type="button"
                    role="tab"
                    :aria-selected="activeTab === 'regular'"
                    class="relative z-10 inline-flex items-center justify-center rounded-md px-3 text-sm font-medium transition-colors duration-200"
                    :class="
                        activeTab === 'regular'
                            ? 'text-white'
                            : 'text-muted-foreground'
                    "
                    @click="activeTab = 'regular'"
                >
                    {{ t('Regular users') }}
                </button>

                <button
                    type="button"
                    role="tab"
                    :aria-selected="activeTab === 'system'"
                    class="relative z-10 inline-flex items-center justify-center rounded-md px-3 text-sm font-medium transition-colors duration-200"
                    :class="
                        activeTab === 'system'
                            ? 'text-white'
                            : 'text-muted-foreground'
                    "
                    @click="activeTab = 'system'"
                >
                    {{ t('System users') }}
                </button>
            </div>
        </div>

        <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
            <div v-if="activeTab === 'regular'" class="min-h-0 flex-1 overflow-hidden">
                <div class="flex h-full min-h-0 flex-1 pr-1">
                    <UsersTable
                        scroll-data="regularUsers"
                        :users="regularUsers.data"
                        :empty-label="t('No regular users found')"
                        :can-edit="can.usersUpdate"
                        :can-toggle-status="can.usersUpdate"
                        :can-delete="can.usersDelete"
                        :loading="isTabLoading"
                        @edit="editUser"
                    />
                </div>
            </div>

            <div v-else class="min-h-0 flex-1 overflow-hidden">
                <div class="flex h-full min-h-0 flex-1 pr-1">
                    <UsersTable
                        scroll-data="systemUsers"
                        :users="systemUsers.data"
                        :empty-label="t('No system users found')"
                        :can-edit="can.usersUpdate"
                        :can-toggle-status="can.usersUpdate"
                        :can-delete="can.usersDelete"
                        :loading="isTabLoading"
                        @edit="editUser"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
