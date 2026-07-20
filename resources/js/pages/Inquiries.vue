<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useTranslations } from '@/composables/useTranslations';
import type { Auth } from '@/types/auth';
import InquiriesTable from './Inquiries/InquiriesTable.vue';
import InquiryFilters from './Inquiries/InquiryFilters.vue';
import InquirySearchBar from './Inquiries/InquirySearchBar.vue';
import InquiryTabs from './Inquiries/InquiryTabs.vue';
import type {
    InquiryCategory,
    InquiryRecord,
    InquiryTab,
    ScrollInquiries,
} from './Inquiries/types';

type Props = {
    categories: InquiryCategory[];
    allInquiries: ScrollInquiries;
    anonymousInquiries: ScrollInquiries;
    archivedInquiries: ScrollInquiries;
    approvalInquiries: ScrollInquiries;
};

const props = defineProps<Props>();

const { t } = useTranslations();
const page = usePage<{ auth: Auth }>();
const canApprove = computed(() => page.props.auth.can.inquiriesApprove);

const activeTab = ref<InquiryTab>('all');
const search = ref('');
const ageFilter = ref('all');
const statusFilter = ref('all');
const categoryFilter = ref('all');
const submittedDateFilter = ref('');
const sortFilter = ref('newest');
const filtersVisible = ref(false);
const isTabLoading = ref(true);
let tabLoadingTimer: ReturnType<typeof window.setTimeout> | null = null;

const activeScrollData = computed(() => {
    if (activeTab.value === 'anonymous') {
        return 'anonymousInquiries';
    }

    if (activeTab.value === 'archived') {
        return 'archivedInquiries';
    }

    if (activeTab.value === 'approval') {
        return 'approvalInquiries';
    }

    return 'allInquiries';
});

const activeInquiries = computed(() => props[activeScrollData.value].data);

const filteredInquiries = computed(() => {
    let rows = activeInquiries.value.filter((inquiry) =>
        searchMatches(inquiry),
    );
    rows = rows.filter((inquiry) => statusMatches(inquiry));
    rows = rows.filter((inquiry) => categoryMatches(inquiry));
    rows = rows.filter((inquiry) => submittedDateMatches(inquiry));
    rows = sortRows(rows);

    return rows;
});

const emptyMessage = computed(() => {
    return {
        all: t('No inquiries found'),
        anonymous: t('No anonymous inquiries found'),
        archived: t('No archived inquiries found'),
        approval: t('No responses are awaiting your approval'),
    }[activeTab.value];
});

function searchMatches(inquiry: InquiryRecord) {
    const query = search.value.trim().toLowerCase();

    if (query === '') {
        return true;
    }

    return [
        inquiry.number,
        inquiry.subject,
        inquiry.status,
        inquiry.categoryName,
        inquiry.submittedAt,
    ].some((value) => value.toLowerCase().includes(query));
}

function statusMatches(inquiry: InquiryRecord) {
    return (
        statusFilter.value === 'all' || inquiry.status === statusFilter.value
    );
}

function categoryMatches(inquiry: InquiryRecord) {
    if (categoryFilter.value === 'all') {
        return true;
    }

    return (
        inquiry.categoryId !== null &&
        String(inquiry.categoryId) === categoryFilter.value
    );
}

function submittedDateMatches(inquiry: InquiryRecord) {
    return (
        submittedDateFilter.value === '' ||
        inquiry.submittedDate === submittedDateFilter.value
    );
}

function sortRows(rows: InquiryRecord[]) {
    const sortedRows = [...rows];

    if (ageFilter.value === 'old' || sortFilter.value === 'oldest') {
        return sortedRows.sort((a, b) => a.id - b.id);
    }

    if (sortFilter.value === 'days') {
        return sortedRows.sort((a, b) => a.daysLeft - b.daysLeft);
    }

    return sortedRows;
}

function clearFilters() {
    search.value = '';
    ageFilter.value = 'all';
    statusFilter.value = 'all';
    categoryFilter.value = 'all';
    submittedDateFilter.value = '';
    sortFilter.value = 'newest';
}

function toggleFilters() {
    filtersVisible.value = !filtersVisible.value;
}

function clearTabLoadingTimer() {
    if (tabLoadingTimer === null) {
        return;
    }

    window.clearTimeout(tabLoadingTimer);
    tabLoadingTimer = null;
}

function showTabSkeleton() {
    clearTabLoadingTimer();
    isTabLoading.value = true;

    tabLoadingTimer = window.setTimeout(() => {
        isTabLoading.value = false;
        tabLoadingTimer = null;
    }, 900);
}

watch(activeTab, showTabSkeleton, { immediate: true });

onBeforeUnmount(clearTabLoadingTimer);
</script>

<template>
    <div
        class="flex min-h-0 flex-1 flex-col bg-white text-[#1d1d1f] dark:bg-[#111113] dark:text-white"
    >
        <Head :title="t('Inquiries')" />

        <div class="flex min-h-0 w-full flex-1 flex-col overflow-hidden">
            <header
                class="flex shrink-0 items-center justify-between gap-4 px-4 py-5 sm:px-6 lg:px-8 lg:py-6"
            >
                <div class="flex min-w-0 items-baseline gap-3">
                    <h1
                        class="truncate text-[1.75rem] font-semibold tracking-[-0.04em] lg:text-[2rem]"
                    >
                        {{ t('Inquiries') }}
                    </h1>
                    <span
                        class="text-sm font-medium text-slate-400 tabular-nums dark:text-slate-500"
                    >
                        {{ filteredInquiries.length }}
                    </span>
                </div>
            </header>

            <section
                class="shrink-0 border-y border-black/8 bg-white dark:border-white/10 dark:bg-[#111113]"
            >
                <div
                    class="flex flex-col gap-3 px-4 py-3 sm:px-6 lg:px-8 xl:flex-row xl:items-center xl:justify-between"
                >
                    <InquiryTabs
                        v-model:active-tab="activeTab"
                        :filters-visible="filtersVisible"
                        :can-approve="canApprove"
                        @toggle-filters="toggleFilters"
                    />

                    <InquirySearchBar
                        v-model="search"
                        class="xl:w-[24rem] xl:shrink-0"
                        @clear="clearFilters"
                    />
                </div>

                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="-translate-y-2 opacity-0"
                    enter-to-class="translate-y-0 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="translate-y-0 opacity-100"
                    leave-to-class="-translate-y-2 opacity-0"
                >
                    <InquiryFilters
                        v-if="filtersVisible"
                        v-model:age="ageFilter"
                        v-model:status="statusFilter"
                        v-model:category="categoryFilter"
                        :categories="props.categories"
                        v-model:submitted-date="submittedDateFilter"
                        v-model:sort="sortFilter"
                    />
                </Transition>
            </section>

            <InquiriesTable
                :scroll-data="activeScrollData"
                :inquiries="filteredInquiries"
                :empty-label="emptyMessage"
                :loading="isTabLoading"
                :open-response="activeTab === 'approval'"
            />
        </div>
    </div>
</template>
