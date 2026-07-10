<script setup lang="ts">
import { cn } from '@/lib/utils';
import { CircleUserRound, Inbox } from '@lucide/vue';
import type { InquiryType } from './types';

type Props = {
    type: InquiryType;
};

defineProps<Props>();
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex size-8 items-center justify-center rounded-full',
                type === 'anonymous'
                    ? 'text-emerald-700 dark:text-emerald-300'
                    : 'text-[var(--color-tab)]',
            )
        "
    >
        <CircleUserRound v-if="type === 'anonymous'" class="size-4" />
        <Inbox v-else class="size-4" />
    </span>
</template>
