<?php

use App\Actions\Inquiries\CreateInquiry;
use App\Models\Inquiry;
use App\Models\InquiryCategory;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('the AI assistant widget is only mounted on the inquiry detail page', function () {
    $appLayout = file_get_contents(resource_path('js/layouts/app/AppSidebarLayout.vue'));
    $inquiryDetailPage = file_get_contents(resource_path('js/pages/Inquiries/Show.vue'));

    expect($appLayout)
        ->not->toContain('AIAssistantWidget')
        ->and($inquiryDetailPage)
        ->toContain("import AIAssistantWidget from '@/components/AIAssistantWidget.vue';")
        ->toContain('<AIAssistantWidget />');
});

test('authenticated users can ask the AI assistant to analyze an inquiry', function () {
    config([
        'services.ai_assistant.base_url' => 'http://127.0.0.1:1337/v1',
        'services.ai_assistant.model' => 'janhq/Jan-v3-4b-base-instruct-Q4_K_XL',
    ]);

    Http::fake([
        'http://127.0.0.1:1337/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Краткий анализ обращения.',
                    ],
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo([
        Permission::findOrCreate('inquiries.view'),
        Permission::findOrCreate('inquiries.view_all'),
    ]);
    $category = InquiryCategory::factory()->create([
        'fallback_name' => 'Safety',
        'fallback_description' => 'Safety risks and missing protective equipment.',
        'review_days' => 15,
    ]);
    $inquiry = app(CreateInquiry::class)->handle([
        'category' => $category,
        'creator' => $user,
        'title' => 'Missing protective guard',
        'description' => 'The equipment guard was removed.',
        'submitted_at' => Carbon::parse('2026-07-10 19:13:00'),
    ]);

    $this
        ->actingAs($user)
        ->postJson(route('ai-assistant.chat'), [
            'job' => 'analyze_inquiry',
            'message' => 'Что изменилось?',
            'history' => [
                [
                    'role' => 'user',
                    'content' => 'Сначала кратко проанализируй обращение.',
                ],
                [
                    'role' => 'assistant',
                    'content' => 'Есть риск по безопасности оборудования.',
                ],
            ],
            'locale' => 'ru',
            'inquiry_number' => $inquiry->number,
        ])
        ->assertOk()
        ->assertJson([
            'message' => 'Краткий анализ обращения.',
        ]);

    Http::assertSent(fn ($request): bool => $request->url() === 'http://127.0.0.1:1337/v1/chat/completions'
        && $request['model'] === 'janhq/Jan-v3-4b-base-instruct-Q4_K_XL'
        && str_contains($request['messages'][0]['content'], 'Always answer in the current site language: Russian (ru)')
        && str_contains($request['messages'][1]['content'], 'Previous conversation in this open AI drawer')
        && str_contains($request['messages'][1]['content'], 'Assistant: Есть риск по безопасности оборудования.')
        && str_contains($request['messages'][1]['content'], 'Additional user instruction:')
        && str_contains($request['messages'][1]['content'], 'Что изменилось?')
        && str_contains($request['messages'][1]['content'], 'Missing protective guard'));
});

test('the AI assistant returns a temporary unavailable response when local service fails', function () {
    Http::fake([
        '*' => Http::response([], 500),
    ]);

    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->postJson(route('ai-assistant.chat'), [
            'job' => 'recommend_response',
            'message' => 'Что делать дальше?',
            'locale' => 'ru',
        ])
        ->assertStatus(502)
        ->assertJson([
            'message' => 'ИИ ассистент временно недоступен.',
        ]);
});

test('the AI assistant does not expose inquiry context without view permission', function () {
    $user = User::factory()->create();
    $inquiry = Inquiry::factory()->create();

    $this->actingAs($user)
        ->postJson(route('ai-assistant.chat'), [
            'job' => 'analyze_inquiry',
            'locale' => 'ru',
            'inquiry_number' => $inquiry->number,
        ])
        ->assertForbidden();
});

test('the AI assistant recommends assignees by role relevance and active workload', function () {
    Http::fake([
        '*' => Http::response([], 500),
    ]);

    $requestUser = User::factory()->create();
    $requestUser->givePermissionTo([
        Permission::findOrCreate('inquiries.view'),
        Permission::findOrCreate('inquiries.view_all'),
    ]);
    $category = InquiryCategory::factory()->create([
        'fallback_name' => 'Safety',
        'fallback_description' => 'Safety risks, equipment hazards, and missing protective equipment.',
        'review_days' => 15,
    ]);
    $inquiry = app(CreateInquiry::class)->handle([
        'category' => $category,
        'creator' => $requestUser,
        'title' => 'Missing protective guard',
        'description' => 'The equipment guard was removed and workers use the machine without protection.',
        'submitted_at' => Carbon::parse('2026-07-10 19:13:00'),
    ]);

    $safetyRole = Role::create([
        'name' => 'safety_specialist',
        'fallback_label' => 'Safety specialist',
        'guard_name' => 'web',
        'ai_description' => 'Handles workplace safety, equipment hazards, PPE, incidents, and safety procedure violations.',
    ]);
    $financeRole = Role::create([
        'name' => 'financial_security_specialist',
        'fallback_label' => 'Financial security specialist',
        'guard_name' => 'web',
        'ai_description' => 'Handles fraud, theft, financial abuse, supplier risks, and asset misuse.',
    ]);

    $safetySpecialist = User::factory()->create([
        'name' => 'Safety Specialist',
        'status' => 'active',
    ]);
    $financeSpecialist = User::factory()->create([
        'name' => 'Finance Specialist',
        'status' => 'active',
    ]);

    $safetySpecialist->assignRole($safetyRole);
    $financeSpecialist->assignRole($financeRole);

    app(CreateInquiry::class)->handle([
        'category' => $category,
        'creator' => $requestUser,
        'title' => 'Assigned safety issue',
        'description' => 'Another safety issue.',
        'submitted_at' => Carbon::parse('2026-07-09 10:00:00'),
    ])->forceFill(['assigned_to_id' => $safetySpecialist->id])->save();

    $this
        ->actingAs($requestUser)
        ->postJson(route('ai-assistant.chat'), [
            'job' => 'recommend_assignee',
            'message' => '',
            'locale' => 'ru',
            'inquiry_number' => $inquiry->number,
        ])
        ->assertOk()
        ->assertJsonPath('recommendations.0.user_id', $safetySpecialist->id)
        ->assertJsonPath('recommendations.0.active_assignments_count', 1)
        ->assertJsonPath('recommendations.0.role', 'Safety specialist')
        ->assertJsonPath(
            'recommendations.0.reason',
            fn (string $reason): bool => str_contains($reason, 'активных назначений: 1')
        );
});
