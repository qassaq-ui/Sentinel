<script setup lang="ts">
import type { Component } from 'vue';

type Props = {
    icon: Component;
    label: string;
    value: string;
};

defineProps<Props>();
</script>

<template>
    <div class="min-w-0">
        <div class="flex items-center gap-1.5 text-xs font-semibold text-muted-foreground">
            <component :is="icon" class="size-3.5" />
            <span>{{ label }}</span>
        </div>
        <div class="mt-1 truncate text-sm font-semibold text-foreground">
            {{ value }}
        </div>
    </div>
</template>
