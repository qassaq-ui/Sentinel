<?php

namespace App\Http\Controllers;

use App\Actions\Inquiries\RecordInquiryEvent;
use App\Http\Requests\InquiryReportRequest;
use App\Jobs\GenerateInquiryReportJob;
use App\Models\Inquiry;
use App\Models\InquiryReport;
use App\Services\AIAssistant\InquiryTranslationService;
use App\Support\Localization\LocalizationManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InquiryReportsController extends Controller
{
    public function store(
        InquiryReportRequest $request,
        Inquiry $inquiry,
        LocalizationManager $localization,
        RecordInquiryEvent $recordEvent,
    ): JsonResponse {
        $language = (string) ($request->validated('language') ?? $localization->currentLocale());

        if (! app(InquiryTranslationService::class)->isSupportedLanguage($language)) {
            $language = $localization->currentLocale();
        }

        $report = DB::transaction(function () use ($request, $inquiry, $recordEvent, $language): InquiryReport {
            $report = $inquiry->reports()->create([
                'created_by_id' => $request->user()?->id,
                'status' => InquiryReport::STATUS_PENDING,
                'locale' => $language,
            ]);

            $recordEvent->handle($inquiry, 'report_requested', $request->user(), [
                'report_id' => $report->uuid,
                'language' => $language,
            ]);

            return $report;
        }, attempts: 3);

        GenerateInquiryReportJob::dispatch($inquiry, $report);

        return response()->json($this->present($report), 201);
    }

    public function show(Inquiry $inquiry): JsonResponse
    {
        Gate::authorize('view', $inquiry);

        $report = $inquiry->reports()->latest('id')->first();

        return response()->json($report !== null ? $this->present($report) : ['status' => null]);
    }

    public function download(Inquiry $inquiry, string $report): StreamedResponse|JsonResponse
    {
        Gate::authorize('view', $inquiry);

        /** @var InquiryReport|null $record */
        $record = $inquiry->reports()->where('uuid', $report)->first();

        if ($record === null) {
            return response()->json(['message' => __('Report not found.')], 404);
        }

        if ($record->status !== InquiryReport::STATUS_COMPLETED || $record->pdf_path === null) {
            return response()->json(['message' => __('Report is not ready.')], 409);
        }

        if (! Storage::disk('local')->exists($record->pdf_path)) {
            return response()->json(['message' => __('Report file is missing.')], 404);
        }

        return Storage::disk('local')->download(
            $record->pdf_path,
            "Report-{$inquiry->number}.pdf",
            ['Content-Type' => 'application/pdf'],
        );
    }

    /**
     * @return array{report_id: string, status: string, pdf_path: string|null, error_message: string|null}
     */
    private function present(InquiryReport $report): array
    {
        return [
            'report_id' => $report->uuid,
            'status' => $report->status,
            'pdf_path' => $report->pdf_path,
            'error_message' => $report->error_message,
        ];
    }
}
