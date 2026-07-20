<?php

use App\Models\Inquiry;
use App\Models\InquiryOutcome;
use App\Models\InquiryResponse;
use App\Models\InquiryResponseAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('assigned responder can attach documents when saving a draft', function () {
    Storage::fake('local');

    $author = inquiryUser(['inquiries.view', 'inquiries.respond']);
    $outcome = InquiryOutcome::factory()->create();
    $inquiry = Inquiry::factory()->create(['assigned_to_id' => $author->id]);
    $document = UploadedFile::fake()->create('legal-opinion.pdf', 250, 'application/pdf');

    $this->actingAs($author)
        ->post(route('inquiries.response.draft', $inquiry), [
            '_method' => 'PATCH',
            'inquiry_outcome_id' => $outcome->id,
            'body' => 'Draft with an attachment.',
            'attachments' => [$document],
        ])
        ->assertSessionHasNoErrors();

    $attachment = InquiryResponseAttachment::query()->firstOrFail();

    expect($attachment->response->inquiry_id)->toBe($inquiry->id)
        ->and($attachment->original_name)->toBe('legal-opinion.pdf')
        ->and($inquiry->events()->where('type', 'response_attachment_uploaded')->exists())->toBeTrue();
    Storage::disk('local')->assertExists($attachment->path);

    $this->actingAs($author)
        ->get(route('inquiries.show', $inquiry))
        ->assertInertia(fn ($page) => $page
            ->where('inquiry.response.attachments.0.id', $attachment->uuid)
            ->where('inquiry.response.attachments.0.originalName', 'legal-opinion.pdf'));
});

test('response attachment validation rejects unsupported documents', function () {
    Storage::fake('local');

    $author = inquiryUser(['inquiries.view', 'inquiries.respond']);
    $inquiry = Inquiry::factory()->create(['assigned_to_id' => $author->id]);

    $this->actingAs($author)
        ->post(route('inquiries.response.draft', $inquiry), [
            '_method' => 'PATCH',
            'body' => 'Unsafe attachment.',
            'attachments' => [
                UploadedFile::fake()->create('program.exe', 100, 'application/octet-stream'),
            ],
        ])
        ->assertSessionHasErrors('attachments.0');

    expect($inquiry->response()->exists())->toBeFalse()
        ->and(InquiryResponseAttachment::query()->exists())->toBeFalse();
});

test('a response cannot contain more than ten attachments', function () {
    Storage::fake('local');

    $author = inquiryUser(['inquiries.view', 'inquiries.respond']);
    $inquiry = Inquiry::factory()->create(['assigned_to_id' => $author->id]);
    $response = InquiryResponse::factory()->create([
        'inquiry_id' => $inquiry->id,
        'authored_by_id' => $author->id,
    ]);
    InquiryResponseAttachment::factory()->count(10)->create([
        'inquiry_response_id' => $response->id,
        'uploaded_by_id' => $author->id,
    ]);

    $this->actingAs($author)
        ->post(route('inquiries.response.draft', $inquiry), [
            '_method' => 'PATCH',
            'body' => $response->body,
            'attachments' => [
                UploadedFile::fake()->create('additional.pdf', 100, 'application/pdf'),
            ],
        ])
        ->assertSessionHasErrors('attachments');

    expect($response->attachments()->count())->toBe(10);
});

test('visible reviewer can download but cannot remove a response attachment', function () {
    Storage::fake('local');

    $author = inquiryUser(['inquiries.view', 'inquiries.respond']);
    $reviewer = inquiryUser(['inquiries.view', 'inquiries.approve']);
    $inquiry = Inquiry::factory()->create(['assigned_to_id' => $author->id]);
    $response = InquiryResponse::factory()->pendingApproval($reviewer)->create([
        'inquiry_id' => $inquiry->id,
        'authored_by_id' => $author->id,
    ]);
    $attachment = InquiryResponseAttachment::factory()->create([
        'inquiry_response_id' => $response->id,
        'uploaded_by_id' => $author->id,
        'path' => "inquiry-responses/{$response->uuid}/attachments/reply.pdf",
    ]);
    Storage::disk('local')->put($attachment->path, 'pdf contents');

    $this->actingAs($reviewer)
        ->get(route('inquiries.response.attachments.download', [$inquiry, $attachment]))
        ->assertOk()
        ->assertDownload($attachment->original_name);

    $this->actingAs($reviewer)
        ->delete(route('inquiries.response.attachments.destroy', [$inquiry, $attachment]))
        ->assertForbidden();

    expect($attachment->fresh())->not->toBeNull();
    Storage::disk('local')->assertExists($attachment->path);
});

test('assigned responder can remove an attachment only while response is editable', function () {
    Storage::fake('local');

    $author = inquiryUser(['inquiries.view', 'inquiries.respond']);
    $inquiry = Inquiry::factory()->create(['assigned_to_id' => $author->id]);
    $response = InquiryResponse::factory()->create([
        'inquiry_id' => $inquiry->id,
        'authored_by_id' => $author->id,
    ]);
    $attachment = InquiryResponseAttachment::factory()->create([
        'inquiry_response_id' => $response->id,
        'uploaded_by_id' => $author->id,
    ]);
    Storage::disk('local')->put($attachment->path, 'pdf contents');

    $this->actingAs($author)
        ->delete(route('inquiries.response.attachments.destroy', [$inquiry, $attachment]))
        ->assertSessionHasNoErrors();

    expect($attachment->fresh())->toBeNull()
        ->and($inquiry->events()->where('type', 'response_attachment_removed')->exists())->toBeTrue();
    Storage::disk('local')->assertMissing($attachment->path);
});
