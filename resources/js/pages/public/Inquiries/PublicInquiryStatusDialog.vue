<script setup lang="ts">
import { useHttp, usePage } from '@inertiajs/vue3';
import {
    CalendarDays,
    CheckCircle2,
    Clock3,
    KeyRound,
    LoaderCircle,
    MessageSquareText,
    RefreshCw,
    Search,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { status } from '@/actions/App/Http/Controllers/PublicInquiryController';
import {
    Dialog,
    DialogDescription,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTranslations } from '@/composables/useTranslations';

type InquiryStatus =
    | 'new'
    | 'in_progress'
    | 'suspended'
    | 'completed'
    | 'rejected'
    | 'withdrawn';

type StatusResponse = {
    number: string;
    status: InquiryStatus;
    submittedAt: string;
    updatedAt: string | null;
    responseAvailable: boolean;
};

const props = defineProps<{ open: boolean }>();
const emit = defineEmits<{
    'update:open': [open: boolean];
    'view-response': [credentials: { number: string; accessCode: string }];
}>();

const { t } = useTranslations();
const page = usePage();
const result = ref<StatusResponse | null>(null);
const localError = ref('');
const http = useHttp<{ access_code: string }, StatusResponse>({
    access_code: '',
});

const statusLabel = computed(() => {
    if (!result.value) {
        return '';
    }

    return {
        new: t('New'),
        in_progress: t('In progress'),
        suspended: t('Suspended'),
        completed: t('Completed'),
        rejected: t('Rejected'),
        withdrawn: t('Withdrawn by applicant'),
    }[result.value.status];
});

const statusColor = computed(() => {
    if (!result.value) {
        return 'text-slate-700';
    }

    return {
        new: 'text-blue-700',
        in_progress: 'text-amber-700',
        suspended: 'text-violet-700',
        completed: 'text-emerald-700',
        rejected: 'text-red-700',
        withdrawn: 'text-slate-700',
    }[result.value.status];
});

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat(String(page.props.locale.current ?? 'ru'), {
        dateStyle: 'long',
    }).format(new Date(value));
}

function formatAccessCode(code: string): string {
    const normalized = code
        .toUpperCase()
        .replace(/[^A-HJ-NP-Z2-9]/g, '')
        .slice(0, 12);

    return normalized.match(/.{1,4}/g)?.join('-') ?? '';
}

async function checkStatus(preserveResult = false): Promise<void> {
    localError.value = '';

    if (!preserveResult) {
        result.value = null;
    }

    http.clearErrors();
    http.access_code = formatAccessCode(http.access_code);

    if (http.access_code.replaceAll('-', '').length !== 12) {
        http.setError('access_code', t('Enter a valid access code.'));
    }

    if (http.hasErrors) {
        return;
    }

    try {
        const response = await http.post(status.url(), {
            onHttpException: (response) => {
                if (response.status === 429) {
                    localError.value = t(
                        'Too many status checks. Please try again in a minute.',
                    );
                }
            },
            onNetworkError: () => {
                localError.value = t(
                    'The status could not be checked. Please try again.',
                );
            },
        });

        if (response) {
            result.value = response;
        }
    } catch {
        if (!localError.value) {
            localError.value = t(
                'The status could not be checked. Please try again.',
            );
        }
    }
}

function viewResponse(): void {
    if (!result.value?.responseAvailable) {
        return;
    }

    emit('view-response', {
        number: result.value.number,
        accessCode: http.access_code,
    });
}

function resetSearch(): void {
    result.value = null;
    localError.value = '';
    http.resetAndClearErrors();
}

watch(
    () => http.access_code,
    (accessCode) => {
        const formatted = formatAccessCode(accessCode);

        if (formatted !== accessCode) {
            http.access_code = formatted;
        }
    },
);

watch(
    () => props.open,
    (open) => {
        if (!open) {
            resetSearch();
        }
    },
);
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogScrollContent
            overlay-class="bg-slate-950/20 backdrop-blur-sm"
            class="mx-0 mt-auto mb-0 w-full max-w-none gap-0 self-end overflow-hidden rounded-t-2xl rounded-b-none border-0 bg-white p-0 pb-[env(safe-area-inset-bottom)] text-slate-900 shadow-xl sm:mx-3 sm:my-8 sm:max-w-[31rem] sm:self-auto sm:rounded-2xl sm:border sm:border-slate-200 sm:pb-0 [&>button]:rounded-xl [&>button]:p-1.5 [&>button]:hover:bg-slate-100"
        >
            <span
                class="mx-auto mt-2.5 h-1.5 w-9 rounded-full bg-slate-300 sm:hidden"
                aria-hidden="true"
            />

            <DialogHeader
                class="gap-1.5 px-5 pt-4 pb-5 text-left sm:px-8 sm:pt-8"
            >
                <DialogTitle
                    class="pr-8 text-xl leading-7 font-semibold tracking-[-0.02em] text-slate-950"
                >
                    {{ t('Check inquiry status') }}
                </DialogTitle>
                <DialogDescription class="text-sm leading-5 text-slate-500">
                    {{ t('Enter the access code issued after submission.') }}
                </DialogDescription>
            </DialogHeader>

            <div v-if="result" class="px-5 pb-6 sm:px-8 sm:pb-8">
                <div class="border-y border-slate-200 py-5">
                    <div class="mb-5 flex items-start gap-3">
                        <CheckCircle2
                            class="mt-0.5 size-5 shrink-0 text-[#1875e6]"
                            :stroke-width="2"
                        />
                        <div>
                            <p class="text-xs font-medium text-slate-500">
                                {{ t('Inquiry number') }}
                            </p>
                            <p
                                class="mt-1 text-lg font-semibold text-slate-950"
                            >
                                {{ result.number }}
                            </p>
                        </div>
                    </div>

                    <dl class="grid grid-cols-2 gap-x-4 gap-y-5">
                        <div>
                            <dt class="text-xs font-medium text-slate-500">
                                {{ t('Status') }}
                            </dt>
                            <dd
                                class="mt-1 text-sm font-semibold"
                                :class="statusColor"
                            >
                                {{ statusLabel }}
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="flex items-center gap-1.5 text-xs font-medium text-slate-500"
                            >
                                <CalendarDays class="size-3.5" />
                                {{ t('Submitted') }}
                            </dt>
                            <dd class="mt-1 text-sm font-medium text-slate-800">
                                {{ formatDate(result.submittedAt) }}
                            </dd>
                        </div>
                        <div class="col-span-2">
                            <dt
                                class="flex items-center gap-1.5 text-xs font-medium text-slate-500"
                            >
                                <Clock3 class="size-3.5" />
                                {{ t('Last updated') }}
                            </dt>
                            <dd class="mt-1 text-sm font-medium text-slate-800">
                                {{ formatDate(result.updatedAt) }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <button
                    v-if="result.responseAvailable"
                    type="button"
                    class="mt-5 flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-[#1875e6] px-4 text-sm font-semibold text-white transition-colors hover:bg-[#1267ce] active:bg-blue-800"
                    @click="viewResponse"
                >
                    <MessageSquareText class="size-4" />
                    {{ t('View response') }}
                </button>

                <button
                    type="button"
                    class="mt-3 flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-800 transition-colors hover:bg-slate-50 active:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="http.processing"
                    @click="checkStatus(true)"
                >
                    <LoaderCircle
                        v-if="http.processing"
                        class="size-4 animate-spin"
                    />
                    <RefreshCw v-else class="size-4" />
                    {{ t('Refresh status') }}
                </button>

                <button
                    type="button"
                    class="mt-2 min-h-10 w-full rounded-xl px-4 text-sm font-semibold text-[#1875e6] transition-colors hover:bg-blue-50 active:bg-blue-100"
                    @click="resetSearch"
                >
                    {{ t('Check another inquiry') }}
                </button>
            </div>

            <form
                v-else
                class="px-5 pb-6 sm:px-8 sm:pb-8"
                @submit.prevent="checkStatus()"
            >
                <label
                    for="public-inquiry-access-code"
                    class="block text-sm font-medium text-slate-800"
                >
                    {{ t('Access code') }}
                </label>
                <div class="relative mt-2">
                    <KeyRound
                        class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400"
                    />
                    <input
                        id="public-inquiry-access-code"
                        v-model="http.access_code"
                        type="text"
                        inputmode="text"
                        autocomplete="one-time-code"
                        autocapitalize="characters"
                        spellcheck="false"
                        maxlength="14"
                        :placeholder="t('For example: 7KMP-9RXT-4WQ2')"
                        class="h-12 w-full rounded-xl border border-slate-300 bg-slate-50 pr-4 pl-10 font-mono text-base font-semibold tracking-[0.08em] text-slate-950 transition-colors outline-none placeholder:text-sm placeholder:font-normal placeholder:tracking-normal placeholder:text-slate-400 focus:border-[#1875e6] focus:ring-2 focus:ring-blue-600/15"
                        :aria-invalid="Boolean(http.errors.access_code)"
                        @input="
                            http.clearErrors('access_code');
                            localError = '';
                        "
                    />
                </div>
                <p
                    v-if="http.errors.access_code"
                    class="mt-2 text-sm leading-5 text-red-600"
                    role="alert"
                >
                    {{ http.errors.access_code }}
                </p>

                <p
                    v-if="localError"
                    class="mt-3 text-sm leading-5 text-red-600"
                    role="alert"
                >
                    {{ localError }}
                </p>

                <button
                    type="submit"
                    class="mt-5 flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-[#1875e6] px-5 text-sm font-semibold text-white transition-colors hover:bg-[#1267ce] active:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="http.processing"
                >
                    <LoaderCircle
                        v-if="http.processing"
                        class="size-4 animate-spin"
                    />
                    <Search v-else class="size-4" />
                    {{ http.processing ? t('Checking…') : t('Check status') }}
                </button>
            </form>
        </DialogScrollContent>
    </Dialog>
</template>
