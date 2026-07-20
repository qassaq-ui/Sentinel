<?php

namespace App\Http\Controllers;

use App\Actions\Inquiries\RecordInquiryEvent;
use App\Http\Requests\InquiryResponseAttachmentDestroyRequest;
use App\Models\Inquiry;
use App\Models\InquiryResponseAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InquiryResponseAttachmentsController extends Controller
{
    public function download(Inquiry $inquiry, InquiryResponseAttachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $inquiry);
        abort_unless($attachment->response->inquiry_id === $inquiry->id, 404);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type],
        );
    }

    public function destroy(
        InquiryResponseAttachmentDestroyRequest $request,
        Inquiry $inquiry,
        InquiryResponseAttachment $attachment,
        RecordInquiryEvent $recordEvent,
    ): RedirectResponse {
        $response = $attachment->response;
        $metadata = [
            'attachment_id' => $attachment->uuid,
            'file_name' => $attachment->original_name,
            'size_bytes' => $attachment->size_bytes,
        ];

        DB::transaction(function () use ($attachment, $recordEvent, $inquiry, $request, $metadata, $response): void {
            $attachment->delete();
            $recordEvent->handle($inquiry, 'response_attachment_removed', $request->user(), $metadata, $response);
        });
        Storage::disk($attachment->disk)->delete($attachment->path);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Attachment removed.')]);

        return back();
    }
}
