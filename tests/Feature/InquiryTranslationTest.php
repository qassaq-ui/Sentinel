<?php

use App\Models\Inquiry;
use App\Models\InquiryCategory;
use App\Services\AIAssistant\AIAssistantClient;
use App\Services\AIAssistant\InquiryTranslationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

function bindFakeTranslator(string $translation): AIAssistantClient
{
    $fake = new class($translation) extends AIAssistantClient
    {
        public function __construct(private string $translation) {}

        public function chat(array $messages): string
        {
            return $this->translation;
        }
    };

    app()->instance(AIAssistantClient::class, $fake);

    return $fake;
}

beforeEach(function (): void {
    config(['cache.default' => 'array']);
    Cache::flush();
});

test('guests are redirected from the inquiry translate endpoint to the login page', function () {
    $inquiry = Inquiry::factory()->create();

    $this
        ->post(route('inquiries.translate', $inquiry), ['language' => 'ru'])
        ->assertRedirect(route('login'));
});

test('users without inquiries view permission cannot translate an inquiry', function () {
    $user = inquiryUser([]);
    $inquiry = Inquiry::factory()->create();

    $this
        ->actingAs($user)
        ->post(route('inquiries.translate', $inquiry), ['language' => 'ru'])
        ->assertForbidden();
});

test('users can translate an inquiry description into a supported language', function () {
    $user = inquiryUser(['inquiries.view']);
    $category = InquiryCategory::factory()->create(['is_active' => true]);
    $inquiry = Inquiry::factory()->create([
        'inquiry_category_id' => $category->id,
        'description' => 'The protective guard is missing after maintenance.',
        'submitted_at' => Carbon::parse('2026-07-10 19:13:00'),
    ]);

    bindFakeTranslator('Защитный кожух отсутствует после техобслуживания.');

    $this
        ->actingAs($user)
        ->post(route('inquiries.translate', $inquiry), ['language' => 'ru'])
        ->assertOk()
        ->assertJson([
            'description' => 'Защитный кожух отсутствует после техобслуживания.',
            'language' => 'Russian',
            'fromCache' => false,
        ]);
});

test('translation results are cached and served on the second request', function () {
    $user = inquiryUser(['inquiries.view']);
    $category = InquiryCategory::factory()->create(['is_active' => true]);
    $inquiry = Inquiry::factory()->create([
        'inquiry_category_id' => $category->id,
        'description' => 'The protective guard is missing after maintenance.',
        'submitted_at' => Carbon::parse('2026-07-10 19:13:00'),
    ]);

    bindFakeTranslator('Перевод из ИИ.');

    $this
        ->actingAs($user)
        ->post(route('inquiries.translate', $inquiry), ['language' => 'ru'])
        ->assertOk()
        ->assertJson(['fromCache' => false, 'description' => 'Перевод из ИИ.']);

    $spy = new class extends AIAssistantClient
    {
        public bool $called = false;

        public function chat(array $messages): string
        {
            $this->called = true;

            return 'should-not-be-returned';
        }
    };
    app()->instance(AIAssistantClient::class, $spy);

    $this
        ->actingAs($user)
        ->post(route('inquiries.translate', $inquiry), ['language' => 'ru'])
        ->assertOk()
        ->assertJson(['fromCache' => true, 'description' => 'Перевод из ИИ.']);

    expect($spy->called)->toBeFalse();
});

test('an unsupported language is rejected', function () {
    $user = inquiryUser(['inquiries.view']);
    $inquiry = Inquiry::factory()->create();

    $this
        ->actingAs($user)
        ->post(route('inquiries.translate', $inquiry), ['language' => 'xx'])
        ->assertSessionHasErrors(['language']);
});

test('a missing language parameter is rejected', function () {
    $user = inquiryUser(['inquiries.view']);
    $inquiry = Inquiry::factory()->create();

    $this
        ->actingAs($user)
        ->post(route('inquiries.translate', $inquiry), [])
        ->assertSessionHasErrors(['language']);
});

test('an inquiry with a null description returns a null translation', function () {
    $user = inquiryUser(['inquiries.view']);
    $category = InquiryCategory::factory()->create(['is_active' => true]);
    $inquiry = Inquiry::factory()->create([
        'inquiry_category_id' => $category->id,
        'description' => null,
        'submitted_at' => Carbon::parse('2026-07-10 19:13:00'),
    ]);

    $spy = new class extends AIAssistantClient
    {
        public bool $called = false;

        public function chat(array $messages): string
        {
            $this->called = true;

            return 'should-not-be-returned';
        }
    };
    app()->instance(AIAssistantClient::class, $spy);

    $this
        ->actingAs($user)
        ->post(route('inquiries.translate', $inquiry), ['language' => 'ru'])
        ->assertOk()
        ->assertJson([
            'description' => null,
            'language' => 'Russian',
            'fromCache' => true,
        ]);

    expect($spy->called)->toBeFalse();
});

test('translation service exposes the ten supported languages including kazakh and russian', function () {
    $languages = app(InquiryTranslationService::class)->supportedLanguages();

    $codes = array_column($languages, 'code');

    expect($languages)->toHaveCount(10)
        ->and($codes)->toContain('ru')
        ->and($codes)->toContain('kk');
});
