<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import type { InquiryStatus } from './types';

type Props = {
    status: InquiryStatus;
};

defineProps<Props>();

const { t } = useTranslations();

function statusLabel(status: InquiryStatus) {
    return {
        new: t('New'),
        in_progress: t('In progress'),
        suspended: t('Suspended'),
        completed: t('Completed'),
        rejected: t('Rejected'),
        withdrawn: t('Withdrawn by applicant'),
    }[status];
}

function statusClass(status: InquiryStatus) {
    return {
        new: 'border-blue-500/20 bg-blue-500/10 text-blue-700 dark:text-blue-300',
        in_progress:
            'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-300',
        suspended:
            'border-violet-500/20 bg-violet-500/10 text-violet-700 dark:text-violet-300',
        completed:
            'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
        rejected:
            'border-red-500/20 bg-red-500/10 text-red-700 dark:text-red-300',
        withdrawn:
            'border-slate-500/20 bg-slate-500/10 text-slate-700 dark:text-slate-300',
    }[status];
}
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-semibold',
                statusClass(status),
            )
        "
    >
        <span class="size-1.5 rounded-full bg-current" />
        {{ statusLabel(status) }}
    </span>
</template>
