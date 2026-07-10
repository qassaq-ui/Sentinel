<script setup lang="ts">
import { Sparkles } from '@lucide/vue';

type Props = {
    label: string;
};

defineProps<Props>();

const emit = defineEmits<{
    open: [];
}>();
</script>

<template>
    <button
        type="button"
        class="fixed right-5 bottom-5 z-50 inline-flex size-20 items-center justify-center rounded-full transition-transform hover:scale-105 focus-visible:ring-2 focus-visible:ring-[var(--color-tab)] focus-visible:outline-none"
        :aria-label="label"
        @click="emit('open')"
    >
        <span class="absolute size-11 rounded-full border-2 border-[var(--color-tab)]/45 motion-safe:animate-ping" />
        <span class="relative inline-flex size-11 items-center justify-center rounded-full bg-[var(--color-tab)] text-white shadow-lg">
            <Sparkles class="size-6" />
        </span>
    </button>
</template>
