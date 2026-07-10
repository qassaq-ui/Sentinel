<script setup lang="ts">
import DictionariesController from '@/actions/App/Http/Controllers/DictionariesController';
import { Badge } from '@/components/ui/badge';
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
import type { Auth } from '@/types/auth';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import DictionaryTableSkeletonRows from './dictionaries/DictionaryTableSkeletonRows.vue';
import InquiryCategoryDialog from './dictionaries/InquiryCategoryDialog.vue';
import InquiryOutcomeDialog from './dictionaries/InquiryOutcomeDialog.vue';
import type {
    InquiryCategory,
    InquiryCategoryFormData,
    InquiryOutcome,
    InquiryOutcomeFormData,
} from './dictionaries/types';

type Props = {
    categories: InquiryCategory[];
    outcomes: InquiryOutcome[];
};

const props = defineProps<Props>();

const { t } = useTranslations();
const page = usePage<{ auth: Auth }>();
const can = computed(() => page.props.auth.can);
const isDialogOpen = ref(false);
const isOutcomeDialogOpen = ref(false);
const editingCategory = ref<InquiryCategory | null>(null);
const editingOutcome = ref<InquiryOutcome | null>(null);
const activeTab = ref<'category' | 'outcome'>('category');
const outcomeToggleProcessingId = ref<number | null>(null);
const isTabLoading = ref(true);
const categoryTableViewport = ref<HTMLElement | null>(null);
const outcomeTableViewport = ref<HTMLElement | null>(null);
const tableViewportHeight = ref(0);
const loadingSkeletonRows = computed(() => {
    const headerHeight = 41;
    const rowHeight = 57;
    const availableHeight = Math.max(0, tableViewportHeight.value - headerHeight);

    return Math.max(4, Math.ceil(availableHeight / rowHeight));
});
let tabLoadingTimer: ReturnType<typeof window.setTimeout> | null = null;
let tableViewportObserver: ResizeObserver | null = null;

const form = useForm<InquiryCategoryFormData>({
    fallback_name: '',
    fallback_description: '',
    review_days: 15,
    is_active: true,
    sort_order: 0,
});

const outcomeForm = useForm<InquiryOutcomeFormData>({
    fallback_name: '',
    fallback_description: '',
    ai_instruction: '',
    is_active: true,
    sort_order: 0,
});

const dialogMode = computed(() => (editingCategory.value ? 'edit' : 'create'));

function activeTableViewport() {
    return activeTab.value === 'category'
        ? categoryTableViewport.value
        : outcomeTableViewport.value;
}

function updateTableViewportHeight() {
    tableViewportHeight.value = activeTableViewport()?.clientHeight ?? 0;
}

function disconnectTableViewportObserver() {
    tableViewportObserver?.disconnect();
    tableViewportObserver = null;
}

function observeActiveTableViewport() {
    disconnectTableViewportObserver();
    updateTableViewportHeight();

    const viewport = activeTableViewport();

    if (viewport === null) {
        return;
    }

    tableViewportObserver = new ResizeObserver(updateTableViewportHeight);
    tableViewportObserver.observe(viewport);
}

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

function openCreateDialog() {
    if (!can.value.dictionariesCreate) {
        return;
    }

    editingCategory.value = null;
    form.defaults({
        fallback_name: '',
        fallback_description: '',
        review_days: 15,
        is_active: true,
        sort_order: nextSortOrder(),
    });
    form.reset();
    form.clearErrors();
    isDialogOpen.value = true;
}

function openEditDialog(category: InquiryCategory) {
    if (!can.value.dictionariesUpdate) {
        return;
    }

    editingCategory.value = category;
    form.defaults({
        fallback_name: category.fallback_name,
        fallback_description: category.fallback_description ?? '',
        review_days: category.review_days,
        is_active: category.is_active,
        sort_order: category.sort_order,
    });
    form.reset();
    form.clearErrors();
    isDialogOpen.value = true;
}

function saveCategory() {
    if (!editingCategory.value && !can.value.dictionariesCreate) {
        return;
    }

    if (editingCategory.value && !can.value.dictionariesUpdate) {
        return;
    }

    if (editingCategory.value) {
        form.patch(
            DictionariesController.update(editingCategory.value.id).url,
            {
                preserveScroll: true,
                onSuccess: closeDialog,
            },
        );

        return;
    }

    form.post(DictionariesController.store().url, {
        preserveScroll: true,
        onSuccess: closeDialog,
    });
}

function openEditOutcomeDialog(outcome: InquiryOutcome) {
    if (!can.value.dictionariesUpdate) {
        return;
    }

    editingOutcome.value = outcome;
    outcomeForm.defaults({
        fallback_name: outcome.fallback_name,
        fallback_description: outcome.fallback_description ?? '',
        ai_instruction: outcome.ai_instruction,
        is_active: outcome.is_active,
        sort_order: outcome.sort_order,
    });
    outcomeForm.reset();
    outcomeForm.clearErrors();
    isOutcomeDialogOpen.value = true;
}

function saveOutcome() {
    if (!editingOutcome.value) {
        return;
    }

    if (!can.value.dictionariesUpdate) {
        return;
    }

    outcomeForm.patch(
        DictionariesController.updateOutcome(editingOutcome.value.id).url,
        {
            preserveScroll: true,
            onSuccess: closeOutcomeDialog,
        },
    );
}

function toggleOutcomeActive(outcome: InquiryOutcome) {
    if (!can.value.dictionariesUpdate) {
        return;
    }

    if (outcomeToggleProcessingId.value !== null) {
        return;
    }

    outcomeToggleProcessingId.value = outcome.id;

    router.patch(
        DictionariesController.updateOutcome(outcome.id).url,
        {
            fallback_name: outcome.fallback_name,
            fallback_description: outcome.fallback_description ?? '',
            ai_instruction: outcome.ai_instruction,
            is_active: !outcome.is_active,
            sort_order: outcome.sort_order,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                outcomeToggleProcessingId.value = null;
            },
        },
    );
}

function outcomeSwitchLabel(outcome: InquiryOutcome) {
    return outcome.is_active ? t('Deactivate') : t('Activate');
}

function applyFormUpdate(value: InquiryCategoryFormData) {
    form.fallback_name = value.fallback_name;
    form.fallback_description = value.fallback_description;
    form.review_days = value.review_days;
    form.is_active = value.is_active;
    form.sort_order = value.sort_order;
}

function applyOutcomeFormUpdate(value: InquiryOutcomeFormData) {
    outcomeForm.fallback_name = value.fallback_name;
    outcomeForm.fallback_description = value.fallback_description;
    outcomeForm.ai_instruction = value.ai_instruction;
    outcomeForm.is_active = value.is_active;
    outcomeForm.sort_order = value.sort_order;
}

function deleteCategory(category: InquiryCategory) {
    if (!can.value.dictionariesDelete) {
        return;
    }

    if (!window.confirm(t('Delete inquiry category?'))) {
        return;
    }

    router.delete(DictionariesController.destroy(category.id).url, {
        preserveScroll: true,
    });
}

function closeDialog() {
    form.clearErrors();
    isDialogOpen.value = false;
    editingCategory.value = null;
}

function closeOutcomeDialog() {
    outcomeForm.clearErrors();
    isOutcomeDialogOpen.value = false;
    editingOutcome.value = null;
}

function nextSortOrder() {
    return (
        Math.max(
            0,
            ...props.categories.map((category) => category.sort_order),
        ) + 10
    );
}

watch(
    activeTab,
    () => {
        showTabSkeleton();
        void nextTick(observeActiveTableViewport);
    },
    { immediate: true },
);

onMounted(() => {
    void nextTick(observeActiveTableViewport);
});

onBeforeUnmount(() => {
    clearTabLoadingTimer();
    disconnectTableViewportObserver();
});
</script>

<template>
    <div class="flex min-h-0 flex-1 flex-col">
        <Head :title="t('Dictionaries')" />

        <InquiryCategoryDialog
            v-model:open="isDialogOpen"
            :form="form"
            :mode="dialogMode"
            :category="editingCategory"
            :errors="form.errors"
            :processing="form.processing"
            @update:form="applyFormUpdate"
            @submit="saveCategory"
        />

        <InquiryOutcomeDialog
            v-model:open="isOutcomeDialogOpen"
            :form="outcomeForm"
            :outcome="editingOutcome"
            :errors="outcomeForm.errors"
            :processing="outcomeForm.processing"
            @update:form="applyOutcomeFormUpdate"
            @submit="saveOutcome"
        />

        <div class="flex min-h-0 flex-1 flex-col gap-4 overflow-hidden p-4">
            <div class="flex shrink-0 items-center justify-between gap-4">
                <h1 class="text-lg font-semibold">{{ t('Dictionaries') }}</h1>
                <Button
                    v-if="activeTab === 'category' && can.dictionariesCreate"
                    variant="link"
                    class="h-auto px-0 py-0 font-semibold text-[var(--color-tab)] hover:text-[var(--color-tab)]"
                    @click="openCreateDialog"
                >
                    {{ t('+ Add category') }}
                </Button>
            </div>

            <div
                class="relative grid h-10 w-full max-w-md grid-cols-2 rounded-lg bg-muted p-1"
                role="tablist"
                aria-label="Dictionaries tabs"
            >
                <span
                    class="pointer-events-none absolute inset-y-1 left-1 w-[calc(50%_-_0.25rem)] rounded-md bg-[var(--color-tab)] shadow-sm transition-transform duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
                    :class="{
                        'translate-x-full': activeTab === 'outcome',
                    }"
                />

                <button
                    type="button"
                    role="tab"
                    :aria-selected="activeTab === 'category'"
                    class="relative z-10 inline-flex items-center justify-center rounded-md px-3 text-sm font-medium transition-colors duration-200"
                    :class="
                        activeTab === 'category'
                            ? 'text-white'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    @click="activeTab = 'category'"
                >
                    {{ t('Category') }}
                </button>

                <button
                    type="button"
                    role="tab"
                    :aria-selected="activeTab === 'outcome'"
                    class="relative z-10 inline-flex items-center justify-center rounded-md px-3 text-sm font-medium transition-colors duration-200"
                    :class="
                        activeTab === 'outcome'
                            ? 'text-white'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    @click="activeTab = 'outcome'"
                >
                    {{ t('Review outcomes') }}
                </button>
            </div>

            <div
                v-if="activeTab === 'category'"
                class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-lg border border-border bg-background"
            >
                <div class="border-b border-border px-4 py-3">
                    <div class="text-sm font-medium">
                        {{ t('Inquiry categories') }}
                    </div>
                </div>

                <div
                    ref="categoryTableViewport"
                    class="min-h-0 flex-1 overflow-auto"
                >
                    <Table class="w-full table-fixed">
                        <TableHeader class="sticky top-0 z-10 bg-background">
                            <TableRow>
                                <TableHead class="w-[34%]">
                                    {{ t('Name') }}
                                </TableHead>
                                <TableHead class="w-[14%]">
                                    {{ t('Review period') }}
                                </TableHead>
                                <TableHead class="w-[12%]">
                                    {{ t('Status') }}
                                </TableHead>
                                <TableHead class="w-[8%]">
                                    {{ t('Sort order') }}
                                </TableHead>
                                <TableHead class="w-[8%] text-right">
                                    {{ t('Actions') }}
                                </TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <DictionaryTableSkeletonRows
                                v-if="isTabLoading"
                                :loading="true"
                                :count="loadingSkeletonRows"
                                :delay="0"
                                type="category"
                            />

                            <TableRow v-else-if="categories.length === 0">
                                <TableCell
                                    :colspan="5"
                                    class="h-56 text-center text-sm text-muted-foreground"
                                >
                                    {{ t('No categories found') }}
                                </TableCell>
                            </TableRow>

                            <TableRow
                                v-for="category in isTabLoading ? [] : categories"
                                :key="category.uuid"
                            >
                                <TableCell class="min-w-0">
                                    <div class="truncate text-sm font-medium">
                                        {{ category.localized_name }}
                                    </div>
                                    <div
                                        class="mt-1 truncate text-xs text-muted-foreground"
                                    >
                                        {{ category.localized_description }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    {{
                                        t(':count days', {
                                            count: category.review_days,
                                        })
                                    }}
                                </TableCell>

                                <TableCell>
                                    <Badge
                                        variant="outline"
                                        class="rounded-md px-2 py-0.5"
                                        :class="
                                            category.is_active
                                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300'
                                                : 'text-muted-foreground'
                                        "
                                    >
                                        {{
                                            category.is_active
                                                ? t('Active')
                                                : t('Inactive')
                                        }}
                                    </Badge>
                                </TableCell>

                                <TableCell>
                                    {{ category.sort_order }}
                                </TableCell>

                                <TableCell class="text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <Button
                                            v-if="can.dictionariesUpdate"
                                            type="button"
                                            variant="ghost"
                                            size="icon-sm"
                                            :aria-label="
                                                t('Edit inquiry category')
                                            "
                                            @click="openEditDialog(category)"
                                        >
                                            <Pencil class="size-4" />
                                        </Button>
                                        <Button
                                            v-if="can.dictionariesDelete"
                                            type="button"
                                            variant="ghost"
                                            size="icon-sm"
                                            class="text-destructive hover:text-destructive"
                                            :aria-label="
                                                t('Delete inquiry category')
                                            "
                                            @click="deleteCategory(category)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

            <div
                v-if="activeTab === 'outcome'"
                class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-lg border border-border bg-background"
            >
                <div class="border-b border-border px-4 py-3">
                    <div class="text-sm font-medium">
                        {{ t('Review outcomes') }}
                    </div>
                </div>

                <div
                    ref="outcomeTableViewport"
                    class="min-h-0 flex-1 overflow-auto"
                >
                    <Table class="w-full table-fixed">
                        <TableHeader class="sticky top-0 z-10 bg-background">
                            <TableRow>
                                <TableHead class="w-[38%]">
                                    {{ t('Name') }}
                                </TableHead>
                                <TableHead class="w-[34%]">
                                    {{ t('AI instruction') }}
                                </TableHead>
                                <TableHead class="w-[12%]">
                                    {{ t('Status') }}
                                </TableHead>
                                <TableHead class="w-[8%]">
                                    {{ t('Sort order') }}
                                </TableHead>
                                <TableHead class="w-[6%] text-right">
                                    {{ t('Actions') }}
                                </TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <DictionaryTableSkeletonRows
                                v-if="isTabLoading"
                                :loading="true"
                                :count="loadingSkeletonRows"
                                :delay="0"
                                type="outcome"
                            />

                            <TableRow v-else-if="outcomes.length === 0">
                                <TableCell
                                    :colspan="5"
                                    class="h-56 text-center text-sm text-muted-foreground"
                                >
                                    {{ t('No review outcomes found') }}
                                </TableCell>
                            </TableRow>

                            <TableRow
                                v-for="outcome in isTabLoading ? [] : outcomes"
                                :key="outcome.code"
                            >
                                <TableCell class="min-w-0">
                                    <div class="truncate text-sm font-medium">
                                        {{ outcome.localized_name }}
                                    </div>
                                    <div
                                        class="mt-1 truncate text-xs text-muted-foreground"
                                    >
                                        {{ outcome.localized_description }}
                                    </div>
                                </TableCell>

                                <TableCell class="min-w-0">
                                    <div
                                        class="line-clamp-2 text-xs leading-5 text-muted-foreground"
                                    >
                                        {{ outcome.ai_instruction }}
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <button
                                        type="button"
                                        role="switch"
                                        :aria-checked="outcome.is_active"
                                        :aria-label="outcomeSwitchLabel(outcome)"
                                        :disabled="
                                            !can.dictionariesUpdate ||
                                            outcomeToggleProcessingId === outcome.id
                                        "
                                        class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full border border-border px-0.5 transition-colors disabled:cursor-not-allowed disabled:opacity-60"
                                        :class="
                                            outcome.is_active
                                                ? 'border-[var(--color-tab)] bg-[var(--color-tab)]'
                                                : 'bg-muted'
                                        "
                                        @click="toggleOutcomeActive(outcome)"
                                    >
                                        <span
                                            class="size-4 rounded-full bg-background shadow-sm transition-transform"
                                            :class="
                                                outcome.is_active
                                                    ? 'translate-x-4'
                                                    : 'translate-x-0'
                                            "
                                        />
                                    </button>
                                </TableCell>

                                <TableCell>
                                    {{ outcome.sort_order }}
                                </TableCell>

                                <TableCell class="text-right">
                                    <Button
                                        v-if="can.dictionariesUpdate"
                                        type="button"
                                        variant="ghost"
                                        size="icon-sm"
                                        :aria-label="t('Edit review outcome')"
                                        @click="openEditOutcomeDialog(outcome)"
                                    >
                                        <Pencil class="size-4" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>
        </div>
    </div>
</template>
