<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

type Props = {
    label: string;
    /** The `id` of the input element this label is for. */
    forId: string;
    /** Validation error message to display below the field. */
    error?: string;
    /** Extra classes for the field wrapper. */
    class?: HTMLAttributes['class'];
    /** Extra classes for the InputError element. */
    errorClass?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <div :class="cn('grid gap-2', $props.class)">
        <Label :for="forId">{{ label }}</Label>
        <slot />
        <InputError :class="errorClass" :message="error" />
    </div>
</template>
