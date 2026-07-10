<script setup lang="ts">
import type { TabsTriggerProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { TabsTrigger, useForwardProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<TabsTriggerProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = reactiveOmit(props, 'class');
const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <TabsTrigger
        data-slot="tabs-trigger"
        v-bind="forwardedProps"
        :class="
            cn(
                'data-[state=active]:bg-[var(--color-tab)] data-[state=active]:text-white dark:data-[state=active]:bg-[var(--color-tab)] dark:data-[state=active]:text-white focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:outline-ring text-foreground dark:text-muted-foreground inline-flex h-[calc(100%-1px)] flex-1 items-center justify-center gap-1.5 rounded-md border border-transparent px-3 py-1 text-sm font-medium whitespace-nowrap transform-gpu transition-[color,box-shadow,transform,background-color] duration-200 ease-[cubic-bezier(0.34,1.56,0.64,1)] focus-visible:ring-[3px] focus-visible:outline-1 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:scale-[1.03] data-[state=active]:shadow-sm',
                props.class,
            )
        "
    >
        <slot />
    </TabsTrigger>
</template>
