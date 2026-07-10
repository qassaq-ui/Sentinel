<script setup lang="ts">
import { TableCell } from '@/components/ui/table';
import UsersTableSkeletonBlock from './UsersTableSkeletonBlock.vue';
</script>

<template>
    <TableCell>
        <div class="flex justify-end gap-1">
            <UsersTableSkeletonBlock class="size-8" />
            <UsersTableSkeletonBlock class="size-8" />
            <UsersTableSkeletonBlock class="size-8" />
        </div>
    </TableCell>
</template>
