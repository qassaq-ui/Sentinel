<?php

namespace App\Http\Controllers;

use App\Actions\Inquiries\RecordInquiryEvent;
use App\Actions\Inquiries\StoreInquiryComment;
use App\Http\Requests\InquiryCommentDestroyRequest;
use App\Http\Requests\InquiryCommentStoreRequest;
use App\Models\Inquiry;
use App\Models\InquiryComment;
use App\Models\InquiryCommentAttachment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InquiryCommentsController extends Controller
{
    public function store(
        InquiryCommentStoreRequest $request,
        Inquiry $inquiry,
        StoreInquiryComment $storeComment,
    ): RedirectResponse {
        $parent = $request->validated('parent_id') === null
            ? null
            : InquiryComment::query()->where('inquiry_id', $inquiry->id)->where('uuid', $request->validated('parent_id'))->firstOrFail();
        $parent = $parent?->parent ?? $parent;

        /** @var User $user */
        $user = $request->user();
        $storeComment->handle($inquiry, $user, (string) $request->validated('body'), $parent, $request->file('attachments', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Comment added.')]);

        return back();
    }

    public function download(
        Inquiry $inquiry,
        InquiryCommentAttachment $attachment,
    ): StreamedResponse {
        Gate::authorize('view', $inquiry);
        abort_unless($attachment->comment->inquiry_id === $inquiry->id, 404);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
        ]);
    }

    public function destroy(
        InquiryCommentDestroyRequest $request,
        Inquiry $inquiry,
        InquiryComment $comment,
        RecordInquiryEvent $recordEvent,
    ): RedirectResponse {
        abort_unless($comment->inquiry_id === $inquiry->id, 404);

        $attachments = $comment->attachments()->get(['disk', 'path']);
        $metadata = [
            'comment_id' => $comment->uuid,
            'reply_to_id' => $comment->parent?->uuid,
            'attachments_count' => $attachments->count(),
        ];

        DB::transaction(function () use ($comment, $inquiry, $metadata, $recordEvent, $request): void {
            $comment->attachments()->delete();
            $comment->delete();
            $recordEvent->handle($inquiry, 'comment_deleted', $request->user(), $metadata, $comment->response);
        });

        $attachments
            ->groupBy('disk')
            ->each(fn ($files, string $disk) => Storage::disk($disk)->delete($files->pluck('path')->all()));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Comment deleted.')]);

        return back();
    }
}
