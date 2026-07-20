<?php

use App\Actions\Inquiries\CreateInquiry;
use App\Jobs\GenerateInquiryReportJob;
use App\Models\Inquiry;
use App\Models\InquiryCategory;
use App\Models\InquiryEvent;
use App\Models\InquiryOutcome;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;

function inquiryHistoryUser(array $permissions): User
{
    if (in_array('inquiries.view', $permissions, true)
        && ! in_array('inquiries.view_all', $permissions, true)
        && ! in_array('inquiries.view_assigned', $permissions, true)) {
        $permissions[] = 'inquiries.view_all';
    }

    collect($permissions)
        ->each(fn (string $permission): Permission => Permission::findOrCreate($permission));

    $user = User::factory()->create(['status' => 'active']);
    $user->givePermissionTo($permissions);

    return $user;
}

test('creating an inquiry records its initial audit event', function () {
    $creator = inquiryHistoryUser(['inquiries.view']);
    $category = InquiryCategory::factory()->create(['fallback_name' => 'Ethics']);

    $inquiry = app(CreateInquiry::class)->handle([
        'category' => $category,
        'creator' => $creator,
        'title' => 'Audit event inquiry',
        'submitted_at' => Carbon::parse('2026-07-13 10:15:00'),
    ]);

    $event = $inquiry->events()->sole();

    expect($event->type)->toBe('inquiry_created')
        ->and($event->actor_id)->toBe($creator->id)
        ->and($event->actor_name)->toBe($creator->name)
        ->and($event->metadata['category']['name'])->toBe('Ethics')
        ->and($event->created_at->format('Y-m-d H:i'))->toBe('2026-07-13 10:15');
});

test('assignment changes record who acted and the previous and next executors', function () {
    $actor = inquiryHistoryUser(['inquiries.view', 'inquiries.assign']);
    $first = inquiryHistoryUser(['inquiries.view', 'inquiries.respond']);
    $second = inquiryHistoryUser(['inquiries.view', 'inquiries.respond']);
    $inquiry = Inquiry::factory()->create([
        'assigned_to_id' => null,
        'status' => Inquiry::STATUS_NEW,
    ]);

    $this->actingAs($actor)
        ->patch(route('inquiries.assignee.update', $inquiry), ['assigned_to_id' => $first->id])
        ->assertSessionHasNoErrors();
    $this->actingAs($actor)
        ->patch(route('inquiries.assignee.update', $inquiry), ['assigned_to_id' => $second->id])
        ->assertSessionHasNoErrors();
    $this->actingAs($actor)
        ->patch(route('inquiries.assignee.update', $inquiry), ['assigned_to_id' => null])
        ->assertSessionHasNoErrors();

    $events = $inquiry->events()->orderBy('id')->get();

    expect($events->pluck('type')->all())->toBe([
        'assignee_assigned',
        'assignee_reassigned',
        'assignee_unassigned',
    ])->and($events[0]->actor_name)->toBe($actor->name)
        ->and($events[0]->metadata['to']['name'])->toBe($first->name)
        ->and($events[0]->metadata['inquiry_status_from'])->toBe(Inquiry::STATUS_NEW)
        ->and($events[0]->metadata['inquiry_status_to'])->toBe(Inquiry::STATUS_IN_PROGRESS)
        ->and($events[1]->metadata['from']['name'])->toBe($first->name)
        ->and($events[1]->metadata['to']['name'])->toBe($second->name)
        ->and($events[2]->metadata['from']['name'])->toBe($second->name)
        ->and($events[2]->metadata['to'])->toBeNull()
        ->and($inquiry->fresh()->status)->toBe(Inquiry::STATUS_IN_PROGRESS);
});

test('assignment does not overwrite a non-new inquiry status', function (string $status) {
    $actor = inquiryHistoryUser(['inquiries.view', 'inquiries.assign']);
    $assignee = inquiryHistoryUser(['inquiries.view', 'inquiries.respond']);
    $inquiry = Inquiry::factory()->create([
        'assigned_to_id' => null,
        'status' => $status,
    ]);

    $this->actingAs($actor)
        ->patch(route('inquiries.assignee.update', $inquiry), ['assigned_to_id' => $assignee->id])
        ->assertSessionHasNoErrors();

    expect($inquiry->fresh()->status)->toBe($status)
        ->and($inquiry->events()->sole()->metadata)
        ->not->toHaveKeys(['inquiry_status_from', 'inquiry_status_to']);
})->with([
    'suspended' => Inquiry::STATUS_SUSPENDED,
]);

test('category changes record snapshots and the recalculated deadline', function () {
    $actor = inquiryHistoryUser(['inquiries.view', 'inquiries.update']);
    $oldCategory = InquiryCategory::factory()->create([
        'fallback_name' => 'Old category',
        'review_days' => 10,
    ]);
    $newCategory = InquiryCategory::factory()->create([
        'fallback_name' => 'New category',
        'review_days' => 30,
    ]);
    $inquiry = Inquiry::factory()->create([
        'inquiry_category_id' => $oldCategory->id,
        'submitted_at' => Carbon::parse('2026-07-01 09:00:00'),
        'review_days' => 10,
        'review_due_date' => '2026-07-11',
    ]);

    $this->actingAs($actor)
        ->patch(route('inquiries.category.update', $inquiry), [
            'inquiry_category_id' => $newCategory->id,
        ])
        ->assertSessionHasNoErrors();

    $event = $inquiry->events()->sole();

    expect($event->type)->toBe('category_changed')
        ->and($event->actor_name)->toBe($actor->name)
        ->and($event->metadata['from']['name'])->toBe('Old category')
        ->and($event->metadata['to']['name'])->toBe('New category')
        ->and($event->metadata['to']['review_due_date'])->toBe('2026-07-31');
});

test('the inquiry page exposes real history in newest first order', function () {
    $viewer = inquiryHistoryUser(['inquiries.view']);
    $inquiry = Inquiry::factory()->create();
    InquiryEvent::factory()->for($inquiry)->create([
        'actor_id' => $viewer->id,
        'actor_name' => $viewer->name,
        'type' => 'assignee_assigned',
        'created_at' => Carbon::parse('2026-07-13 09:00:00'),
    ]);
    $latest = InquiryEvent::factory()->for($inquiry)->create([
        'actor_id' => $viewer->id,
        'actor_name' => $viewer->name,
        'type' => 'category_changed',
        'created_at' => Carbon::parse('2026-07-13 10:00:00'),
    ]);

    $this->actingAs($viewer)
        ->get(route('inquiries.show', $inquiry))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('inquiry.historyCount', 2)
            ->has('inquiry.history', 2)
            ->where('inquiry.history.0.id', $latest->id)
            ->where('inquiry.history.0.type', 'category_changed')
            ->where('inquiry.history.0.time', '10:00')
            ->where('inquiry.history.1.type', 'assignee_assigned'));
});

test('requesting a report records the initiator and language', function () {
    Queue::fake();
    $user = inquiryHistoryUser(['inquiries.view']);
    $inquiry = Inquiry::factory()->create();

    $this->actingAs($user)
        ->postJson(route('inquiries.report.store', $inquiry), ['language' => 'ru'])
        ->assertCreated();

    $event = $inquiry->events()->sole();

    expect($event->type)->toBe('report_requested')
        ->and($event->actor_name)->toBe($user->name)
        ->and($event->metadata['language'])->toBe('ru');
    Queue::assertPushed(GenerateInquiryReportJob::class);
});

test('actor snapshots remain after a user is deleted', function () {
    $actor = inquiryHistoryUser(['inquiries.view', 'inquiries.assign']);
    $assignee = inquiryHistoryUser(['inquiries.view', 'inquiries.respond']);
    $inquiry = Inquiry::factory()->create(['status' => Inquiry::STATUS_NEW]);

    $this->actingAs($actor)
        ->patch(route('inquiries.assignee.update', $inquiry), ['assigned_to_id' => $assignee->id])
        ->assertSessionHasNoErrors();

    $actorName = $actor->name;
    $actor->delete();
    $event = $inquiry->events()->sole()->refresh();

    expect($event->actor_id)->toBeNull()
        ->and($event->actor_name)->toBe($actorName);
});

test('history localizes inquiry outcomes and hides technical language metadata', function () {
    $viewer = inquiryHistoryUser(['inquiries.view']);
    $outcome = InquiryOutcome::factory()->create([
        'code' => 'requires_follow_up',
        'fallback_name' => 'Requires follow-up',
    ]);
    $inquiry = Inquiry::factory()->create();
    InquiryEvent::factory()->for($inquiry)->create([
        'type' => 'response_generated',
        'metadata' => [
            'outcome_id' => $outcome->id,
            'outcome_name' => 'Requires follow-up',
            'language' => 'ru',
        ],
    ]);

    $this->actingAs($viewer)
        ->withCookie('locale', 'ru')
        ->get(route('inquiries.show', $inquiry))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('inquiry.history.0.metadata.outcome_name', 'Требует контроля')
            ->missing('inquiry.history.0.metadata.language'));

    expect($inquiry->events()->sole()->metadata['language'])->toBe('ru');
});
