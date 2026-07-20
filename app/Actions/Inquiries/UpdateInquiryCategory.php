<?php

namespace App\Actions\Inquiries;

use App\Models\Inquiry;
use App\Models\InquiryCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateInquiryCategory
{
    public function __construct(private RecordInquiryEvent $recordEvent) {}

    public function handle(Inquiry $inquiry, InquiryCategory $category, User $actor): Inquiry
    {
        return DB::transaction(function () use ($inquiry, $category, $actor): Inquiry {
            $locked = Inquiry::query()->lockForUpdate()->findOrFail($inquiry->id);

            if ($locked->inquiry_category_id === $category->id) {
                return $locked;
            }

            $previousCategory = InquiryCategory::query()->findOrFail($locked->inquiry_category_id);
            $previousDueDate = $locked->review_due_date->toDateString();
            $reviewDueDate = $locked->submitted_at->copy()->addDays($category->review_days)->toDateString();

            $locked->forceFill([
                'inquiry_category_id' => $category->id,
                'review_days' => $category->review_days,
                'review_due_date' => $reviewDueDate,
            ])->save();

            $this->recordEvent->handle($locked, 'category_changed', $actor, [
                'from' => [
                    'id' => $previousCategory->id,
                    'name' => $previousCategory->fallback_name,
                    'review_days' => $previousCategory->review_days,
                    'review_due_date' => $previousDueDate,
                ],
                'to' => [
                    'id' => $category->id,
                    'name' => $category->fallback_name,
                    'review_days' => $category->review_days,
                    'review_due_date' => $reviewDueDate,
                ],
            ]);

            return $locked->refresh();
        }, attempts: 3);
    }
}
