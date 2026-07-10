<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

type Props = {
    class?: HTMLAttributes['class'];
};

const props = defineProps<Props>();
</script>

<template>
    <Skeleton
        aria-hidden="true"
        :class="
            cn(
                'rounded-md bg-muted-foreground/15 dark:bg-muted-foreground/20',
                props.class,
            )
        "
    />
</template>
