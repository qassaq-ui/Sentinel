<?php

namespace App\Http\Controllers;

use App\Http\Requests\InquiryCategoryStoreRequest;
use App\Http\Requests\InquiryCategoryUpdateRequest;
use App\Http\Requests\InquiryOutcomeUpdateRequest;
use App\Models\InquiryCategory;
use App\Models\InquiryOutcome;
use App\Support\Localization\LocalizationManager;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DictionariesController extends Controller
{
    private const SYSTEM_OUTCOMES = [
        [
            'code' => 'confirmed',
            'fallback_name' => 'Confirmed',
            'fallback_description' => 'The reported facts were verified and confirmed during review.',
            'ai_instruction' => 'Use when the inquiry facts were verified and the reported issue was confirmed. Generate a response acknowledging confirmation, stating that appropriate measures will be taken, and avoiding confidential investigation details.',
            'sort_order' => 10,
        ],
        [
            'code' => 'partially_confirmed',
            'fallback_name' => 'Partially confirmed',
            'fallback_description' => 'Some reported facts were confirmed, while other parts were not verified.',
            'ai_instruction' => 'Use when only part of the inquiry was confirmed. Generate a balanced response explaining that the review partially confirmed the matter, note that action will be taken for confirmed parts, and avoid overpromising on unverified details.',
            'sort_order' => 20,
        ],
        [
            'code' => 'not_confirmed',
            'fallback_name' => 'Not confirmed',
            'fallback_description' => 'The reported facts were reviewed but not confirmed.',
            'ai_instruction' => 'Use when the review did not confirm the reported issue. Generate a respectful response stating that the matter was reviewed and not confirmed based on available information, without accusing the applicant of bad faith.',
            'sort_order' => 30,
        ],
        [
            'code' => 'duplicate',
            'fallback_name' => 'Duplicate',
            'fallback_description' => 'The inquiry duplicates another inquiry that has already been received or reviewed.',
            'ai_instruction' => 'Use when the inquiry duplicates an existing inquiry. Generate a response explaining that the matter has already been registered or reviewed under another submission and will not be processed separately unless new information is provided.',
            'sort_order' => 40,
        ],
        [
            'code' => 'outside_competence',
            'fallback_name' => 'Outside competence',
            'fallback_description' => 'The issue is outside the organization’s authority or responsibility.',
            'ai_instruction' => 'Use when the issue is outside the organization’s competence. Generate a response explaining that the organization cannot review the matter within its authority and, when possible, suggest contacting the appropriate body without giving legal guarantees.',
            'sort_order' => 50,
        ],
        [
            'code' => 'insufficient_information',
            'fallback_name' => 'Insufficient information',
            'fallback_description' => 'The inquiry does not contain enough information to complete a review.',
            'ai_instruction' => 'Use when the inquiry lacks necessary details. Generate a response asking the applicant to provide specific missing information, documents, dates, names, or other evidence needed for review.',
            'sort_order' => 60,
        ],
        [
            'code' => 'forwarded',
            'fallback_name' => 'Forwarded',
            'fallback_description' => 'The inquiry was forwarded to the responsible department or authority.',
            'ai_instruction' => 'Use when the inquiry was transferred to another responsible unit or authority. Generate a response stating that the inquiry has been forwarded for consideration and identify the destination only when disclosure is appropriate.',
            'sort_order' => 70,
        ],
        [
            'code' => 'resolved',
            'fallback_name' => 'Resolved',
            'fallback_description' => 'The issue described in the inquiry has been resolved.',
            'ai_instruction' => 'Use when the issue has been resolved. Generate a response explaining that corrective or resolving action has been completed, keeping operational details concise and confidential where needed.',
            'sort_order' => 80,
        ],
        [
            'code' => 'withdrawn',
            'fallback_name' => 'Withdrawn by applicant',
            'fallback_description' => 'The applicant withdrew the inquiry before completion of review.',
            'ai_instruction' => 'Use when the applicant withdrew the inquiry. Generate a response confirming that the inquiry has been closed due to withdrawal and explain that a new submission may be filed if needed.',
            'sort_order' => 90,
        ],
        [
            'code' => 'spam_or_abuse',
            'fallback_name' => 'Spam or abuse',
            'fallback_description' => 'The inquiry is spam, abusive, irrelevant, or clearly not a valid request.',
            'ai_instruction' => 'Use when the inquiry is spam, abusive, irrelevant, or not reviewable. Generate a short neutral response, if a response is required, explaining that the submission cannot be processed because it does not meet requirements.',
            'sort_order' => 100,
        ],
        [
            'code' => 'requires_follow_up',
            'fallback_name' => 'Requires follow-up',
            'fallback_description' => 'The inquiry needs additional action or monitoring after the initial review.',
            'ai_instruction' => 'Use when additional follow-up is required. Generate a response explaining that the review is continuing or that follow-up actions are planned, without presenting the matter as finally resolved.',
            'sort_order' => 110,
        ],
        [
            'code' => 'no_action_required',
            'fallback_name' => 'No action required',
            'fallback_description' => 'The inquiry was reviewed and no further action is required.',
            'ai_instruction' => 'Use when the inquiry was reviewed and no action is required. Generate a polite response stating that the matter was considered and no further action will be taken based on the review.',
            'sort_order' => 120,
        ],
    ];

    public function index(LocalizationManager $localization): Response
    {
        $this->ensureInquiryOutcomesExist();

        $currentMessages = $localization->messages($localization->currentLocale());

        $categories = InquiryCategory::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (InquiryCategory $category): array => $this->categoryData(
                category: $category,
                messages: $currentMessages,
            ));

        $outcomes = InquiryOutcome::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (InquiryOutcome $outcome): array => $this->outcomeData(
                outcome: $outcome,
                messages: $currentMessages,
            ));

        return Inertia::render('Dictionaries', [
            'categories' => $categories,
            'outcomes' => $outcomes,
        ]);
    }

    public function store(InquiryCategoryStoreRequest $request): RedirectResponse
    {
        InquiryCategory::create([
            'fallback_name' => $request->validated('fallback_name'),
            'fallback_description' => $request->validated('fallback_description'),
            'review_days' => (int) $request->validated('review_days'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($request->validated('sort_order') ?? 0),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category created.')]);

        return back();
    }

    public function update(InquiryCategoryUpdateRequest $request, InquiryCategory $category): RedirectResponse
    {
        $category->update([
            'fallback_name' => $request->validated('fallback_name'),
            'fallback_description' => $request->validated('fallback_description'),
            'review_days' => (int) $request->validated('review_days'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($request->validated('sort_order') ?? 0),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category updated.')]);

        return back();
    }

    public function destroy(InquiryCategory $category): RedirectResponse
    {
        $category->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category deleted.')]);

        return back();
    }

    public function updateOutcome(InquiryOutcomeUpdateRequest $request, InquiryOutcome $outcome): RedirectResponse
    {
        $outcome->update([
            'fallback_name' => $request->validated('fallback_name'),
            'fallback_description' => $request->validated('fallback_description'),
            'ai_instruction' => $request->validated('ai_instruction'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) $request->validated('sort_order'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Review outcome updated.')]);

        return back();
    }

    /**
     * @param  array<string, string>  $messages
     * @return array{
     *     id: int,
     *     uuid: string,
     *     name_key: string,
     *     description_key: string,
     *     fallback_name: string,
     *     fallback_description: string|null,
     *     localized_name: string,
     *     localized_description: string|null,
     *     review_days: int,
     *     is_active: bool,
     *     sort_order: int
     * }
     */
    private function categoryData(InquiryCategory $category, array $messages): array
    {
        return [
            'id' => $category->id,
            'uuid' => $category->uuid,
            'name_key' => $category->name_key,
            'description_key' => $category->description_key,
            'fallback_name' => $category->fallback_name,
            'fallback_description' => $category->fallback_description,
            'localized_name' => $messages[$category->name_key] ?? $category->fallback_name,
            'localized_description' => $messages[$category->description_key] ?? $category->fallback_description,
            'review_days' => $category->review_days,
            'is_active' => $category->is_active,
            'sort_order' => $category->sort_order,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     code: string,
     *     name_key: string,
     *     description_key: string,
     *     fallback_name: string,
     *     fallback_description: string|null,
     *     localized_name: string,
     *     localized_description: string|null,
     *     ai_instruction: string,
     *     is_active: bool,
     *     sort_order: int
     * }
     */
    private function outcomeData(InquiryOutcome $outcome, array $messages): array
    {
        return [
            'id' => $outcome->id,
            'code' => $outcome->code,
            'name_key' => $outcome->name_key,
            'description_key' => $outcome->description_key,
            'fallback_name' => $outcome->fallback_name,
            'fallback_description' => $outcome->fallback_description,
            'localized_name' => $messages[$outcome->name_key] ?? $outcome->fallback_name,
            'localized_description' => $messages[$outcome->description_key] ?? $outcome->fallback_description,
            'ai_instruction' => $outcome->ai_instruction,
            'is_active' => $outcome->is_active,
            'sort_order' => $outcome->sort_order,
        ];
    }

    private function ensureInquiryOutcomesExist(): void
    {
        collect(self::SYSTEM_OUTCOMES)->each(function (array $outcome): void {
            $model = InquiryOutcome::query()->firstOrCreate(
                ['code' => $outcome['code']],
                [
                    'fallback_name' => $outcome['fallback_name'],
                    'fallback_description' => $outcome['fallback_description'],
                    'ai_instruction' => $outcome['ai_instruction'],
                    'is_active' => true,
                    'sort_order' => $outcome['sort_order'],
                ],
            );

            $model->forceFill([
                'name_key' => "inquiry_outcomes.{$outcome['code']}.name",
                'description_key' => "inquiry_outcomes.{$outcome['code']}.description",
            ])->save();
        });
    }
}
