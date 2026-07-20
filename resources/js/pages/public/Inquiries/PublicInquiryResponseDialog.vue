<script setup lang="ts">
import { useHttp, usePage } from '@inertiajs/vue3';
import { CalendarDays, FileText, LoaderCircle } from '@lucide/vue';
import { ref, watch } from 'vue';
import { response as fetchResponse } from '@/actions/App/Http/Controllers/PublicInquiryController';
import {
    Dialog,
    DialogDescription,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTranslations } from '@/composables/useTranslations';

type ResponseResult = {
    number: string;
    body: string;
    sentAt: string | null;
};

const props = defineProps<{
    open: boolean;
    number: string;
    accessCode: string;
}>();
const emit = defineEmits<{ 'update:open': [open: boolean] }>();

const { t } = useTranslations();
const page = usePage();
const result = ref<ResponseResult | null>(null);
const localError = ref('');
const http = useHttp<{ access_code: string }, ResponseResult>({
    access_code: '',
});

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat(String(page.props.locale.current ?? 'ru'), {
        dateStyle: 'long',
        timeStyle: 'short',
    }).format(new Date(value));
}

async function loadResponse(): Promise<void> {
    result.value = null;
    localError.value = '';
    http.clearErrors();
    http.access_code = props.accessCode;

    try {
        const response = await http.post(fetchResponse.url(), {
            onError: (errors) => {
                localError.value = Object.values(errors)[0] ?? '';
            },
            onHttpException: (response) => {
                localError.value =
                    response.status === 429
                        ? t(
                              'Too many status checks. Please try again in a minute.',
                          )
                        : t(
                              'The response could not be loaded. Please try again.',
                          );
            },
            onNetworkError: () => {
                localError.value = t(
                    'The response could not be loaded. Please try again.',
                );
            },
        });

        if (response) {
            result.value = response;
        }
    } catch {
        if (!localError.value) {
            localError.value = t(
                'The response could not be loaded. Please try again.',
            );
        }
    }
}

watch(
    () => props.open,
    (open) => {
        if (open) {
            void loadResponse();
        } else {
            result.value = null;
            localError.value = '';
            http.resetAndClearErrors();
        }
    },
);
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogScrollContent
            overlay-class="bg-slate-950/20 backdrop-blur-sm"
            class="mx-0 mt-auto mb-0 w-full max-w-none gap-0 self-end overflow-hidden rounded-t-2xl rounded-b-none border-0 bg-white p-0 pb-[env(safe-area-inset-bottom)] text-slate-900 shadow-xl sm:mx-3 sm:my-8 sm:max-w-[38rem] sm:self-auto sm:rounded-2xl sm:border sm:border-slate-200 sm:pb-0 [&>button]:rounded-xl [&>button]:p-1.5 [&>button]:hover:bg-slate-100"
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
                    {{ t('Response to inquiry') }}
                </DialogTitle>
                <DialogDescription class="text-sm leading-5 text-slate-500">
                    {{ props.number }}
                </DialogDescription>
            </DialogHeader>

            <div
                v-if="http.processing"
                class="flex min-h-56 flex-col items-center justify-center gap-3 px-5 pb-8 text-slate-500"
                role="status"
            >
                <LoaderCircle class="size-6 animate-spin text-[#1875e6]" />
                <p class="text-sm font-medium">{{ t('Loading response…') }}</p>
            </div>

            <div v-else-if="result" class="px-5 pb-6 sm:px-8 sm:pb-8">
                <div
                    class="mb-4 flex items-center justify-between gap-4 border-y border-slate-200 py-3 text-xs text-slate-500"
                >
                    <span class="flex items-center gap-2 font-medium">
                        <FileText class="size-4 text-[#1875e6]" />
                        {{ t('Official response') }}
                    </span>
                    <span class="flex items-center gap-1.5 text-right">
                        <CalendarDays class="size-3.5" />
                        {{ formatDate(result.sentAt) }}
                    </span>
                </div>

                <div
                    class="max-h-[55vh] overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm leading-6 whitespace-pre-wrap text-slate-800 sm:px-5 sm:py-5"
                >
                    {{ result.body }}
                </div>

                <button
                    type="button"
                    class="mt-5 min-h-12 w-full rounded-xl bg-[#1875e6] px-5 text-sm font-semibold text-white transition-colors hover:bg-[#1267ce] active:bg-blue-800"
                    @click="emit('update:open', false)"
                >
                    {{ t('Close') }}
                </button>
            </div>

            <div v-else class="px-5 pb-6 sm:px-8 sm:pb-8">
                <p class="text-sm leading-6 text-red-600" role="alert">
                    {{ localError }}
                </p>
                <button
                    type="button"
                    class="mt-5 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                    @click="loadResponse"
                >
                    {{ t('Try again') }}
                </button>
            </div>
        </DialogScrollContent>
    </Dialog>
</template>
