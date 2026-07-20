<script setup lang="ts">
import { Search, X } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useTranslations } from '@/composables/useTranslations';

type Props = {
    modelValue: string;
};

defineProps<Props>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
    clear: [];
}>();

const { t } = useTranslations();
</script>

<template>
    <div class="relative min-w-0">
        <Search
            class="pointer-events-none absolute top-1/2 left-3.5 z-10 size-4 -translate-y-1/2 text-slate-400"
        />
        <Input
            :model-value="modelValue"
            class="h-10 rounded-[10px] border-0 bg-black/[0.055] pr-10 pl-10 text-sm shadow-none placeholder:text-slate-400 focus-visible:ring-2 focus-visible:ring-[#007aff]/20 dark:bg-white/[0.08]"
            :placeholder="t('Search by number, subject, status, category')"
            @update:model-value="emit('update:modelValue', String($event))"
        />

        <Button
            v-if="modelValue"
            type="button"
            variant="ghost"
            size="icon-sm"
            class="absolute top-1/2 right-1.5 size-7 -translate-y-1/2 rounded-lg text-slate-400 hover:bg-black/5 hover:text-slate-700 dark:hover:bg-white/10 dark:hover:text-white"
            :aria-label="t('Clear')"
            @click="emit('clear')"
        >
            <X class="size-3.5" />
        </Button>
    </div>
</template>
