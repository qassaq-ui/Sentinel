<script setup lang="ts">
import { Check, Clock3 } from '@lucide/vue';
import { useTranslations } from '@/composables/useTranslations';
import type {
    InquiryHistoryEvent,
    InquiryHistoryMetadata,
    InquiryHistoryUserSnapshot,
    InquiryStatus,
} from './types';

type Props = {
    events: InquiryHistoryEvent[];
};

defineProps<Props>();

const { t } = useTranslations();

type DetailRow = {
    label: string;
    value: string;
};

function eventTitle(event: InquiryHistoryEvent): string {
    return t(`Inquiry event: ${event.type}`);
}

function snapshotName(
    snapshot: InquiryHistoryMetadata['from'] | InquiryHistoryMetadata['to'],
): string | null {
    if (snapshot === null || snapshot === undefined) {
        return null;
    }

    const role = 'role' in snapshot ? snapshot.role : null;

    return role ? `${snapshot.name} · ${role}` : snapshot.name;
}

function statusLabel(status: string | null | undefined): string | null {
    if (!status) {
        return null;
    }

    const labels: Record<InquiryStatus | string, string> = {
        new: 'New',
        in_progress: 'In progress',
        suspended: 'Suspended',
        completed: 'Completed',
        rejected: 'Rejected',
        withdrawn: 'Withdrawn by applicant',
        draft: 'Draft',
        pending_approval: 'Awaiting approval',
        changes_requested: 'Changes requested',
        approved: 'Approved',
        sent: 'Sent',
    };

    return t(labels[status] ?? status);
}

function pushDetail(
    details: DetailRow[],
    label: string,
    value: string | null | undefined,
) {
    if (value) {
        details.push({ label: t(label), value });
    }
}

function eventDetails(event: InquiryHistoryEvent): DetailRow[] {
    const details: DetailRow[] = [];
    const metadata = event.metadata;

    if (event.type === 'inquiry_created') {
        pushDetail(details, 'Category', metadata.category?.name);
        pushDetail(details, 'Status', statusLabel(metadata.status));
    }

    if (event.type.startsWith('assignee_')) {
        pushDetail(details, 'Previous executor', snapshotName(metadata.from));
        pushDetail(details, 'Assigned executor', snapshotName(metadata.to));
        pushDetail(details, 'Status', statusLabel(metadata.inquiry_status_to));
    }

    if (event.type === 'category_changed') {
        pushDetail(details, 'Previous category', snapshotName(metadata.from));
        pushDetail(details, 'New category', snapshotName(metadata.to));
        pushDetail(
            details,
            'Review deadline',
            metadata.to && 'review_due_date' in metadata.to
                ? metadata.to.review_due_date
                : null,
        );
    }

    if (event.type.startsWith('response_')) {
        pushDetail(details, 'Inquiry outcome', metadata.outcome_name);
        pushDetail(
            details,
            'Approver',
            metadata.reviewer
                ? snapshotName(metadata.reviewer as InquiryHistoryUserSnapshot)
                : null,
        );
        pushDetail(details, 'Comment', metadata.comment);
        pushDetail(
            details,
            'Status',
            statusLabel(metadata.inquiry_status_to ?? metadata.status_to),
        );
    }

    return details;
}
</script>

<template>
    <section
        v-if="events.length === 0"
        class="flex min-h-32 flex-col items-center justify-center border-y border-black/8 bg-[#f7f7f8] px-4 py-8 text-center dark:border-white/10 dark:bg-[#1a1a1c]"
    >
        <Clock3 class="size-5 text-muted-foreground" />
        <p class="mt-2 text-sm font-medium text-muted-foreground">
            {{ t('No history yet') }}
        </p>
    </section>

    <section
        v-else
        class="border-y border-black/8 bg-[#f7f7f8] px-3 py-4 sm:px-4 dark:border-white/10 dark:bg-[#1a1a1c]"
    >
        <div
            v-for="(event, index) in events"
            :key="event.id"
            class="grid grid-cols-[4.5rem_1.25rem_minmax(0,1fr)] gap-x-2 sm:grid-cols-[6rem_1.5rem_minmax(0,1fr)] sm:gap-x-3"
        >
            <time
                :datetime="event.createdAt"
                class="pt-2 pb-4 text-right text-[11px] leading-4 text-muted-foreground sm:text-xs"
            >
                <span class="block font-semibold text-foreground">
                    {{ event.time }}
                </span>
                <span class="block">{{ event.date }}</span>
            </time>

            <div class="relative flex justify-center">
                <span
                    v-if="index > 0"
                    class="absolute top-0 h-4 w-px bg-border"
                />
                <span
                    v-if="index < events.length - 1"
                    class="absolute top-4 bottom-0 w-px bg-border"
                />
                <span
                    class="relative mt-3 inline-flex size-3.5 items-center justify-center rounded-full border-2 border-[var(--color-tab)] bg-background shadow-sm"
                >
                    <Check class="size-2 text-[var(--color-tab)]" />
                </span>
            </div>

            <article class="mb-4 rounded-lg bg-background px-4 py-3 shadow-xs">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <h2 class="text-sm font-semibold text-foreground">
                        {{ eventTitle(event) }}
                    </h2>
                    <span
                        v-if="event.metadata.backfilled"
                        class="text-[10px] font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        {{ t('Imported') }}
                    </span>
                </div>

                <dl
                    v-if="eventDetails(event).length > 0"
                    class="mt-2 grid gap-1.5 text-xs"
                >
                    <div
                        v-for="detail in eventDetails(event)"
                        :key="`${detail.label}-${detail.value}`"
                        class="grid gap-0.5 sm:grid-cols-[9rem_minmax(0,1fr)] sm:gap-3"
                    >
                        <dt class="text-muted-foreground">
                            {{ detail.label }}
                        </dt>
                        <dd class="font-medium text-foreground">
                            {{ detail.value }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-3 border-t border-border/70 pt-2 text-xs">
                    <span class="text-muted-foreground">
                        {{ t('Initiator') }}:
                    </span>
                    <span class="font-medium text-foreground">
                        {{ event.actorName ?? t('System') }}
                    </span>
                    <span v-if="event.actorRole" class="text-muted-foreground">
                        · {{ event.actorRole }}
                    </span>
                </div>
            </article>
        </div>
    </section>
</template>
