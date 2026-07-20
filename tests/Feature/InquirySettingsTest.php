<?php

use App\Models\Inquiry;
use App\Models\InquiryCategory;
use App\Models\InquirySetting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;

function inquirySettingsAdministrator(): User
{
    Permission::findOrCreate('settings.access');

    $user = User::factory()->create();
    $user->givePermissionTo('settings.access');

    return $user;
}

test('settings page exposes inquiry settings and inquiry tab', function () {
    $user = inquirySettingsAdministrator();

    $this
        ->actingAs($user)
        ->get(route('settings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Index')
            ->where('inquirySettings.numberPrefix', 'KAZM')
            ->where('inquirySettings.sequencePadding', 4)
            ->where('inquirySettings.aiScreeningEnabled', false)
            ->where('inquirySettings.aiScreeningInstructions', InquirySetting::DEFAULT_SCREENING_INSTRUCTIONS)
        );

    expect(file_get_contents(resource_path('js/pages/settings/SettingsTabs.vue')))
        ->toContain("value: 'inquiries'")
        ->toContain("label: 'Inquiries'")
        ->toContain('bg-white text-[#1d1d1f]')
        ->not->toContain('activeTabIndex')
        ->and(file_get_contents(resource_path('js/pages/settings/InquirySettingsPanel.vue')))
        ->toContain('role="switch"')
        ->toContain('Admission criteria')
        ->toContain('Safe fallback')
        ->toContain('sticky bottom-0')
        ->not->toContain('form.processing || !form.isDirty')
        ->and(file_get_contents(resource_path('js/layouts/settings/Layout.vue')))
        ->toContain('bg-white text-[#1d1d1f]')
        ->toContain('overflow-hidden')
        ->and(file_get_contents(resource_path('js/pages/settings/Index.vue')))
        ->toContain("sessionStorage.getItem('settings.activeTab')")
        ->toContain('border-y border-black/8')
        ->and(file_get_contents(resource_path('js/pages/settings/InquirySettingsPanel.vue')))
        ->toContain('bg-[#f7f7f8]')
        ->and(file_get_contents(resource_path('js/pages/settings/LocalizationSettingsPanel.vue')))
        ->toContain('bg-[#f7f7f8]');
});

test('profile and security settings share the internal page shell', function () {
    $profile = file_get_contents(resource_path('js/pages/settings/Profile.vue'));
    $security = file_get_contents(resource_path('js/pages/settings/Security.vue'));

    expect($profile)
        ->toContain('text-[1.75rem]')
        ->toContain('border-y border-black/8')
        ->toContain('scroll-region')
        ->and($security)
        ->toContain('text-[1.75rem]')
        ->toContain('border-y border-black/8')
        ->toContain('scroll-region');
});

test('authorized user can update inquiry settings', function () {
    $user = inquirySettingsAdministrator();

    $this
        ->actingAs($user)
        ->patch(route('settings.inquiries.update'), [
            'number_prefix' => 'su',
            'sequence_padding' => 6,
            'ai_screening_enabled' => true,
            'ai_screening_instructions' => 'Accept company misconduct reports. Reject unrelated household requests.',
        ])
        ->assertRedirect();

    $settings = InquirySetting::query()->sole();

    expect($settings->number_prefix)->toBe('SU')
        ->and($settings->sequence_padding)->toBe(6)
        ->and($settings->ai_screening_enabled)->toBeTrue()
        ->and($settings->ai_screening_instructions)
        ->toBe('Accept company misconduct reports. Reject unrelated household requests.');
});

test('inquiry settings require permission and valid criteria', function () {
    Permission::findOrCreate('settings.access');
    $user = User::factory()->create();

    $payload = [
        'number_prefix' => 'INVALID PREFIX',
        'sequence_padding' => 2,
        'ai_screening_enabled' => true,
        'ai_screening_instructions' => '',
    ];

    $this
        ->actingAs($user)
        ->patch(route('settings.inquiries.update'), $payload)
        ->assertForbidden();

    expect(InquirySetting::query()->count())->toBe(0);

    $user->givePermissionTo('settings.access');

    $this
        ->actingAs($user)
        ->patch(route('settings.inquiries.update'), $payload)
        ->assertSessionHasErrors([
            'number_prefix',
            'sequence_padding',
            'ai_screening_instructions',
        ]);

    expect(InquirySetting::query()->count())->toBe(0);
});

test('configured prefix and padding are used for new inquiry numbers', function () {
    InquirySetting::factory()->create([
        'number_prefix' => 'SPEAK',
        'sequence_padding' => 6,
    ]);
    $category = InquiryCategory::factory()->create();

    $this->post(route('public-inquiries.store'), [
        'submission_mode' => 'anonymous',
        'inquiry_category_id' => $category->id,
        'title' => 'Safety concern',
        'description' => 'A detailed safety concern connected to the company.',
    ])->assertRedirect(route('home'));

    expect(Inquiry::query()->sole()->number)
        ->toMatch('/^SPEAK-\d{4}-000001$/');
});

test('ai screening rejects a clearly irrelevant inquiry when enabled', function () {
    InquirySetting::factory()->create([
        'ai_screening_enabled' => true,
        'ai_screening_instructions' => 'Reject unrelated household requests.',
    ]);
    $category = InquiryCategory::factory()->create(['fallback_name' => 'Other']);

    Http::fake([
        '*' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'decision' => 'reject',
                        'confidence' => 0.98,
                        'reason' => 'Unrelated household request.',
                    ]),
                ],
            ]],
        ]),
    ]);

    $response = $this->post(route('public-inquiries.store'), [
        'submission_mode' => 'anonymous',
        'inquiry_category_id' => $category->id,
        'title' => 'My key is broken',
        'description' => 'I cannot open the door to my apartment.',
    ]);

    $response
        ->assertSessionHasErrors('admission')
        ->assertSessionHasErrors([
            'admission' => 'The information provided does not fall within the scope of matters handled through the Speak Up channel and cannot be registered as an inquiry in this system. For guidance or referral to the appropriate service, please contact senimkmm@kazminerals.com.',
        ]);

    expect(Inquiry::query()->count())->toBe(0);
});

test('uncertain or failed ai screening accepts the inquiry for human review', function (string $responseType) {
    InquirySetting::factory()->create(['ai_screening_enabled' => true]);
    $category = InquiryCategory::factory()->create(['fallback_name' => 'Other']);

    if ($responseType === 'uncertain') {
        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => '{"decision":"reject","confidence":0.52,"reason":"Uncertain relevance."}',
                    ],
                ]],
            ]),
        ]);
    } else {
        Http::fake(['*' => Http::response([], 503)]);
    }

    $this->post(route('public-inquiries.store'), [
        'submission_mode' => 'anonymous',
        'inquiry_category_id' => $category->id,
        'title' => 'Request requiring review',
        'description' => 'This situation may be connected to company operations.',
    ])->assertRedirect(route('home'));

    expect(Inquiry::query()->count())->toBe(1);
})->with(['uncertain', 'unavailable']);
