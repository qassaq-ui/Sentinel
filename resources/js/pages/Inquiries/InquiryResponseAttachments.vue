<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Download, FileText, Paperclip, UploadCloud, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import InquiryResponseAttachmentsController from '@/actions/App/Http/Controllers/InquiryResponseAttachmentsController';
import { useTranslations } from '@/composables/useTranslations';
import type { InquiryResponseAttachment } from './types';

type Props = {
    inquiryNumber: string;
    attachments: InquiryResponseAttachment[];
    modelValue: File[];
    editable: boolean;
    processing: boolean;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:modelValue': [files: File[]];
    error: [message: string];
}>();

const { t } = useTranslations();
const input = ref<HTMLInputElement | null>(null);
const dragDepth = ref(0);
const isDragging = computed(() => dragDepth.value > 0);
const acceptedExtensions = [
    'pdf',
    'doc',
    'docx',
    'xls',
    'xlsx',
    'ppt',
    'pptx',
    'txt',
    'rtf',
    'odt',
    'ods',
    'jpg',
    'jpeg',
    'png',
];
const accept = acceptedExtensions.map((extension) => `.${extension}`).join(',');
const maximumFiles = 10;
const maximumSize = 10 * 1024 * 1024;

function openPicker(): void {
    if (props.editable && !props.processing) {
        input.value?.click();
    }
}

function fileKey(file: File): string {
    return `${file.name}:${file.size}:${file.lastModified}`;
}

function addFiles(fileList: FileList | File[]): void {
    const nextFiles = [...props.modelValue];
    const existingKeys = new Set(nextFiles.map(fileKey));

    for (const file of Array.from(fileList)) {
        const extension = file.name.split('.').pop()?.toLowerCase() ?? '';

        if (!acceptedExtensions.includes(extension)) {
            emit('error', t('This file type is not supported.'));
            continue;
        }

        if (file.size > maximumSize) {
            emit('error', t('Each attachment must not exceed 10 MB.'));
            continue;
        }

        if (props.attachments.length + nextFiles.length >= maximumFiles) {
            emit('error', t('You can attach up to 10 files.'));
            break;
        }

        if (!existingKeys.has(fileKey(file))) {
            nextFiles.push(file);
            existingKeys.add(fileKey(file));
        }
    }

    emit('update:modelValue', nextFiles);

    if (input.value) {
        input.value.value = '';
    }
}

function onDrop(event: DragEvent): void {
    dragDepth.value = 0;

    if (!props.editable || props.processing || !event.dataTransfer?.files) {
        return;
    }

    addFiles(event.dataTransfer.files);
}

function removePending(index: number): void {
    emit(
        'update:modelValue',
        props.modelValue.filter((_, fileIndex) => fileIndex !== index),
    );
}

function removeStored(attachment: InquiryResponseAttachment): void {
    router.delete(
        InquiryResponseAttachmentsController.destroy({
            inquiry: props.inquiryNumber,
            attachment: attachment.id,
        }).url,
        { preserveScroll: true },
    );
}

function formatFileSize(sizeBytes: number): string {
    if (sizeBytes < 1024) {
        return `${sizeBytes} B`;
    }

    if (sizeBytes < 1024 * 1024) {
        return `${Math.round(sizeBytes / 1024)} KB`;
    }

    return `${(sizeBytes / 1024 / 1024).toFixed(1)} MB`;
}
</script>

<template>
    <div class="grid gap-2">
        <label class="text-sm font-medium">{{ t('Attachments') }}</label>

        <button
            v-if="editable"
            type="button"
            class="flex min-h-28 w-full flex-col items-center justify-center rounded-lg border border-dashed px-4 py-5 text-center transition-colors outline-none"
            :class="
                isDragging
                    ? 'border-blue-500 bg-blue-50 text-blue-700 dark:border-blue-400 dark:bg-blue-950/30 dark:text-blue-300'
                    : 'border-input bg-muted/20 text-muted-foreground hover:border-blue-400 hover:bg-muted/40'
            "
            :disabled="processing"
            @click="openPicker"
            @dragenter.prevent="dragDepth += 1"
            @dragover.prevent
            @dragleave.prevent="dragDepth = Math.max(0, dragDepth - 1)"
            @drop.prevent="onDrop"
        >
            <UploadCloud class="mb-2 size-6" />
            <span class="text-sm font-medium text-foreground">
                {{ t('Drag documents here or select files') }}
            </span>
            <span class="mt-1 text-xs">
                {{ t('PDF, Office documents and images, up to 10 MB each') }}
            </span>
        </button>

        <input
            ref="input"
            type="file"
            class="hidden"
            :accept="accept"
            multiple
            :disabled="!editable || processing"
            @change="
                ($event) =>
                    addFiles(($event.target as HTMLInputElement).files ?? [])
            "
        />

        <div
            v-if="attachments.length > 0 || modelValue.length > 0"
            class="space-y-2"
        >
            <div
                v-for="attachment in attachments"
                :key="attachment.id"
                class="flex min-h-12 items-center justify-between gap-3 rounded-lg bg-muted/40 px-3 py-2"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <span
                        class="inline-flex size-9 shrink-0 items-center justify-center rounded-md bg-blue-600 text-white"
                    >
                        <FileText class="size-[18px]" />
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold">
                            {{ attachment.originalName }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ formatFileSize(attachment.sizeBytes) }}
                            <span v-if="attachment.extension">
                                · {{ attachment.extension.toUpperCase() }}
                            </span>
                        </p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-1">
                    <a
                        :href="attachment.downloadUrl"
                        class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        :aria-label="t('Download')"
                    >
                        <Download class="size-4" />
                    </a>
                    <button
                        v-if="editable"
                        type="button"
                        class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors outline-none hover:bg-destructive/10 hover:text-destructive"
                        :aria-label="t('Remove attachment')"
                        @click="removeStored(attachment)"
                    >
                        <X class="size-4" />
                    </button>
                </div>
            </div>

            <div
                v-for="(file, index) in modelValue"
                :key="fileKey(file)"
                class="flex min-h-12 items-center justify-between gap-3 rounded-lg border border-blue-200 bg-blue-50/60 px-3 py-2 dark:border-blue-900 dark:bg-blue-950/20"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <span
                        class="inline-flex size-9 shrink-0 items-center justify-center rounded-md bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200"
                    >
                        <Paperclip class="size-[18px]" />
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold">
                            {{ file.name }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ formatFileSize(file.size) }} ·
                            {{ t('Will be uploaded when the draft is saved') }}
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    class="inline-flex size-8 shrink-0 items-center justify-center rounded-md text-muted-foreground transition-colors outline-none hover:bg-destructive/10 hover:text-destructive"
                    :aria-label="t('Remove attachment')"
                    @click="removePending(index)"
                >
                    <X class="size-4" />
                </button>
            </div>
        </div>
    </div>
</template>
