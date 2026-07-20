<script setup lang="ts">
import type { TabsListProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { TabsList, useForwardProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<TabsListProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = reactiveOmit(props, 'class');
const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <TabsList
        data-slot="tabs-list"
        v-bind="forwardedProps"
        :class="
            cn(
                'inline-flex h-10 w-fit items-center justify-center gap-0.5 rounded-[10px] bg-black/[0.055] p-0.5 text-slate-500 transition-colors dark:bg-white/[0.08] dark:text-slate-400',
                props.class,
            )
        "
    >
        <slot />
    </TabsList>
</template>
