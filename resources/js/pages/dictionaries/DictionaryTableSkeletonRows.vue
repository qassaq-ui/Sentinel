<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue';
import InquiryCategorySkeletonRow from './InquiryCategorySkeletonRow.vue';
import InquiryOutcomeSkeletonRow from './InquiryOutcomeSkeletonRow.vue';

type Props = {
    count?: number;
    delay?: number;
    loading: boolean;
    type: 'category' | 'outcome';
};

const props = withDefaults(defineProps<Props>(), {
    count: 4,
    delay: 900,
});

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
    <template v-if="visible">
        <component
            :is="type === 'category' ? InquiryCategorySkeletonRow : InquiryOutcomeSkeletonRow"
            v-for="row in count"
            :key="row"
        />
    </template>
</template>
