<?php

namespace App\Jobs;

use App\Actions\Inquiries\RecordInquiryEvent;
use App\Models\Inquiry;
use App\Models\InquiryReport;
use App\Models\User;
use App\Services\AIAssistant\InquiryReportBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateInquiryReportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Inquiry $inquiry,
        public InquiryReport $report,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(InquiryReportBuilder $builder, RecordInquiryEvent $recordEvent): void
    {
        app()->setLocale($this->report->locale ?: config('app.fallback_locale'));
        $creator = $this->report->created_by_id === null
            ? null
            : User::query()->find($this->report->created_by_id);

        $this->report->update(['status' => InquiryReport::STATUS_PROCESSING]);
        $eventType = 'report_generated';

        try {
            $data = $builder->build($this->inquiry, $this->report->locale);

            $html = view('reports.inquiry', [
                'inquiry' => $this->inquiry,
                'data' => $data,
                'generatedAt' => now(),
            ])->render();

            $disk = Storage::disk('local');
            $directory = 'inquiry-reports';
            if (! $disk->exists($directory)) {
                $disk->makeDirectory($directory);
            }

            $relativePath = "{$directory}/{$this->report->uuid}.pdf";
            $absolutePath = $disk->path($relativePath);

            Pdf::loadHTML($html)
                ->setPaper('a4')
                ->save($absolutePath);

            $this->report->update([
                'status' => InquiryReport::STATUS_COMPLETED,
                'pdf_path' => $relativePath,
                'error_message' => null,
                'generated_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $eventType = 'report_failed';

            $this->report->update([
                'status' => InquiryReport::STATUS_FAILED,
                'error_message' => mb_substr($exception->getMessage(), 0, 1000),
            ]);
        }

        $recordEvent->handle($this->inquiry, $eventType, $creator, [
            'report_id' => $this->report->uuid,
            'language' => $this->report->locale,
        ]);
    }
}
