<script setup lang="ts">
import { Spinner } from '@/components/ui/spinner';
import { onBeforeUnmount, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        loading: boolean;
        delay?: number;
    }>(),
    {
        delay: 1200,
    },
);

const visible = ref(false);
let hideTimer: ReturnType<typeof window.setTimeout> | null = null;

function clearHideTimer() {
    if (hideTimer === null) {
        return;
    }

    window.clearTimeout(hideTimer);
    hideTimer = null;
}

watch(
    () => props.loading,
    (loading) => {
        clearHideTimer();

        if (loading) {
            visible.value = true;

            return;
        }

        if (visible.value) {
            hideTimer = window.setTimeout(() => {
                visible.value = false;
                hideTimer = null;
            }, props.delay);
        }
    },
    { immediate: true },
);

onBeforeUnmount(clearHideTimer);
</script>

<template>
    <div
        v-if="visible"
        class="pointer-events-none absolute inset-x-0 bottom-0 z-20 flex h-14 items-center justify-center border-t border-border bg-background/95 text-muted-foreground shadow-[0_-8px_18px_-16px_rgba(0,0,0,0.45)] backdrop-blur"
    >
        <Spinner class="size-6" />
    </div>
</template>
