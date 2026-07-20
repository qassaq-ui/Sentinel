<?php

use App\Models\Inquiry;
use App\Models\InquiryResponse;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;

function visibilityUser(array $permissions): User
{
    collect($permissions)
        ->each(fn (string $permission): Permission => Permission::findOrCreate($permission));

    $user = User::factory()->create(['status' => 'active']);
    $user->givePermissionTo($permissions);

    return $user;
}

test('view all permission exposes every inquiry', function () {
    $user = visibilityUser(['inquiries.view', 'inquiries.view_all']);
    $first = Inquiry::factory()->create();
    $second = Inquiry::factory()->create();

    $this->actingAs($user)
        ->get(route('inquiries.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('allInquiries.data', 2)
            ->where('allInquiries.data', fn ($inquiries): bool => collect($inquiries)
                ->pluck('id')
                ->sort()
                ->values()
                ->all() === collect([$first->id, $second->id])->sort()->values()->all()));

    $this->actingAs($user)
        ->get(route('inquiries.show', $first))
        ->assertOk();
});

test('an employee without a visibility scope sees no inquiries', function () {
    $user = visibilityUser(['inquiries.view']);
    $inquiry = Inquiry::factory()->create();

    $this->actingAs($user)
        ->get(route('inquiries.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('allInquiries.data', 0));

    $this->actingAs($user)
        ->get(route('inquiries.show', $inquiry))
        ->assertForbidden();
});

test('a former executor loses every inquiry access after reassignment', function () {
    $manager = visibilityUser(['inquiries.view', 'inquiries.view_all', 'inquiries.assign']);
    $former = visibilityUser(['inquiries.view', 'inquiries.view_assigned', 'inquiries.respond']);
    $current = visibilityUser(['inquiries.view', 'inquiries.view_assigned', 'inquiries.respond']);
    $inquiry = Inquiry::factory()->create([
        'assigned_to_id' => $former->id,
        'status' => Inquiry::STATUS_IN_PROGRESS,
    ]);

    $this->actingAs($former)
        ->get(route('inquiries.show', $inquiry))
        ->assertOk();

    $this->actingAs($manager)
        ->patch(route('inquiries.assignee.update', $inquiry), ['assigned_to_id' => $current->id])
        ->assertSessionHasNoErrors();

    $this->actingAs($former)
        ->get(route('inquiries.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('allInquiries.data', 0));

    $this->actingAs($former)
        ->get(route('inquiries.show', $inquiry))
        ->assertForbidden();

    $this->actingAs($former)
        ->post(route('inquiries.translate', $inquiry), ['language' => 'ru'])
        ->assertForbidden();

    $this->actingAs($former)
        ->get(route('inquiries.report.show', $inquiry))
        ->assertForbidden();

    $this->actingAs($former)
        ->postJson(route('ai-assistant.chat'), [
            'job' => 'analyze_inquiry',
            'message' => '',
            'locale' => 'ru',
            'inquiry_number' => $inquiry->number,
        ])
        ->assertForbidden();

    $this->actingAs($current)
        ->get(route('inquiries.show', $inquiry))
        ->assertOk();
});

test('a selected approver sees only inquiries awaiting their review', function () {
    $reviewer = visibilityUser(['inquiries.view', 'inquiries.approve']);
    $otherReviewer = visibilityUser(['inquiries.view', 'inquiries.approve']);
    $mine = Inquiry::factory()->create();
    $other = Inquiry::factory()->create();

    InquiryResponse::factory()->pendingApproval($reviewer)->create(['inquiry_id' => $mine->id]);
    InquiryResponse::factory()->pendingApproval($otherReviewer)->create(['inquiry_id' => $other->id]);

    $this->actingAs($reviewer)
        ->get(route('inquiries.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('allInquiries.data', 1)
            ->where('allInquiries.data.0.id', $mine->id)
            ->has('approvalInquiries.data', 1)
            ->where('approvalInquiries.data.0.id', $mine->id));

    $this->actingAs($reviewer)
        ->get(route('inquiries.show', $mine))
        ->assertOk();

    $this->actingAs($reviewer)
        ->get(route('inquiries.show', $other))
        ->assertForbidden();
});
