<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { updateAssignee } from '@/actions/App/Http/Controllers/InquiriesController';
import { chat as aiAssistantChat } from '@/routes/ai-assistant';
import { router } from '@inertiajs/vue3';
import { ArrowUp, Check, Copy, Expand, Shrink, Sparkles, UserCheck, X } from '@lucide/vue';
import { computed, nextTick, onBeforeUnmount, ref } from 'vue';
import AIAssistantQuickActions from './AIAssistantQuickActions.vue';
import type { AIAssistantAssigneeRecommendation, AIAssistantContext, AIAssistantJobDefinition } from './types';

type Props = {
    context: AIAssistantContext;
    jobs: AIAssistantJobDefinition[];
};

type ConversationMessage = {
    role: 'user' | 'assistant';
    content: string;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
}>();

const { t } = useTranslations();
const message = ref('');
const activeJob = ref<AIAssistantJobDefinition | null>(null);
const answer = ref('');
const displayedAnswer = ref('');
const error = ref('');
const isLoading = ref(false);
const recommendations = ref<AIAssistantAssigneeRecommendation[]>([]);
const pendingRecommendations = ref<AIAssistantAssigneeRecommendation[]>([]);
const assigningUserId = ref<number | null>(null);
const messagesScroll = ref<HTMLElement | null>(null);
const typingTimer = ref<number | null>(null);
const isExpanded = ref(false);
const conversationMessages = ref<ConversationMessage[]>([]);

const quickJobs = computed(() => {
    return props.jobs.filter((job) => job.key !== 'translate_text');
});

const greeting = computed(() => {
    if (activeJob.value === null) {
        return t('Hello! How can I help you?');
    }

    return t('Selected AI task: :task', { task: t(activeJob.value.label) });
});

function selectJob(job: AIAssistantJobDefinition) {
    activeJob.value = job;
    void send(job);
}

function rememberMessage(message: ConversationMessage) {
    if (message.content.trim() === '') {
        return;
    }

    conversationMessages.value = [
        ...conversationMessages.value,
        message,
    ].slice(-20);
}

function stopTyping() {
    if (typingTimer.value !== null) {
        window.clearInterval(typingTimer.value);
        typingTimer.value = null;
    }
}

function revealPendingRecommendations() {
    if (pendingRecommendations.value.length === 0) {
        return;
    }

    recommendations.value = pendingRecommendations.value;
    pendingRecommendations.value = [];
    void scrollMessagesToBottom();
}

async function scrollMessagesToBottom() {
    await nextTick();

    if (messagesScroll.value === null) {
        return;
    }

    messagesScroll.value.scrollTo({
        top: messagesScroll.value.scrollHeight,
        behavior: 'smooth',
    });
}

function typeAnswer(text: string, revealRecommendations = false, remember = false) {
    stopTyping();

    answer.value = text;
    displayedAnswer.value = '';

    const characters = Array.from(text);
    let index = 0;

    if (characters.length === 0) {
        if (remember) {
            rememberMessage({ role: 'assistant', content: text });
            displayedAnswer.value = '';
            answer.value = '';
        }

        if (revealRecommendations) {
            revealPendingRecommendations();
        }

        return;
    }

    typingTimer.value = window.setInterval(() => {
        displayedAnswer.value += characters[index] ?? '';
        index += 1;
        void scrollMessagesToBottom();

        if (index >= characters.length) {
            stopTyping();

            if (remember) {
                rememberMessage({ role: 'assistant', content: text });
                displayedAnswer.value = '';
                answer.value = '';
                void scrollMessagesToBottom();
            }

            if (revealRecommendations) {
                revealPendingRecommendations();
            }
        }
    }, 14);
}

async function send(job = activeJob.value ?? quickJobs.value[0]) {
    if (isLoading.value || job === undefined) {
        return;
    }

    stopTyping();
    activeJob.value = job;
    isLoading.value = true;
    error.value = '';
    answer.value = '';
    displayedAnswer.value = '';
    recommendations.value = [];
    pendingRecommendations.value = [];
    const currentMessage = message.value.trim();
    const visibleUserMessage = currentMessage || t(job.label);
    const requestHistory = conversationMessages.value.slice(-10);

    rememberMessage({ role: 'user', content: visibleUserMessage });

    try {
        const response = await fetch(aiAssistantChat.url(), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document
                    .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                    ?.content ?? '',
            },
            body: JSON.stringify({
                job: job.key,
                message: currentMessage,
                history: requestHistory,
                locale: props.context.locale,
                inquiry_number: props.context.inquiry?.number ?? null,
            }),
        });

        const payload = await response.json() as {
            message?: string;
            recommendations?: AIAssistantAssigneeRecommendation[];
        };

        if (!response.ok) {
            throw new Error(payload.message ?? t('AI assistant request failed.'));
        }

        pendingRecommendations.value = payload.recommendations ?? [];
        typeAnswer(payload.message ?? '', true, true);
        void scrollMessagesToBottom();
        message.value = '';
    } catch (caughtError) {
        error.value = caughtError instanceof Error
            ? caughtError.message
            : t('AI assistant request failed.');
    } finally {
        isLoading.value = false;
    }
}

function dismissRecommendations() {
    recommendations.value = [];
    pendingRecommendations.value = [];
}

function assignRecommendation(recommendation: AIAssistantAssigneeRecommendation) {
    if (!props.context.inquiry || !props.context.canAssignInquiries || assigningUserId.value !== null) {
        return;
    }

    assigningUserId.value = recommendation.user_id;

    router.patch(
        updateAssignee(props.context.inquiry.number).url,
        { assigned_to_id: recommendation.user_id },
        {
            preserveScroll: true,
            onSuccess: () => {
                typeAnswer(t('Executor assigned.'), false, true);
                recommendations.value = [];
                pendingRecommendations.value = [];
            },
            onError: () => {
                error.value = t('Unable to assign executor.');
            },
            onFinish: () => {
                assigningUserId.value = null;
            },
        },
    );
}

onBeforeUnmount(() => {
    stopTyping();
});
</script>

<template>
    <aside
        class="fixed inset-x-3 bottom-3 z-50 flex h-[min(760px,calc(100vh-1.5rem))] flex-col overflow-hidden rounded-2xl border border-border bg-background shadow-2xl transition-[width,transform] duration-300 ease-out sm:top-5 sm:bottom-5 sm:h-auto"
        :class="
            isExpanded
                ? 'sm:right-auto sm:left-1/2 sm:w-[min(920px,calc(100vw-2.5rem))] sm:-translate-x-1/2'
                : 'sm:inset-x-auto sm:right-5 sm:w-[560px] sm:translate-x-0'
        "
        role="dialog"
        aria-modal="true"
        :aria-label="t('AI assistant')"
    >
        <header class="flex h-16 shrink-0 items-center justify-between gap-3 border-b border-border px-5">
            <div class="flex min-w-0 items-center gap-3">
                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-[var(--color-tab)] text-white">
                    <Sparkles class="size-5" />
                </span>
                <h2 class="truncate text-base font-semibold text-foreground">{{ t('AI assistant') }}</h2>
                <span class="rounded-md bg-blue-100 px-3 py-1 text-xs font-bold text-[var(--color-tab)]">
                    DEMO
                </span>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="h-9 border-[var(--color-tab)] px-3 text-[var(--color-tab)] hover:bg-blue-50 hover:text-[var(--color-tab)]"
                    :aria-label="isExpanded ? t('Collapse') : t('Expand')"
                    @click="isExpanded = !isExpanded"
                >
                    <Shrink v-if="isExpanded" class="size-4" />
                    <Expand v-else class="size-4" />
                    <span class="hidden sm:inline">{{ isExpanded ? t('Collapse') : t('Expand') }}</span>
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    class="size-9 border-[var(--color-tab)] text-[var(--color-tab)] hover:bg-blue-50 hover:text-[var(--color-tab)]"
                    :aria-label="t('Close')"
                    @click="emit('close')"
                >
                    <X class="size-4" />
                </Button>
            </div>
        </header>

        <div ref="messagesScroll" class="min-h-0 flex-1 overflow-y-auto px-5 py-5">
            <div class="flex items-start gap-3">
                <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-[var(--color-tab)] text-white">
                    <Sparkles class="size-4" />
                </span>
                <div class="max-w-[78%] rounded-2xl bg-muted px-4 py-3 text-sm font-medium leading-6 text-foreground">
                    {{ greeting }}
                </div>
            </div>

            <div class="mt-3 ml-11 flex items-center gap-2 text-muted-foreground">
                <button
                    type="button"
                    class="inline-flex size-7 items-center justify-center rounded-md transition-colors hover:bg-muted hover:text-foreground"
                    :aria-label="t('Copy')"
                >
                    <Copy class="size-4" />
                </button>
            </div>

            <div
                v-for="(conversationMessage, index) in conversationMessages"
                :key="`${conversationMessage.role}-${index}`"
                class="mt-5 flex"
                :class="conversationMessage.role === 'user' ? 'justify-end' : 'justify-start'"
            >
                <div
                    class="max-w-[82%] rounded-2xl px-4 py-3 text-sm leading-6 whitespace-pre-line"
                    :class="
                        conversationMessage.role === 'user'
                            ? 'bg-[var(--color-tab)] text-white'
                            : 'bg-muted text-foreground'
                    "
                >
                    {{ conversationMessage.content }}
                </div>
            </div>

            <div v-if="isLoading" class="mt-5 flex items-center gap-2 text-sm font-medium text-muted-foreground">
                <span class="inline-flex size-2 rounded-full bg-[var(--color-tab)] motion-safe:animate-ping" />
                {{ t('AI assistant is thinking...') }}
            </div>

            <div v-if="displayedAnswer" class="mt-5 rounded-2xl bg-muted px-4 py-3 text-sm leading-6 text-foreground whitespace-pre-line">
                {{ displayedAnswer }}<span
                    v-if="typingTimer !== null"
                    class="ml-0.5 inline-block h-4 w-1 translate-y-0.5 rounded-full bg-[var(--color-tab)] motion-safe:animate-pulse"
                />
            </div>

            <div
                v-if="recommendations.length"
                class="mt-5 space-y-2"
            >
                <div
                    v-for="recommendation in recommendations"
                    :key="recommendation.user_id"
                    class="rounded-lg border border-border bg-background p-3"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex min-w-0 items-center gap-2">
                                <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-md bg-blue-50 text-[var(--color-tab)]">
                                    <UserCheck class="size-4" />
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-foreground">
                                        {{ recommendation.name }}
                                    </p>
                                    <p class="truncate text-xs text-muted-foreground">
                                        {{ recommendation.role ?? t('No role') }} · {{ recommendation.email }}
                                    </p>
                                </div>
                            </div>
                            <p class="mt-2 text-xs leading-5 text-muted-foreground">
                                {{ recommendation.reason }}
                            </p>
                            <p class="mt-1 text-xs font-semibold text-muted-foreground">
                                {{ t('Active assigned inquiries') }}: {{ recommendation.active_assignments_count }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="h-8 px-3 text-xs"
                            :disabled="assigningUserId !== null"
                            @click="dismissRecommendations"
                        >
                            {{ t('No') }}
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            class="h-8 bg-[var(--color-tab)] px-3 text-xs text-white hover:bg-[var(--color-tab)]/90"
                            :disabled="!context.inquiry || !context.canAssignInquiries || assigningUserId !== null"
                            @click="assignRecommendation(recommendation)"
                        >
                            <Check class="size-3.5" />
                            {{ assigningUserId === recommendation.user_id ? t('Assigning...') : t('Assign') }}
                        </Button>
                    </div>
                </div>
            </div>

            <div v-if="error" class="mt-5 rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm font-medium text-destructive">
                {{ error }}
            </div>
        </div>

        <footer class="shrink-0 border-t border-border px-5 py-4">
            <AIAssistantQuickActions
                class="mb-3"
                :jobs="quickJobs"
                :active-job-key="activeJob?.key ?? null"
                @select="selectJob"
            />

            <div class="flex items-center gap-2">
                <div class="relative min-w-0 flex-1">
                    <Sparkles class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        v-model="message"
                        type="text"
                        class="h-11 w-full rounded-lg border border-[var(--color-tab)] bg-background pr-3 pl-10 text-sm font-medium outline-none transition-shadow placeholder:text-muted-foreground focus:ring-2 focus:ring-[var(--color-tab)]/20"
                        :placeholder="t('Ask a question...')"
                    />
                </div>
                <button
                    type="button"
                    class="inline-flex size-11 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground transition-colors hover:bg-[var(--color-tab)] hover:text-white disabled:opacity-60"
                    :disabled="message.trim().length === 0 || isLoading"
                    :aria-label="t('Send')"
                    @click="send()"
                >
                    <ArrowUp class="size-5" />
                </button>
            </div>
            <p class="mx-auto mt-3 max-w-sm text-center text-xs leading-5 text-muted-foreground">
                {{ t('AI assistant can make mistakes. We recommend checking important information.') }}
            </p>
        </footer>
    </aside>
</template>
