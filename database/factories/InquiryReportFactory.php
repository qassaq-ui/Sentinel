<?php

namespace Database\Factories;

use App\Models\Inquiry;
use App\Models\InquiryReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InquiryReport>
 */
class InquiryReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inquiry_id' => Inquiry::factory(),
            'created_by_id' => null,
            'status' => InquiryReport::STATUS_PENDING,
            'pdf_path' => null,
            'error_message' => null,
            'generated_at' => null,
        ];
    }

    public function completed(string $pdfPath = 'inquiry-reports/test-report.pdf'): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InquiryReport::STATUS_COMPLETED,
            'pdf_path' => $pdfPath,
            'generated_at' => now(),
        ]);
    }

    public function failed(string $message = 'AI service error.'): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InquiryReport::STATUS_FAILED,
            'error_message' => $message,
        ]);
    }
}
