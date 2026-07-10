<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
import type { InquiryAttachment } from './types';
import { AudioLines, Download, File, FileImage, FileSpreadsheet, FileText, FileType } from '@lucide/vue';
import { computed, type Component } from 'vue';

type Props = {
    attachments: InquiryAttachment[];
};

const props = defineProps<Props>();

const { t } = useTranslations();

type AttachmentVisual = {
    icon: Component;
    label: string;
    className: string;
};

const visualByType: Record<InquiryAttachment['fileType'], AttachmentVisual> = {
    photo: {
        icon: FileImage,
        label: 'Image',
        className: 'bg-violet-500 text-white',
    },
    document: {
        icon: FileText,
        label: 'Word',
        className: 'bg-blue-600 text-white',
    },
    spreadsheet: {
        icon: FileSpreadsheet,
        label: 'Excel',
        className: 'bg-emerald-600 text-white',
    },
    text: {
        icon: FileType,
        label: 'Text file',
        className: 'bg-slate-500 text-white',
    },
    pdf: {
        icon: FileText,
        label: 'PDF',
        className: 'bg-red-600 text-white',
    },
    audio: {
        icon: AudioLines,
        label: 'Audio',
        className: 'bg-amber-500 text-white',
    },
    other: {
        icon: File,
        label: 'File',
        className: 'bg-muted-foreground text-background',
    },
};

const sortedAttachments = computed(() => {
    return [...props.attachments].sort((first, second) => first.id - second.id);
});

function visualFor(attachment: InquiryAttachment): AttachmentVisual {
    return visualByType[attachment.fileType] ?? visualByType.other;
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
    <section>
        <div class="space-y-2">
            <div
                v-for="attachment in sortedAttachments"
                :key="attachment.id"
                class="flex min-h-12 items-center justify-between gap-3 rounded-lg bg-muted/40 px-3 py-2"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <span
                        class="inline-flex size-9 shrink-0 items-center justify-center rounded-md"
                        :class="visualFor(attachment).className"
                        :title="t(visualFor(attachment).label)"
                    >
                        <component :is="visualFor(attachment).icon" class="size-[18px]" />
                    </span>

                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-foreground">
                            {{ attachment.originalName }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ t(visualFor(attachment).label) }}
                            <span v-if="attachment.extension"> · {{ attachment.extension.toUpperCase() }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <span class="text-sm font-medium text-muted-foreground">
                        {{ formatFileSize(attachment.sizeBytes) }}
                    </span>
                    <button
                        type="button"
                        class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-[var(--color-tab)] hover:text-white"
                        :aria-label="t('Download')"
                    >
                        <Download class="size-4" />
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>
