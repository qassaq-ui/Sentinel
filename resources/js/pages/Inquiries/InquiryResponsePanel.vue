<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import {
    Check,
    Languages,
    LoaderCircle,
    RotateCcw,
    Send,
    Sparkles,
    WandSparkles,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InquiryResponsesController from '@/actions/App/Http/Controllers/InquiryResponsesController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslations } from '@/composables/useTranslations';
import InquiryResponseAttachments from './InquiryResponseAttachments.vue';
import { supportedLanguageOptions } from './languageOptions';
import type {
    InquiryOutcomeOption,
    InquiryResponse,
    InquiryResponsePermissions,
    InquiryResponseUser,
} from './types';

type Props = {
    inquiryNumber: string;
    response: InquiryResponse | null;
    outcomes: InquiryOutcomeOption[];
    reviewers: InquiryResponseUser[];
    permissions: InquiryResponsePermissions;
    locale: string;
};

const props = defineProps<Props>();
const { t } = useTranslations();
const generating = ref(false);
const transforming = ref<'translate' | 'polish' | null>(null);
const generationError = ref('');
const attachmentError = ref('');
const submittingForApproval = ref(false);
const approvalError = ref('');

const draftForm = useForm<{
    _method: 'patch';
    inquiry_outcome_id: number | null;
    body: string;
    attachments: File[];
}>({
    _method: 'patch',
    inquiry_outcome_id: props.response?.outcomeId ?? null,
    body: props.response?.body ?? '',
    attachments: [],
});

const submitForm = useForm<{ reviewer_id: number | null }>({
    reviewer_id: props.response?.reviewer?.id ?? null,
});

const reviewForm = useForm<{
    decision: 'approve' | 'request_changes';
    comment: string;
}>({
    decision: 'approve',
    comment: '',
});

const sendForm = useForm({});
const submitResponseError = computed(
    () =>
        approvalError.value ||
        (submitForm.errors as Record<string, string>).response,
);
const selectedOutcomeId = computed({
    get: () =>
        draftForm.inquiry_outcome_id === null
            ? undefined
            : String(draftForm.inquiry_outcome_id),
    set: (value: string | undefined) => {
        draftForm.inquiry_outcome_id = value ? Number(value) : null;
    },
});
const selectedReviewerId = computed({
    get: () =>
        submitForm.reviewer_id === null
            ? undefined
            : String(submitForm.reviewer_id),
    set: (value: string | undefined) => {
        submitForm.reviewer_id = value ? Number(value) : null;
        submitForm.clearErrors();
    },
});

const editable = computed(
    () =>
        props.permissions.respond &&
        (props.response === null ||
            ['draft', 'changes_requested'].includes(props.response.status)),
);

const selectedOutcome = computed(() =>
    props.outcomes.find(
        (outcome) => outcome.id === draftForm.inquiry_outcome_id,
    ),
);

const statusLabel = computed(() => {
    const labels = {
        draft: 'Draft',
        pending_approval: 'Awaiting approval',
        changes_requested: 'Changes requested',
        approved: 'Approved',
        sent: 'Sent',
    } as const;

    return props.response
        ? t(labels[props.response.status])
        : t('Not prepared');
});

watch(
    () => props.response,
    (response) => {
        draftForm.inquiry_outcome_id = response?.outcomeId ?? null;
        draftForm.body = response?.body ?? '';

        if (response?.reviewer !== null && response?.reviewer !== undefined) {
            submitForm.reviewer_id = response.reviewer.id;
        }
    },
);

watch(
    () => props.inquiryNumber,
    () => {
        submitForm.reviewer_id = props.response?.reviewer?.id ?? null;
        submitForm.clearErrors();
    },
);

function saveDraft(onSuccess?: () => void) {
    attachmentError.value = '';
    draftForm.post(InquiryResponsesController.draft(props.inquiryNumber).url, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            draftForm.reset('attachments');
            draftForm.defaults();
            onSuccess?.();
        },
    });
}

function submitForApproval() {
    const reviewerId = submitForm.reviewer_id;
    submitForm.clearErrors();
    draftForm.clearErrors();
    attachmentError.value = '';
    approvalError.value = '';

    if (reviewerId === null) {
        submitForm.setError('reviewer_id', t('Select an approver'));

        return;
    }

    const payload = new FormData();
    payload.append('reviewer_id', String(reviewerId));
    payload.append('body', draftForm.body);

    if (draftForm.inquiry_outcome_id !== null) {
        payload.append(
            'inquiry_outcome_id',
            String(draftForm.inquiry_outcome_id),
        );
    }

    for (const attachment of draftForm.attachments) {
        payload.append('attachments[]', attachment);
    }

    router.post(
        InquiryResponsesController.submit(props.inquiryNumber).url,
        payload,
        {
            preserveScroll: true,
            forceFormData: true,
            onStart: () => {
                submittingForApproval.value = true;
            },
            onError: (errors) => {
                if (errors.reviewer_id) {
                    submitForm.setError('reviewer_id', errors.reviewer_id);
                }

                if (errors.inquiry_outcome_id) {
                    draftForm.setError(
                        'inquiry_outcome_id',
                        errors.inquiry_outcome_id,
                    );
                }

                if (errors.body) {
                    draftForm.setError('body', errors.body);
                }

                attachmentError.value =
                    errors.attachments ??
                    Object.entries(errors).find(([field]) =>
                        field.startsWith('attachments.'),
                    )?.[1] ??
                    '';
                approvalError.value = errors.response ?? '';
            },
            onSuccess: () => {
                draftForm.reset('attachments');
            },
            onFinish: () => {
                submittingForApproval.value = false;
            },
        },
    );
}

function review(decision: 'approve' | 'request_changes') {
    reviewForm.decision = decision;
    reviewForm.patch(
        InquiryResponsesController.review(props.inquiryNumber).url,
        {
            preserveScroll: true,
            onSuccess: () => reviewForm.reset('comment'),
        },
    );
}

function sendResponse() {
    sendForm.post(InquiryResponsesController.send(props.inquiryNumber).url, {
        preserveScroll: true,
    });
}

async function generateResponse() {
    if (draftForm.inquiry_outcome_id === null || generating.value) {
        draftForm.setError(
            'inquiry_outcome_id',
            t('Select an inquiry outcome first'),
        );

        return;
    }

    generating.value = true;
    generationError.value = '';
    draftForm.clearErrors('inquiry_outcome_id');

    try {
        const response = await fetch(
            InquiryResponsesController.generate(props.inquiryNumber).url,
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
                body: JSON.stringify({
                    inquiry_outcome_id: draftForm.inquiry_outcome_id,
                    current_body: draftForm.body,
                    locale: props.locale,
                }),
            },
        );
        const payload = (await response.json()) as {
            body?: string;
            message?: string;
        };

        if (!response.ok || !payload.body) {
            throw new Error(
                payload.message ??
                    t('AI assistant is temporarily unavailable.'),
            );
        }

        draftForm.body = payload.body;
    } catch (error) {
        generationError.value =
            error instanceof Error
                ? error.message
                : t('AI assistant is temporarily unavailable.');
    } finally {
        generating.value = false;
    }
}

async function transformResponse(
    action: 'translate' | 'polish',
    locale?: string,
) {
    if (draftForm.body.trim() === '' || transforming.value !== null) {
        if (draftForm.body.trim() === '') {
            draftForm.setError('body', t('Enter response text first'));
        }

        return;
    }

    transforming.value = action;
    generationError.value = '';
    draftForm.clearErrors('body');

    try {
        const response = await fetch(
            InquiryResponsesController.transform(props.inquiryNumber).url,
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
                body: JSON.stringify({
                    action,
                    body: draftForm.body,
                    locale,
                }),
            },
        );
        const payload = (await response.json()) as {
            body?: string;
            message?: string;
        };

        if (!response.ok || !payload.body) {
            throw new Error(
                payload.message ??
                    t('AI assistant is temporarily unavailable.'),
            );
        }

        draftForm.body = payload.body;
    } catch (error) {
        generationError.value =
            error instanceof Error
                ? error.message
                : t('AI assistant is temporarily unavailable.');
    } finally {
        transforming.value = null;
    }
}
</script>

<template>
    <section
        class="space-y-4 border-y border-black/8 bg-[#f7f7f8] p-4 dark:border-white/10 dark:bg-[#1a1a1c]"
    >
        <div class="flex flex-wrap items-start justify-between gap-3">
            <h2 class="text-base font-semibold">{{ t('Response') }}</h2>
            <span
                class="rounded-full bg-muted px-3 py-1 text-xs font-semibold text-muted-foreground"
            >
                {{ statusLabel }}
            </span>
        </div>

        <div class="grid max-w-xs gap-2">
            <label for="inquiry-response-outcome" class="text-sm font-medium">
                {{ t('Inquiry outcome') }}
            </label>
            <Select v-model="selectedOutcomeId" :disabled="!editable">
                <SelectTrigger
                    id="inquiry-response-outcome"
                    class="h-10 w-full bg-background focus-visible:border-blue-500 dark:focus-visible:border-blue-400"
                >
                    <SelectValue :placeholder="t('Select an outcome')" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="outcome in outcomes"
                        :key="outcome.id"
                        :value="String(outcome.id)"
                    >
                        {{ outcome.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <p
                v-if="selectedOutcome?.description"
                class="text-xs text-muted-foreground"
            >
                {{ selectedOutcome.description }}
            </p>
            <InputError :message="draftForm.errors.inquiry_outcome_id" />
        </div>

        <div class="grid gap-2">
            <label for="inquiry-response-body" class="text-sm font-medium">
                {{ t('Response text') }}
            </label>
            <div class="relative">
                <textarea
                    id="inquiry-response-body"
                    v-model="draftForm.body"
                    rows="12"
                    :readonly="!editable"
                    class="min-h-64 w-full resize-y rounded-md border border-input bg-background px-3 py-3 pb-24 text-sm leading-6 read-only:cursor-default read-only:bg-muted/30 focus-visible:border-blue-500 focus-visible:outline-none sm:pb-14 dark:focus-visible:border-blue-400"
                    :placeholder="
                        t(
                            'Prepare the response text or generate a draft with AI.',
                        )
                    "
                />
                <div
                    v-if="editable"
                    class="absolute inset-x-3 bottom-3 flex flex-wrap items-center justify-between gap-2"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="secondary"
                                    :disabled="
                                        generating || transforming !== null
                                    "
                                >
                                    <LoaderCircle
                                        v-if="transforming === 'translate'"
                                        class="size-4 animate-spin"
                                    />
                                    <Languages v-else class="size-4" />
                                    {{
                                        transforming === 'translate'
                                            ? t('Translating…')
                                            : t('Translate')
                                    }}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="start" class="w-48">
                                <DropdownMenuItem
                                    v-for="language in supportedLanguageOptions"
                                    :key="language.code"
                                    @select="
                                        transformResponse(
                                            'translate',
                                            language.code,
                                        )
                                    "
                                >
                                    {{ language.label }}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <Button
                            type="button"
                            size="sm"
                            variant="secondary"
                            :disabled="generating || transforming !== null"
                            @click="transformResponse('polish')"
                        >
                            <LoaderCircle
                                v-if="transforming === 'polish'"
                                class="size-4 animate-spin"
                            />
                            <WandSparkles v-else class="size-4" />
                            {{
                                transforming === 'polish'
                                    ? t('Improving…')
                                    : t('Improve response')
                            }}
                        </Button>
                    </div>

                    <Button
                        type="button"
                        size="sm"
                        variant="secondary"
                        :disabled="generating || transforming !== null"
                        @click="generateResponse"
                    >
                        <LoaderCircle
                            v-if="generating"
                            class="size-4 animate-spin"
                        />
                        <Sparkles v-else class="size-4" />
                        {{
                            generating
                                ? t('Generating…')
                                : t('Generate response')
                        }}
                    </Button>
                </div>
            </div>
            <InputError :message="draftForm.errors.body" />
            <p v-if="generationError" class="text-sm text-destructive">
                {{ generationError }}
            </p>
        </div>

        <InquiryResponseAttachments
            v-model="draftForm.attachments"
            :inquiry-number="inquiryNumber"
            :attachments="response?.attachments ?? []"
            :editable="editable"
            :processing="draftForm.processing"
            @error="attachmentError = $event"
        />
        <InputError
            :message="
                attachmentError ||
                draftForm.errors.attachments ||
                (draftForm.errors as Record<string, string>)['attachments.0']
            "
        />

        <div
            v-if="editable"
            class="grid gap-3 border-t border-border pt-4 md:grid-cols-[1fr_auto]"
        >
            <div class="grid gap-2">
                <label
                    for="inquiry-response-reviewer"
                    class="text-sm font-medium"
                >
                    {{ t('Approver') }}
                </label>
                <Select v-model="selectedReviewerId">
                    <SelectTrigger
                        id="inquiry-response-reviewer"
                        class="h-10 w-full bg-background focus-visible:border-blue-500 dark:focus-visible:border-blue-400"
                    >
                        <SelectValue :placeholder="t('Select an approver')" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="reviewer in reviewers"
                            :key="reviewer.id"
                            :value="String(reviewer.id)"
                        >
                            {{ reviewer.name }} — {{ reviewer.email }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="submitForm.errors.reviewer_id" />
                <InputError :message="submitResponseError" />
            </div>

            <div class="flex flex-wrap items-end justify-end gap-2">
                <Button
                    type="button"
                    variant="outline"
                    :disabled="draftForm.processing || !draftForm.isDirty"
                    @click="saveDraft()"
                >
                    {{
                        draftForm.processing
                            ? t('Saving…')
                            : !draftForm.isDirty && response !== null
                              ? t('Saved')
                              : t('Save draft')
                    }}
                </Button>
                <Button
                    type="button"
                    :disabled="
                        draftForm.processing ||
                        submitForm.processing ||
                        submittingForApproval
                    "
                    @click="submitForApproval"
                >
                    <Send class="size-4" />
                    {{ t('Send for approval') }}
                </Button>
            </div>
        </div>

        <div
            v-if="permissions.review"
            class="space-y-3 border-t border-border pt-4"
        >
            <label
                for="inquiry-response-review-comment"
                class="text-sm font-medium"
            >
                {{ t('Review comment') }}
            </label>
            <textarea
                id="inquiry-response-review-comment"
                v-model="reviewForm.comment"
                rows="3"
                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:border-blue-500 focus-visible:outline-none dark:focus-visible:border-blue-400"
                :placeholder="
                    t('A comment is required when returning for revision.')
                "
            />
            <InputError :message="reviewForm.errors.comment" />
            <div class="flex flex-wrap justify-end gap-2">
                <Button
                    type="button"
                    variant="outline"
                    :disabled="reviewForm.processing"
                    @click="review('request_changes')"
                >
                    <RotateCcw class="size-4" />
                    {{ t('Return for revision') }}
                </Button>
                <Button
                    type="button"
                    :disabled="reviewForm.processing"
                    @click="review('approve')"
                >
                    <Check class="size-4" />
                    {{ t('Approve response') }}
                </Button>
            </div>
        </div>

        <div
            v-if="permissions.send"
            class="flex justify-end border-t border-border pt-4"
        >
            <Button
                type="button"
                :disabled="sendForm.processing"
                @click="sendResponse"
            >
                <Send class="size-4" />
                {{ t('Send response') }}
            </Button>
        </div>

        <div
            v-if="
                !editable &&
                !permissions.review &&
                !permissions.send &&
                response === null
            "
            class="rounded-lg bg-muted/40 px-4 py-8 text-center text-sm text-muted-foreground"
        >
            {{ t('The response has not been prepared yet.') }}
        </div>
    </section>
</template>
