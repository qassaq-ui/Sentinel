<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

type Props = {
    processing: boolean;
    /** tabindex for keyboard navigation. */
    tabindex?: number | string;
    /** Value for the `data-test` attribute. */
    testId?: string;
    /** Button variant. */
    variant?:
        'default' | 'secondary' | 'destructive' | 'outline' | 'ghost' | 'link';
    /** Extra classes for the button. */
    class?: HTMLAttributes['class'];
};

const props = defineProps<Props>();
</script>

<template>
    <Button
        type="submit"
        :variant="variant"
        :tabindex="tabindex"
        :disabled="processing"
        :data-test="testId"
        :class="cn('w-full', props.class)"
    >
        <Spinner v-if="processing" />
        <slot />
    </Button>
</template>
