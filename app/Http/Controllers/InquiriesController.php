<?php

namespace App\Http\Controllers;

use App\Actions\Inquiries\RecordInquiryEvent;
use App\Actions\Inquiries\UpdateInquiryAssignee;
use App\Actions\Inquiries\UpdateInquiryCategory;
use App\Http\Requests\InquiryAssigneeUpdateRequest;
use App\Http\Requests\InquiryCategoryAssignmentUpdateRequest;
use App\Http\Requests\InquiryTranslationRequest;
use App\Models\Inquiry;
use App\Models\InquiryCategory;
use App\Models\InquiryComment;
use App\Models\InquiryEvent;
use App\Models\InquiryOutcome;
use App\Models\InquiryResponse;
use App\Models\User;
use App\Services\AIAssistant\InquiryTranslationService;
use App\Support\Localization\LocalizationManager;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Throwable;

class InquiriesController extends Controller
{
    private const int INQUIRIES_PER_PAGE = 15;

    public function index(Request $request, LocalizationManager $localization): Response
    {
        /** @var User $user */
        $user = $request->user();
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
            'allInquiries' => Inertia::scroll($this->inquiries($user, 'allInquiries', $messages, fn (Builder $query): Builder => $query->notArchived())),
            'anonymousInquiries' => Inertia::scroll($this->inquiries($user, 'anonymousInquiries', $messages, fn (Builder $query): Builder => $query
                ->notArchived()
                ->where('type', Inquiry::TYPE_ANONYMOUS))),
            'archivedInquiries' => Inertia::scroll($this->inquiries($user, 'archivedInquiries', $messages, fn (Builder $query): Builder => $query->archived())),
            'approvalInquiries' => Inertia::scroll($this->inquiries(
                $user,
                'approvalInquiries',
                $messages,
                fn (Builder $query): Builder => $query
                    ->notArchived()
                    ->whereHas(
                        'response',
                        fn (Builder $responseQuery): Builder => $responseQuery
                            ->where('status', InquiryResponse::STATUS_PENDING_APPROVAL)
                            ->where('reviewer_id', $request->user()?->id),
                    ),
            )),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Inquiries/Create');
    }

    public function show(Request $request, Inquiry $inquiry, LocalizationManager $localization): Response
    {
        Gate::authorize('view', $inquiry);

        $messages = $localization->messages($localization->currentLocale());
        $inquiry->load([
            'assignee:id,name,email',
            'assignee.roles:id,name,fallback_label',
            'attachments:id,inquiry_id,original_name,mime_type,extension,file_type,size_bytes,created_at',
            'category:id,name_key,fallback_name',
            'applicant:id,inquiry_id,name,email,phone',
            'creator:id,name,email',
            'response.outcome:id,name_key,fallback_name,description_key,fallback_description',
            'response.inquiry:id,number',
            'response.author:id,name,email',
            'response.reviewer:id,name,email',
            'response.reviewedBy:id,name,email',
            'response.sentBy:id,name,email',
            'response.attachments:id,uuid,inquiry_response_id,original_name,mime_type,extension,size_bytes,created_at',
            'events' => fn (HasMany $query): HasMany => $query
                ->latest('created_at')
                ->latest('id'),
        ])->loadCount(['attachments', 'events']);

        $comments = $this->commentsData($inquiry);

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
                'applicantName' => $inquiry->type === Inquiry::TYPE_ANONYMOUS
                    ? null
                    : ($inquiry->applicant?->name ?? $inquiry->creator?->name),
                'applicantPhone' => $inquiry->type === Inquiry::TYPE_ANONYMOUS ? null : $inquiry->applicant?->phone,
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
                'commentsCount' => $inquiry->comments()->count(),
                'comments' => $comments,
                'historyCount' => $inquiry->events_count,
                'history' => $this->historyData($inquiry, $messages),
                'response' => $this->responseData($inquiry->response, $messages),
            ],
            'categories' => $this->categories($messages),
            'systemUsers' => $this->systemUsers(),
            'outcomes' => $this->outcomes($messages),
            'reviewers' => $this->reviewers($request->user()?->id),
            'canAssignExecutor' => $request->user()?->can('assign', $inquiry) ?? false,
            'responsePermissions' => [
                'respond' => $request->user()?->can('respond', $inquiry) ?? false,
                'review' => $inquiry->response !== null
                    && ($request->user()?->can('review', $inquiry->response) ?? false),
                'send' => $inquiry->response !== null
                    && ($request->user()?->can('send', $inquiry->response) ?? false),
                'comment' => $request->user()?->can('comment', $inquiry) ?? false,
            ],
        ]);
    }

    public function updateCategory(
        InquiryCategoryAssignmentUpdateRequest $request,
        Inquiry $inquiry,
        UpdateInquiryCategory $updateCategory,
    ): RedirectResponse {
        $category = InquiryCategory::query()
            ->where('is_active', true)
            ->findOrFail($request->integer('inquiry_category_id'));

        /** @var User $actor */
        $actor = $request->user();
        $updateCategory->handle($inquiry, $category, $actor);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category updated.')]);

        return back();
    }

    public function updateAssignee(
        InquiryAssigneeUpdateRequest $request,
        Inquiry $inquiry,
        UpdateInquiryAssignee $updateAssignee,
    ): RedirectResponse {
        $assignedToId = $request->validated('assigned_to_id');

        /** @var User $actor */
        $actor = $request->user();
        $updateAssignee->handle($inquiry, $actor, $assignedToId);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $assignedToId === null ? __('Executor unassigned.') : __('Executor assigned.'),
        ]);

        return back();
    }

    public function translate(
        InquiryTranslationRequest $request,
        Inquiry $inquiry,
        InquiryTranslationService $translationService,
        RecordInquiryEvent $recordEvent,
    ): JsonResponse {
        $language = (string) $request->validated('language');

        try {
            $result = $translationService->translateDescription($inquiry, $language);

            $recordEvent->handle($inquiry, 'description_translated', $request->user(), [
                'language' => $language,
                'from_cache' => $result['from_cache'],
            ]);

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
    private function inquiries(User $user, string $pageName, array $messages, callable $constraint): LengthAwarePaginator
    {
        $inquiries = fn (?int $page = null): LengthAwarePaginator => $constraint(Inquiry::query()->visibleTo($user))
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
            'archived' => $inquiry->isArchived(),
        ]);
    }

    private function formatDateTime(CarbonInterface $date): string
    {
        return $date->format('d.m.Y, H:i');
    }

    /**
     * @param  array<string, string>  $messages
     * @return array<int, array<string, mixed>>
     */
    private function historyData(Inquiry $inquiry, array $messages): array
    {
        $outcomeIds = $inquiry->events
            ->pluck('metadata.outcome_id')
            ->filter(fn (mixed $id): bool => is_int($id) || (is_string($id) && ctype_digit($id)))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        $outcomeNames = InquiryOutcome::query()
            ->whereKey($outcomeIds)
            ->get(['id', 'name_key', 'fallback_name'])
            ->mapWithKeys(fn (InquiryOutcome $outcome): array => [
                $outcome->id => $messages[$outcome->name_key] ?? $outcome->fallback_name,
            ]);

        return $inquiry->events
            ->map(function (InquiryEvent $event) use ($outcomeNames): array {
                $metadata = $event->metadata ?? [];
                $outcomeId = $metadata['outcome_id'] ?? null;
                unset($metadata['language'], $metadata['from_cache']);

                if ((is_int($outcomeId) || (is_string($outcomeId) && ctype_digit($outcomeId)))) {
                    $metadata['outcome_name'] = $outcomeNames->get((int) $outcomeId)
                        ?? ($metadata['outcome_name'] ?? null);
                }

                return [
                    'id' => $event->id,
                    'type' => $event->type,
                    'actorName' => $event->actor_name,
                    'actorRole' => $event->actor_role,
                    'metadata' => $metadata,
                    'date' => $event->created_at->format('d.m.Y'),
                    'time' => $event->created_at->format('H:i'),
                    'createdAt' => $this->formatDateTime($event->created_at),
                ];
            })
            ->values()
            ->all();
    }

    /** @return array{data: array<int, array<string, mixed>>, currentPage: int, lastPage: int, total: int} */
    private function commentsData(Inquiry $inquiry): array
    {
        $comments = $inquiry->comments()
            ->withTrashed()
            ->whereNull('parent_id')
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('deleted_at')
                ->orWhereHas('replies'))
            ->with([
                'attachments:id,uuid,inquiry_comment_id,original_name,mime_type,extension,size_bytes',
                'replies:id,uuid,inquiry_id,inquiry_response_id,user_id,parent_id,author_name,author_role,body,source,created_at',
                'replies.attachments:id,uuid,inquiry_comment_id,original_name,mime_type,extension,size_bytes',
            ])
            ->latest('id')
            ->paginate(5, ['*'], 'comments_page')
            ->withQueryString();

        return [
            'data' => collect($comments->items())->map(fn (InquiryComment $comment): array => $this->commentData($inquiry, $comment))->all(),
            'currentPage' => $comments->currentPage(),
            'lastPage' => $comments->lastPage(),
            'total' => $comments->total(),
        ];
    }

    /** @return array<string, mixed> */
    private function commentData(Inquiry $inquiry, InquiryComment $comment): array
    {
        $comment->setRelation('inquiry', $inquiry);
        $isDeleted = $comment->trashed();

        return [
            'id' => $comment->uuid,
            'body' => $isDeleted ? __('Comment deleted.') : $comment->body,
            'authorName' => $isDeleted ? null : $comment->author_name,
            'authorRole' => $isDeleted ? null : $comment->author_role,
            'source' => $comment->source,
            'createdAt' => $this->formatDateTime($comment->created_at),
            'deleted' => $isDeleted,
            'canDelete' => ! $isDeleted && Gate::allows('delete', $comment),
            'attachments' => $isDeleted ? [] : $comment->attachments->map(fn ($attachment): array => [
                'id' => $attachment->uuid,
                'originalName' => $attachment->original_name,
                'extension' => $attachment->extension,
                'sizeBytes' => $attachment->size_bytes,
                'downloadUrl' => route('inquiries.comments.attachments.download', [$inquiry, $attachment]),
            ])->all(),
            'replies' => $comment->relationLoaded('replies')
                ? $comment->replies->map(fn (InquiryComment $reply): array => $this->commentData($inquiry, $reply))->all()
                : [],
        ];
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
            ->where('status', 'active')
            ->permission('inquiries.respond')
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->filter(fn (User $user): bool => $user->can('inquiries.view')
                && ($user->can('inquiries.view_assigned') || $user->can('inquiries.view_all')))
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first() === null ? null : $this->roleLabel($user->roles->first()),
            ])
            ->all();
    }

    /** @param array<string, string> $messages */
    private function outcomes(array $messages): array
    {
        return InquiryOutcome::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name_key', 'fallback_name', 'description_key', 'fallback_description'])
            ->map(fn (InquiryOutcome $outcome): array => [
                'id' => $outcome->id,
                'name' => $messages[$outcome->name_key] ?? $outcome->fallback_name,
                'description' => $messages[$outcome->description_key] ?? $outcome->fallback_description,
            ])
            ->all();
    }

    /** @return array<int, array{id: int, name: string, email: string}> */
    private function reviewers(?int $excludeUserId): array
    {
        return User::query()
            ->select(['id', 'name', 'email'])
            ->where('status', 'active')
            ->when($excludeUserId !== null, fn (Builder $query): Builder => $query->whereKeyNot($excludeUserId))
            ->permission('inquiries.approve')
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user): bool => $user->can('inquiries.view'))
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->all();
    }

    /**
     * @param  array<string, string>  $messages
     * @return array<string, mixed>|null
     */
    private function responseData(?InquiryResponse $response, array $messages): ?array
    {
        if ($response === null) {
            return null;
        }

        return [
            'id' => $response->id,
            'status' => $response->status,
            'outcomeId' => $response->inquiry_outcome_id,
            'outcomeName' => $response->outcome === null
                ? null
                : ($messages[$response->outcome->name_key] ?? $response->outcome->fallback_name),
            'body' => $response->body,
            'author' => $this->responseUser($response->author),
            'reviewer' => $this->responseUser($response->reviewer),
            'reviewedBy' => $this->responseUser($response->reviewedBy),
            'sentBy' => $this->responseUser($response->sentBy),
            'reviewComment' => $response->review_comment,
            'submittedAt' => $response->submitted_at?->format('d.m.Y, H:i'),
            'reviewedAt' => $response->reviewed_at?->format('d.m.Y, H:i'),
            'sentAt' => $response->sent_at?->format('d.m.Y, H:i'),
            'attachments' => $response->attachments
                ->sortBy('id')
                ->map(fn ($attachment): array => [
                    'id' => $attachment->uuid,
                    'originalName' => $attachment->original_name,
                    'mimeType' => $attachment->mime_type,
                    'extension' => $attachment->extension,
                    'sizeBytes' => $attachment->size_bytes,
                    'uploadedAt' => $this->formatDateTime($attachment->created_at),
                    'downloadUrl' => route('inquiries.response.attachments.download', [
                        'inquiry' => $response->inquiry->number,
                        'attachment' => $attachment->uuid,
                    ]),
                ])
                ->values()
                ->all(),
        ];
    }

    /** @return array{id: int, name: string, email: string}|null */
    private function responseUser(?User $user): ?array
    {
        return $user === null ? null : [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
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
