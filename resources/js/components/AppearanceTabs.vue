<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import { useAppearance } from '@/composables/useAppearance';
import { useTranslations } from '@/composables/useTranslations';

const { appearance, updateAppearance } = useAppearance();
const { t } = useTranslations();

const tabs = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
] as const;
</script>

<template>
    <div
        class="inline-flex h-10 gap-0.5 rounded-[10px] bg-black/[0.055] p-0.5 dark:bg-white/[0.08]"
    >
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            @click="updateAppearance(value)"
            :class="[
                'flex h-9 items-center rounded-lg px-3.5 text-[13px] font-medium transition-[color,background-color,box-shadow] duration-150',
                appearance === value
                    ? 'bg-white text-[#1d1d1f] shadow-[0_1px_3px_rgba(0,0,0,0.12)] dark:bg-white/15 dark:text-white dark:shadow-none'
                    : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white',
            ]"
        >
            <component :is="Icon" class="-ml-1 h-4 w-4" />
            <span class="ml-1.5 text-sm">{{ t(label) }}</span>
        </button>
    </div>
</template>
