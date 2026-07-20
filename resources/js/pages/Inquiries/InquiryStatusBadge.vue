<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import type { InquiryStatus } from './types';

type Props = {
    status: InquiryStatus;
    appearance?: 'badge' | 'text';
};

withDefaults(defineProps<Props>(), {
    appearance: 'badge',
});

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

function statusColor(status: InquiryStatus) {
    return {
        new: 'text-blue-700 dark:text-blue-300',
        in_progress: 'text-amber-700 dark:text-amber-300',
        suspended: 'text-violet-700 dark:text-violet-300',
        completed: 'text-emerald-700 dark:text-emerald-300',
        rejected: 'text-red-700 dark:text-red-300',
        withdrawn: 'text-slate-700 dark:text-slate-300',
    }[status];
}

function statusSurface(status: InquiryStatus) {
    return {
        new: 'border-blue-500/20 bg-blue-500/10',
        in_progress: 'border-amber-500/20 bg-amber-500/10',
        suspended: 'border-violet-500/20 bg-violet-500/10',
        completed: 'border-emerald-500/20 bg-emerald-500/10',
        rejected: 'border-red-500/20 bg-red-500/10',
        withdrawn: 'border-slate-500/20 bg-slate-500/10',
    }[status];
}
</script>

<template>
    <span
        :class="
            cn(
                appearance === 'text'
                    ? 'text-sm font-semibold'
                    : 'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-semibold',
                statusColor(status),
                appearance === 'badge' && statusSurface(status),
            )
        "
    >
        <span
            v-if="appearance === 'badge'"
            class="size-1.5 rounded-full bg-current"
        />
        {{ statusLabel(status) }}
    </span>
</template>
