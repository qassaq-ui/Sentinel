<script setup lang="ts">
import { TableCell, TableRow } from '@/components/ui/table';
import DictionaryTableSkeletonBlock from './DictionaryTableSkeletonBlock.vue';
</script>

<template>
    <TableRow aria-hidden="true">
        <TableCell>
            <div class="flex min-w-0 flex-col gap-2">
                <DictionaryTableSkeletonBlock class="h-4 w-48 max-w-full" />
                <DictionaryTableSkeletonBlock class="h-3 w-72 max-w-full" />
            </div>
        </TableCell>

        <TableCell>
            <DictionaryTableSkeletonBlock class="h-4 w-20 max-w-full" />
        </TableCell>

        <TableCell>
            <DictionaryTableSkeletonBlock class="h-6 w-20 rounded-md" />
        </TableCell>

        <TableCell>
            <DictionaryTableSkeletonBlock class="h-4 w-10 max-w-full" />
        </TableCell>

        <TableCell>
            <div class="flex justify-end gap-1">
                <DictionaryTableSkeletonBlock class="size-8" />
                <DictionaryTableSkeletonBlock class="size-8" />
            </div>
        </TableCell>
    </TableRow>
</template>
