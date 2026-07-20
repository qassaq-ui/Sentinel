<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Download,
    FileText,
    LoaderCircle,
    RotateCcw,
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import AIAssistantWidget from '@/components/AIAssistantWidget.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useInquiryReport } from '@/composables/useInquiryReport';
import { useTranslations } from '@/composables/useTranslations';
import {
    index as inquiriesIndex,
    translate as inquiryTranslate,
} from '@/routes/inquiries';
import type { Auth } from '@/types/auth';
import InquiryAttachmentsPanel from './InquiryAttachmentsPanel.vue';
import InquiryCommentsPanel from './InquiryCommentsPanel.vue';
import InquiryDescriptionPanel from './InquiryDescriptionPanel.vue';
import InquiryDetailSummary from './InquiryDetailSummary.vue';
import InquiryDetailTabs from './InquiryDetailTabs.vue';
import InquiryHistoryTimeline from './InquiryHistoryTimeline.vue';
import InquiryResponsePanel from './InquiryResponsePanel.vue';
import { supportedLanguageOptions } from './languageOptions';
import type {
    InquiryAssigneeOption,
    InquiryCategory,
    InquiryDetail,
    InquiryDetailTab,
    InquiryOutcomeOption,
    InquiryResponsePermissions,
    InquiryResponseUser,
} from './types';

type Props = {
    inquiry: InquiryDetail;
    categories: InquiryCategory[];
    systemUsers: InquiryAssigneeOption[];
    outcomes: InquiryOutcomeOption[];
    reviewers: InquiryResponseUser[];
    canAssignExecutor: boolean;
    responsePermissions: InquiryResponsePermissions;
};

const props = defineProps<Props>();

const { t } = useTranslations();
const page = usePage<{ auth: Auth }>();
const canAssign = computed(() => page.props.auth.can.inquiriesAssign);
const detailTabs: InquiryDetailTab[] = [
    'description',
    'attachments',
    'comments',
    'history',
    'response',
];
const requestedTab = new URLSearchParams(window.location.search).get('tab');
const tabStorageKey = `inquiries.show.${props.inquiry.number}.active-tab`;
const storedTab = window.sessionStorage.getItem(tabStorageKey);
const initialTab = detailTabs.includes(requestedTab as InquiryDetailTab)
    ? (requestedTab as InquiryDetailTab)
    : detailTabs.includes(storedTab as InquiryDetailTab)
      ? (storedTab as InquiryDetailTab)
      : 'description';
const activeTabState = ref<InquiryDetailTab>(initialTab);
window.sessionStorage.setItem(tabStorageKey, initialTab);

function syncTabWithUrl(tab: InquiryDetailTab): void {
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tab);

    if (tab !== 'comments') {
        url.searchParams.delete('comments_page');
    }

    window.history.replaceState(window.history.state, '', url);
}

const activeTab = computed<InquiryDetailTab>({
    get: () => activeTabState.value,
    set: (tab) => {
        activeTabState.value = tab;
        window.sessionStorage.setItem(tabStorageKey, tab);
        syncTabWithUrl(tab);
    },
});

const report = useInquiryReport(props.inquiry.number);

onMounted(() => {
    void report.init();
});

const currentSiteLocale = computed(() =>
    String(page.props.locale.current ?? 'ru'),
);

const selectedLanguage = ref<string>('');
const translatedDescription = ref<string | null>(null);
const isTranslating = ref(false);
const translationError = ref<string>('');

async function requestTranslation(language: string) {
    if (language === '' || isTranslating.value) {
        return;
    }

    isTranslating.value = true;
    translationError.value = '';

    try {
        const response = await fetch(
            inquiryTranslate(props.inquiry.number).url,
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN':
                        document.querySelector<HTMLMetaElement>(
                            'meta[name="csrf-token"]',
                        )?.content ?? '',
                },
                body: JSON.stringify({ language }),
            },
        );

        const payload = (await response.json()) as {
            description?: string | null;
            language?: string;
            message?: string;
        };

        if (!response.ok) {
            throw new Error(
                payload.message ?? t('Translation is temporarily unavailable.'),
            );
        }

        selectedLanguage.value = language;
        translatedDescription.value = payload.description ?? '';
    } catch (caughtError) {
        translationError.value =
            caughtError instanceof Error
                ? caughtError.message
                : t('Translation is temporarily unavailable.');
        translatedDescription.value = null;
        selectedLanguage.value = '';
    } finally {
        isTranslating.value = false;
    }
}

function showOriginal() {
    selectedLanguage.value = '';
    translatedDescription.value = null;
    translationError.value = '';
}
</script>

<template>
    <Head :title="`${t('Inquiry')} № ${inquiry.number}`" />

    <div
        class="flex min-h-0 flex-1 flex-col overflow-hidden bg-white text-[#1d1d1f] dark:bg-[#111113] dark:text-white"
    >
        <div
            class="scroll-region flex min-h-0 flex-1 flex-col gap-4 overflow-auto pb-6"
        >
            <header
                class="flex shrink-0 flex-col gap-4 border-b border-black/8 px-4 py-5 sm:px-6 lg:flex-row lg:items-end lg:justify-between lg:px-8 lg:py-6 dark:border-white/10"
            >
                <div class="min-w-0">
                    <div
                        class="mb-2 flex min-w-0 items-center gap-2 text-xs font-semibold text-muted-foreground"
                    >
                        <Link
                            :href="inquiriesIndex()"
                            class="transition-colors hover:text-foreground"
                        >
                            {{ t('Inquiries') }}
                        </Link>
                        <span>›</span>
                        <span class="truncate">№ {{ inquiry.number }}</span>
                    </div>
                    <h1
                        class="truncate text-[1.75rem] leading-none font-semibold tracking-[-0.04em] lg:text-[2rem]"
                    >
                        {{ inquiry.subject }}
                    </h1>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <template v-if="report.state.status === 'completed'">
                        <Button
                            type="button"
                            size="sm"
                            class="h-8 text-xs"
                            @click="report.download()"
                        >
                            <Download class="size-3.5" />
                            {{ t('Download report') }}
                        </Button>

                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="secondary"
                                    class="h-8 text-xs"
                                >
                                    <RotateCcw class="size-3.5" />
                                    {{ t('Regenerate report') }}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-48">
                                <DropdownMenuItem
                                    v-for="language in supportedLanguageOptions"
                                    :key="language.code"
                                    @select="report.generate(language.code)"
                                >
                                    {{ language.label }}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </template>
                    <Button
                        v-else-if="
                            report.state.status === 'pending' ||
                            report.state.status === 'processing'
                        "
                        type="button"
                        size="sm"
                        variant="secondary"
                        class="h-8 text-xs"
                        disabled
                    >
                        <LoaderCircle class="size-3.5 animate-spin" />
                        {{ t('Report is being generated…') }}
                    </Button>
                    <Button
                        v-else-if="report.state.status === 'failed'"
                        type="button"
                        size="sm"
                        variant="secondary"
                        class="h-8 text-xs"
                        :title="report.state.error"
                        @click="report.generate(currentSiteLocale)"
                    >
                        <RotateCcw class="size-3.5" />
                        {{ t('Retry') }}
                    </Button>
                    <DropdownMenu v-else>
                        <DropdownMenuTrigger as-child>
                            <Button
                                type="button"
                                size="sm"
                                variant="secondary"
                                class="h-8 text-xs"
                            >
                                <FileText class="size-3.5" />
                                {{ t('Generate report') }}
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-48">
                            <DropdownMenuItem
                                v-for="language in supportedLanguageOptions"
                                :key="language.code"
                                @select="report.generate(language.code)"
                            >
                                {{ language.label }}
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <Link
                        :href="inquiriesIndex()"
                        class="inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold text-muted-foreground transition-colors hover:text-[var(--color-tab)]"
                    >
                        <ArrowLeft class="size-3.5" />
                        {{ t('Back to list') }}
                    </Link>
                </div>
            </header>

            <InquiryDetailSummary
                class="mx-4 sm:mx-6 lg:mx-8"
                :inquiry="inquiry"
                :categories="categories"
                :system-users="systemUsers"
                :can-assign="canAssign"
                :can-assign-executor="canAssignExecutor"
            />

            <InquiryDetailTabs
                class="mx-4 sm:mx-6 lg:mx-8"
                v-model="activeTab"
                :attachments-count="inquiry.attachmentsCount"
                :comments-count="inquiry.commentsCount"
                :history-count="inquiry.historyCount"
            />

            <InquiryDescriptionPanel
                v-if="activeTab === 'description'"
                class="mx-4 sm:mx-6 lg:mx-8"
                :description="inquiry.description"
                :selected-language="selectedLanguage"
                :translated-description="translatedDescription"
                :is-translating="isTranslating"
                :translation-error="translationError"
                @select-language="requestTranslation"
                @show-original="showOriginal"
            />

            <InquiryAttachmentsPanel
                v-else-if="activeTab === 'attachments'"
                class="mx-4 sm:mx-6 lg:mx-8"
                :attachments="inquiry.attachments"
            />

            <InquiryCommentsPanel
                v-else-if="activeTab === 'comments'"
                class="mx-4 sm:mx-6 lg:mx-8"
                :inquiry-number="inquiry.number"
                :comments="inquiry.comments"
                :can-comment="responsePermissions.comment"
            />

            <InquiryHistoryTimeline
                v-else-if="activeTab === 'history'"
                class="mx-4 sm:mx-6 lg:mx-8"
                :events="inquiry.history"
            />

            <InquiryResponsePanel
                v-else-if="activeTab === 'response'"
                class="mx-4 sm:mx-6 lg:mx-8"
                :inquiry-number="inquiry.number"
                :response="inquiry.response"
                :outcomes="outcomes"
                :reviewers="reviewers"
                :permissions="responsePermissions"
                :locale="currentSiteLocale"
            />

            <section
                v-else
                class="mx-4 flex min-h-28 flex-col items-center justify-center border-y border-black/8 bg-[#f7f7f8] px-4 py-6 text-center sm:mx-6 lg:mx-8 dark:border-white/10 dark:bg-[#1a1a1c]"
            >
                <span
                    class="inline-flex size-10 items-center justify-center rounded-full bg-muted text-muted-foreground"
                >
                    <FileText class="size-5" />
                </span>
                <p class="mt-2 text-sm font-semibold text-muted-foreground">
                    {{ t('No data yet') }}
                </p>
            </section>
        </div>
    </div>

    <AIAssistantWidget />
</template>
