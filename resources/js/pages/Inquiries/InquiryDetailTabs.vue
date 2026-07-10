<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
import type { InquiryDetailTab } from './types';
import { Clock3, FileText, MessageSquare, Paperclip } from '@lucide/vue';
import { computed } from 'vue';

type Props = {
    modelValue: InquiryDetailTab;
    attachmentsCount: number;
    commentsCount: number;
    historyCount: number;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:modelValue': [value: InquiryDetailTab];
}>();

const { t } = useTranslations();

const tabs: InquiryDetailTab[] = ['description', 'attachments', 'comments', 'history'];
const activeIndex = computed(() => tabs.indexOf(props.modelValue));

function selectTab(tab: InquiryDetailTab) {
    emit('update:modelValue', tab);
}

function tabClasses(tab: InquiryDetailTab): string {
    return props.modelValue === tab
        ? 'text-white'
        : 'text-muted-foreground hover:text-foreground';
}
</script>

<template>
    <div
        class="relative grid h-10 w-fit max-w-full grid-cols-4 rounded-lg bg-muted p-1 self-start"
        role="tablist"
        aria-label="Inquiry tabs"
    >
        <span
            class="pointer-events-none absolute inset-y-1 left-1 w-[calc(25%-0.25rem)] rounded-md bg-[var(--color-tab)] shadow-sm transition-transform duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
            :style="{ transform: `translateX(${activeIndex * 100}%)` }"
        />

        <button
            type="button"
            role="tab"
            :aria-selected="modelValue === 'description'"
            class="relative z-10 inline-flex min-w-0 items-center justify-center gap-1.5 rounded-md px-3 text-sm font-medium transition-colors duration-200"
            :class="tabClasses('description')"
            @click="selectTab('description')"
        >
            <FileText class="size-4" />
            <span class="hidden truncate sm:inline">{{ t('Inquiry description') }}</span>
        </button>

        <button
            type="button"
            role="tab"
            :aria-selected="modelValue === 'attachments'"
            class="relative z-10 inline-flex min-w-0 items-center justify-center gap-1.5 rounded-md px-3 text-sm font-medium transition-colors duration-200"
            :class="tabClasses('attachments')"
            @click="selectTab('attachments')"
        >
            <Paperclip class="size-4" />
            <span class="hidden truncate sm:inline">{{ t('Attachments') }}</span>
            <span v-if="attachmentsCount > 0" class="rounded-full bg-primary px-1.5 text-[11px] text-primary-foreground">
                {{ attachmentsCount }}
            </span>
        </button>

        <button
            type="button"
            role="tab"
            :aria-selected="modelValue === 'comments'"
            class="relative z-10 inline-flex min-w-0 items-center justify-center gap-1.5 rounded-md px-3 text-sm font-medium transition-colors duration-200"
            :class="tabClasses('comments')"
            @click="selectTab('comments')"
        >
            <MessageSquare class="size-4" />
            <span class="hidden truncate sm:inline">{{ t('Comments') }}</span>
            <span v-if="commentsCount > 0" class="rounded-full bg-primary px-1.5 text-[11px] text-primary-foreground">
                {{ commentsCount }}
            </span>
        </button>

        <button
            type="button"
            role="tab"
            :aria-selected="modelValue === 'history'"
            class="relative z-10 inline-flex min-w-0 items-center justify-center gap-1.5 rounded-md px-3 text-sm font-medium transition-colors duration-200"
            :class="tabClasses('history')"
            @click="selectTab('history')"
        >
            <Clock3 class="size-4" />
            <span class="hidden truncate sm:inline">{{ t('History') }}</span>
            <span v-if="historyCount > 0" class="rounded-full bg-primary px-1.5 text-[11px] text-primary-foreground">
                {{ historyCount }}
            </span>
        </button>
    </div>
</template>
