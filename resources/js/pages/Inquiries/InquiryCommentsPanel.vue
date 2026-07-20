<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import {
    CornerUpLeft,
    FileText,
    MessageSquare,
    Paperclip,
    Send,
    Trash2,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import InquiryCommentsController from '@/actions/App/Http/Controllers/InquiryCommentsController';
import InputError from '@/components/InputError.vue';
import { useTranslations } from '@/composables/useTranslations';
import { show as inquiryShow } from '@/routes/inquiries';
import type { InquiryComment, InquiryCommentsPage } from './types';

type Props = {
    inquiryNumber: string;
    comments: InquiryCommentsPage;
    canComment: boolean;
};

const props = defineProps<Props>();
const { t } = useTranslations();
const fileInput = ref<HTMLInputElement | null>(null);
const replyingTo = ref<InquiryComment | null>(null);
const isDragging = ref(false);
const attachmentError = ref('');
const deletingCommentId = ref<string | null>(null);
const form = useForm<{
    body: string;
    parent_id: string | null;
    attachments: File[];
}>({
    body: '',
    parent_id: null,
    attachments: [],
});
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
];
const accept = acceptedExtensions.map((extension) => `.${extension}`).join(',');
const pages = computed(() =>
    Array.from({ length: props.comments.lastPage }, (_, index) => index + 1),
);

function selectReply(comment: InquiryComment): void {
    replyingTo.value = comment;
    form.parent_id = comment.id;
    form.clearErrors();
    document
        .getElementById('inquiry-comment-body')
        ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function cancelReply(): void {
    replyingTo.value = null;
    form.parent_id = null;
}

function fileKey(file: File): string {
    return `${file.name}:${file.size}:${file.lastModified}`;
}

function addFiles(files: FileList | File[]): void {
    attachmentError.value = '';
    const next = [...form.attachments];
    const keys = new Set(next.map(fileKey));

    for (const file of Array.from(files)) {
        const extension = file.name.split('.').pop()?.toLowerCase() ?? '';

        if (!acceptedExtensions.includes(extension)) {
            attachmentError.value = t('This file type is not supported.');
            continue;
        }

        if (file.size > 10 * 1024 * 1024) {
            attachmentError.value = t('Each attachment must not exceed 10 MB.');
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

    form.attachments = next;
}

function removeFile(index: number): void {
    form.attachments = form.attachments.filter(
        (_, fileIndex) => fileIndex !== index,
    );
}

function submit(): void {
    if (form.processing || !form.body.trim()) {
        return;
    }

    form.post(InquiryCommentsController.store(props.inquiryNumber).url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            cancelReply();
            attachmentError.value = '';
        },
    });
}

function formatFileSize(bytes: number): string {
    return bytes < 1024 * 1024
        ? `${Math.max(1, Math.round(bytes / 1024))} KB`
        : `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}

function initials(name: string | null): string {
    return (name ?? '?')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0))
        .join('')
        .toUpperCase();
}

function deleteComment(comment: InquiryComment): void {
    if (!comment.canDelete || !window.confirm(t('Delete this comment?'))) {
        return;
    }

    deletingCommentId.value = comment.id;
    router.delete(
        InquiryCommentsController.destroy({
            inquiry: props.inquiryNumber,
            comment: comment.id,
        }).url,
        {
            preserveScroll: true,
            onFinish: () => {
                deletingCommentId.value = null;
            },
        },
    );
}
</script>

<template>
    <section class="flex w-full flex-col">
        <form
            v-if="canComment"
            class="order-last mt-5 w-full max-w-3xl self-center border-t border-border pt-5"
            @submit.prevent="submit"
        >
            <div
                v-if="replyingTo"
                class="mb-2 flex items-center gap-2 px-12 text-xs text-muted-foreground"
            >
                <CornerUpLeft class="size-3.5 shrink-0" />
                <span class="min-w-0 flex-1 truncate">
                    {{ t('Reply to') }}
                    <strong class="font-semibold text-foreground"
                        >@{{ replyingTo.authorName }}</strong
                    >
                </span>
                <button
                    type="button"
                    class="rounded-full p-1 transition-colors hover:bg-muted hover:text-foreground"
                    :aria-label="t('Cancel reply')"
                    @click="cancelReply"
                >
                    <X class="size-3.5" />
                </button>
            </div>

            <div
                class="flex w-full items-end gap-3 rounded-lg border bg-background p-1.5 pl-3 transition-colors"
                :class="
                    isDragging
                        ? 'border-[var(--color-tab)] bg-[var(--color-tab)]/5'
                        : 'border-input focus-within:border-[var(--color-tab)]'
                "
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="
                    isDragging = false;
                    addFiles($event.dataTransfer?.files ?? []);
                "
            >
                <textarea
                    id="inquiry-comment-body"
                    v-model="form.body"
                    rows="1"
                    class="max-h-32 min-h-9 flex-1 resize-none bg-transparent py-2 text-sm leading-5 outline-none placeholder:text-muted-foreground"
                    :placeholder="t('Write a comment')"
                    @keydown.meta.enter="submit"
                    @keydown.ctrl.enter="submit"
                />
                <button
                    type="button"
                    class="mb-0.5 inline-flex size-9 shrink-0 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                    :aria-label="t('Drag attachments here or select files')"
                    @click="fileInput?.click()"
                >
                    <Paperclip class="size-[18px]" />
                </button>
                <button
                    type="submit"
                    class="mb-0.5 inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-[var(--color-tab)] text-white transition-all hover:brightness-95 disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="form.processing || !form.body.trim()"
                    :aria-label="replyingTo ? t('Reply') : t('Add comment')"
                >
                    <Send class="size-4" />
                </button>
            </div>

            <input
                ref="fileInput"
                type="file"
                class="hidden"
                :accept="accept"
                multiple
                @change="
                    addFiles(($event.target as HTMLInputElement).files ?? [])
                "
            />
            <InputError class="px-3" :message="form.errors.body" />
            <InputError
                class="px-3"
                :message="
                    attachmentError ||
                    form.errors.attachments ||
                    (form.errors as Record<string, string>)['attachments.0']
                "
            />

            <div
                v-if="form.attachments.length"
                class="mt-2 flex flex-wrap gap-1.5 px-3"
            >
                <span
                    v-for="(file, index) in form.attachments"
                    :key="fileKey(file)"
                    class="inline-flex max-w-full items-center gap-1.5 rounded-full bg-muted px-2.5 py-1 text-xs"
                >
                    <FileText class="size-3.5 shrink-0" />
                    <span class="truncate">{{ file.name }}</span>
                    <button
                        type="button"
                        class="rounded-full text-muted-foreground transition-colors hover:text-foreground"
                        :aria-label="`${t('Remove attachment')} ${file.name}`"
                        @click="removeFile(index)"
                    >
                        <X class="size-3.5" />
                    </button>
                </span>
            </div>
        </form>

        <div
            v-if="comments.data.length === 0"
            class="flex min-h-48 flex-col items-center justify-center px-4 py-10 text-center"
        >
            <span
                class="flex size-14 items-center justify-center rounded-full border-2 border-foreground"
            >
                <MessageSquare class="size-6" />
            </span>
            <p class="mt-3 text-sm font-semibold">
                {{ t('No comments yet') }}
            </p>
        </div>

        <div v-else class="divide-y divide-border">
            <article
                v-for="comment in comments.data"
                :key="comment.id"
                class="py-5"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="flex size-9 shrink-0 items-center justify-center rounded-full bg-neutral-200 text-[11px] font-bold text-neutral-600 dark:bg-neutral-700 dark:text-neutral-200"
                    >
                        {{ initials(comment.authorName) }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <p
                            class="text-sm leading-5 whitespace-pre-wrap"
                            :class="
                                comment.deleted
                                    ? 'text-muted-foreground italic'
                                    : ''
                            "
                        >
                            <strong
                                v-if="comment.authorName"
                                class="mr-1.5 font-semibold"
                                >{{ comment.authorName }}</strong
                            >{{ comment.body }}
                        </p>

                        <div
                            v-if="comment.attachments.length"
                            class="mt-2 flex flex-wrap gap-1.5"
                        >
                            <a
                                v-for="attachment in comment.attachments"
                                :key="attachment.id"
                                :href="attachment.downloadUrl"
                                class="inline-flex max-w-full items-center gap-1.5 rounded-lg border border-border px-2.5 py-1.5 text-xs transition-colors hover:bg-muted"
                            >
                                <FileText class="size-3.5 shrink-0" />
                                <span class="truncate font-medium">{{
                                    attachment.originalName
                                }}</span>
                                <span class="shrink-0 text-muted-foreground">{{
                                    formatFileSize(attachment.sizeBytes)
                                }}</span>
                            </a>
                        </div>

                        <div
                            class="mt-2 flex items-center gap-3 text-xs text-muted-foreground"
                        >
                            <time>{{ comment.createdAt }}</time>
                            <span>{{ comment.authorRole }}</span>
                            <button
                                v-if="canComment"
                                type="button"
                                class="inline-flex items-center gap-1 font-semibold transition-colors hover:text-foreground"
                                @click="selectReply(comment)"
                            >
                                <CornerUpLeft class="size-3" />
                                {{ t('Reply') }}
                            </button>
                            <button
                                v-if="comment.canDelete"
                                type="button"
                                class="inline-flex items-center gap-1 font-semibold transition-colors hover:text-destructive disabled:opacity-50"
                                :disabled="deletingCommentId === comment.id"
                                @click="deleteComment(comment)"
                            >
                                <Trash2 class="size-3" />
                                {{ t('Delete') }}
                            </button>
                        </div>

                        <div
                            v-if="comment.replies.length"
                            class="mt-4 space-y-4"
                        >
                            <div
                                v-for="reply in comment.replies"
                                :key="reply.id"
                                class="relative flex items-start gap-3 pl-7"
                            >
                                <span
                                    class="absolute top-4 left-0 h-px w-5 bg-border"
                                />
                                <div
                                    class="flex size-7 shrink-0 items-center justify-center rounded-full bg-neutral-200 text-[9px] font-bold text-neutral-600 dark:bg-neutral-700 dark:text-neutral-200"
                                >
                                    {{ initials(reply.authorName) }}
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p
                                        class="text-sm leading-5 whitespace-pre-wrap"
                                    >
                                        <strong class="mr-1.5 font-semibold">{{
                                            reply.authorName
                                        }}</strong
                                        >{{ reply.body }}
                                    </p>

                                    <div
                                        v-if="reply.attachments.length"
                                        class="mt-2 flex flex-wrap gap-1.5"
                                    >
                                        <a
                                            v-for="attachment in reply.attachments"
                                            :key="attachment.id"
                                            :href="attachment.downloadUrl"
                                            class="inline-flex max-w-full items-center gap-1.5 rounded-lg border border-border px-2 py-1 text-xs transition-colors hover:bg-muted"
                                        >
                                            <Paperclip
                                                class="size-3.5 shrink-0"
                                            />
                                            <span class="truncate">{{
                                                attachment.originalName
                                            }}</span>
                                        </a>
                                    </div>

                                    <div
                                        class="mt-2 flex items-center gap-3 text-xs text-muted-foreground"
                                    >
                                        <time>{{ reply.createdAt }}</time>
                                        <span>{{ reply.authorRole }}</span>
                                        <button
                                            v-if="canComment"
                                            type="button"
                                            class="inline-flex items-center gap-1 font-semibold transition-colors hover:text-foreground"
                                            @click="selectReply(comment)"
                                        >
                                            <CornerUpLeft class="size-3" />
                                            {{ t('Reply') }}
                                        </button>
                                        <button
                                            v-if="reply.canDelete"
                                            type="button"
                                            class="inline-flex items-center gap-1 font-semibold transition-colors hover:text-destructive disabled:opacity-50"
                                            :disabled="
                                                deletingCommentId === reply.id
                                            "
                                            @click="deleteComment(reply)"
                                        >
                                            <Trash2 class="size-3" />
                                            {{ t('Delete') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        </div>

        <nav
            v-if="comments.lastPage > 1"
            class="flex items-center justify-center gap-4 border-t border-border pt-5"
        >
            <Link
                v-for="pageNumber in pages"
                :key="pageNumber"
                :href="
                    inquiryShow(inquiryNumber, {
                        query: { tab: 'comments', comments_page: pageNumber },
                    })
                "
                preserve-scroll
                class="text-xs font-semibold transition-colors"
                :class="
                    pageNumber === comments.currentPage
                        ? 'text-[var(--color-tab)]'
                        : 'text-muted-foreground hover:text-foreground'
                "
            >
                {{ pageNumber }}
            </Link>
        </nav>
    </section>
</template>
