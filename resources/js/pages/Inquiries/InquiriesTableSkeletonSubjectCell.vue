<script setup lang="ts">
import { TableCell } from '@/components/ui/table';
import InquiriesTableSkeletonBlock from './InquiriesTableSkeletonBlock.vue';
</script>

<template>
    <TableCell class="min-w-0">
        <div class="flex min-w-0 flex-col gap-2">
            <InquiriesTableSkeletonBlock class="h-4 w-56 max-w-full" />
            <InquiriesTableSkeletonBlock class="h-3 w-48 max-w-full" />
        </div>
    </TableCell>
</template>
