<?php

namespace App\Http\Controllers;

use App\Http\Requests\InquiryAssigneeUpdateRequest;
use App\Http\Requests\InquiryCategoryAssignmentUpdateRequest;
use App\Http\Requests\InquiryTranslationRequest;
use App\Models\Inquiry;
use App\Models\InquiryCategory;
use App\Models\User;
use App\Services\AIAssistant\InquiryTranslationService;
use App\Support\Localization\LocalizationManager;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Throwable;

class InquiriesController extends Controller
{
    private const int INQUIRIES_PER_PAGE = 15;

    public function index(LocalizationManager $localization): Response
    {
        $messages = $localization->messages($localization->currentLocale());

        $categories = InquiryCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name_key', 'fallback_name', 'review_days'])
            ->map(fn (InquiryCategory $category): array => [
                'id' => $category->id,
                'name' => $messages[$category->name_key] ?? $category->fallback_name,
                'reviewDays' => $category->review_days,
            ])
            ->values();

        return Inertia::render('Inquiries', [
            'categories' => $categories,
            'allInquiries' => Inertia::scroll($this->inquiries('allInquiries', $messages, fn (Builder $query): Builder => $query->whereNull('archived_at'))),
            'anonymousInquiries' => Inertia::scroll($this->inquiries('anonymousInquiries', $messages, fn (Builder $query): Builder => $query->where('type', Inquiry::TYPE_ANONYMOUS))),
            'archivedInquiries' => Inertia::scroll($this->inquiries('archivedInquiries', $messages, fn (Builder $query): Builder => $query->whereNotNull('archived_at'))),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Inquiries/Create');
    }

    public function show(Inquiry $inquiry, LocalizationManager $localization): Response
    {
        $messages = $localization->messages($localization->currentLocale());
        $inquiry->load([
            'assignee:id,name,email',
            'assignee.roles:id,name,fallback_label',
            'attachments:id,inquiry_id,original_name,mime_type,extension,file_type,size_bytes,created_at',
            'category:id,name_key,fallback_name',
            'creator:id,name,email',
        ])->loadCount('attachments');

        return Inertia::render('Inquiries/Show', [
            'inquiry' => [
                'id' => $inquiry->id,
                'number' => $inquiry->number,
                'type' => $inquiry->type,
                'status' => $inquiry->status,
                'subject' => $inquiry->title,
                'description' => $inquiry->description,
                'categoryId' => $inquiry->inquiry_category_id,
                'categoryName' => $messages[$inquiry->category->name_key] ?? $inquiry->category->fallback_name,
                'submittedAt' => $this->formatDateTime($inquiry->submitted_at),
                'reviewDueDate' => $inquiry->review_due_date->format('d.m.Y'),
                'source' => $inquiry->type === Inquiry::TYPE_ANONYMOUS ? 'Anonymous web form' : 'Website',
                'applicantName' => $inquiry->type === Inquiry::TYPE_ANONYMOUS ? null : $inquiry->creator?->name,
                'applicantPhone' => null,
                'assignee' => $inquiry->assignee === null ? null : [
                    'id' => $inquiry->assignee->id,
                    'name' => $inquiry->assignee->name,
                    'email' => $inquiry->assignee->email,
                    'role' => $inquiry->assignee->roles->first() === null
                        ? null
                        : $this->roleLabel($inquiry->assignee->roles->first()),
                ],
                'location' => null,
                'attachmentsCount' => $inquiry->attachments_count,
                'attachments' => $inquiry->attachments
                    ->sortBy('id')
                    ->map(fn ($attachment): array => [
                        'id' => $attachment->id,
                        'originalName' => $attachment->original_name,
                        'mimeType' => $attachment->mime_type,
                        'extension' => $attachment->extension,
                        'fileType' => $attachment->file_type,
                        'sizeBytes' => $attachment->size_bytes,
                        'uploadedAt' => $this->formatDateTime($attachment->created_at),
                    ])
                    ->values()
                    ->all(),
                'commentsCount' => 0,
                'historyCount' => 2,
            ],
            'categories' => $this->categories($messages),
            'systemUsers' => $this->systemUsers(),
        ]);
    }

    public function updateCategory(InquiryCategoryAssignmentUpdateRequest $request, Inquiry $inquiry): RedirectResponse
    {
        $category = InquiryCategory::query()
            ->where('is_active', true)
            ->findOrFail($request->integer('inquiry_category_id'));

        $inquiry->forceFill([
            'inquiry_category_id' => $category->id,
            'review_days' => $category->review_days,
            'review_due_date' => $inquiry->submitted_at->copy()->addDays($category->review_days)->toDateString(),
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category updated.')]);

        return back();
    }

    public function updateAssignee(InquiryAssigneeUpdateRequest $request, Inquiry $inquiry): RedirectResponse
    {
        $assignedToId = $request->validated('assigned_to_id');

        $inquiry->forceFill([
            'assigned_to_id' => $assignedToId,
        ])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $assignedToId === null ? __('Executor unassigned.') : __('Executor assigned.'),
        ]);

        return back();
    }

    public function translate(InquiryTranslationRequest $request, Inquiry $inquiry, InquiryTranslationService $translationService): JsonResponse
    {
        $language = (string) $request->validated('language');

        try {
            $result = $translationService->translateDescription($inquiry, $language);

            return response()->json([
                'description' => $result['description'],
                'language' => $result['language'],
                'fromCache' => $result['from_cache'],
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => __('Translation is temporarily unavailable.'),
            ], 502);
        }
    }

    /**
     * @param  array<string, string>  $messages
     * @param  callable(Builder<Inquiry>): Builder<Inquiry>  $constraint
     * @return LengthAwarePaginator<int, array{
     *     id: int,
     *     number: string,
     *     type: string,
     *     status: string,
     *     daysLeft: int,
     *     subject: string,
     *     categoryId: int,
     *     categoryName: string,
     *     submittedDate: string,
     *     submittedAt: string,
     *     anonymous: bool,
     *     archived: bool
     * }>
     */
    private function inquiries(string $pageName, array $messages, callable $constraint): LengthAwarePaginator
    {
        $inquiries = fn (?int $page = null): LengthAwarePaginator => $constraint(Inquiry::query())
            ->with('category:id,name_key,fallback_name')
            ->select([
                'id',
                'number',
                'type',
                'status',
                'inquiry_category_id',
                'title',
                'submitted_at',
                'review_days',
                'archived_at',
            ])
            ->latest('submitted_at')
            ->latest('id')
            ->paginate(self::INQUIRIES_PER_PAGE, ['*'], $pageName, $page);

        $paginator = $inquiries();

        if ($paginator->count() === 0 && $paginator->currentPage() > 1 && $paginator->total() > 0) {
            $paginator = $inquiries(1);
        }

        return $paginator->through(fn (Inquiry $inquiry): array => [
            'id' => $inquiry->id,
            'number' => $inquiry->number,
            'type' => $inquiry->type,
            'status' => $inquiry->status,
            'daysLeft' => $inquiry->review_days,
            'subject' => $inquiry->title,
            'categoryId' => $inquiry->inquiry_category_id,
            'categoryName' => $messages[$inquiry->category->name_key] ?? $inquiry->category->fallback_name,
            'submittedDate' => $inquiry->submitted_at->toDateString(),
            'submittedAt' => $this->formatDateTime($inquiry->submitted_at),
            'anonymous' => $inquiry->type === Inquiry::TYPE_ANONYMOUS,
            'archived' => $inquiry->archived_at !== null,
        ]);
    }

    private function formatDateTime(CarbonInterface $date): string
    {
        return $date->format('d.m.Y, H:i');
    }

    /**
     * @param  array<string, string>  $messages
     * @return array<int, array{id: int, name: string, reviewDays: int}>
     */
    private function categories(array $messages): array
    {
        return InquiryCategory::query()
            ->select(['id', 'name_key', 'fallback_name', 'review_days'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (InquiryCategory $category): array => [
                'id' => $category->id,
                'name' => $messages[$category->name_key] ?? $category->fallback_name,
                'reviewDays' => $category->review_days,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string, email: string, role: string|null}>
     */
    private function systemUsers(): array
    {
        return User::query()
            ->with('roles:id,name,fallback_label')
            ->select(['id', 'name', 'email'])
            ->where('type', 'system')
            ->where('status', 'active')
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first() === null ? null : $this->roleLabel($user->roles->first()),
            ])
            ->all();
    }

    private function roleLabel(Role $role): string
    {
        return $role->fallback_label ?: match ($role->name) {
            'admin' => 'Administrator',
            'user' => 'User',
            default => Str::headline($role->name),
        };
    }
}
