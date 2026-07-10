<?php

namespace App\Services\AIAssistant;

use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class AssigneeRecommendationService
{
    /**
     * @return array<int, array{
     *     user_id: int,
     *     name: string,
     *     email: string,
     *     role: string|null,
     *     active_assignments_count: int,
     *     score: int,
     *     reason: string
     * }>
     */
    public function forInquiry(?Inquiry $inquiry): array
    {
        /** @var Collection<int, User> $users */
        $users = User::query()
            ->with('roles:id,name,fallback_label,ai_description')
            ->select(['id', 'name', 'email'])
            ->withCount([
                'assignedInquiries as active_assignments_count' => fn (Builder $query): Builder => $query
                    ->whereNotIn('status', [
                        Inquiry::STATUS_COMPLETED,
                        Inquiry::STATUS_REJECTED,
                        Inquiry::STATUS_WITHDRAWN,
                    ]),
            ])
            ->where('type', 'system')
            ->where('status', 'active')
            ->get();

        $context = $this->inquiryText($inquiry);

        return $users
            ->map(fn (User $user): array => $this->candidate($user, $context))
            ->sortBy([
                ['score', 'desc'],
                ['active_assignments_count', 'asc'],
                ['name', 'asc'],
            ])
            ->take(3)
            ->values()
            ->all();
    }

    public function promptContext(?Inquiry $inquiry): string
    {
        $recommendations = collect($this->forInquiry($inquiry))
            ->map(fn (array $candidate): string => sprintf(
                '- User ID %d: %s, role: %s, active assigned inquiries: %d, score: %d, reason: %s',
                $candidate['user_id'],
                $candidate['name'],
                $candidate['role'] ?? 'No role',
                $candidate['active_assignments_count'],
                $candidate['score'],
                $candidate['reason'],
            ))
            ->implode("\n");

        return "Pre-ranked specialist candidates:\n".($recommendations !== '' ? $recommendations : 'No active system specialists found.');
    }

    /**
     * @return array{
     *     user_id: int,
     *     name: string,
     *     email: string,
     *     role: string|null,
     *     active_assignments_count: int,
     *     score: int,
     *     reason: string
     * }
     */
    private function candidate(User $user, string $context): array
    {
        /** @var Role|null $role */
        $role = $user->roles->first();
        $activeAssignmentsCount = (int) ($user->active_assignments_count ?? 0);
        $relevance = $this->roleRelevance($role, $context);
        $loadPenalty = min($activeAssignmentsCount, 10);
        $score = max(0, ($relevance * 10) + 20 - ($loadPenalty * 2));

        return [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role === null ? null : $this->roleLabel($role),
            'active_assignments_count' => $activeAssignmentsCount,
            'score' => $score,
            'reason' => $this->reason($role, $relevance, $activeAssignmentsCount),
        ];
    }

    private function inquiryText(?Inquiry $inquiry): string
    {
        if ($inquiry === null) {
            return '';
        }

        $inquiry->loadMissing('category:id,fallback_name,fallback_description');

        return Str::lower(implode(' ', [
            $inquiry->title,
            $inquiry->description,
            $inquiry->category?->fallback_name,
            $inquiry->category?->fallback_description,
        ]));
    }

    private function roleRelevance(?Role $role, string $context): int
    {
        if ($role === null || $context === '') {
            return 0;
        }

        $roleText = Str::lower(implode(' ', [
            $role->name,
            $role->fallback_label,
            $role->ai_description,
        ]));

        preg_match_all('/[\pL\pN_]{4,}/u', $roleText, $matches);

        return collect($matches[0] ?? [])
            ->unique()
            ->reject(fn (string $word): bool => in_array($word, $this->stopWords(), true))
            ->filter(fn (string $word): bool => Str::contains($context, $word))
            ->count();
    }

    private function reason(?Role $role, int $relevance, int $activeAssignmentsCount): string
    {
        $roleLabel = $role === null ? 'No role' : $this->roleLabel($role);

        if ($relevance > 0) {
            return __('Role :role matches the inquiry context; active assignments: :count.', [
                'role' => $roleLabel,
                'count' => $activeAssignmentsCount,
            ]);
        }

        return __('Lowest available workload among system specialists; active assignments: :count.', [
            'count' => $activeAssignmentsCount,
        ]);
    }

    private function roleLabel(Role $role): string
    {
        return $role->fallback_label ?: Str::headline($role->name);
    }

    /**
     * @return array<int, string>
     */
    private function stopWords(): array
    {
        return [
            'about',
            'and',
            'handles',
            'inquiries',
            'questions',
            'role',
            'with',
        ];
    }
}
