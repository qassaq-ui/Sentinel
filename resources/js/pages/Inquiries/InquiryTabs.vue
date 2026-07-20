<script setup lang="ts">
import { SlidersHorizontal } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import type { InquiryTab } from './types';

type Props = {
    activeTab: InquiryTab;
    filtersVisible: boolean;
    canApprove: boolean;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:activeTab': [value: InquiryTab];
    toggleFilters: [];
}>();

const { t } = useTranslations();

const tabs = computed<Array<{ value: InquiryTab; label: string }>>(() => [
    { value: 'all', label: 'All inquiries' },
    { value: 'anonymous', label: 'Anonymous inquiries' },
    ...(props.canApprove
        ? [
              {
                  value: 'approval' as InquiryTab,
                  label: 'Awaiting approval',
              },
          ]
        : []),
    { value: 'archived', label: 'Archived inquiries' },
]);
</script>

<template>
    <div class="flex min-w-0 items-center gap-2">
        <div
            class="min-w-0 flex-1 [scrollbar-width:none] overflow-x-auto [&::-webkit-scrollbar]:hidden"
        >
            <div
                class="inline-flex h-10 min-w-max items-center gap-0.5 rounded-[10px] bg-black/[0.055] p-0.5 dark:bg-white/[0.08]"
                role="tablist"
                :aria-label="t('Inquiries')"
            >
                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    type="button"
                    role="tab"
                    :aria-selected="activeTab === tab.value"
                    class="inline-flex h-9 items-center justify-center rounded-lg px-3.5 text-[13px] font-medium whitespace-nowrap transition-[color,background-color,box-shadow] duration-150"
                    :class="
                        activeTab === tab.value
                            ? 'bg-white text-[#1d1d1f] shadow-[0_1px_3px_rgba(0,0,0,0.12)] dark:bg-white/15 dark:text-white dark:shadow-none'
                            : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white'
                    "
                    @click="emit('update:activeTab', tab.value)"
                >
                    {{ t(tab.label) }}
                </button>
            </div>
        </div>

        <Button
            type="button"
            class="h-10 shrink-0 gap-2 rounded-[10px] border px-3 shadow-none transition-colors"
            :class="
                filtersVisible
                    ? 'border-[#007aff] bg-[#007aff] text-white hover:bg-[#006ee6]'
                    : 'border-black/8 bg-white text-slate-600 hover:bg-black/[0.04] hover:text-slate-950 dark:border-white/10 dark:bg-transparent dark:text-slate-300 dark:hover:bg-white/8 dark:hover:text-white'
            "
            :aria-label="t('Filters')"
            :aria-pressed="filtersVisible"
            @click="emit('toggleFilters')"
        >
            <SlidersHorizontal class="size-4" />
            <span class="hidden text-[13px] font-medium sm:inline">
                {{ t('Filters') }}
            </span>
        </Button>
    </div>
</template>
