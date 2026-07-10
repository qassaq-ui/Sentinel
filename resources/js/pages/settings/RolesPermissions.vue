<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import RolesPermissionsController from '@/actions/App/Http/Controllers/Settings/RolesPermissionsController';
import { useTranslations } from '@/composables/useTranslations';
import PermissionsList from './roles-permissions/PermissionsList.vue';
import RoleCreateDialog from './roles-permissions/RoleCreateDialog.vue';
import RoleEditDialog from './roles-permissions/RoleEditDialog.vue';
import RolesList from './roles-permissions/RolesList.vue';
import type { Permission, Role } from './roles-permissions/types';

type Props = {
    roles: Role[];
    permissions: Permission[];
};

const props = defineProps<Props>();
const page = usePage();
const can = computed(() => page.props.auth.can);

const selectedRoleId = ref<number | null>(props.roles[0]?.id ?? null);
const processingKey = ref<string | null>(null);
const isCreateRoleDialogOpen = ref(false);
const isEditRoleDialogOpen = ref(false);
const editingRole = ref<Role | null>(null);
const { t } = useTranslations();
const createRoleForm = useForm({
    name: '',
});
const updateRoleForm = useForm({
    name: '',
});
const deleteRoleForm = useForm({});

const selectedRole = computed(
    () =>
        props.roles.find((role) => role.id === selectedRoleId.value) ??
        props.roles[0] ??
        null,
);

watch(
    () => props.roles,
    (roles) => {
        if (!roles.some((role) => role.id === selectedRoleId.value)) {
            selectedRoleId.value = roles[0]?.id ?? null;
        }
    },
);

function hasPermission(role: Role | null, permission: Permission) {
    return Boolean(role?.permissions.includes(permission.name));
}

function togglePermission(permission: Permission) {
    if (!can.value.rolesPermissionsUpdate || selectedRole.value?.protected) {
        return;
    }

    if (!selectedRole.value) {
        return;
    }

    const enabled = !hasPermission(selectedRole.value, permission);
    const key = `${selectedRole.value.id}:${permission.name}`;

    processingKey.value = key;

    router.patch(
        RolesPermissionsController.updatePermission(selectedRole.value.id).url,
        {
            permission: permission.name,
            enabled,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                processingKey.value = null;
            },
        },
    );
}

function createRole() {
    if (!can.value.rolesCreate) {
        return;
    }

    createRoleForm.post(RolesPermissionsController.store().url, {
        preserveScroll: true,
        onSuccess: () => {
            createRoleForm.reset();
            createRoleForm.clearErrors();
            isCreateRoleDialogOpen.value = false;
        },
    });
}

function openEditRoleDialog(role: Role) {
    if (!can.value.rolesUpdate) {
        return;
    }

    editingRole.value = role;
    updateRoleForm.defaults({ name: role.name });
    updateRoleForm.reset();
    updateRoleForm.clearErrors();
    isEditRoleDialogOpen.value = true;
}

function updateRole() {
    if (!editingRole.value) {
        return;
    }

    updateRoleForm.patch(
        RolesPermissionsController.update(editingRole.value.id).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                updateRoleForm.clearErrors();
                isEditRoleDialogOpen.value = false;
                editingRole.value = null;
            },
        },
    );
}

function deleteRole() {
    if (!can.value.rolesDelete || !editingRole.value || editingRole.value.protected) {
        return;
    }

    deleteRoleForm.delete(
        RolesPermissionsController.destroy(editingRole.value.id).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                isEditRoleDialogOpen.value = false;
                editingRole.value = null;
            },
        },
    );
}
</script>

<template>
    <Head :title="t('Roles and permissions')" />

    <RoleCreateDialog
        v-model:open="isCreateRoleDialogOpen"
        v-model:name="createRoleForm.name"
        :error="createRoleForm.errors.name"
        :processing="createRoleForm.processing"
        :can-create="can.rolesCreate"
        @submit="createRole"
    />

    <RoleEditDialog
        v-model:open="isEditRoleDialogOpen"
        v-model:name="updateRoleForm.name"
        :role="editingRole"
        :error="updateRoleForm.errors.name"
        :update-processing="updateRoleForm.processing"
        :delete-processing="deleteRoleForm.processing"
        :can-delete="can.rolesDelete"
        @submit="updateRole"
        @delete="deleteRole"
    />

    <div class="grid gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">
        <RolesList
            v-model:selected-role-id="selectedRoleId"
            :roles="roles"
            :can-edit="can.rolesUpdate"
            @edit="openEditRoleDialog"
        />

        <PermissionsList
            :permissions="permissions"
            :selected-role="selectedRole"
            :processing-key="processingKey"
            :can-update="can.rolesPermissionsUpdate"
            @toggle="togglePermission"
        />
    </div>
</template>
