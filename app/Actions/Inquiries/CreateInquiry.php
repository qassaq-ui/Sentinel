<?php

namespace App\Actions\Inquiries;

use App\Models\Inquiry;
use App\Models\InquiryCategory;
use App\Models\User;
use Carbon\CarbonInterface;

class CreateInquiry
{
    /**
     * @param  array{
     *     category: InquiryCategory,
     *     title: string,
     *     description?: string|null,
     *     creator?: User|null,
     *     anonymous?: bool,
     *     submitted_at?: CarbonInterface|null,
     *     number_prefix?: string|null
     * }  $data
     */
    public function handle(array $data): Inquiry
    {
        $category = $data['category'];
        $submittedAt = $data['submitted_at'] ?? now();
        $anonymous = (bool) ($data['anonymous'] ?? false);
        $number = app(GenerateInquiryNumber::class)->handle(
            submittedAt: $submittedAt,
            prefix: $data['number_prefix'] ?? 'KAZM',
        );

        return Inquiry::create([
            'number' => $number['number'],
            'number_prefix' => $number['prefix'],
            'number_period' => $number['period'],
            'number_sequence' => $number['sequence'],
            'number_format' => $number['format'],
            'type' => $anonymous ? Inquiry::TYPE_ANONYMOUS : Inquiry::TYPE_PORTAL,
            'status' => Inquiry::STATUS_NEW,
            'inquiry_category_id' => $category->id,
            'created_by_id' => $anonymous ? null : ($data['creator']->id ?? null),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'submitted_at' => $submittedAt,
            'review_days' => $category->review_days,
            'review_due_date' => $submittedAt->copy()->addDays($category->review_days)->toDateString(),
        ]);
    }

    /**
     * @param  array{
     *     category: InquiryCategory,
     *     title: string,
     *     description?: string|null,
     *     creator?: User|null,
     *     anonymous?: bool,
     *     submitted_at?: CarbonInterface|null,
     *     number_prefix?: string|null
     * }  $data
     */
    public function __invoke(array $data): Inquiry
    {
        return $this->handle($data);
    }
}
