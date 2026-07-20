<script setup lang="ts">
import type { FormDataConvertible } from '@inertiajs/core';
import { Form, router, usePage } from '@inertiajs/vue3';
import {
    BrainCircuit,
    Camera,
    CheckCircle2,
    ChevronLeft,
    CircleAlert,
    Clock3,
    Copy,
    EyeOff,
    FileText,
    Mic,
    Mail,
    Paperclip,
    Send,
    ShieldCheck,
    Square,
    X,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { store } from '@/actions/App/Http/Controllers/PublicInquiryController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslations } from '@/composables/useTranslations';

type Category = {
    id: number;
    name: string;
    description: string;
};

type Props = {
    open: boolean;
    categories: Category[];
    initialMode?: 'anonymous' | 'identified';
    aiScreeningEnabled: boolean;
    alternativeInquiriesEmail: string;
    submissionNumber?: string | null;
    submissionAccessCode?: string | null;
};

type ScreeningDialogState = 'idle' | 'analyzing' | 'accepted' | 'rejected';

const props = withDefaults(defineProps<Props>(), {
    initialMode: 'anonymous',
    submissionNumber: null,
    submissionAccessCode: null,
});

const emit = defineEmits<{
    'update:open': [open: boolean];
    accepted: [];
    back: [];
}>();

const { t } = useTranslations();
const page = usePage();
const submissionMode = ref<'anonymous' | 'identified'>(props.initialMode);
const selectedCategoryId = ref('');
const fileInput = ref<HTMLInputElement | null>(null);
const photoInput = ref<HTMLInputElement | null>(null);
const attachments = ref<File[]>([]);
const attachmentError = ref('');
const isRecording = ref(false);
const recordingSeconds = ref(0);
const rateLimitOpen = ref(false);
const rateLimitSeconds = ref(0);
const initialAdmissionError =
    typeof (page.props.errors as Record<string, unknown>)?.admission ===
    'string'
        ? String((page.props.errors as Record<string, unknown>).admission)
        : '';
const admissionError = ref(initialAdmissionError);
const copiedDetails = ref(false);
const screeningDialogState = ref<ScreeningDialogState>(
    props.submissionNumber
        ? 'accepted'
        : props.aiScreeningEnabled && admissionError.value
          ? 'rejected'
          : 'idle',
);
let recorder: MediaRecorder | null = null;
let microphoneStream: MediaStream | null = null;
let recordingTimer: ReturnType<typeof setInterval> | null = null;
let recordingChunks: Blob[] = [];
let discardRecording = false;
let rateLimitTimer: ReturnType<typeof setInterval> | null = null;
let removeHttpExceptionListener: VoidFunction | null = null;

const formattedRateLimitTime = computed(() => {
    const minutes = Math.floor(rateLimitSeconds.value / 60)
        .toString()
        .padStart(2, '0');
    const seconds = (rateLimitSeconds.value % 60).toString().padStart(2, '0');

    return `${minutes}:${seconds}`;
});
const alternativeInquiriesMailto = computed(
    () => `mailto:${props.alternativeInquiriesEmail}`,
);

const acceptedExtensions = [
    'pdf',
    'doc',
    'docx',
    'xls',
    'xlsx',
    'txt',
    'jpg',
    'jpeg',
    'png',
    'heic',
    'heif',
    'webp',
    'gif',
    'avif',
    'mp3',
    'm4a',
    'wav',
    'ogg',
    'webm',
];
const acceptedFiles = acceptedExtensions
    .map((extension) => `.${extension}`)
    .join(',');

watch(
    () => props.initialMode,
    (mode) => {
        submissionMode.value = mode;
    },
);

watch(
    () => props.submissionNumber,
    (number) => {
        copiedDetails.value = false;

        if (number) {
            screeningDialogState.value = 'accepted';
        }
    },
);

watch(
    () => props.open,
    (open) => {
        if (!open && isRecording.value) {
            stopVoiceRecording(true);
        }

        if (!open) {
            screeningDialogState.value = 'idle';
            admissionError.value = '';
        }
    },
);

onMounted(() => {
    removeHttpExceptionListener = router.on('httpException', (event) => {
        if (event.detail.response.status !== 429) {
            return;
        }

        event.preventDefault();

        const retryAfterHeader =
            event.detail.response.headers['retry-after'] ??
            event.detail.response.headers['Retry-After'];
        const retryAfter = Number.parseInt(retryAfterHeader ?? '60', 10);

        showRateLimitDialog(Number.isFinite(retryAfter) ? retryAfter : 60);
    });
});

onBeforeUnmount(() => {
    stopVoiceRecording(true);
    stopRateLimitTimer();
    removeHttpExceptionListener?.();
});

function showRateLimitDialog(seconds: number): void {
    stopRateLimitTimer();
    screeningDialogState.value = 'idle';
    admissionError.value = '';
    rateLimitSeconds.value = Math.max(1, seconds);
    rateLimitOpen.value = true;

    rateLimitTimer = setInterval(() => {
        if (rateLimitSeconds.value <= 1) {
            rateLimitSeconds.value = 0;
            stopRateLimitTimer();

            return;
        }

        rateLimitSeconds.value -= 1;
    }, 1000);
}

function handleSubmissionStart(): void {
    admissionError.value = '';

    if (props.aiScreeningEnabled) {
        screeningDialogState.value = 'analyzing';
    }
}

function handleSubmissionError(errors: Record<string, string>): void {
    admissionError.value = errors.admission ?? '';
    screeningDialogState.value = admissionError.value ? 'rejected' : 'idle';
}

function closeScreeningDialog(): void {
    if (screeningDialogState.value !== 'analyzing') {
        screeningDialogState.value = 'idle';
        admissionError.value = '';
    }
}

function finishAcceptedSubmission(): void {
    screeningDialogState.value = 'idle';
    emit('accepted');
    emit('update:open', false);
}

async function copySubmissionDetails(): Promise<void> {
    if (!props.submissionNumber || !props.submissionAccessCode) {
        return;
    }

    try {
        await navigator.clipboard.writeText(
            `${t('Inquiry number')}: ${props.submissionNumber}\n${t('Access code')}: ${props.submissionAccessCode}`,
        );
        copiedDetails.value = true;
    } catch {
        copiedDetails.value = false;
    }
}

function stopRateLimitTimer(): void {
    if (rateLimitTimer !== null) {
        clearInterval(rateLimitTimer);
        rateLimitTimer = null;
    }
}

function updateRateLimitOpen(open: boolean): void {
    rateLimitOpen.value = open;

    if (!open) {
        stopRateLimitTimer();
    }
}

function transformFormData(
    data: Record<string, FormDataConvertible>,
): Record<string, FormDataConvertible> {
    return {
        ...data,
        inquiry_category_id: selectedCategoryId.value,
        attachments: attachments.value,
    };
}

function fileKey(file: File): string {
    return `${file.name}:${file.size}:${file.lastModified}`;
}

function fileExtension(file: File): string {
    const dotPosition = file.name.lastIndexOf('.');

    return dotPosition > 0
        ? file.name.slice(dotPosition + 1).toLowerCase()
        : '';
}

function isSupportedFile(file: File, capturedPhoto: boolean): boolean {
    const extension = fileExtension(file);

    if (acceptedExtensions.includes(extension)) {
        return true;
    }

    if (file.type.startsWith('image/') || file.type.startsWith('audio/')) {
        return true;
    }

    return capturedPhoto && extension === '';
}

function addFiles(files: FileList | File[], capturedPhoto = false): void {
    attachmentError.value = '';
    const next = [...attachments.value];
    const keys = new Set(next.map(fileKey));

    for (const file of Array.from(files)) {
        if (!isSupportedFile(file, capturedPhoto)) {
            attachmentError.value = t('This file type is not supported.');
            continue;
        }

        if (file.size > 20 * 1024 * 1024) {
            attachmentError.value = t('Each attachment must not exceed 20 MB.');
            continue;
        }

        if (next.length >= 5) {
            attachmentError.value = t('You can attach up to 5 files.');
            break;
        }

        if (!keys.has(fileKey(file))) {
            next.push(file);
            keys.add(fileKey(file));
        }
    }

    attachments.value = next;
}

function handleFileInputChange(event: Event, capturedPhoto = false): void {
    const input = event.target as HTMLInputElement;

    addFiles(input.files ?? [], capturedPhoto);
    input.value = '';
}

function removeFile(index: number): void {
    attachments.value = attachments.value.filter(
        (_, fileIndex) => fileIndex !== index,
    );
}

function formatFileSize(bytes: number): string {
    return bytes < 1024 * 1024
        ? `${Math.max(1, Math.round(bytes / 1024))} KB`
        : `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}

function formatRecordingTime(seconds: number): string {
    const minutes = Math.floor(seconds / 60)
        .toString()
        .padStart(2, '0');
    const remainingSeconds = (seconds % 60).toString().padStart(2, '0');

    return `${minutes}:${remainingSeconds}`;
}

function preferredAudioMimeType(): string | undefined {
    return [
        'audio/mp4',
        'audio/webm;codecs=opus',
        'audio/webm',
        'audio/ogg;codecs=opus',
    ].find((mimeType) => MediaRecorder.isTypeSupported(mimeType));
}

function audioExtension(mimeType: string): string {
    if (mimeType.includes('mp4')) {
        return 'm4a';
    }

    if (mimeType.includes('ogg')) {
        return 'ogg';
    }

    return 'webm';
}

async function startVoiceRecording(): Promise<void> {
    attachmentError.value = '';

    if (
        typeof MediaRecorder === 'undefined' ||
        !navigator.mediaDevices?.getUserMedia
    ) {
        attachmentError.value = t(
            'Voice recording is not supported on this device.',
        );

        return;
    }

    if (attachments.value.length >= 5) {
        attachmentError.value = t('You can attach up to 5 files.');

        return;
    }

    try {
        microphoneStream = await navigator.mediaDevices.getUserMedia({
            audio: true,
        });
        const mimeType = preferredAudioMimeType();
        recorder = mimeType
            ? new MediaRecorder(microphoneStream, { mimeType })
            : new MediaRecorder(microphoneStream);
        recordingChunks = [];
        discardRecording = false;
        recordingSeconds.value = 0;

        recorder.addEventListener('dataavailable', (event) => {
            if (event.data.size > 0) {
                recordingChunks.push(event.data);
            }
        });
        recorder.addEventListener('stop', () => {
            const recordedMimeType =
                recorder?.mimeType || mimeType || 'audio/webm';

            if (!discardRecording && recordingChunks.length > 0) {
                const recording = new File(
                    recordingChunks,
                    `voice-message-${Date.now()}.${audioExtension(recordedMimeType)}`,
                    { type: recordedMimeType },
                );
                addFiles([recording]);
            }

            releaseMicrophone();
        });

        recorder.start(250);
        isRecording.value = true;
        recordingTimer = setInterval(() => {
            recordingSeconds.value += 1;
        }, 1000);
    } catch {
        releaseMicrophone();
        attachmentError.value = t('Unable to access the microphone.');
    }
}

function stopVoiceRecording(discard = false): void {
    discardRecording = discard;

    if (recorder?.state === 'recording') {
        recorder.stop();

        return;
    }

    releaseMicrophone();
}

function releaseMicrophone(): void {
    microphoneStream?.getTracks().forEach((track) => track.stop());
    microphoneStream = null;
    recorder = null;
    recordingChunks = [];
    isRecording.value = false;

    if (recordingTimer !== null) {
        clearInterval(recordingTimer);
        recordingTimer = null;
    }
}

function goBack(): void {
    if (isRecording.value) {
        stopVoiceRecording(true);
    }

    emit('update:open', false);
    emit('back');
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogScrollContent
            overlay-class="bg-slate-950/20 backdrop-blur-sm"
            class="mx-0 my-0 h-[100dvh] max-h-[100dvh] max-w-none gap-0 overflow-y-auto rounded-none border-0 bg-white p-0 text-slate-900 sm:mx-3 sm:my-8 sm:h-auto sm:max-h-[calc(100dvh-4rem)] sm:max-w-3xl sm:rounded-2xl sm:border sm:border-slate-200 sm:shadow-xl [&>button]:hidden sm:[&>button]:inline-flex"
        >
            <DialogHeader
                class="sticky top-0 z-20 h-14 shrink-0 justify-center gap-1 border-b border-slate-200 bg-white px-5 py-0 text-center sm:static sm:h-auto sm:gap-1.5 sm:px-8 sm:pt-8 sm:pb-6 sm:text-left"
            >
                <button
                    type="button"
                    class="absolute top-1/2 left-2 flex min-h-11 -translate-y-1/2 items-center gap-0.5 rounded-full px-2 text-sm font-medium text-[#007aff] active:bg-black/5 sm:hidden"
                    @click="goBack"
                >
                    <ChevronLeft class="size-5" :stroke-width="2" />
                    {{ t('Back') }}
                </button>
                <DialogTitle
                    class="px-16 text-[1.0625rem] leading-6 font-semibold tracking-[-0.015em] text-slate-950 sm:px-0 sm:text-2xl sm:tracking-[-0.025em]"
                >
                    {{ t('Submit an inquiry') }}
                </DialogTitle>
                <DialogDescription
                    class="hidden text-sm text-slate-500 sm:block"
                >
                    {{
                        t(
                            'Describe the situation. You may remain anonymous or provide contact details for a response.',
                        )
                    }}
                </DialogDescription>
            </DialogHeader>

            <Form
                v-bind="store.form()"
                :transform="transformFormData"
                class="grid gap-5 px-4 py-5 sm:gap-6 sm:px-8 sm:py-7"
                @start="handleSubmissionStart"
                @error="handleSubmissionError"
                @cancel="screeningDialogState = 'idle'"
                #default="{ errors, processing, progress, clearErrors }"
            >
                <fieldset class="grid gap-3">
                    <legend class="text-sm font-bold text-slate-800">
                        {{ t('How would you like to submit?') }}
                    </legend>
                    <div
                        class="grid overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/40 sm:grid-cols-2 sm:gap-3 sm:overflow-visible sm:rounded-none sm:border-0 sm:bg-transparent"
                    >
                        <label
                            class="flex min-h-20 cursor-pointer gap-3 border-b p-4 transition-all last:border-b-0 sm:rounded-2xl sm:border-0 sm:ring-1"
                            :class="
                                submissionMode === 'anonymous'
                                    ? 'border-slate-200 bg-blue-50 sm:ring-2 sm:ring-[#1875e6]/50'
                                    : 'border-slate-200 bg-white hover:bg-slate-50 sm:ring-1 sm:ring-slate-200 sm:hover:border-slate-300'
                            "
                        >
                            <input
                                v-model="submissionMode"
                                type="radio"
                                name="submission_mode"
                                value="anonymous"
                                class="mt-1 accent-[#1875e6]"
                            />
                            <span>
                                <span
                                    class="flex items-center gap-2 text-sm font-bold"
                                >
                                    <EyeOff class="size-4 text-[#1875e6]" />
                                    {{ t('Anonymous') }}
                                </span>
                                <span
                                    class="mt-1 block text-xs leading-5 text-slate-500"
                                >
                                    {{
                                        t(
                                            'Your name and contact details will not be requested.',
                                        )
                                    }}
                                </span>
                            </span>
                        </label>

                        <label
                            class="flex min-h-20 cursor-pointer gap-3 border-b p-4 transition-all last:border-b-0 sm:rounded-2xl sm:border-0 sm:ring-1"
                            :class="
                                submissionMode === 'identified'
                                    ? 'border-slate-200 bg-blue-50 sm:ring-2 sm:ring-[#1875e6]/50'
                                    : 'border-slate-200 bg-white hover:bg-slate-50 sm:ring-1 sm:ring-slate-200 sm:hover:border-slate-300'
                            "
                        >
                            <input
                                v-model="submissionMode"
                                type="radio"
                                name="submission_mode"
                                value="identified"
                                class="mt-1 accent-[#1875e6]"
                            />
                            <span>
                                <span
                                    class="flex items-center gap-2 text-sm font-bold"
                                >
                                    <ShieldCheck
                                        class="size-4 text-[#1875e6]"
                                    />
                                    {{ t('Provide contact details') }}
                                </span>
                                <span
                                    class="mt-1 block text-xs leading-5 text-slate-500"
                                >
                                    {{
                                        t(
                                            'Provide your name and at least one way to contact you.',
                                        )
                                    }}
                                </span>
                            </span>
                        </label>
                    </div>
                    <InputError :message="errors.submission_mode" />
                </fieldset>

                <div class="grid gap-2">
                    <Label for="inquiry-category">{{ t('Category') }}</Label>
                    <Select v-model="selectedCategoryId" required>
                        <SelectTrigger
                            id="inquiry-category"
                            class="w-full rounded-[10px] border border-slate-200 bg-slate-50 px-4 text-base shadow-none ring-0 transition-colors outline-none hover:border-blue-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 data-[placeholder]:text-slate-400 data-[size=default]:h-12 sm:text-sm"
                            :aria-invalid="Boolean(errors.inquiry_category_id)"
                        >
                            <SelectValue :placeholder="t('Select category')" />
                        </SelectTrigger>
                        <SelectContent
                            class="max-h-72 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg"
                        >
                            <SelectItem
                                v-for="category in categories"
                                :key="category.id"
                                :value="String(category.id)"
                                class="min-h-11 rounded-lg px-3 text-[0.9375rem] focus:bg-slate-100"
                            >
                                {{ category.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.inquiry_category_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="inquiry-title">{{ t('Subject') }}</Label>
                    <Input
                        id="inquiry-title"
                        name="title"
                        required
                        maxlength="255"
                        class="h-12 rounded-[10px] border border-slate-200 bg-slate-50 px-4 text-base shadow-none ring-0 transition-colors hover:border-blue-400 focus-visible:border-blue-600 focus-visible:ring-2 focus-visible:ring-blue-600/20 sm:text-sm"
                    />
                    <InputError :message="errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="inquiry-description">{{
                        t('Description')
                    }}</Label>
                    <textarea
                        id="inquiry-description"
                        name="description"
                        required
                        maxlength="10000"
                        rows="6"
                        class="min-h-40 w-full resize-y rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3 text-base shadow-none ring-0 transition-colors outline-none placeholder:text-slate-400 hover:border-blue-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 sm:min-h-36 sm:text-sm"
                        :placeholder="
                            t(
                                'Describe what happened and include relevant dates, places, and details.',
                            )
                        "
                    />
                    <InputError :message="errors.description" />
                </div>

                <section class="grid gap-2.5">
                    <div class="flex items-center justify-between gap-3">
                        <Label>{{ t('Attachments') }}</Label>
                        <span class="text-xs text-slate-400">
                            {{ attachments.length }}/5
                        </span>
                    </div>

                    <div
                        class="grid grid-cols-3 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/40 sm:gap-3 sm:overflow-visible sm:rounded-none sm:border-0 sm:bg-transparent"
                    >
                        <button
                            type="button"
                            class="flex min-h-20 flex-col items-center justify-center gap-1.5 border-r border-slate-200 bg-white px-2 text-center text-xs font-medium text-slate-700 transition-colors active:bg-slate-100 sm:rounded-xl sm:border sm:border-slate-200 sm:hover:border-slate-300 sm:hover:bg-slate-50"
                            @click="fileInput?.click()"
                        >
                            <Paperclip
                                class="size-6 text-[#007aff]"
                                :stroke-width="1.8"
                            />
                            {{ t('Add files') }}
                        </button>
                        <button
                            type="button"
                            class="flex min-h-20 flex-col items-center justify-center gap-1.5 border-r border-slate-200 bg-white px-2 text-center text-xs font-medium text-slate-700 transition-colors active:bg-slate-100 sm:rounded-xl sm:border sm:border-slate-200 sm:hover:border-slate-300 sm:hover:bg-slate-50"
                            @click="photoInput?.click()"
                        >
                            <Camera
                                class="size-6 text-[#007aff]"
                                :stroke-width="1.8"
                            />
                            {{ t('Take photo') }}
                        </button>
                        <button
                            type="button"
                            class="flex min-h-20 flex-col items-center justify-center gap-1.5 bg-white px-2 text-center text-xs font-medium transition-colors active:bg-slate-100 sm:rounded-xl sm:border sm:border-slate-200 sm:hover:border-slate-300 sm:hover:bg-slate-50"
                            :class="
                                isRecording
                                    ? 'bg-red-50 text-red-600'
                                    : 'text-slate-700'
                            "
                            @click="
                                isRecording
                                    ? stopVoiceRecording()
                                    : startVoiceRecording()
                            "
                        >
                            <Square
                                v-if="isRecording"
                                class="size-5 fill-current"
                            />
                            <Mic
                                v-else
                                class="size-6 text-[#007aff]"
                                :stroke-width="1.8"
                            />
                            <span v-if="isRecording">
                                {{ formatRecordingTime(recordingSeconds) }}
                            </span>
                            <span v-else>{{ t('Record voice') }}</span>
                        </button>
                    </div>

                    <input
                        ref="fileInput"
                        type="file"
                        class="hidden"
                        :accept="acceptedFiles"
                        multiple
                        @change="handleFileInputChange($event)"
                    />
                    <input
                        ref="photoInput"
                        type="file"
                        class="hidden"
                        accept="image/*"
                        capture="environment"
                        @change="handleFileInputChange($event, true)"
                    />

                    <div
                        v-if="attachments.length"
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
                    >
                        <div
                            v-for="(file, index) in attachments"
                            :key="fileKey(file)"
                            class="flex min-h-14 items-center gap-3 border-b border-black/5 px-4 last:border-b-0"
                        >
                            <FileText
                                class="size-5 shrink-0 text-slate-400"
                                :stroke-width="1.8"
                            />
                            <span class="min-w-0 flex-1">
                                <span
                                    class="block truncate text-sm font-medium text-slate-800"
                                >
                                    {{ file.name }}
                                </span>
                                <span class="block text-xs text-slate-400">
                                    {{ formatFileSize(file.size) }}
                                </span>
                            </span>
                            <button
                                type="button"
                                class="flex size-10 shrink-0 items-center justify-center rounded-full text-slate-400 active:bg-black/5"
                                :aria-label="`${t('Remove attachment')} ${file.name}`"
                                @click="removeFile(index)"
                            >
                                <X class="size-4" />
                            </button>
                        </div>
                    </div>

                    <InputError
                        :message="
                            attachmentError ||
                            errors.attachments ||
                            (errors as Record<string, string>)['attachments.0']
                        "
                    />
                </section>

                <div
                    v-if="submissionMode === 'identified'"
                    class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50/40 p-4 sm:grid-cols-2 sm:p-5"
                >
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="applicant-name">{{ t('Full name') }}</Label>
                        <Input
                            id="applicant-name"
                            name="applicant_name"
                            required
                            autocomplete="name"
                            class="h-12 rounded-[10px] border border-slate-200 bg-white px-4 shadow-none ring-0 hover:border-blue-400 focus-visible:border-blue-600 focus-visible:ring-2 focus-visible:ring-blue-600/20"
                        />
                        <InputError :message="errors.applicant_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="applicant-email">{{ t('Email') }}</Label>
                        <Input
                            id="applicant-email"
                            name="applicant_email"
                            type="email"
                            autocomplete="email"
                            class="h-12 rounded-[10px] border border-slate-200 bg-white px-4 shadow-none ring-0 hover:border-blue-400 focus-visible:border-blue-600 focus-visible:ring-2 focus-visible:ring-blue-600/20"
                        />
                        <InputError :message="errors.applicant_email" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="applicant-phone">{{ t('Phone') }}</Label>
                        <Input
                            id="applicant-phone"
                            name="applicant_phone"
                            autocomplete="tel"
                            class="h-12 rounded-[10px] border border-slate-200 bg-white px-4 shadow-none ring-0 hover:border-blue-400 focus-visible:border-blue-600 focus-visible:ring-2 focus-visible:ring-blue-600/20"
                        />
                        <InputError :message="errors.applicant_phone" />
                    </div>
                    <p class="text-xs text-slate-500 sm:col-span-2">
                        {{
                            t(
                                'Enter an email address or phone number so we can contact you.',
                            )
                        }}
                    </p>
                </div>

                <div
                    class="sticky bottom-0 z-10 -mx-5 flex justify-stretch border-t border-slate-200 bg-white px-5 pt-4 pb-[max(1rem,env(safe-area-inset-bottom))] sm:static sm:mx-0 sm:justify-end sm:bg-transparent sm:px-0 sm:pt-5 sm:pb-0"
                >
                    <div
                        v-if="progress"
                        class="absolute inset-x-0 top-0 h-0.5 overflow-hidden bg-slate-100"
                    >
                        <div
                            class="h-full bg-[#007aff] transition-[width]"
                            :style="{ width: `${progress.percentage ?? 0}%` }"
                        />
                    </div>
                    <Button
                        type="submit"
                        size="lg"
                        :disabled="processing"
                        class="min-h-12 w-full rounded-xl bg-[#1875e6] font-semibold text-white shadow-sm transition-all hover:-translate-y-0.5 hover:bg-blue-700 hover:shadow-md sm:w-auto sm:min-w-48 sm:rounded-full sm:px-7"
                    >
                        <Send class="size-4" />
                        {{
                            processing && aiScreeningEnabled
                                ? t('Checking inquiry…')
                                : processing
                                  ? t('Submitting…')
                                  : t('Submit inquiry')
                        }}
                    </Button>
                </div>

                <Dialog
                    :open="screeningDialogState !== 'idle'"
                    @update:open="
                        (isOpen) => {
                            if (
                                !isOpen &&
                                screeningDialogState !== 'analyzing'
                            ) {
                                clearErrors('admission');
                                closeScreeningDialog();
                            }
                        }
                    "
                >
                    <DialogContent
                        :show-close-button="false"
                        overlay-class="bg-slate-950/20 backdrop-blur-sm"
                        class="w-[calc(100%-2rem)] max-w-md gap-0 overflow-hidden rounded-2xl border border-slate-200 bg-white p-0 text-left shadow-xl"
                    >
                        <template v-if="screeningDialogState === 'analyzing'">
                            <div
                                class="flex min-h-[25rem] flex-col items-center justify-center px-7 py-10 text-center sm:px-10"
                                role="status"
                                aria-live="polite"
                            >
                                <div
                                    class="relative flex size-28 items-center justify-center"
                                >
                                    <div
                                        class="absolute inset-0 rounded-full border border-blue-100"
                                    />
                                    <div
                                        class="absolute inset-2 animate-[spin_1.4s_linear_infinite] rounded-full border-2 border-transparent border-t-[#1875e6] border-r-blue-200"
                                    />
                                    <div
                                        class="absolute inset-5 animate-pulse rounded-full bg-blue-50"
                                    />
                                    <BrainCircuit
                                        class="relative z-10 size-9 text-[#1875e6]"
                                        :stroke-width="1.65"
                                    />
                                </div>

                                <DialogHeader
                                    class="mt-7 items-center gap-2 text-center"
                                >
                                    <DialogTitle
                                        class="text-xl font-semibold tracking-[-0.025em] text-slate-950"
                                    >
                                        {{ t('AI is analyzing the inquiry') }}
                                    </DialogTitle>
                                    <DialogDescription
                                        class="max-w-xs text-sm leading-6 text-slate-500"
                                    >
                                        {{
                                            t(
                                                'The message is being checked against the Speak Up admission criteria. This usually takes a few seconds.',
                                            )
                                        }}
                                    </DialogDescription>
                                </DialogHeader>

                                <div
                                    class="mt-7 flex items-center gap-2"
                                    aria-hidden="true"
                                >
                                    <span
                                        class="size-2 animate-bounce rounded-full bg-[#1875e6] [animation-delay:-0.3s]"
                                    />
                                    <span
                                        class="size-2 animate-bounce rounded-full bg-[#1875e6] [animation-delay:-0.15s]"
                                    />
                                    <span
                                        class="size-2 animate-bounce rounded-full bg-[#1875e6]"
                                    />
                                </div>
                            </div>
                        </template>

                        <template
                            v-else-if="screeningDialogState === 'accepted'"
                        >
                            <div
                                class="flex min-h-[25rem] flex-col items-center justify-center px-7 py-9 text-center sm:px-10"
                                role="status"
                                aria-live="polite"
                            >
                                <div
                                    class="flex size-16 items-center justify-center rounded-full bg-emerald-50 text-emerald-600"
                                >
                                    <CheckCircle2
                                        class="size-9"
                                        :stroke-width="1.8"
                                    />
                                </div>

                                <DialogHeader
                                    class="mt-6 items-center gap-2 text-center"
                                >
                                    <DialogTitle
                                        class="text-2xl font-semibold tracking-[-0.03em] text-slate-950"
                                    >
                                        {{ t('Inquiry submitted') }}
                                    </DialogTitle>
                                    <DialogDescription
                                        class="max-w-xs text-sm leading-6 text-slate-500"
                                    >
                                        {{
                                            t(
                                                'Save the access code to check the status from any device.',
                                            )
                                        }}
                                    </DialogDescription>
                                </DialogHeader>

                                <div
                                    class="mt-5 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white text-left"
                                >
                                    <div class="px-5 py-4">
                                        <p
                                            class="text-xs font-medium text-slate-500"
                                        >
                                            {{ t('Inquiry number') }}
                                        </p>
                                        <p
                                            class="mt-1 font-mono text-lg font-bold tracking-[-0.02em] text-slate-950 sm:text-xl"
                                        >
                                            {{ submissionNumber }}
                                        </p>
                                    </div>
                                    <div
                                        class="border-t border-slate-200 bg-blue-50/60 px-5 py-4"
                                    >
                                        <p
                                            class="text-xs font-medium text-slate-500"
                                        >
                                            {{ t('Access code') }}
                                        </p>
                                        <p
                                            class="mt-1 font-mono text-xl font-bold tracking-[0.08em] text-[#1875e6] sm:text-2xl"
                                        >
                                            {{ submissionAccessCode }}
                                        </p>
                                    </div>
                                </div>

                                <p
                                    class="mt-4 max-w-sm text-sm leading-6 text-slate-600"
                                >
                                    {{
                                        t(
                                            'The access code is shown only once. It cannot be recovered for an anonymous inquiry.',
                                        )
                                    }}
                                </p>

                                <button
                                    type="button"
                                    class="mt-4 inline-flex min-h-10 items-center justify-center gap-2 rounded-xl px-3 text-sm font-semibold text-[#1875e6] transition-colors hover:bg-blue-50 active:bg-blue-100"
                                    @click="copySubmissionDetails"
                                >
                                    <Copy class="size-4" />
                                    {{
                                        copiedDetails
                                            ? t('Copied')
                                            : t('Copy details')
                                    }}
                                </button>
                            </div>

                            <div class="border-t border-slate-100 p-3">
                                <Button
                                    type="button"
                                    class="h-12 w-full rounded-2xl bg-[#1875e6] text-base font-semibold text-white hover:bg-blue-700"
                                    @click="finishAcceptedSubmission"
                                >
                                    {{ t('Done') }}
                                </Button>
                            </div>
                        </template>

                        <template v-else>
                            <div class="px-6 pt-7 pb-6 sm:px-8 sm:pt-8">
                                <div
                                    class="flex size-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-700"
                                >
                                    <CircleAlert
                                        class="size-6"
                                        :stroke-width="1.8"
                                    />
                                </div>

                                <DialogHeader class="mt-5 gap-2 text-left">
                                    <DialogTitle
                                        class="text-xl font-semibold tracking-[-0.02em] text-slate-950"
                                    >
                                        {{ t('Inquiry not registered') }}
                                    </DialogTitle>
                                    <DialogDescription
                                        class="text-sm leading-6 text-slate-600"
                                    >
                                        {{ admissionError }}
                                    </DialogDescription>
                                </DialogHeader>

                                <a
                                    :href="alternativeInquiriesMailto"
                                    class="mt-5 flex min-h-14 items-center gap-3 rounded-2xl bg-slate-50 px-4 text-sm font-semibold text-[#1875e6] ring-1 ring-black/5 transition-colors hover:bg-blue-50"
                                >
                                    <Mail
                                        class="size-5 shrink-0"
                                        :stroke-width="1.8"
                                    />
                                    <span class="break-all">{{
                                        alternativeInquiriesEmail
                                    }}</span>
                                </a>

                                <p
                                    class="mt-4 text-xs leading-5 text-slate-500"
                                >
                                    {{
                                        t(
                                            'If your message concerns company activities, misconduct, safety, ethics, or another Speak Up matter, clarify the relevant facts and submit it again.',
                                        )
                                    }}
                                </p>
                            </div>

                            <div class="border-t border-slate-100 p-3">
                                <Button
                                    type="button"
                                    class="h-12 w-full rounded-2xl bg-[#1875e6] text-base font-semibold text-white hover:bg-blue-700"
                                    @click="
                                        clearErrors('admission');
                                        closeScreeningDialog();
                                    "
                                >
                                    {{ t('Edit inquiry') }}
                                </Button>
                            </div>
                        </template>
                    </DialogContent>
                </Dialog>
            </Form>
        </DialogScrollContent>

        <Dialog :open="rateLimitOpen" @update:open="updateRateLimitOpen">
            <DialogContent
                :show-close-button="false"
                overlay-class="bg-slate-950/20 backdrop-blur-sm"
                class="w-[calc(100%-2rem)] max-w-sm gap-0 overflow-hidden rounded-2xl border border-slate-200 bg-white p-0 text-center shadow-xl"
            >
                <div class="px-6 pt-7 pb-5 sm:px-8 sm:pt-8">
                    <div
                        class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-blue-50 text-[#1875e6]"
                    >
                        <Clock3 class="size-7" :stroke-width="1.8" />
                    </div>
                    <DialogHeader class="mt-5 items-center gap-2 text-center">
                        <DialogTitle
                            class="text-xl font-semibold tracking-[-0.02em] text-slate-950"
                        >
                            {{ t('Too many attempts') }}
                        </DialogTitle>
                        <DialogDescription
                            class="max-w-xs text-sm leading-6 text-slate-500"
                        >
                            {{
                                t(
                                    'Submission has been temporarily limited. Your entered information remains in the form.',
                                )
                            }}
                        </DialogDescription>
                    </DialogHeader>

                    <div
                        class="mx-auto mt-5 flex max-w-[15rem] items-center justify-between rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-black/5"
                    >
                        <span
                            class="text-left text-xs leading-4 text-slate-500"
                        >
                            {{ t('Try again after') }}
                        </span>
                        <span
                            class="font-mono text-lg font-semibold tracking-tight text-slate-900 tabular-nums"
                        >
                            {{ formattedRateLimitTime }}
                        </span>
                    </div>
                </div>

                <div class="border-t border-slate-100 p-3">
                    <Button
                        type="button"
                        class="h-12 w-full rounded-2xl bg-[#1875e6] text-base font-semibold text-white hover:bg-blue-700"
                        @click="updateRateLimitOpen(false)"
                    >
                        {{ t('Close') }}
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    </Dialog>
</template>
