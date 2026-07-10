<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { Archive, Inbox, SlidersHorizontal, UserRoundX } from '@lucide/vue';
import { computed } from 'vue';
import type { InquiryTab } from './types';

type Props = {
    activeTab: InquiryTab;
    count: number;
    filtersVisible: boolean;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:activeTab': [value: InquiryTab];
    toggleFilters: [];
}>();

const { t } = useTranslations();

const tabs: Array<{
    value: InquiryTab;
    label: string;
    icon: typeof Inbox;
}> = [
    { value: 'all', label: 'All inquiries', icon: Inbox },
    { value: 'anonymous', label: 'Anonymous inquiries', icon: UserRoundX },
    { value: 'archived', label: 'Archived inquiries', icon: Archive },
];

const activeTabIndex = computed(() =>
    Math.max(
        0,
        tabs.findIndex((tab) => tab.value === props.activeTab),
    ),
);
</script>

<template>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3">
            <div
                class="relative grid h-10 w-full max-w-[34rem] grid-cols-3 rounded-lg bg-muted p-1"
                role="tablist"
                :aria-label="t('Inquiries')"
            >
                <span
                    class="pointer-events-none absolute inset-y-1 left-1 w-[calc(33.333333%-0.25rem)] rounded-md bg-[var(--color-tab)] shadow-sm transition-transform duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
                    :style="{ transform: `translateX(${activeTabIndex * 100}%)` }"
                />

                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    type="button"
                    role="tab"
                    :aria-selected="activeTab === tab.value"
                    class="relative z-10 inline-flex min-w-0 items-center justify-center gap-2 rounded-md px-3 text-sm font-semibold transition-colors duration-200"
                    :class="
                        activeTab === tab.value
                            ? 'text-white'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    @click="emit('update:activeTab', tab.value)"
                >
                    <component :is="tab.icon" class="size-4 shrink-0" />
                    <span class="truncate">{{ t(tab.label) }}</span>
                </button>
            </div>

            <Button
                type="button"
                size="icon"
                class="size-10 transition-colors"
                :class="
                    filtersVisible
                        ? 'bg-[var(--color-tab)] text-white hover:bg-[var(--color-tab)]/90'
                        : 'bg-muted text-muted-foreground hover:bg-muted/80 hover:text-foreground'
                "
                :aria-label="t('Filters')"
                :aria-pressed="filtersVisible"
                @click="emit('toggleFilters')"
            >
                <SlidersHorizontal class="size-4" />
            </Button>
        </div>

        <div class="text-sm font-semibold text-muted-foreground">
            {{ t('Inquiries count: :count', { count }) }}
        </div>
    </div>
</template>
