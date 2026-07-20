<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useTranslations } from '@/composables/useTranslations';
import { index as rolesIndex } from '@/routes/roles-permissions';
import { index as usersIndex } from '@/routes/users';
import type { Auth } from '@/types/auth';
import RolesPermissionsSkeleton from './settings/roles-permissions/RolesPermissionsSkeleton.vue';
import type { Permission, Role } from './settings/roles-permissions/types';
import RolesPermissionsPanel from './settings/RolesPermissions.vue';
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
    initialTab: 'users' | 'roles';
    users?: ScrollUsers;
    assignableRoles?: UserRoleOption[];
    roleCatalog?: Role[];
    permissions?: Permission[];
};

const props = withDefaults(defineProps<Props>(), {
    users: () => ({ data: [] }),
    assignableRoles: () => [],
    roleCatalog: () => [],
    permissions: () => [],
});

const { t } = useTranslations();
const page = usePage<{ auth: Auth }>();
const can = computed(() => page.props.auth.can);
const activeTab = ref<'users' | 'roles'>(props.initialTab);
const isTableLoading = ref(props.initialTab === 'users');
const isRolesLoading = ref(false);
const isEditSheetOpen = ref(false);
const editingUser = ref<UsersTableUser | null>(null);
const loadingTimer = window.setTimeout(() => {
    isTableLoading.value = false;
}, 900);
let rolesLoadingTimer: ReturnType<typeof window.setTimeout> | null = null;

function showRolesSkeleton() {
    if (rolesLoadingTimer !== null) {
        window.clearTimeout(rolesLoadingTimer);
    }

    isRolesLoading.value = true;
    rolesLoadingTimer = window.setTimeout(() => {
        isRolesLoading.value = false;
        rolesLoadingTimer = null;
    }, 900);
}

function editUser(user: UsersTableUser) {
    editingUser.value = user;
    isEditSheetOpen.value = true;
}

watch(
    () => props.initialTab,
    (initialTab) => {
        activeTab.value = initialTab;

        if (initialTab === 'roles') {
            showRolesSkeleton();
        }
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    window.clearTimeout(loadingTimer);

    if (rolesLoadingTimer !== null) {
        window.clearTimeout(rolesLoadingTimer);
    }
});
</script>

<template>
    <Head
        :title="
            initialTab === 'roles' ? t('Roles and permissions') : t('Users')
        "
    />

    <div
        class="flex min-h-0 flex-1 flex-col overflow-hidden bg-white text-[#1d1d1f] dark:bg-[#111113] dark:text-white"
    >
        <header
            class="flex shrink-0 items-center justify-between gap-4 px-4 py-5 sm:px-6 lg:px-8 lg:py-6"
        >
            <h1
                class="text-[1.75rem] font-semibold tracking-[-0.04em] lg:text-[2rem]"
            >
                {{ t('Users') }}
            </h1>
            <template v-if="initialTab === 'users'">
                <UserCreateSheet
                    v-if="can.usersCreate"
                    :roles="assignableRoles"
                />
                <UserEditSheet
                    v-if="can.usersUpdate"
                    v-model:open="isEditSheetOpen"
                    :user="editingUser"
                    :roles="assignableRoles"
                />
            </template>
        </header>

        <div
            class="shrink-0 border-y border-black/8 px-4 py-3 sm:px-6 lg:px-8 dark:border-white/10"
        >
            <div
                class="grid h-10 w-full max-w-md gap-0.5 rounded-[10px] bg-black/[0.055] p-0.5 dark:bg-white/[0.08]"
                :class="
                    can.usersView && can.rolesView
                        ? 'grid-cols-2'
                        : 'grid-cols-1'
                "
                role="tablist"
                :aria-label="t('Users')"
            >
                <Link
                    v-if="can.usersView"
                    :href="usersIndex()"
                    preserve-state
                    role="tab"
                    :aria-selected="activeTab === 'users'"
                    class="inline-flex items-center justify-center rounded-lg px-3.5 text-[13px] font-medium transition-[color,background-color,box-shadow] duration-150"
                    :class="
                        activeTab === 'users'
                            ? 'bg-white text-[#1d1d1f] shadow-[0_1px_3px_rgba(0,0,0,0.12)] dark:bg-white/15 dark:text-white dark:shadow-none'
                            : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white'
                    "
                    @click="activeTab = 'users'"
                >
                    {{ t('Users') }}
                </Link>
                <Link
                    v-if="can.rolesView"
                    :href="rolesIndex()"
                    preserve-state
                    role="tab"
                    :aria-selected="activeTab === 'roles'"
                    class="inline-flex items-center justify-center rounded-lg px-3.5 text-[13px] font-medium transition-[color,background-color,box-shadow] duration-150"
                    :class="
                        activeTab === 'roles'
                            ? 'bg-white text-[#1d1d1f] shadow-[0_1px_3px_rgba(0,0,0,0.12)] dark:bg-white/15 dark:text-white dark:shadow-none'
                            : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white'
                    "
                    @click="activeTab = 'roles'"
                >
                    {{ t('Roles and permissions') }}
                </Link>
            </div>
        </div>

        <div v-if="initialTab === 'users'" class="flex min-h-0 flex-1">
            <UsersTable
                scroll-data="users"
                :users="users.data"
                :empty-label="t('No users found')"
                :can-edit="can.usersUpdate"
                :can-toggle-status="can.usersUpdate"
                :can-delete="can.usersDelete"
                :loading="isTableLoading"
                @edit="editUser"
            />
        </div>

        <div
            v-else
            class="min-h-0 flex-1 overflow-auto px-4 py-5 sm:px-6 lg:px-8"
            :aria-busy="isRolesLoading"
        >
            <RolesPermissionsSkeleton v-if="isRolesLoading" />
            <RolesPermissionsPanel
                v-else
                :roles="roleCatalog"
                :permissions="permissions"
            />
        </div>
    </div>
</template>
