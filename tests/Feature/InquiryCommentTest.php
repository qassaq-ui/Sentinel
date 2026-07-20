<?php

use App\Models\Inquiry;
use App\Models\InquiryComment;
use App\Models\InquiryCommentAttachment;
use App\Models\InquiryResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('current executor can add a comment with a private attachment', function () {
    Storage::fake('local');

    $executor = inquiryUser(['inquiries.view', 'inquiries.respond']);
    $inquiry = Inquiry::factory()->create(['assigned_to_id' => $executor->id]);
    InquiryResponse::factory()->create([
        'inquiry_id' => $inquiry->id,
        'authored_by_id' => $executor->id,
    ]);

    $this->actingAs($executor)
        ->post(route('inquiries.comments.store', $inquiry), [
            'body' => 'Прошу проверить приложенный документ.',
            'attachments' => [
                UploadedFile::fake()->create('document.pdf', 120, 'application/pdf'),
            ],
        ])
        ->assertSessionHasNoErrors();

    $comment = InquiryComment::query()->sole();
    $attachment = $comment->attachments()->sole();

    expect($comment->body)->toBe('Прошу проверить приложенный документ.')
        ->and($comment->user_id)->toBe($executor->id)
        ->and($inquiry->events()->where('type', 'comment_added')->exists())->toBeTrue();
    Storage::disk('local')->assertExists($attachment->path);

    $this->actingAs($executor)
        ->get(route('inquiries.comments.attachments.download', [$inquiry, $attachment]))
        ->assertOk()
        ->assertDownload('document.pdf');
});

test('designated reviewer can reply and replies are grouped under the root comment', function () {
    $executor = inquiryUser(['inquiries.view', 'inquiries.respond']);
    $reviewer = inquiryUser(['inquiries.view', 'inquiries.approve']);
    $inquiry = Inquiry::factory()->create(['assigned_to_id' => $executor->id]);
    $response = InquiryResponse::factory()->pendingApproval($reviewer)->create([
        'inquiry_id' => $inquiry->id,
        'authored_by_id' => $executor->id,
    ]);
    $root = InquiryComment::factory()->create([
        'inquiry_id' => $inquiry->id,
        'inquiry_response_id' => $response->id,
        'user_id' => $executor->id,
    ]);

    $this->actingAs($reviewer)
        ->post(route('inquiries.comments.store', $inquiry), [
            'body' => 'Ответ согласующего.',
            'parent_id' => $root->uuid,
        ])
        ->assertSessionHasNoErrors();

    $reply = InquiryComment::query()->whereKeyNot($root->id)->sole();
    expect($reply->parent_id)->toBe($root->id)
        ->and($reply->user_id)->toBe($reviewer->id);

    $this->actingAs($executor)
        ->get(route('inquiries.show', $inquiry).'?tab=comments')
        ->assertInertia(fn ($page) => $page
            ->where('inquiry.comments.data.0.id', $root->uuid)
            ->where('inquiry.comments.data.0.canDelete', true)
            ->where('inquiry.comments.data.0.replies.0.body', 'Ответ согласующего.')
            ->where('inquiry.comments.data.0.replies.0.canDelete', false)
            ->where('responsePermissions.comment', true));
});

test('comment owner can delete a comment and its private attachments', function () {
    Storage::fake('local');

    $executor = inquiryUser(['inquiries.view', 'inquiries.respond']);
    $inquiry = Inquiry::factory()->create(['assigned_to_id' => $executor->id]);
    $response = InquiryResponse::factory()->create([
        'inquiry_id' => $inquiry->id,
        'authored_by_id' => $executor->id,
    ]);
    $comment = InquiryComment::factory()->create([
        'inquiry_id' => $inquiry->id,
        'inquiry_response_id' => $response->id,
        'user_id' => $executor->id,
    ]);
    $attachment = InquiryCommentAttachment::factory()->create([
        'inquiry_comment_id' => $comment->id,
        'disk' => 'local',
        'path' => "inquiry-comments/{$comment->uuid}/attachments/file.pdf",
    ]);
    Storage::disk('local')->put($attachment->path, 'document');

    $this->actingAs($executor)
        ->delete(route('inquiries.comments.destroy', [$inquiry, $comment]))
        ->assertSessionHasNoErrors();

    expect(InquiryComment::withTrashed()->findOrFail($comment->id)->trashed())->toBeTrue()
        ->and(InquiryCommentAttachment::query()->whereKey($attachment->id)->exists())->toBeFalse()
        ->and($inquiry->events()->where('type', 'comment_deleted')->exists())->toBeTrue();
    Storage::disk('local')->assertMissing($attachment->path);
});

test('deleted root comment remains as a tombstone when it has replies', function () {
    $executor = inquiryUser(['inquiries.view', 'inquiries.respond']);
    $reviewer = inquiryUser(['inquiries.view', 'inquiries.approve']);
    $inquiry = Inquiry::factory()->create(['assigned_to_id' => $executor->id]);
    $response = InquiryResponse::factory()->pendingApproval($reviewer)->create([
        'inquiry_id' => $inquiry->id,
        'authored_by_id' => $executor->id,
    ]);
    $root = InquiryComment::factory()->create([
        'inquiry_id' => $inquiry->id,
        'inquiry_response_id' => $response->id,
        'user_id' => $executor->id,
    ]);
    $reply = InquiryComment::factory()->create([
        'inquiry_id' => $inquiry->id,
        'inquiry_response_id' => $response->id,
        'user_id' => $reviewer->id,
        'parent_id' => $root->id,
        'body' => 'Ответ остаётся в обсуждении.',
    ]);

    $this->actingAs($executor)
        ->delete(route('inquiries.comments.destroy', [$inquiry, $root]))
        ->assertSessionHasNoErrors();

    $this->actingAs($reviewer)
        ->get(route('inquiries.show', $inquiry).'?tab=comments')
        ->assertInertia(fn ($page) => $page
            ->where('inquiry.comments.data.0.id', $root->uuid)
            ->where('inquiry.comments.data.0.deleted', true)
            ->where('inquiry.comments.data.0.canDelete', false)
            ->where('inquiry.comments.data.0.replies.0.id', $reply->uuid)
            ->where('inquiry.comments.data.0.replies.0.body', 'Ответ остаётся в обсуждении.'));
});

test('user cannot delete another users comment', function () {
    $executor = inquiryUser(['inquiries.view', 'inquiries.respond']);
    $reviewer = inquiryUser(['inquiries.view', 'inquiries.approve']);
    $inquiry = Inquiry::factory()->create(['assigned_to_id' => $executor->id]);
    $response = InquiryResponse::factory()->pendingApproval($reviewer)->create([
        'inquiry_id' => $inquiry->id,
        'authored_by_id' => $executor->id,
    ]);
    $comment = InquiryComment::factory()->create([
        'inquiry_id' => $inquiry->id,
        'inquiry_response_id' => $response->id,
        'user_id' => $executor->id,
    ]);

    $this->actingAs($reviewer)
        ->delete(route('inquiries.comments.destroy', [$inquiry, $comment]))
        ->assertForbidden();

    expect($comment->fresh()->trashed())->toBeFalse();
});

test('unrelated approver cannot comment on the response', function () {
    $executor = inquiryUser(['inquiries.view', 'inquiries.respond']);
    $reviewer = inquiryUser(['inquiries.view', 'inquiries.approve']);
    $otherApprover = inquiryUser(['inquiries.view', 'inquiries.approve']);
    $inquiry = Inquiry::factory()->create(['assigned_to_id' => $executor->id]);
    InquiryResponse::factory()->pendingApproval($reviewer)->create([
        'inquiry_id' => $inquiry->id,
        'authored_by_id' => $executor->id,
    ]);

    $this->actingAs($otherApprover)
        ->post(route('inquiries.comments.store', $inquiry), ['body' => 'Недопустимый комментарий.'])
        ->assertForbidden();

    expect(InquiryComment::query()->exists())->toBeFalse();
});

test('root comment threads are paginated with five threads per page', function () {
    $executor = inquiryUser(['inquiries.view', 'inquiries.respond']);
    $inquiry = Inquiry::factory()->create(['assigned_to_id' => $executor->id]);
    $response = InquiryResponse::factory()->create([
        'inquiry_id' => $inquiry->id,
        'authored_by_id' => $executor->id,
    ]);
    InquiryComment::factory()->count(6)->create([
        'inquiry_id' => $inquiry->id,
        'inquiry_response_id' => $response->id,
        'user_id' => $executor->id,
    ]);

    $this->actingAs($executor)
        ->get(route('inquiries.show', $inquiry).'?tab=comments&comments_page=2')
        ->assertInertia(fn ($page) => $page
            ->where('inquiry.comments.currentPage', 2)
            ->where('inquiry.comments.lastPage', 2)
            ->where('inquiry.comments.total', 6)
            ->has('inquiry.comments.data', 1)
            ->where('inquiry.commentsCount', 6));
});

test('comments are locked after the response is sent', function () {
    $executor = inquiryUser(['inquiries.view', 'inquiries.respond']);
    $inquiry = Inquiry::factory()->create(['assigned_to_id' => $executor->id]);
    $response = InquiryResponse::factory()->create([
        'inquiry_id' => $inquiry->id,
        'authored_by_id' => $executor->id,
        'status' => InquiryResponse::STATUS_SENT,
    ]);
    $comment = InquiryComment::factory()->create([
        'inquiry_id' => $inquiry->id,
        'inquiry_response_id' => $response->id,
        'user_id' => $executor->id,
    ]);

    $this->actingAs($executor)
        ->post(route('inquiries.comments.store', $inquiry), ['body' => 'Поздний комментарий.'])
        ->assertForbidden();

    $this->actingAs($executor)
        ->delete(route('inquiries.comments.destroy', [$inquiry, $comment]))
        ->assertForbidden();
});

test('workflow review comment cannot be deleted as a regular comment', function () {
    $reviewer = inquiryUser(['inquiries.view', 'inquiries.approve']);
    $inquiry = Inquiry::factory()->create();
    $response = InquiryResponse::factory()->pendingApproval($reviewer)->create([
        'inquiry_id' => $inquiry->id,
    ]);
    $comment = InquiryComment::factory()->create([
        'inquiry_id' => $inquiry->id,
        'inquiry_response_id' => $response->id,
        'user_id' => $reviewer->id,
        'source' => 'review',
    ]);

    $this->actingAs($reviewer)
        ->delete(route('inquiries.comments.destroy', [$inquiry, $comment]))
        ->assertForbidden();
});
