<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
import { index as inquiriesIndex, translate as inquiryTranslate } from '@/routes/inquiries';
import type { Auth } from '@/types/auth';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, FileText } from '@lucide/vue';
import { computed, ref } from 'vue';
import InquiryAttachmentsPanel from './InquiryAttachmentsPanel.vue';
import InquiryDescriptionPanel from './InquiryDescriptionPanel.vue';
import InquiryDetailSummary from './InquiryDetailSummary.vue';
import InquiryDetailTabs from './InquiryDetailTabs.vue';
import type { InquiryAssigneeOption, InquiryCategory, InquiryDetail, InquiryDetailTab } from './types';

type Props = {
    inquiry: InquiryDetail;
    categories: InquiryCategory[];
    systemUsers: InquiryAssigneeOption[];
};

const props = defineProps<Props>();

const { t } = useTranslations();
const page = usePage<{ auth: Auth }>();
const canAssign = computed(() => page.props.auth.can.inquiriesUpdate);
const activeTab = ref<InquiryDetailTab>('description');

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
        const response = await fetch(inquiryTranslate(props.inquiry.number).url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document
                    .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                    ?.content ?? '',
            },
            body: JSON.stringify({ language }),
        });

        const payload = await response.json() as {
            description?: string | null;
            language?: string;
            message?: string;
        };

        if (!response.ok) {
            throw new Error(payload.message ?? t('Translation is temporarily unavailable.'));
        }

        selectedLanguage.value = language;
        translatedDescription.value = payload.description ?? '';
    } catch (caughtError) {
        translationError.value = caughtError instanceof Error
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

    <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
        <div class="flex min-h-0 flex-1 flex-col gap-3 overflow-auto p-3">
            <header
                class="flex shrink-0 items-center justify-between gap-3 border-b border-border pb-3"
            >
                <div class="flex min-w-0 items-center gap-2 text-xs font-semibold">
                    <Link
                        :href="inquiriesIndex()"
                        class="text-muted-foreground transition-colors hover:text-foreground"
                    >
                        {{ t('Inquiries') }}
                    </Link>
                    <span class="text-muted-foreground">›</span>
                    <span class="truncate">
                        {{ t('Inquiry') }} № {{ inquiry.number }}
                    </span>
                </div>

                <Link
                    :href="inquiriesIndex()"
                    class="inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold text-muted-foreground transition-colors hover:text-[var(--color-tab)]"
                >
                    <ArrowLeft class="size-3.5" />
                    {{ t('Back to list') }}
                </Link>
            </header>

            <InquiryDetailSummary
                :inquiry="inquiry"
                :categories="categories"
                :system-users="systemUsers"
                :can-assign="canAssign"
            />

            <InquiryDetailTabs
                v-model="activeTab"
                :attachments-count="inquiry.attachmentsCount"
                :comments-count="inquiry.commentsCount"
                :history-count="inquiry.historyCount"
            />

            <InquiryDescriptionPanel
                v-if="activeTab === 'description'"
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
                :attachments="inquiry.attachments"
            />

            <section
                v-else
                class="flex min-h-28 flex-col items-center justify-center rounded-lg bg-muted/40 px-4 py-6 text-center"
            >
                <span class="inline-flex size-10 items-center justify-center rounded-full bg-muted text-muted-foreground">
                    <FileText class="size-5" />
                </span>
                <p class="mt-2 text-sm font-semibold text-muted-foreground">{{ t('No data yet') }}</p>
            </section>
        </div>
    </div>
</template>
