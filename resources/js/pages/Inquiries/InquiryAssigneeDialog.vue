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
import { useTranslations } from '@/composables/useTranslations';
import { updateAssignee } from '@/actions/App/Http/Controllers/InquiriesController';
import { useForm } from '@inertiajs/vue3';
import { Check, Pencil, Search } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import type { InquiryAssigneeOption } from './types';

type Props = {
    inquiryNumber: string;
    assignee: InquiryAssigneeOption | null;
    systemUsers: InquiryAssigneeOption[];
    canAssign: boolean;
};

const props = defineProps<Props>();

const { t } = useTranslations();
const isOpen = ref(false);
const search = ref('');

const form = useForm<{ assigned_to_id: number | null }>({
    assigned_to_id: props.assignee?.id ?? null,
});

const canSubmit = computed(() => props.assignee !== null || form.assigned_to_id !== null);
const submitLabel = computed(() => {
    if (props.assignee !== null && form.assigned_to_id === null) {
        return t('Unassign executor');
    }

    return t('Assign executor');
});
const filteredUsers = computed(() => {
    const query = search.value.trim().toLowerCase();

    if (query === '') {
        return props.systemUsers;
    }

    return props.systemUsers.filter((user) => {
        return `${user.name} ${user.email} ${user.role ?? ''}`
            .toLowerCase()
            .includes(query);
    });
});

watch(
    () => props.assignee?.id,
    (assigneeId) => {
        form.assigned_to_id = assigneeId ?? null;
    },
);

function selectUser(user: InquiryAssigneeOption) {
    form.assigned_to_id = form.assigned_to_id === user.id ? null : user.id;
}

function submit() {
    if (!canSubmit.value) {
        return;
    }

    form.patch(updateAssignee(props.inquiryNumber).url, {
        preserveScroll: true,
        onSuccess: () => {
            isOpen.value = false;
        },
    });
}
</script>

<template>
    <Dialog :open="isOpen" @update:open="isOpen = $event">
        <DialogTrigger v-if="canAssign" as-child>
            <Button
                v-if="assignee === null"
                type="button"
                variant="link"
                class="h-auto px-0 py-0 text-xs font-semibold text-[var(--color-tab)]"
            >
                {{ t('+ Choose executor') }}
            </Button>

            <Button
                v-else
                type="button"
                variant="ghost"
                size="icon"
                class="size-6 shrink-0 text-[var(--color-tab)] hover:bg-[var(--color-tab)] hover:text-white"
                :aria-label="t('Change executor')"
            >
                <Pencil class="size-3" />
            </Button>
        </DialogTrigger>

        <DialogContent class="gap-0 overflow-hidden p-0 sm:max-w-xl">
            <DialogHeader class="border-b border-border px-5 py-4">
                <DialogTitle class="text-base font-semibold">
                    {{ t('Choose specialist') }}
                </DialogTitle>
                <DialogDescription class="text-xs">
                    {{ t('Select a system user who will handle this inquiry.') }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit">
                <div class="space-y-3 px-5 py-4">
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            v-model="search"
                            class="h-10 pl-9"
                            :placeholder="t('Search specialist')"
                        />
                    </div>

                    <div
                        v-if="systemUsers.length"
                        class="overflow-hidden rounded-lg border border-border"
                    >
                        <div
                            class="grid grid-cols-[minmax(0,1fr)_minmax(120px,0.55fr)_36px] gap-3 border-b border-border bg-muted/40 px-3 py-2 text-xs font-semibold text-muted-foreground"
                        >
                            <span>{{ t('Specialist') }}</span>
                            <span>{{ t('Role') }}</span>
                            <span class="text-right">{{ t('Selected') }}</span>
                        </div>

                        <div class="max-h-72 overflow-y-auto">
                            <button
                                v-for="user in filteredUsers"
                                :key="user.id"
                                type="button"
                                class="grid w-full grid-cols-[minmax(0,1fr)_minmax(120px,0.55fr)_36px] items-center gap-3 border-b border-border px-3 py-2.5 text-left transition-colors last:border-b-0 hover:bg-[var(--color-tab)]/10"
                                :class="
                                    form.assigned_to_id === user.id
                                        ? 'bg-[var(--color-tab)]/10'
                                        : 'bg-background'
                                "
                                @click="selectUser(user)"
                            >
                                <span class="min-w-0">
                                    <span
                                        class="block truncate text-sm font-semibold text-foreground"
                                    >
                                        {{ user.name }}
                                    </span>
                                    <span
                                        class="block truncate text-xs text-muted-foreground"
                                    >
                                        {{ user.email }}
                                    </span>
                                </span>

                                <span class="truncate text-xs font-medium text-muted-foreground">
                                    {{ user.role ?? t('No role') }}
                                </span>

                                <span
                                    class="ml-auto flex size-5 items-center justify-center rounded-sm border border-border"
                                    :class="
                                        form.assigned_to_id === user.id
                                            ? 'border-[var(--color-tab)] bg-[var(--color-tab)] text-white'
                                            : 'bg-background'
                                    "
                                >
                                    <Check
                                        v-if="form.assigned_to_id === user.id"
                                        class="size-3.5"
                                    />
                                </span>
                            </button>

                            <div
                                v-if="filteredUsers.length === 0"
                                class="px-3 py-8 text-center text-sm text-muted-foreground"
                            >
                                {{ t('No specialists found') }}
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="rounded-lg border border-dashed px-3 py-8 text-center text-sm text-muted-foreground"
                    >
                        {{ t('No system users found') }}
                    </div>

                    <InputError :message="form.errors.assigned_to_id" />
                </div>

                <DialogFooter class="border-t border-border bg-muted/30 px-5 py-3">
                    <Button
                        type="button"
                        variant="outline"
                        @click="isOpen = false"
                    >
                        {{ t('Cancel') }}
                    </Button>
                    <Button
                        type="submit"
                        class="bg-[var(--color-tab)] text-white hover:bg-[var(--color-tab)]/90"
                        :disabled="form.processing || !canSubmit"
                    >
                        {{ submitLabel }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
