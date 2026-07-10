<script setup lang="ts">
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
import { create as inquiriesCreate } from '@/routes/inquiries';
import { Head, Link } from '@inertiajs/vue3';
import { FileText } from '@lucide/vue';
import { computed, ref } from 'vue';

const { t } = useTranslations();

type InquiryTab = 'all' | 'anonymous' | 'archived';

const activeTab = ref<InquiryTab>('all');

const emptyMessage = computed(() => {
    return {
        all: t('No inquiries found'),
        anonymous: t('No anonymous inquiries found'),
        archived: t('No archived inquiries found'),
    }[activeTab.value];
});

const tabIndicatorClass = computed(() => {
    return {
        all: 'translate-x-0',
        anonymous: 'translate-x-full',
        archived: 'translate-x-[200%]',
    }[activeTab.value];
});
</script>

<template>
    <div class="flex min-h-0 flex-1 flex-col">
        <Head :title="t('Inquiries')" />

        <div class="flex min-h-0 flex-1 flex-col gap-4 overflow-hidden p-4">
            <div class="flex shrink-0 flex-col gap-4">
                <div class="flex items-center justify-between gap-4">
                    <h1 class="text-lg font-semibold">{{ t('Inquiries') }}</h1>
                    <Button
                        as-child
                        variant="link"
                        class="h-auto px-0 py-0 font-semibold text-[var(--color-tab)] hover:text-[var(--color-tab)]"
                    >
                        <Link :href="inquiriesCreate()">
                            {{ t('+ New inquiry') }}
                        </Link>
                    </Button>
                </div>

                <div
                    class="relative grid h-10 w-full max-w-md grid-cols-3 rounded-lg bg-muted p-1"
                    role="tablist"
                    aria-label="Inquiries tabs"
                >
                    <span
                        class="pointer-events-none absolute inset-y-1 left-1 w-[calc((100%_-_0.5rem)/3)] rounded-md bg-[var(--color-tab)] shadow-sm transition-transform duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
                        :class="tabIndicatorClass"
                    />

                    <button
                        type="button"
                        role="tab"
                        :aria-selected="activeTab === 'all'"
                        class="relative z-10 inline-flex items-center justify-center rounded-md px-3 text-sm font-medium transition-colors duration-200"
                        :class="
                            activeTab === 'all'
                                ? 'text-white'
                                : 'text-muted-foreground'
                        "
                        @click="activeTab = 'all'"
                    >
                        {{ t('All') }}
                    </button>

                    <button
                        type="button"
                        role="tab"
                        :aria-selected="activeTab === 'anonymous'"
                        class="relative z-10 inline-flex items-center justify-center rounded-md px-3 text-sm font-medium transition-colors duration-200"
                        :class="
                            activeTab === 'anonymous'
                                ? 'text-white'
                                : 'text-muted-foreground'
                        "
                        @click="activeTab = 'anonymous'"
                    >
                        {{ t('Anonymous') }}
                    </button>

                    <button
                        type="button"
                        role="tab"
                        :aria-selected="activeTab === 'archived'"
                        class="relative z-10 inline-flex items-center justify-center rounded-md px-3 text-sm font-medium transition-colors duration-200"
                        :class="
                            activeTab === 'archived'
                                ? 'text-white'
                                : 'text-muted-foreground'
                        "
                        @click="activeTab = 'archived'"
                    >
                        {{ t('Archived') }}
                    </button>
                </div>
            </div>

            <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <Transition name="rubber-tab" mode="out-in">
                    <div :key="activeTab" class="min-h-0 flex-1 overflow-hidden">
                        <div class="flex h-full min-h-0 flex-1 pr-1">
                            <div
                                class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-lg border border-border bg-background"
                            >
                                <div class="relative min-h-0 flex-1 overflow-auto">
                                    <Table class="w-full table-fixed">
                                        <TableHeader class="sticky top-0 z-10 bg-background">
                                            <TableRow>
                                                <TableHead class="w-[42%]">
                                                    {{ t('Subject') }}
                                                </TableHead>
                                                <TableHead class="w-[18%]">
                                                    {{ t('Status') }}
                                                </TableHead>
                                                <TableHead class="w-[18%]">
                                                    {{ t('Created at') }}
                                                </TableHead>
                                                <TableHead class="w-[22%] text-right">
                                                    {{ t('Actions') }}
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>

                                        <TableBody>
                                            <TableRow>
                                                <TableCell
                                                    :colspan="4"
                                                    class="h-56 overflow-hidden text-center"
                                                >
                                                    <div
                                                        class="flex h-full flex-col items-center justify-center gap-4"
                                                    >
                                                        <FileText
                                                            class="size-32 text-muted-foreground opacity-[0.08]"
                                                            :stroke-width="1.25"
                                                        />
                                                        <div
                                                            class="text-sm font-medium text-muted-foreground"
                                                        >
                                                            {{ emptyMessage }}
                                                        </div>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </div>
</template>

<style scoped>
.rubber-tab-enter-active,
.rubber-tab-leave-active {
    transition:
        opacity 180ms ease,
        transform 280ms cubic-bezier(0.34, 1.56, 0.64, 1);
}

.rubber-tab-enter-from {
    opacity: 0;
    transform: translateY(6px) scale(0.985);
}

.rubber-tab-leave-to {
    opacity: 0;
    transform: translateY(-4px) scale(0.99);
}
</style>
