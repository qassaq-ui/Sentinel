<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
import type { AIAssistantJobDefinition } from './types';

type Props = {
    jobs: AIAssistantJobDefinition[];
    activeJobKey: string | null;
};

defineProps<Props>();

const emit = defineEmits<{
    select: [job: AIAssistantJobDefinition];
}>();

const { t } = useTranslations();
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <button
            v-for="job in jobs"
            :key="job.key"
            type="button"
            class="inline-flex h-8 items-center gap-1.5 rounded-md border px-2.5 text-xs font-semibold transition-colors"
            :class="
                activeJobKey === job.key
                    ? 'border-[var(--color-tab)] bg-blue-50 text-[var(--color-tab)]'
                    : 'border-border bg-background text-muted-foreground hover:border-[var(--color-tab)] hover:text-[var(--color-tab)]'
            "
            :title="t(job.description)"
            @click="emit('select', job)"
        >
            <component :is="job.icon" class="size-3.5" />
            {{ t(job.label) }}
        </button>
    </div>
</template>
