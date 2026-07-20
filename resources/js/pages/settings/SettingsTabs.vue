<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';

export type SettingsTab = 'localization' | 'inquiries' | 'appearance';

defineProps<{
    modelValue: SettingsTab;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: SettingsTab];
}>();

const { t } = useTranslations();

const tabs: Array<{ value: SettingsTab; label: string }> = [
    { value: 'localization', label: 'Localization' },
    { value: 'inquiries', label: 'Inquiries' },
    { value: 'appearance', label: 'Appearance' },
];
</script>

<template>
    <div
        class="grid h-10 w-full max-w-[38rem] grid-cols-3 gap-0.5 rounded-[10px] bg-black/[0.055] p-0.5 dark:bg-white/[0.08]"
        role="tablist"
        :aria-label="t('General settings')"
    >
        <button
            v-for="tab in tabs"
            :key="tab.value"
            type="button"
            role="tab"
            :aria-selected="modelValue === tab.value"
            class="inline-flex min-w-0 items-center justify-center rounded-lg px-3.5 text-[13px] font-medium transition-[color,background-color,box-shadow] duration-150"
            :class="
                modelValue === tab.value
                    ? 'bg-white text-[#1d1d1f] shadow-[0_1px_3px_rgba(0,0,0,0.12)] dark:bg-white/15 dark:text-white dark:shadow-none'
                    : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white'
            "
            @click="emit('update:modelValue', tab.value)"
        >
            <span class="truncate">{{ t(tab.label) }}</span>
        </button>
    </div>
</template>
