<script setup lang="ts">
import { TableCell } from '@/components/ui/table';
import UsersTableSkeletonBlock from './UsersTableSkeletonBlock.vue';
</script>

<template>
    <TableCell>
        <UsersTableSkeletonBlock class="h-6 w-20 rounded-md" />
    </TableCell>
</template>
