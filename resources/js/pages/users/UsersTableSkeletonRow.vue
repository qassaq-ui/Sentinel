<script setup lang="ts">
import { TableCell, TableRow } from '@/components/ui/table';
import UsersTableSkeletonActionsCell from './UsersTableSkeletonActionsCell.vue';
import UsersTableSkeletonBlock from './UsersTableSkeletonBlock.vue';
import UsersTableSkeletonNameCell from './UsersTableSkeletonNameCell.vue';
import UsersTableSkeletonStatusCell from './UsersTableSkeletonStatusCell.vue';

type Props = {
    showActions: boolean;
};

defineProps<Props>();
</script>

<template>
    <TableRow aria-hidden="true">
        <UsersTableSkeletonNameCell />

        <TableCell>
            <UsersTableSkeletonBlock class="h-4 w-28 max-w-full" />
        </TableCell>

        <UsersTableSkeletonStatusCell />

        <TableCell>
            <UsersTableSkeletonBlock class="h-4 w-32 max-w-full" />
        </TableCell>

        <TableCell>
            <UsersTableSkeletonBlock class="h-4 w-24 max-w-full" />
        </TableCell>

        <UsersTableSkeletonActionsCell v-if="showActions" />
    </TableRow>
</template>
