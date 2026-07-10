<script setup lang="ts">
import { TableCell } from '@/components/ui/table';
import InquiriesTableSkeletonBlock from './InquiriesTableSkeletonBlock.vue';
</script>

<template>
    <TableCell>
        <InquiriesTableSkeletonBlock class="h-6 w-24 rounded-md" />
    </TableCell>
</template>
