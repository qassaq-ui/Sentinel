<?php

namespace App\Http\Controllers;

use App\Actions\Inquiries\CreateInquiry;
use App\Actions\Inquiries\ResolvePublicInquiryAccess;
use App\Actions\Inquiries\StoreInquiryAttachments;
use App\Http\Requests\PublicInquiryStatusRequest;
use App\Http\Requests\PublicInquiryStoreRequest;
use App\Models\Inquiry;
use App\Models\InquiryCategory;
use App\Models\InquiryResponse;
use App\Models\InquirySetting;
use App\Services\AIAssistant\InquiryAdmissionService;
use App\Support\InquiryAccessCode;
use App\Support\Localization\LocalizationManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PublicInquiryController extends Controller
{
    public function create(LocalizationManager $localization): Response
    {
        $messages = $localization->messages($localization->currentLocale());
        $inquirySettings = InquirySetting::current();

        return Inertia::render('public/Inquiries/Create', [
            'aiScreeningEnabled' => $inquirySettings->ai_screening_enabled,
            'alternativeInquiriesEmail' => config('speakup.alternative_inquiries_email'),
            'categories' => InquiryCategory::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'name_key', 'fallback_name', 'description_key', 'fallback_description'])
                ->map(fn (InquiryCategory $category): array => [
                    'id' => $category->id,
                    'name' => $messages[$category->name_key] ?? $category->fallback_name,
                    'description' => $messages[$category->description_key] ?? $category->fallback_description,
                ])
                ->values(),
        ]);
    }

    public function store(
        PublicInquiryStoreRequest $request,
        CreateInquiry $createInquiry,
        StoreInquiryAttachments $storeInquiryAttachments,
        InquiryAdmissionService $inquiryAdmission,
    ): RedirectResponse {
        $validated = $request->validated();
        $category = InquiryCategory::query()->findOrFail($validated['inquiry_category_id']);
        $admission = $inquiryAdmission->evaluate(
            $category,
            $validated['title'],
            $validated['description'],
        );

        if (! $admission['allowed']) {
            throw ValidationException::withMessages([
                'admission' => __('The information provided does not fall within the scope of matters handled through the Speak Up channel and cannot be registered as an inquiry in this system. For guidance or referral to the appropriate service, please contact :email.', [
                    'email' => config('speakup.alternative_inquiries_email'),
                ]),
            ]);
        }

        $accessCode = InquiryAccessCode::generate();

        $inquiry = DB::transaction(function () use ($validated, $accessCode, $category, $createInquiry, $storeInquiryAttachments): Inquiry {
            $anonymous = $validated['submission_mode'] === 'anonymous';
            $inquiry = $createInquiry->handle([
                'category' => $category,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'anonymous' => $anonymous,
            ]);

            $inquiry->applicant()->create([
                'name' => $anonymous ? null : $validated['applicant_name'],
                'email' => $anonymous ? null : ($validated['applicant_email'] ?? null),
                'phone' => $anonymous ? null : ($validated['applicant_phone'] ?? null),
                'tracking_token_hash' => InquiryAccessCode::hash($accessCode),
            ]);

            $storeInquiryAttachments->handle($inquiry, $validated['attachments'] ?? []);

            return $inquiry;
        });

        Inertia::flash('submission', [
            'number' => $inquiry->number,
            'accessCode' => InquiryAccessCode::format($accessCode),
        ]);

        return to_route('home');
    }

    public function status(
        PublicInquiryStatusRequest $request,
        ResolvePublicInquiryAccess $resolveAccess,
    ): JsonResponse {
        $validated = $request->validated();
        $inquiry = $resolveAccess->handle($validated['access_code']);
        $sentResponse = $inquiry->response()
            ->where('status', InquiryResponse::STATUS_SENT)
            ->first(['id', 'inquiry_id', 'body', 'sent_at', 'updated_at']);
        $updatedAt = $sentResponse?->updated_at?->greaterThan($inquiry->updated_at)
            ? $sentResponse->updated_at
            : $inquiry->updated_at;

        return response()->json([
            'number' => $inquiry->number,
            'status' => $inquiry->status,
            'submittedAt' => $inquiry->submitted_at->toISOString(),
            'updatedAt' => $updatedAt?->toISOString(),
            'responseAvailable' => filled($sentResponse?->body),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function response(
        PublicInquiryStatusRequest $request,
        ResolvePublicInquiryAccess $resolveAccess,
    ): JsonResponse {
        $validated = $request->validated();
        $inquiry = $resolveAccess->handle($validated['access_code']);
        $sentResponse = $inquiry->response()
            ->where('status', InquiryResponse::STATUS_SENT)
            ->first(['id', 'inquiry_id', 'body', 'sent_at']);

        if (blank($sentResponse?->body)) {
            return response()->json([
                'message' => __('The response has not been sent yet.'),
            ], 404)->header('Cache-Control', 'no-store, private');
        }

        return response()->json([
            'number' => $inquiry->number,
            'body' => $sentResponse->body,
            'sentAt' => $sentResponse->sent_at?->toISOString(),
        ])->header('Cache-Control', 'no-store, private');
    }
}
