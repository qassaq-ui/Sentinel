<script setup lang="ts">
import { Checkbox } from '@/components/ui/checkbox';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useTranslations } from '@/composables/useTranslations';
import { show as showInquiry } from '@/routes/inquiries';
import { InfiniteScroll, Link } from '@inertiajs/vue3';
import { ChevronRight, FileText } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import InquiriesTableSkeletonRows from './InquiriesTableSkeletonRows.vue';
import InquiryStatusBadge from './InquiryStatusBadge.vue';
import InquiryTypeIcon from './InquiryTypeIcon.vue';
import type { InquiryRecord } from './types';

type Props = {
    scrollData: string;
    inquiries: InquiryRecord[];
    emptyLabel: string;
    loading?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

const { t } = useTranslations();
const tableViewport = ref<HTMLElement | null>(null);
const tableViewportHeight = ref(0);
const tableBodyId = computed(() => `inquiries-table-body-${props.scrollData}`);
const loadingSkeletonRows = computed(() => {
    const headerHeight = 49;
    const rowHeight = 61;
    const availableHeight = Math.max(0, tableViewportHeight.value - headerHeight);

    return Math.max(4, Math.ceil(availableHeight / rowHeight));
});
let tableViewportObserver: ResizeObserver | null = null;

function updateTableViewportHeight() {
    tableViewportHeight.value = tableViewport.value?.clientHeight ?? 0;
}

onMounted(() => {
    updateTableViewportHeight();

    if (tableViewport.value === null) {
        return;
    }

    tableViewportObserver = new ResizeObserver(updateTableViewportHeight);
    tableViewportObserver.observe(tableViewport.value);
});

onBeforeUnmount(() => {
    tableViewportObserver?.disconnect();
    tableViewportObserver = null;
});
</script>

<template>
    <div
        class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-lg border border-border bg-background"
    >
        <div ref="tableViewport" class="relative min-h-0 flex-1 overflow-auto">
            <InfiniteScroll
                :data="scrollData"
                :items-element="`#${tableBodyId}`"
                :buffer="160"
                only-next
                preserve-url
                class="block min-h-full"
                #default="{ loadingNext }"
            >
                <Table class="w-full table-fixed">
                    <TableHeader class="sticky top-0 z-10 bg-background">
                        <TableRow>
                            <TableHead class="w-12">
                                <Checkbox aria-label="Select all inquiries" />
                            </TableHead>
                            <TableHead class="w-[16%] whitespace-normal leading-tight">
                                {{ t('Inquiry number') }}
                            </TableHead>
                            <TableHead class="w-[10%] whitespace-normal leading-tight">
                                {{ t('Inquiry type') }}
                            </TableHead>
                            <TableHead class="w-[13%] whitespace-normal leading-tight">
                                {{ t('Inquiry status') }}
                            </TableHead>
                            <TableHead class="w-[13%] whitespace-normal leading-tight">
                                {{ t('Review period') }}
                            </TableHead>
                            <TableHead class="w-[26%] whitespace-normal leading-tight">
                                {{ t('Message subject') }}
                            </TableHead>
                            <TableHead class="w-[17%] whitespace-normal leading-tight">
                                {{ t('Inquiry date') }}
                            </TableHead>
                            <TableHead class="w-10" />
                        </TableRow>
                    </TableHeader>

                    <TableBody :id="tableBodyId">
                        <InquiriesTableSkeletonRows
                            v-if="loading"
                            :loading="true"
                            :count="loadingSkeletonRows"
                            :delay="0"
                        />

                        <TableRow v-else-if="inquiries.length === 0">
                            <TableCell :colspan="8" class="h-56 overflow-hidden text-center">
                                <div
                                    class="flex h-full flex-col items-center justify-center gap-4"
                                >
                                    <FileText
                                        class="size-32 text-muted-foreground opacity-[0.08]"
                                        :stroke-width="1.25"
                                    />
                                    <div class="text-sm font-medium text-muted-foreground">
                                        {{ emptyLabel }}
                                    </div>
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow
                            v-for="inquiry in loading ? [] : inquiries"
                            :key="inquiry.id"
                            class="group"
                        >
                            <TableCell>
                                <div @click.stop @keydown.stop>
                                    <Checkbox
                                        :aria-label="`${t('Select inquiry')} ${inquiry.number}`"
                                    />
                                </div>
                            </TableCell>

                            <TableCell>
                                <span class="font-medium">
                                    № {{ inquiry.number }}
                                </span>
                            </TableCell>

                            <TableCell>
                                <InquiryTypeIcon :type="inquiry.type" />
                            </TableCell>

                            <TableCell>
                                <InquiryStatusBadge :status="inquiry.status" />
                            </TableCell>

                            <TableCell>
                                <span class="font-medium">
                                    {{ t(':count days short', { count: inquiry.daysLeft }) }}
                                </span>
                            </TableCell>

                            <TableCell class="min-w-0">
                                <div class="truncate font-medium">
                                    {{ inquiry.subject }}
                                </div>
                                <div class="mt-1 truncate text-sm text-muted-foreground">
                                    {{ t('Category') }}:
                                    {{ inquiry.categoryName }}
                                </div>
                            </TableCell>

                            <TableCell class="text-muted-foreground">
                                <span>
                                    {{ inquiry.submittedAt }}
                                </span>
                            </TableCell>

                            <TableCell>
                                <div class="flex justify-end">
                                    <Link
                                        :href="showInquiry(inquiry.number)"
                                        class="inline-flex size-9 items-center justify-center rounded-xl text-[var(--color-tab)] transition-colors hover:bg-[var(--color-tab)] hover:text-white focus-visible:bg-[var(--color-tab)] focus-visible:text-white focus-visible:outline-none"
                                        :aria-label="`${t('Open inquiry')} ${inquiry.number}`"
                                    >
                                        <ChevronRight class="size-5" />
                                    </Link>
                                </div>
                            </TableCell>
                        </TableRow>

                        <InquiriesTableSkeletonRows
                            v-if="!loading"
                            :loading="loadingNext"
                        />
                    </TableBody>
                </Table>
            </InfiniteScroll>
        </div>
    </div>
</template>
