<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
import type { Auth } from '@/types/auth';
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AIAssistantLauncher from './ai-assistant/AIAssistantLauncher.vue';
import AIAssistantPanel from './ai-assistant/AIAssistantPanel.vue';
import { AI_ASSISTANT_JOBS } from './ai-assistant/jobs';
import type { AIAssistantContext } from './ai-assistant/types';

const { t } = useTranslations();
const page = usePage();
const isOpen = ref(false);

const context = computed<AIAssistantContext>(() => {
    const props = page.props as Record<string, unknown>;
    const inquiry = props.inquiry as Record<string, unknown> | undefined;
    const auth = props.auth as Auth | undefined;

    return {
        locale: String(page.props.locale.current ?? 'ru'),
        page: page.component,
        canAssignInquiries: Boolean(auth?.can.inquiriesUpdate),
        inquiry: inquiry === undefined
            ? undefined
            : {
                number: String(inquiry.number ?? ''),
                title: String(inquiry.subject ?? ''),
                description: inquiry.description === null ? null : String(inquiry.description ?? ''),
                status: String(inquiry.status ?? ''),
                categoryName: String(inquiry.categoryName ?? ''),
                submittedAt: String(inquiry.submittedAt ?? ''),
                reviewDueDate: String(inquiry.reviewDueDate ?? ''),
                applicantName: inquiry.applicantName === null ? null : String(inquiry.applicantName ?? ''),
                applicantPhone: inquiry.applicantPhone === null ? null : String(inquiry.applicantPhone ?? ''),
                assigneeName: typeof inquiry.assignee === 'object' && inquiry.assignee !== null
                    ? String((inquiry.assignee as Record<string, unknown>).name ?? '')
                    : null,
                attachmentsCount: Number(inquiry.attachmentsCount ?? 0),
            },
    };
});
</script>

<template>
    <Teleport to="body">
        <AIAssistantLauncher
            v-if="!isOpen"
            :label="t('Open AI assistant')"
            @open="isOpen = true"
        />

        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-y-4 opacity-0 sm:translate-x-6 sm:translate-y-0"
            enter-to-class="translate-x-0 translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-x-0 translate-y-0 opacity-100"
            leave-to-class="translate-y-4 opacity-0 sm:translate-x-6 sm:translate-y-0"
        >
            <AIAssistantPanel
                v-if="isOpen"
                :context="context"
                :jobs="AI_ASSISTANT_JOBS"
                @close="isOpen = false"
            />
        </Transition>
    </Teleport>
</template>
