<script setup lang="ts">
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { InquiryFilterOption } from './types';

type Props = {
    label: string;
    modelValue: string;
    options: InquiryFilterOption[];
};

defineProps<Props>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();
</script>

<template>
    <label class="grid min-w-0 gap-2">
        <span
            class="px-0.5 text-[11px] font-semibold tracking-[0.04em] text-slate-500 uppercase dark:text-slate-400"
        >
            {{ label }}
        </span>
        <Select
            :model-value="modelValue"
            @update:model-value="emit('update:modelValue', String($event))"
        >
            <SelectTrigger
                class="h-10 w-full rounded-[10px] border-black/10 bg-white px-3 shadow-none focus:ring-2 focus:ring-[#007aff]/15 dark:border-white/10 dark:bg-white/[0.07]"
            >
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem
                    v-for="option in options"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </SelectItem>
            </SelectContent>
        </Select>
    </label>
</template>
