<script setup lang="ts">
import { InfiniteScroll, Link } from '@inertiajs/vue3';
import { ChevronRight, FileText } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
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
import InquiriesTableSkeletonRows from './InquiriesTableSkeletonRows.vue';
import InquiryStatusBadge from './InquiryStatusBadge.vue';
import InquiryTypeIcon from './InquiryTypeIcon.vue';
import type { InquiryRecord } from './types';

type Props = {
    scrollData: string;
    inquiries: InquiryRecord[];
    emptyLabel: string;
    loading?: boolean;
    openResponse?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    openResponse: false,
});

const { t } = useTranslations();
const tableViewport = ref<HTMLElement | null>(null);
const tableViewportHeight = ref(0);
const tableBodyId = computed(() => `inquiries-table-body-${props.scrollData}`);
const loadingSkeletonRows = computed(() => {
    const headerHeight = 49;
    const rowHeight = 61;
    const availableHeight = Math.max(
        0,
        tableViewportHeight.value - headerHeight,
    );

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
        class="flex min-h-0 flex-1 flex-col overflow-hidden bg-white dark:bg-[#111113]"
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
                <Table class="w-full min-w-[920px] table-fixed">
                    <TableHeader
                        class="sticky top-0 z-10 bg-[#f7f7f8] dark:bg-[#1a1a1c]"
                    >
                        <TableRow
                            class="border-black/8 hover:bg-transparent dark:border-white/10"
                        >
                            <TableHead class="h-12 w-12 pl-4">
                                <Checkbox aria-label="Select all inquiries" />
                            </TableHead>
                            <TableHead
                                class="w-[16%] text-[11px] leading-tight font-semibold tracking-[0.04em] whitespace-normal text-slate-500 uppercase dark:text-slate-400"
                            >
                                {{ t('Inquiry number') }}
                            </TableHead>
                            <TableHead
                                class="w-[10%] text-[11px] leading-tight font-semibold tracking-[0.04em] whitespace-normal text-slate-500 uppercase dark:text-slate-400"
                            >
                                {{ t('Inquiry type') }}
                            </TableHead>
                            <TableHead
                                class="w-[13%] text-[11px] leading-tight font-semibold tracking-[0.04em] whitespace-normal text-slate-500 uppercase dark:text-slate-400"
                            >
                                {{ t('Inquiry status') }}
                            </TableHead>
                            <TableHead
                                class="w-[13%] text-[11px] leading-tight font-semibold tracking-[0.04em] whitespace-normal text-slate-500 uppercase dark:text-slate-400"
                            >
                                {{ t('Review period') }}
                            </TableHead>
                            <TableHead
                                class="w-[26%] text-[11px] leading-tight font-semibold tracking-[0.04em] whitespace-normal text-slate-500 uppercase dark:text-slate-400"
                            >
                                {{ t('Message subject') }}
                            </TableHead>
                            <TableHead
                                class="w-[17%] text-[11px] leading-tight font-semibold tracking-[0.04em] whitespace-normal text-slate-500 uppercase dark:text-slate-400"
                            >
                                {{ t('Inquiry date') }}
                            </TableHead>
                            <TableHead class="w-12" />
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
                            <TableCell
                                :colspan="8"
                                class="h-56 overflow-hidden text-center"
                            >
                                <div
                                    class="flex h-full flex-col items-center justify-center gap-4"
                                >
                                    <div
                                        class="flex size-14 items-center justify-center rounded-2xl bg-black/[0.035] text-slate-400 dark:bg-white/[0.06] dark:text-slate-500"
                                    >
                                        <FileText
                                            class="size-6"
                                            :stroke-width="1.6"
                                        />
                                    </div>
                                    <div
                                        class="text-sm font-medium text-slate-500 dark:text-slate-400"
                                    >
                                        {{ emptyLabel }}
                                    </div>
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow
                            v-for="inquiry in loading ? [] : inquiries"
                            :key="inquiry.id"
                            class="group h-16 border-black/7 transition-colors hover:bg-[#f7f7f8] dark:border-white/8 dark:hover:bg-white/[0.045]"
                        >
                            <TableCell class="pl-4">
                                <div @click.stop @keydown.stop>
                                    <Checkbox
                                        :aria-label="`${t('Select inquiry')} ${inquiry.number}`"
                                    />
                                </div>
                            </TableCell>

                            <TableCell>
                                <span
                                    class="font-semibold text-slate-950 dark:text-white"
                                >
                                    № {{ inquiry.number }}
                                </span>
                            </TableCell>

                            <TableCell>
                                <InquiryTypeIcon :type="inquiry.type" />
                            </TableCell>

                            <TableCell>
                                <InquiryStatusBadge
                                    :status="inquiry.status"
                                    appearance="text"
                                />
                            </TableCell>

                            <TableCell>
                                <span
                                    class="font-medium text-slate-700 dark:text-slate-300"
                                >
                                    {{
                                        t(':count days short', {
                                            count: inquiry.daysLeft,
                                        })
                                    }}
                                </span>
                            </TableCell>

                            <TableCell class="min-w-0">
                                <div
                                    class="truncate font-semibold text-slate-900 dark:text-slate-100"
                                >
                                    {{ inquiry.subject }}
                                </div>
                                <div
                                    class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400"
                                >
                                    {{ t('Category') }}:
                                    {{ inquiry.categoryName }}
                                </div>
                            </TableCell>

                            <TableCell
                                class="text-sm text-slate-500 dark:text-slate-400"
                            >
                                <span>
                                    {{ inquiry.submittedAt }}
                                </span>
                            </TableCell>

                            <TableCell>
                                <div class="flex justify-end">
                                    <Link
                                        :href="
                                            showInquiry(inquiry.number, {
                                                query: openResponse
                                                    ? { tab: 'response' }
                                                    : {},
                                            })
                                        "
                                        class="inline-flex size-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-black/5 hover:text-[#007aff] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#007aff] dark:hover:bg-white/8"
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
