<script setup lang="ts">
import { TableCell, TableRow } from '@/components/ui/table';
import DictionaryTableSkeletonBlock from './DictionaryTableSkeletonBlock.vue';
</script>

<template>
    <TableRow aria-hidden="true">
        <TableCell>
            <div class="flex min-w-0 flex-col gap-2">
                <DictionaryTableSkeletonBlock class="h-4 w-44 max-w-full" />
                <DictionaryTableSkeletonBlock class="h-3 w-64 max-w-full" />
            </div>
        </TableCell>

        <TableCell>
            <div class="flex min-w-0 flex-col gap-2">
                <DictionaryTableSkeletonBlock class="h-3 w-full max-w-full" />
                <DictionaryTableSkeletonBlock class="h-3 w-5/6 max-w-full" />
            </div>
        </TableCell>

        <TableCell>
            <DictionaryTableSkeletonBlock class="h-5 w-9 rounded-full" />
        </TableCell>

        <TableCell>
            <DictionaryTableSkeletonBlock class="h-4 w-10 max-w-full" />
        </TableCell>

        <TableCell>
            <div class="flex justify-end">
                <DictionaryTableSkeletonBlock class="size-8" />
            </div>
        </TableCell>
    </TableRow>
</template>
