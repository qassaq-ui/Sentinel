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
        <span class="text-xs font-bold tracking-wide text-muted-foreground uppercase">
            {{ label }}
        </span>
        <Select
            :model-value="modelValue"
            @update:model-value="emit('update:modelValue', String($event))"
        >
            <SelectTrigger class="h-10 w-full rounded-lg bg-background">
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
