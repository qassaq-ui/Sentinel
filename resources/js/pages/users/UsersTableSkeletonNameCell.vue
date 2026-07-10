<script setup lang="ts">
import { TableCell } from '@/components/ui/table';
import UsersTableSkeletonBlock from './UsersTableSkeletonBlock.vue';
</script>

<template>
    <TableCell>
        <div class="flex min-w-0 flex-col gap-2">
            <UsersTableSkeletonBlock class="h-4 w-40 max-w-full" />
            <UsersTableSkeletonBlock class="h-3 w-56 max-w-full" />
        </div>
    </TableCell>
</template>
