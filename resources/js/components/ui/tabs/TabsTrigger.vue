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
                'inline-flex h-9 flex-1 items-center justify-center gap-1.5 rounded-lg border border-transparent px-3.5 py-1 text-[13px] font-medium whitespace-nowrap text-slate-500 transition-[color,background-color,box-shadow] duration-150 hover:text-slate-800 focus-visible:ring-2 focus-visible:ring-[#007aff]/20 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-white data-[state=active]:text-[#1d1d1f] data-[state=active]:shadow-[0_1px_3px_rgba(0,0,0,0.12)] dark:text-slate-400 dark:hover:text-white dark:data-[state=active]:bg-white/15 dark:data-[state=active]:text-white dark:data-[state=active]:shadow-none',
                props.class,
            )
        "
    >
        <slot />
    </TabsTrigger>
</template>
