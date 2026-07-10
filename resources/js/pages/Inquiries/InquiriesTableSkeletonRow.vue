<script setup lang="ts">
import { TableCell, TableRow } from '@/components/ui/table';
import InquiriesTableSkeletonBlock from './InquiriesTableSkeletonBlock.vue';
import InquiriesTableSkeletonStatusCell from './InquiriesTableSkeletonStatusCell.vue';
import InquiriesTableSkeletonSubjectCell from './InquiriesTableSkeletonSubjectCell.vue';
</script>

<template>
    <TableRow aria-hidden="true">
        <TableCell>
            <InquiriesTableSkeletonBlock class="size-4 rounded-sm" />
        </TableCell>

        <TableCell>
            <InquiriesTableSkeletonBlock class="h-4 w-36 max-w-full" />
        </TableCell>

        <TableCell>
            <InquiriesTableSkeletonBlock class="size-5 rounded-full" />
        </TableCell>

        <InquiriesTableSkeletonStatusCell />

        <TableCell>
            <InquiriesTableSkeletonBlock class="h-4 w-14 max-w-full" />
        </TableCell>

        <InquiriesTableSkeletonSubjectCell />

        <TableCell>
            <InquiriesTableSkeletonBlock class="h-4 w-32 max-w-full" />
        </TableCell>

        <TableCell>
            <div class="flex justify-end">
                <InquiriesTableSkeletonBlock class="size-5" />
            </div>
        </TableCell>
    </TableRow>
</template>
