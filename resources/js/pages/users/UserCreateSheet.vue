<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { RefreshCw } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
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
    SheetTrigger,
} from '@/components/ui/sheet';
import { useTranslations } from '@/composables/useTranslations';
import { store as storeUser } from '@/routes/users';

export type UserRoleOption = {
    id: number;
    name: string;
    label: string;
};

type Props = {
    roles: UserRoleOption[];
};

const props = defineProps<Props>();

const { t } = useTranslations();
const isOpen = ref(false);
const form = useForm({
    type: 'regular',
    name: '',
    email: '',
    password: '',
    role_id: 'none',
});

const userRole = computed(() => props.roles.find((role) => role.name === 'user') ?? null);
const systemRoles = computed(() => props.roles.filter((role) => role.name !== 'user'));
const firstSystemRole = computed(() => systemRoles.value[0] ?? null);
const isSystemAccount = computed(() => form.type === 'system');

watch(
    userRole,
    (role) => {
        if (form.type === 'regular' && role) {
            form.role_id = String(role.id);
        }
    },
    { immediate: true },
);

watch(
    () => form.type,
    (type) => {
        if (type === 'regular' && userRole.value) {
            form.role_id = String(userRole.value.id);
        }

        if (type === 'system' && firstSystemRole.value) {
            if (form.role_id === 'none' || form.role_id === String(userRole.value?.id ?? '')) {
                form.role_id = String(firstSystemRole.value.id);
            }
        }
    },
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

function createUser() {
    form
        .transform((data) => ({
            ...data,
            role_id: data.role_id === 'none' ? null : Number(data.role_id),
        }))
        .post(storeUser().url, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                form.clearErrors();
                isOpen.value = false;
            },
        });
}
</script>

<template>
    <Sheet v-model:open="isOpen">
        <SheetTrigger as-child>
            <button
                type="button"
                class="text-sm font-medium text-primary underline-offset-4 hover:underline"
            >
                {{ t('+ Add user') }}
            </button>
        </SheetTrigger>

        <SheetContent class="w-full gap-0 sm:max-w-xl">
            <SheetHeader class="border-b border-border px-6 py-5 pr-12">
                <SheetTitle>{{ t('Add user') }}</SheetTitle>
                <SheetDescription class="sr-only">
                    {{ t('Create a user for requests management.') }}
                </SheetDescription>
            </SheetHeader>

            <form
                class="flex min-h-0 flex-1 flex-col"
                @submit.prevent="createUser"
            >
                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="grid gap-2 md:col-span-2">
                            <Label for="user-type">{{ t('User type') }}</Label>
                            <Select v-model="form.type">
                                <SelectTrigger id="user-type" class="w-full">
                                    <SelectValue
                                        :placeholder="t('Select user type')"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="regular">
                                        {{ t('Regular user') }}
                                    </SelectItem>
                                    <SelectItem value="system">
                                        {{ t('System user') }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.type" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="user-name">{{ t('Full name') }}</Label>
                            <Input
                                id="user-name"
                                v-model="form.name"
                                name="name"
                                autocomplete="name"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="user-email">{{ t('Email') }}</Label>
                            <Input
                                id="user-email"
                                v-model="form.email"
                                name="email"
                                type="email"
                                autocomplete="email"
                            />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="user-password">{{ t('Password') }}</Label>
                            <div class="grid grid-cols-[minmax(0,1fr)_2.25rem] gap-2">
                                <PasswordInput
                                    id="user-password"
                                    v-model="form.password"
                                    name="password"
                                    autocomplete="new-password"
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

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="user-role">{{ t('Role') }}</Label>
                            <template v-if="isSystemAccount">
                                <Select v-model="form.role_id">
                                    <SelectTrigger id="user-role" class="w-full">
                                        <SelectValue :placeholder="t('Select role')" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="role in systemRoles"
                                            :key="role.id"
                                            :value="String(role.id)"
                                        >
                                            {{ role.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.role_id" />
                            </template>
                            <div
                                v-else
                                class="rounded-md border border-border bg-muted/40 px-3 py-2 text-sm font-medium"
                            >
                                {{ t('User') }}
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
                        @click="isOpen = false"
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
