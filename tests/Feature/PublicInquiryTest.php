<?php

use App\Models\Inquiry;
use App\Models\InquiryAttachment;
use App\Models\InquiryCategory;
use App\Models\InquiryResponse;
use App\Models\User;
use App\Support\InquiryAccessCode;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('the public home page uses the hotline landing design', function () {
    $page = file_get_contents(resource_path('js/pages/public/Inquiries/Create.vue'));
    $entryDialog = file_get_contents(resource_path('js/pages/public/Inquiries/PublicInquiryEntryDialog.vue'));
    $formDialog = file_get_contents(resource_path('js/pages/public/Inquiries/PublicInquiryFormDialog.vue'));
    $statusDialog = file_get_contents(resource_path('js/pages/public/Inquiries/PublicInquiryStatusDialog.vue'));
    $responseDialog = file_get_contents(resource_path('js/pages/public/Inquiries/PublicInquiryResponseDialog.vue'));
    $logo = file_get_contents(resource_path('js/pages/public/Inquiries/SpeakUpLogo.vue'));
    $languageSwitcher = file_get_contents(resource_path('js/components/LanguageSwitcher.vue'));

    expect($page)
        ->toContain("t('Hotline')")
        ->toContain("t('Key information')")
        ->toContain("t('Frequently asked questions')")
        ->toContain('<SpeakUpLogo />')
        ->toContain('<PublicInquiryEntryDialog')
        ->toContain("t('Check inquiry status')")
        ->toContain('<PublicInquiryFormDialog')
        ->toContain('<PublicInquiryStatusDialog')
        ->toContain('<PublicInquiryResponseDialog')
        ->toContain('@view-response="openInquiryResponse"')
        ->toContain('@click="statusOpen = true"')
        ->toContain('submittedInquiryAccessCode')
        ->toContain("router.on('flash'")
        ->toContain('submittedInquiryNumber.value !== null')
        ->toContain('@accepted="dismissSubmittedInquiry"')
        ->toContain('bottom-[max(0.75rem,env(safe-area-inset-bottom))]')
        ->toContain('left-3 z-40 rounded-2xl')
        ->and(substr_count($page, 'min-h-12 items-center justify-center gap-2 rounded-xl'))
        ->toBe(2)
        ->and($page)
        ->not->toContain('rounded-[1.15rem]')
        ->toContain('snap-x snap-mandatory')
        ->toContain('flex h-16 shrink-0 items-center justify-between')
        ->toContain('border-b border-slate-900/10 bg-slate-50/20')
        ->toContain('rounded-2xl border border-slate-200 bg-white')
        ->toContain('bg-slate-50/40')
        ->toContain('rounded-xl bg-[#1875e6]')
        ->not->toContain('rounded-full bg-[#1875e6]')
        ->toContain("t('Check status')")
        ->and(substr_count($page, 'whitespace-nowrap'))
        ->toBeGreaterThanOrEqual(2)
        ->and($entryDialog)
        ->toContain('sm:max-w-[34rem]')
        ->toContain('sm:rounded-2xl')
        ->toContain('overlay-class="bg-slate-950/20 backdrop-blur-sm"')
        ->toContain('border border-slate-200 bg-slate-50/40')
        ->toContain('border-slate-200 bg-white')
        ->and(substr_count($entryDialog, 'sm:last:border-b'))
        ->toBe(2)
        ->and($entryDialog)
        ->not->toContain('backdrop-blur-md')
        ->toContain('/images/inquiries/anonymous-submission-icon.png')
        ->toContain('/images/inquiries/identified-submission-icon.png')
        ->toContain("t('No account is required')")
        ->toContain("emit('select', 'identified')")
        ->toContain("emit('select', 'anonymous')")
        ->not->toContain('bg-emerald-50/70')
        ->and(strpos($entryDialog, "emit('select', 'anonymous')"))
        ->toBeLessThan(strpos($entryDialog, "emit('select', 'identified')"))
        ->and(file_exists(public_path('images/inquiries/anonymous-submission-icon.png')))
        ->toBeTrue()
        ->and(file_exists(public_path('images/inquiries/identified-submission-icon.png')))
        ->toBeTrue()
        ->and($formDialog)
        ->toContain('h-[100dvh]')
        ->toContain('h-14 shrink-0 justify-center')
        ->toContain('pb-[max(1rem,env(safe-area-inset-bottom))]')
        ->toContain("t('Back')")
        ->toContain('capture="environment"')
        ->toContain('MediaRecorder')
        ->toContain("file.type.startsWith('image/')")
        ->toContain('handleFileInputChange($event, true)')
        ->toContain("input.value = ''")
        ->toContain(':transform="transformFormData"')
        ->toContain('<Select v-model="selectedCategoryId" required>')
        ->toContain('inquiry_category_id: selectedCategoryId.value')
        ->toContain('data-[size=default]:h-12')
        ->toContain('overlay-class="bg-slate-950/20 backdrop-blur-sm"')
        ->toContain('sm:rounded-2xl sm:border sm:border-slate-200')
        ->toContain('bg-slate-50')
        ->toContain('focus:ring-blue-600/20')
        ->not->toContain('bg-white/80')
        ->not->toContain('<select')
        ->toContain("router.on('httpException'")
        ->toContain('event.detail.response.status !== 429')
        ->toContain("t('Too many attempts')")
        ->toContain('formattedRateLimitTime')
        ->toContain('@start="handleSubmissionStart"')
        ->toContain('@error="handleSubmissionError"')
        ->toContain("admissionError.value = errors.admission ?? ''")
        ->toContain('{{ admissionError }}')
        ->not->toContain('{{ errors.admission }}')
        ->toContain("screeningDialogState !== 'idle'")
        ->toContain("screeningDialogState === 'analyzing'")
        ->toContain("t('AI is analyzing the inquiry')")
        ->toContain("screeningDialogState === 'accepted'")
        ->toContain('Save the access code to check the status from any device.')
        ->toContain('The access code is shown only once.')
        ->toContain('{{ submissionNumber }}')
        ->toContain('{{ submissionAccessCode }}')
        ->toContain("t('Access code')")
        ->toContain('copySubmissionDetails')
        ->toContain("t('Inquiry not registered')")
        ->toContain('alternativeInquiriesMailto')
        ->toContain("clearErrors('admission')")
        ->toContain("t('Checking inquiry…')")
        ->and($statusDialog)
        ->toContain('useHttp')
        ->toContain('status.url()')
        ->toContain('http.access_code')
        ->not->toContain('http.number')
        ->not->toContain('public-inquiry-number')
        ->toContain('Enter the access code issued after submission.')
        ->toContain("t('Access code')")
        ->not->toContain('localStorage')
        ->toContain("t('Check another inquiry')")
        ->toContain("t('Refresh status')")
        ->toContain("t('View response')")
        ->toContain('overlay-class="bg-slate-950/20 backdrop-blur-sm"')
        ->not->toContain("'/inquiries/status'")
        ->and($responseDialog)
        ->toContain('fetchResponse.url()')
        ->toContain("t('Response to inquiry')")
        ->toContain('{{ result.body }}')
        ->toContain('whitespace-pre-wrap')
        ->and($logo)
        ->toContain('h-12 pl-5 sm:h-16 sm:pl-6')
        ->and($languageSwitcher)
        ->toContain('<Globe2')
        ->toContain('group h-10 min-w-24')
        ->toContain('bg-white/55')
        ->toContain('backdrop-blur-md')
        ->toContain('group-data-[state=open]:rotate-180')
        ->toContain('min-w-52 rounded-2xl')
        ->toContain('locale.code.slice(0, 2)');
});

test('guests can open the public inquiry form', function () {
    $category = InquiryCategory::factory()->create([
        'fallback_name' => 'Ethics',
        'fallback_description' => 'Ethics concerns.',
        'is_active' => true,
    ]);
    InquiryCategory::factory()->create(['is_active' => false]);

    $this
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/Inquiries/Create')
            ->has('categories', 1)
            ->where('categories.0.id', $category->id)
            ->where('categories.0.name', 'Ethics')
            ->where('aiScreeningEnabled', false)
            ->where('alternativeInquiriesEmail', 'senimkmm@kazminerals.com')
        );

    $this->assertGuest();
});

test('guests can submit an anonymous inquiry without creating a user', function () {
    $category = InquiryCategory::factory()->create(['is_active' => true]);

    $response = $this->post(route('public-inquiries.store'), [
        'submission_mode' => 'anonymous',
        'inquiry_category_id' => $category->id,
        'title' => 'Anonymous safety concern',
        'description' => 'A detailed description of the safety concern.',
    ]);

    $inquiry = Inquiry::query()->firstOrFail();
    $accessCode = data_get(
        session()->get('inertia.flash_data'),
        'submission.accessCode',
    );

    $response
        ->assertRedirect(route('home'))
        ->assertInertiaFlash('submission.number', $inquiry->number)
        ->assertInertiaFlash('submission.accessCode');

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/Inquiries/Create')
            ->hasFlash('submission.number', $inquiry->number)
            ->hasFlash('submission.accessCode')
        );

    expect($accessCode)->toBeString()->toMatch('/^[A-HJ-NP-Z2-9]{4}(?:-[A-HJ-NP-Z2-9]{4}){2}$/')
        ->and($inquiry->applicant?->tracking_token_hash)->toBe(InquiryAccessCode::hash($accessCode))
        ->and($inquiry->type)->toBe(Inquiry::TYPE_ANONYMOUS)
        ->and($inquiry->created_by_id)->toBeNull()
        ->and($inquiry->applicant)->not->toBeNull()
        ->and($inquiry->applicant->name)->toBeNull()
        ->and($inquiry->applicant->email)->toBeNull()
        ->and($inquiry->applicant->phone)->toBeNull()
        ->and(User::query()->count())->toBe(0)
        ->and($inquiry->events()->where('type', 'inquiry_created')->value('actor_id'))->toBeNull()
        ->and($inquiry->events()->where('type', 'inquiry_created')->value('metadata'))
        ->not->toContain('applicant');
});

test('a guest can securely check an inquiry status from any device using the access code', function () {
    $accessCode = InquiryAccessCode::generate();
    $inquiry = Inquiry::factory()->anonymous()->create([
        'status' => Inquiry::STATUS_IN_PROGRESS,
    ]);
    $inquiry->applicant()->create([
        'tracking_token_hash' => InquiryAccessCode::hash($accessCode),
    ]);

    $response = $this->postJson(route('public-inquiries.status'), [
        'access_code' => strtolower(InquiryAccessCode::format($accessCode)),
    ]);

    $response
        ->assertOk()
        ->assertExactJson([
            'number' => $inquiry->number,
            'status' => Inquiry::STATUS_IN_PROGRESS,
            'submittedAt' => $inquiry->submitted_at->toISOString(),
            'updatedAt' => $inquiry->updated_at?->toISOString(),
            'responseAvailable' => false,
        ])
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonMissingPath('title')
        ->assertJsonMissingPath('description')
        ->assertJsonMissingPath('applicant');
});

test('only a sent response is available to an applicant in a separate public request', function () {
    $accessCode = InquiryAccessCode::generate();
    $inquiry = Inquiry::factory()->anonymous()->create([
        'status' => Inquiry::STATUS_IN_PROGRESS,
    ]);
    $inquiry->applicant()->create([
        'tracking_token_hash' => InquiryAccessCode::hash($accessCode),
    ]);
    $inquiryResponse = InquiryResponse::factory()->for($inquiry)->create([
        'body' => 'Confidential final response text.',
        'status' => InquiryResponse::STATUS_DRAFT,
    ]);
    $credentials = [
        'access_code' => InquiryAccessCode::format($accessCode),
    ];

    $this->postJson(route('public-inquiries.status'), $credentials)
        ->assertOk()
        ->assertJsonPath('responseAvailable', false)
        ->assertJsonMissing(['body' => 'Confidential final response text.']);

    $this->postJson(route('public-inquiries.status.response'), $credentials)
        ->assertNotFound()
        ->assertJsonMissing(['body' => 'Confidential final response text.']);

    $inquiryResponse->forceFill([
        'status' => InquiryResponse::STATUS_SENT,
        'sent_at' => now(),
    ])->save();

    $this->postJson(route('public-inquiries.status'), $credentials)
        ->assertOk()
        ->assertJsonPath('responseAvailable', true);

    $this->postJson(route('public-inquiries.status.response'), $credentials)
        ->assertOk()
        ->assertExactJson([
            'number' => $inquiry->number,
            'body' => 'Confidential final response text.',
            'sentAt' => $inquiryResponse->sent_at?->toISOString(),
        ])
        ->assertHeader('Cache-Control', 'no-store, private');
});

test('the public status endpoint rejects an invalid access code without exposing inquiry data', function () {
    $accessCode = InquiryAccessCode::generate();
    $inquiry = Inquiry::factory()->anonymous()->create();
    $inquiry->applicant()->create([
        'tracking_token_hash' => InquiryAccessCode::hash($accessCode),
    ]);

    $expectedError = __('The access code is incorrect.');

    $this->postJson(route('public-inquiries.status'), [
        'access_code' => InquiryAccessCode::format(InquiryAccessCode::generate()),
    ])->assertUnprocessable()
        ->assertJsonPath('errors.access_code.0', $expectedError)
        ->assertJsonMissingPath('number')
        ->assertJsonMissingPath('status');
});

test('guests can attach photos documents and voice recordings to an inquiry', function () {
    Storage::fake('local');
    $category = InquiryCategory::factory()->create(['is_active' => true]);

    $response = $this->post(route('public-inquiries.store'), [
        'submission_mode' => 'anonymous',
        'inquiry_category_id' => $category->id,
        'title' => 'Inquiry with evidence',
        'description' => 'The supporting files contain relevant evidence.',
        'attachments' => [
            UploadedFile::fake()->image('evidence.jpg'),
            new UploadedFile(
                public_path('images/inquiries/anonymous-submission-icon.png'),
                'camera',
                'image/png',
                null,
                true,
            ),
            UploadedFile::fake()->createWithContent(
                'voice-note.wav',
                "RIFF\x24\x00\x00\x00WAVEfmt \x10\x00\x00\x00\x01\x00\x01\x00\x40\x1F\x00\x00\x80\x3E\x00\x00\x02\x00\x10\x00data\x00\x00\x00\x00",
            ),
            UploadedFile::fake()->create('details.pdf', 128, 'application/pdf'),
        ],
    ]);

    $response->assertRedirect(route('home'));

    $inquiry = Inquiry::query()->with('attachments')->sole();

    expect($inquiry->attachments)->toHaveCount(4);
    expect($inquiry->attachments->pluck('file_type')->all())->toEqualCanonicalizing([
        InquiryAttachment::TYPE_PHOTO,
        InquiryAttachment::TYPE_PHOTO,
        InquiryAttachment::TYPE_AUDIO,
        InquiryAttachment::TYPE_PDF,
    ]);
    expect($inquiry->attachments->pluck('uploaded_by_id')->filter()->all())->toBeEmpty();
    expect($inquiry->events()->where('type', 'inquiry_attachment_uploaded')->count())->toBe(4);

    foreach ($inquiry->attachments as $attachment) {
        Storage::disk('local')->assertExists($attachment->path);
    }
});

test('public inquiry attachments reject unsupported files', function () {
    Storage::fake('local');
    $category = InquiryCategory::factory()->create(['is_active' => true]);

    $this->post(route('public-inquiries.store'), [
        'submission_mode' => 'anonymous',
        'inquiry_category_id' => $category->id,
        'title' => 'Inquiry with unsafe file',
        'description' => 'This upload must be rejected.',
        'attachments' => [
            UploadedFile::fake()->create('payload.exe', 10, 'application/x-msdownload'),
        ],
    ])->assertSessionHasErrors('attachments.0');

    expect(Inquiry::query()->count())->toBe(0);
    Storage::disk('local')->assertDirectoryEmpty('/');
});

test('guests can submit an identified inquiry with encrypted contact details', function () {
    $category = InquiryCategory::factory()->create(['is_active' => true]);

    $this->post(route('public-inquiries.store'), [
        'submission_mode' => 'identified',
        'inquiry_category_id' => $category->id,
        'title' => 'Request for review',
        'description' => 'Please review this situation and contact me.',
        'applicant_name' => 'Aruzhan Sarsenova',
        'applicant_email' => 'aruzhan@example.com',
        'applicant_phone' => '+7 777 123 45 67',
    ])->assertRedirect(route('home'));

    $inquiry = Inquiry::query()->with('applicant')->firstOrFail();
    $rawApplicant = DB::table('inquiry_applicants')->first();

    expect($inquiry->type)->toBe(Inquiry::TYPE_IDENTIFIED)
        ->and($inquiry->created_by_id)->toBeNull()
        ->and($inquiry->applicant?->name)->toBe('Aruzhan Sarsenova')
        ->and($inquiry->applicant?->email)->toBe('aruzhan@example.com')
        ->and($inquiry->applicant?->phone)->toBe('+7 777 123 45 67')
        ->and($rawApplicant?->name)->not->toBe('Aruzhan Sarsenova')
        ->and($rawApplicant?->email)->not->toBe('aruzhan@example.com')
        ->and(User::query()->count())->toBe(0);
});

test('identified inquiries require a name and at least one contact method', function () {
    $category = InquiryCategory::factory()->create(['is_active' => true]);

    $this->post(route('public-inquiries.store'), [
        'submission_mode' => 'identified',
        'inquiry_category_id' => $category->id,
        'title' => 'Request for review',
        'description' => 'Please review this situation.',
    ])->assertSessionHasErrors(['applicant_name', 'applicant_email', 'applicant_phone']);
});

test('inactive categories cannot be used for public inquiries', function () {
    $category = InquiryCategory::factory()->create(['is_active' => false]);

    $this->post(route('public-inquiries.store'), [
        'submission_mode' => 'anonymous',
        'inquiry_category_id' => $category->id,
        'title' => 'Request for review',
        'description' => 'Please review this situation.',
    ])->assertSessionHasErrors('inquiry_category_id');
});

test('the legacy confirmation page is no longer exposed', function () {
    $this->get('/inquiry-submitted')->assertNotFound();
});
