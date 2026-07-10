<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue';
import UsersTableSkeletonRow from './UsersTableSkeletonRow.vue';

type Props = {
    count?: number;
    delay?: number;
    loading: boolean;
    showActions: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    count: 4,
    delay: 1200,
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
        <UsersTableSkeletonRow
            v-for="row in count"
            :key="row"
            :show-actions="showActions"
        />
    </template>
</template>
