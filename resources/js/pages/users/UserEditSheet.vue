<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { RefreshCw } from '@lucide/vue';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useTranslations } from '@/composables/useTranslations';
import { update as updateUser } from '@/routes/users';
import type { UserRoleOption } from './UserCreateSheet.vue';
import type { UsersTableUser } from './UsersTable.vue';

type Props = {
    open: boolean;
    user: UsersTableUser | null;
    roles: UserRoleOption[];
};

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const { t } = useTranslations();
const form = useForm({
    status: 'active',
    name: '',
    email: '',
    password: '',
    role_id: 'none',
});

watch(
    () => props.user,
    (user) => {
        if (!user) {
            return;
        }

        form.defaults({
            status: user.status,
            name: user.name,
            email: user.email,
            password: '',
            role_id: user.role_id ? String(user.role_id) : 'none',
        });
        form.reset();
        form.clearErrors();

        form.role_id = String(user.role_id ?? props.roles[0]?.id ?? '');
    },
    { immediate: true },
);

function generatePassword() {
    const characters =
        'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
    const values = new Uint32Array(14);
    crypto.getRandomValues(values);

    form.password = Array.from(
        values,
        (value) => characters[value % characters.length],
    ).join('');
}

function saveUser() {
    if (!props.user) {
        return;
    }

    form.transform((data) => ({
        ...data,
        password: data.password || null,
        role_id: Number(data.role_id),
    })).patch(updateUser(props.user.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            form.clearErrors();
            emit('update:open', false);
        },
    });
}
</script>

<template>
    <Sheet :open="open" @update:open="emit('update:open', $event)">
        <SheetContent class="w-full gap-0 sm:max-w-xl">
            <SheetHeader class="border-b border-border px-6 py-5 pr-12">
                <SheetTitle>{{ t('Edit user') }}</SheetTitle>
                <SheetDescription class="sr-only">
                    {{ t('Update user account details.') }}
                </SheetDescription>
            </SheetHeader>

            <form
                class="flex min-h-0 flex-1 flex-col"
                @submit.prevent="saveUser"
            >
                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="grid gap-2 md:col-span-2">
                            <Label for="edit-user-name">{{
                                t('Full name')
                            }}</Label>
                            <Input
                                id="edit-user-name"
                                v-model="form.name"
                                name="name"
                                autocomplete="name"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="edit-user-email">{{
                                t('Email')
                            }}</Label>
                            <Input
                                id="edit-user-email"
                                v-model="form.email"
                                name="email"
                                type="email"
                                autocomplete="email"
                            />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="edit-user-password">{{
                                t('Password')
                            }}</Label>
                            <div
                                class="grid grid-cols-[minmax(0,1fr)_2.25rem] gap-2"
                            >
                                <PasswordInput
                                    id="edit-user-password"
                                    v-model="form.password"
                                    name="password"
                                    autocomplete="new-password"
                                    :placeholder="
                                        t(
                                            'Leave empty to keep current password',
                                        )
                                    "
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    class="size-9"
                                    :aria-label="t('Generate password')"
                                    @click="generatePassword"
                                >
                                    <RefreshCw class="size-4" />
                                </Button>
                            </div>
                            <InputError :message="form.errors.password" />
                        </div>

                        <div class="grid gap-5 md:col-span-2 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="edit-user-status">{{
                                    t('Status')
                                }}</Label>
                                <Select v-model="form.status">
                                    <SelectTrigger
                                        id="edit-user-status"
                                        class="w-full"
                                    >
                                        <SelectValue
                                            :placeholder="t('Status')"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="active">
                                            {{ t('Active') }}
                                        </SelectItem>
                                        <SelectItem value="blocked">
                                            {{ t('Blocked') }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.status" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="edit-user-role">{{
                                    t('Role')
                                }}</Label>
                                <Select v-model="form.role_id">
                                    <SelectTrigger
                                        id="edit-user-role"
                                        class="w-full"
                                    >
                                        <SelectValue
                                            :placeholder="t('Select role')"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="role in roles"
                                            :key="role.id"
                                            :value="String(role.id)"
                                        >
                                            {{ role.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.role_id" />
                            </div>
                        </div>
                    </div>
                </div>

                <SheetFooter
                    class="mt-0 flex-row justify-end border-t border-border px-6 py-4"
                >
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                    >
                        {{ t('Cancel') }}
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ t('Save') }}
                    </Button>
                </SheetFooter>
            </form>
        </SheetContent>
    </Sheet>
</template>
