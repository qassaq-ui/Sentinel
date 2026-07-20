<?php

namespace App\Http\Controllers;

use App\Actions\Inquiries\RecordInquiryEvent;
use App\Actions\Inquiries\ReviewInquiryResponse;
use App\Actions\Inquiries\SaveInquiryResponseDraft;
use App\Actions\Inquiries\SendInquiryResponse;
use App\Actions\Inquiries\StoreInquiryResponseAttachments;
use App\Actions\Inquiries\SubmitInquiryResponseForApproval;
use App\Actions\Inquiries\TransformInquiryResponseText;
use App\Http\Requests\InquiryResponseDraftRequest;
use App\Http\Requests\InquiryResponseGenerateRequest;
use App\Http\Requests\InquiryResponseReviewRequest;
use App\Http\Requests\InquiryResponseSendRequest;
use App\Http\Requests\InquiryResponseSubmitRequest;
use App\Http\Requests\InquiryResponseTransformRequest;
use App\Models\Inquiry;
use App\Models\InquiryOutcome;
use App\Models\User;
use App\Services\AIAssistant\AIAssistantClient;
use App\Services\AIAssistant\AIAssistantPromptBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Throwable;

class InquiryResponsesController extends Controller
{
    public function draft(
        InquiryResponseDraftRequest $request,
        Inquiry $inquiry,
        SaveInquiryResponseDraft $saveDraft,
        StoreInquiryResponseAttachments $storeAttachments,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        DB::transaction(function () use ($request, $inquiry, $user, $saveDraft, $storeAttachments): void {
            $response = $saveDraft->handle(
                $inquiry,
                $user,
                $request->validated('inquiry_outcome_id'),
                $request->validated('body'),
            );

            $storeAttachments->handle($response, $user, $request->file('attachments', []));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Response draft saved.')]);

        return back();
    }

    public function submit(
        InquiryResponseSubmitRequest $request,
        Inquiry $inquiry,
        SaveInquiryResponseDraft $saveDraft,
        StoreInquiryResponseAttachments $storeAttachments,
        SubmitInquiryResponseForApproval $submit,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $reviewer = User::query()->findOrFail($request->integer('reviewer_id'));
        $existingResponse = $inquiry->response()->first();
        $includesDraft = $request->exists('inquiry_outcome_id')
            || $request->exists('body')
            || $request->hasFile('attachments');

        DB::transaction(function () use (
            $request,
            $inquiry,
            $user,
            $reviewer,
            $existingResponse,
            $includesDraft,
            $saveDraft,
            $storeAttachments,
            $submit,
        ): void {
            $response = $includesDraft
                ? $saveDraft->handle(
                    $inquiry,
                    $user,
                    $request->exists('inquiry_outcome_id')
                        ? $request->validated('inquiry_outcome_id')
                        : $existingResponse?->inquiry_outcome_id,
                    $request->exists('body')
                        ? $request->validated('body')
                        : $existingResponse?->body,
                )
                : $existingResponse;

            abort_if($response === null, 404);

            if ($includesDraft) {
                $storeAttachments->handle($response, $user, $request->file('attachments', []));
            }

            $submit->handle($response, $user, $reviewer);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Response sent for approval.')]);

        return back();
    }

    public function review(
        InquiryResponseReviewRequest $request,
        Inquiry $inquiry,
        ReviewInquiryResponse $review,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $decision = (string) $request->validated('decision');
        $review->handle(
            $inquiry->response()->firstOrFail(),
            $user,
            $decision,
            $request->validated('comment'),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $decision === 'approve'
                ? __('Response approved.')
                : __('Response returned for revision.'),
        ]);

        return back();
    }

    public function send(
        InquiryResponseSendRequest $request,
        Inquiry $inquiry,
        SendInquiryResponse $send,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $send->handle($inquiry->response()->firstOrFail(), $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Response sent.')]);

        return back();
    }

    public function generate(
        InquiryResponseGenerateRequest $request,
        Inquiry $inquiry,
        AIAssistantPromptBuilder $promptBuilder,
        AIAssistantClient $client,
        RecordInquiryEvent $recordEvent,
    ): JsonResponse {
        $outcome = InquiryOutcome::query()
            ->where('is_active', true)
            ->findOrFail($request->integer('inquiry_outcome_id'));

        try {
            $body = $client->chat($promptBuilder->responseMessages(
                $inquiry,
                $outcome,
                (string) $request->validated('locale'),
                $request->validated('current_body'),
            ));

            $recordEvent->handle($inquiry, 'response_generated', $request->user(), [
                'outcome_id' => $outcome->id,
                'outcome_name' => $outcome->fallback_name,
                'language' => (string) $request->validated('locale'),
            ]);

            return response()->json(['body' => $body]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => __('AI assistant is temporarily unavailable.'),
            ], 502);
        }
    }

    public function transform(
        InquiryResponseTransformRequest $request,
        Inquiry $inquiry,
        TransformInquiryResponseText $transformResponseText,
        RecordInquiryEvent $recordEvent,
    ): JsonResponse {
        $action = (string) $request->validated('action');
        $locale = $request->validated('locale');

        try {
            $body = $transformResponseText->handle(
                $action,
                (string) $request->validated('body'),
                is_string($locale) ? $locale : null,
            );

            $recordEvent->handle(
                $inquiry,
                $action === 'translate' ? 'response_translated' : 'response_polished',
                $request->user(),
                $action === 'translate' ? ['language' => $locale] : [],
            );

            return response()->json(['body' => $body]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => __('AI assistant is temporarily unavailable.'),
            ], 502);
        }
    }
}
