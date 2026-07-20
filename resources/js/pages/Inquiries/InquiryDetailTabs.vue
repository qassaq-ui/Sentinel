<script setup lang="ts">
import { Clock3, FileText, MessageSquare, Paperclip, Send } from '@lucide/vue';
import { useTranslations } from '@/composables/useTranslations';
import type { InquiryDetailTab } from './types';

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

function selectTab(tab: InquiryDetailTab) {
    emit('update:modelValue', tab);
}

function tabClasses(tab: InquiryDetailTab): string {
    return props.modelValue === tab
        ? 'bg-white text-[#1d1d1f] shadow-[0_1px_3px_rgba(0,0,0,0.12)] dark:bg-white/15 dark:text-white dark:shadow-none'
        : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white';
}

function counterClasses(): string {
    return 'bg-[#007aff]/10 text-[#007aff] dark:bg-[#0a84ff]/15 dark:text-[#64a9ff]';
}
</script>

<template>
    <div
        class="grid h-10 w-fit max-w-full shrink-0 grid-cols-5 gap-0.5 self-start rounded-[10px] bg-black/[0.055] p-0.5 dark:bg-white/[0.08]"
        role="tablist"
        aria-label="Inquiry tabs"
    >
        <button
            type="button"
            role="tab"
            :aria-selected="modelValue === 'description'"
            class="inline-flex min-w-0 items-center justify-center gap-1.5 rounded-lg px-3 text-[13px] font-medium transition-[color,background-color,box-shadow] duration-150"
            :class="tabClasses('description')"
            @click="selectTab('description')"
        >
            <FileText class="size-4" />
            <span class="hidden truncate sm:inline">{{
                t('Inquiry description')
            }}</span>
        </button>

        <button
            type="button"
            role="tab"
            :aria-selected="modelValue === 'attachments'"
            class="inline-flex min-w-0 items-center justify-center gap-1.5 rounded-lg px-3 text-[13px] font-medium transition-[color,background-color,box-shadow] duration-150"
            :class="tabClasses('attachments')"
            @click="selectTab('attachments')"
        >
            <Paperclip class="size-4" />
            <span class="hidden truncate sm:inline">{{
                t('Attachments')
            }}</span>
            <span
                v-if="attachmentsCount > 0"
                class="inline-flex size-5 items-center justify-center rounded-full text-[11px] font-semibold"
                :class="counterClasses()"
            >
                {{ attachmentsCount }}
            </span>
        </button>

        <button
            type="button"
            role="tab"
            :aria-selected="modelValue === 'comments'"
            class="inline-flex min-w-0 items-center justify-center gap-1.5 rounded-lg px-3 text-[13px] font-medium transition-[color,background-color,box-shadow] duration-150"
            :class="tabClasses('comments')"
            @click="selectTab('comments')"
        >
            <MessageSquare class="size-4" />
            <span class="hidden truncate sm:inline">{{ t('Comments') }}</span>
            <span
                v-if="commentsCount > 0"
                class="inline-flex size-5 items-center justify-center rounded-full text-[11px] font-semibold"
                :class="counterClasses()"
            >
                {{ commentsCount }}
            </span>
        </button>

        <button
            type="button"
            role="tab"
            :aria-selected="modelValue === 'history'"
            class="inline-flex min-w-0 items-center justify-center gap-1.5 rounded-lg px-3 text-[13px] font-medium transition-[color,background-color,box-shadow] duration-150"
            :class="tabClasses('history')"
            @click="selectTab('history')"
        >
            <Clock3 class="size-4" />
            <span class="hidden truncate sm:inline">{{ t('History') }}</span>
            <span
                v-if="historyCount > 0"
                class="inline-flex size-5 items-center justify-center rounded-full text-[11px] font-semibold"
                :class="counterClasses()"
            >
                {{ historyCount }}
            </span>
        </button>

        <button
            type="button"
            role="tab"
            :aria-selected="modelValue === 'response'"
            class="inline-flex min-w-0 items-center justify-center gap-1.5 rounded-lg px-3 text-[13px] font-medium transition-[color,background-color,box-shadow] duration-150"
            :class="tabClasses('response')"
            @click="selectTab('response')"
        >
            <Send class="size-4" />
            <span class="hidden truncate sm:inline">{{ t('Response') }}</span>
        </button>
    </div>
</template>
