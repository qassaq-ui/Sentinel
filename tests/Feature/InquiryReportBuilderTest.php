<?php

use App\Actions\Inquiries\CreateInquiry;
use App\Models\InquiryAttachment;
use App\Models\InquiryCategory;
use App\Models\InquiryComment;
use App\Models\InquiryCommentAttachment;
use App\Models\InquiryEvent;
use App\Models\InquiryOutcome;
use App\Models\InquiryResponse;
use App\Models\InquiryResponseAttachment;
use App\Models\InquiryResponseEvent;
use App\Models\User;
use App\Services\AIAssistant\InquiryReportBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

test('a completed report can be downloaded or regenerated in another language', function () {
    $component = file_get_contents(resource_path('js/pages/Inquiries/Show.vue'));

    expect($component)
        ->toContain("report.state.status === 'completed'")
        ->toContain('@click="report.download()"')
        ->toContain("t('Regenerate report')")
        ->toContain('@select="report.generate(language.code)"');
});

test('inquiry report builder parses all marked sections and enforces report language', function () {
    config([
        'services.ai_assistant.base_url' => 'http://127.0.0.1:1337/v1',
        'services.ai_assistant.model' => 'janhq/Jan-code-4b-Q4_K_M',
    ]);

    Http::fake([
        'http://127.0.0.1:1337/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => implode("\n\n", [
                            '[SUMMARY]',
                            'Краткое содержание на русском языке.',
                            '[FACTS]',
                            'Факты изложены на русском языке.',
                            '[CATEGORY]',
                            'Категория обоснована на русском языке.',
                            '[PROCESSING]',
                            'Ход рассмотрения изложен по хронологии.',
                            '[RESPONSES]',
                            'Ответ и результат рассмотрения указаны.',
                            '[MATERIALS]',
                            'Материалы и комментарии перечислены.',
                            '[RISK]',
                            'Уровень риска: medium. Обоснование на русском языке.',
                            '[RECOMMENDATIONS]',
                            '1. Проверить обстоятельства. Приоритет: high.',
                            '[CONCLUSION]',
                            'Заключение на русском языке.',
                        ]),
                    ],
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create();
    $category = InquiryCategory::factory()->create([
        'fallback_name' => 'Ethics and conduct violations',
        'fallback_description' => 'Complaints about unethical behavior and pressure.',
        'review_days' => 30,
    ]);
    $inquiry = app(CreateInquiry::class)->handle([
        'category' => $category,
        'creator' => $user,
        'title' => 'Қызметкерге қысым жасау',
        'description' => 'Басшы қызметкерді дәлелсіз қатты сынаған жағдай бірнеше рет қайталанды.',
        'submitted_at' => Carbon::parse('2026-07-10 09:00:00'),
    ]);
    $reviewer = User::factory()->create(['name' => 'Согласующий сотрудник']);
    $outcome = InquiryOutcome::factory()->create([
        'fallback_name' => 'Нарушение подтверждено',
        'fallback_description' => 'Факты подтверждены по результатам рассмотрения.',
    ]);
    $response = InquiryResponse::factory()->create([
        'inquiry_id' => $inquiry->id,
        'inquiry_outcome_id' => $outcome->id,
        'authored_by_id' => $user->id,
        'reviewer_id' => $reviewer->id,
        'reviewed_by_id' => $reviewer->id,
        'sent_by_id' => $reviewer->id,
        'body' => 'Итоговый мотивированный ответ заявителю.',
        'status' => InquiryResponse::STATUS_SENT,
        'review_comment' => 'Ответ согласован без замечаний.',
        'submitted_at' => Carbon::parse('2026-07-11 10:00:00'),
        'reviewed_at' => Carbon::parse('2026-07-11 12:00:00'),
        'sent_at' => Carbon::parse('2026-07-11 14:00:00'),
    ]);
    InquiryAttachment::factory()->create([
        'inquiry_id' => $inquiry->id,
        'original_name' => 'первичное-обращение.pdf',
    ]);
    InquiryResponseAttachment::factory()->create([
        'inquiry_response_id' => $response->id,
        'original_name' => 'официальный-ответ.pdf',
    ]);
    InquiryResponseEvent::factory()->create([
        'inquiry_response_id' => $response->id,
        'user_id' => $reviewer->id,
        'type' => 'approved',
        'status_from' => InquiryResponse::STATUS_PENDING_APPROVAL,
        'status_to' => InquiryResponse::STATUS_APPROVED,
        'comment' => 'Согласовано.',
    ]);
    $comment = InquiryComment::factory()->create([
        'inquiry_id' => $inquiry->id,
        'inquiry_response_id' => $response->id,
        'user_id' => $reviewer->id,
        'author_name' => $reviewer->name,
        'body' => 'Проверить соблюдение срока ответа.',
    ]);
    InquiryCommentAttachment::factory()->create([
        'inquiry_comment_id' => $comment->id,
        'original_name' => 'замечания-согласующего.pdf',
    ]);
    InquiryEvent::factory()->create([
        'inquiry_id' => $inquiry->id,
        'actor_id' => $reviewer->id,
        'actor_name' => $reviewer->name,
        'type' => 'response_approved',
        'metadata' => ['comment' => 'Согласовано.'],
        'created_at' => Carbon::parse('2026-07-11 12:00:00'),
    ]);

    $report = app(InquiryReportBuilder::class)->build($inquiry, 'ru');

    expect($report)
        ->summary->toBe('Краткое содержание на русском языке.')
        ->facts->toBe('Факты изложены на русском языке.')
        ->category->toBe('Категория обоснована на русском языке.')
        ->processing->toBe('Ход рассмотрения изложен по хронологии.')
        ->responses->toBe('Ответ и результат рассмотрения указаны.')
        ->materials->toBe('Материалы и комментарии перечислены.')
        ->risk->toBe('Уровень риска: medium. Обоснование на русском языке.')
        ->recommendations->toBe('1. Проверить обстоятельства. Приоритет: high.')
        ->conclusion->toBe('Заключение на русском языке.');

    Http::assertSent(fn ($request): bool => str_contains($request['messages'][0]['content'], 'Write the ENTIRE report in Russian')
        && str_contains($request['messages'][0]['content'], 'translate and summarize its meaning in Russian')
        && str_contains($request['messages'][0]['content'], 'Do not present allegations as established facts')
        && str_contains($request['messages'][0]['content'], 'Attachment data contains metadata only')
        && str_contains($request['messages'][1]['content'], 'The source inquiry may be in another language')
        && str_contains($request['messages'][1]['content'], 'Қызметкерге қысым жасау')
        && str_contains($request['messages'][1]['content'], 'первичное-обращение.pdf')
        && str_contains($request['messages'][1]['content'], 'официальный-ответ.pdf')
        && str_contains($request['messages'][1]['content'], 'Проверить соблюдение срока ответа.')
        && str_contains($request['messages'][1]['content'], 'замечания-согласующего.pdf')
        && str_contains($request['messages'][1]['content'], 'Нарушение подтверждено')
        && str_contains($request['messages'][1]['content'], 'Итоговый мотивированный ответ заявителю.')
        && str_contains($request['messages'][1]['content'], 'Ответ согласован без замечаний.')
        && str_contains($request['messages'][1]['content'], 'response_approved'));
});

test('inquiry report builder falls back to summary when ai omits markers', function () {
    Http::fake([
        '*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Свободный отчет без маркеров.',
                    ],
                ],
            ],
        ]),
    ]);

    $category = InquiryCategory::factory()->create();
    $inquiry = app(CreateInquiry::class)->handle([
        'category' => $category,
        'creator' => User::factory()->create(),
        'title' => 'Test inquiry',
        'description' => 'Test description.',
        'submitted_at' => Carbon::parse('2026-07-10 09:00:00'),
    ]);

    $report = app(InquiryReportBuilder::class)->build($inquiry, 'ru');

    expect($report['summary'])->toBe('Свободный отчет без маркеров.')
        ->and($report['facts'])->toBe('');
});

test('inquiry report builder understands markdown section headings', function () {
    Http::fake([
        '*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => implode("\n\n", [
                            '**Краткое содержание:**',
                            'Русский пересказ обращения.',
                            '**Факты:**',
                            'Факты на русском.',
                            '**Рекомендации:**',
                            '1. Назначить проверку.',
                        ]),
                    ],
                ],
            ],
        ]),
    ]);

    $category = InquiryCategory::factory()->create();
    $inquiry = app(CreateInquiry::class)->handle([
        'category' => $category,
        'creator' => User::factory()->create(),
        'title' => 'Қазақша тақырып',
        'description' => 'Қазақша сипаттама.',
        'submitted_at' => Carbon::parse('2026-07-10 09:00:00'),
    ]);

    $report = app(InquiryReportBuilder::class)->build($inquiry, 'ru');

    expect($report['summary'])->toBe('Русский пересказ обращения.')
        ->and($report['facts'])->toBe('Факты на русском.')
        ->and($report['recommendations'])->toBe('1. Назначить проверку.');
});
