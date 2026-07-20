<?php

use App\Actions\Inquiries\CreateInquiry;
use App\Models\Inquiry;
use App\Models\InquiryAttachment;
use App\Models\InquiryCategory;
use App\Models\User;
use Database\Seeders\InquirySeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('inquiry statuses render as colored text without badges', function () {
    $summary = file_get_contents(resource_path('js/pages/Inquiries/InquiryDetailSummary.vue'));
    $table = file_get_contents(resource_path('js/pages/Inquiries/InquiriesTable.vue'));
    $status = file_get_contents(resource_path('js/pages/Inquiries/InquiryStatusBadge.vue'));

    expect($summary)
        ->toContain('appearance="text"')
        ->and($table)
        ->toContain('appearance="text"')
        ->and($status)
        ->toContain("appearance?: 'badge' | 'text'")
        ->toContain("appearance === 'text'")
        ->toContain("v-if=\"appearance === 'badge'\"");
});

test('the internal inquiries page uses the refined apple style', function () {
    $page = file_get_contents(resource_path('js/pages/Inquiries.vue'));
    $tabs = file_get_contents(resource_path('js/pages/Inquiries/InquiryTabs.vue'));
    $search = file_get_contents(resource_path('js/pages/Inquiries/InquirySearchBar.vue'));
    $filters = file_get_contents(resource_path('js/pages/Inquiries/InquiryFilters.vue'));
    $table = file_get_contents(resource_path('js/pages/Inquiries/InquiriesTable.vue'));

    expect($page)
        ->toContain('bg-white text-[#1d1d1f]')
        ->toContain('border-y border-black/8')
        ->not->toContain('max-w-[1600px]')
        ->and($tabs)
        ->toContain('bg-white text-[#1d1d1f]')
        ->not->toContain('activeTabIndex')
        ->toContain('[scrollbar-width:none]')
        ->and($search)
        ->toContain('h-10 rounded-[10px]')
        ->toContain('focus-visible:ring-[#007aff]/20')
        ->and($filters)
        ->toContain('border-t border-black/8')
        ->and($table)
        ->toContain('bg-[#f7f7f8]')
        ->not->toContain('rounded-2xl border border-black/6 bg-white')
        ->toContain('min-w-[920px]')
        ->toContain('hover:bg-[#f7f7f8]');
});

test('internal segmented tabs use the same neutral active style', function () {
    $dictionaries = file_get_contents(resource_path('js/pages/Dictionaries.vue'));
    $detailTabs = file_get_contents(resource_path('js/pages/Inquiries/InquiryDetailTabs.vue'));
    $tabsList = file_get_contents(resource_path('js/components/ui/tabs/TabsList.vue'));
    $tabsTrigger = file_get_contents(resource_path('js/components/ui/tabs/TabsTrigger.vue'));
    $appearanceTabs = file_get_contents(resource_path('js/components/AppearanceTabs.vue'));

    expect($dictionaries)
        ->toContain('bg-white text-[#1d1d1f]')
        ->not->toContain("'translate-x-full': activeTab")
        ->and($detailTabs)
        ->toContain('bg-white text-[#1d1d1f]')
        ->not->toContain('activeIndex')
        ->and($tabsList)
        ->toContain('bg-black/[0.055]')
        ->and($tabsTrigger)
        ->toContain('data-[state=active]:bg-white')
        ->not->toContain('data-[state=active]:scale')
        ->and($appearanceTabs)
        ->toContain('bg-white text-[#1d1d1f]')
        ->not->toContain('bg-[var(--color-tab)] text-white');
});

test('inquiry detail uses the same flat internal page shell', function () {
    $page = file_get_contents(resource_path('js/pages/Inquiries/Show.vue'));
    $summary = file_get_contents(resource_path('js/pages/Inquiries/InquiryDetailSummary.vue'));
    $description = file_get_contents(resource_path('js/pages/Inquiries/InquiryDescriptionPanel.vue'));
    $history = file_get_contents(resource_path('js/pages/Inquiries/InquiryHistoryTimeline.vue'));
    $response = file_get_contents(resource_path('js/pages/Inquiries/InquiryResponsePanel.vue'));

    expect($page)
        ->toContain('bg-white text-[#1d1d1f]')
        ->toContain('text-[1.75rem]')
        ->toContain('scroll-region')
        ->and($summary)
        ->toContain('border-y border-black/8 bg-[#f7f7f8]')
        ->not->toContain('rounded-lg bg-muted/60')
        ->and($description)
        ->toContain('border-y border-black/8 bg-[#f7f7f8]')
        ->and($history)
        ->toContain('border-y border-black/8 bg-[#f7f7f8]')
        ->and($response)
        ->toContain('border-y border-black/8 bg-[#f7f7f8]');
});

test('guests are redirected from the inquiries page to the login page', function () {
    $response = $this->get(route('inquiries.index'));

    $response->assertRedirect(route('login'));
});

test('guests can visit the public inquiry page without authentication', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('guests are redirected from the inquiry detail page to the login page', function () {
    $inquiry = Inquiry::factory()->create();

    $response = $this->get(route('inquiries.show', $inquiry));

    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the inquiries page', function () {
    $user = inquiryUser(['inquiries.view']);
    $activeCategory = InquiryCategory::factory()->create([
        'fallback_name' => 'Ethics concern',
        'review_days' => 30,
        'is_active' => true,
        'sort_order' => 10,
    ]);

    InquiryCategory::factory()->create([
        'fallback_name' => 'Inactive category',
        'is_active' => false,
        'sort_order' => 5,
    ]);

    $inquiry = app(CreateInquiry::class)->handle([
        'category' => $activeCategory,
        'creator' => $user,
        'title' => 'Ethics hotline concern',
        'submitted_at' => Carbon::parse('2026-07-10 19:13:00'),
    ]);

    $this
        ->actingAs($user)
        ->get(route('inquiries.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Inquiries')
            ->has('categories', 1)
            ->where('categories.0.id', $activeCategory->id)
            ->where('categories.0.name', 'Ethics concern')
            ->where('categories.0.reviewDays', 30)
            ->has('allInquiries.data', 1)
            ->where('allInquiries.data.0.id', $inquiry->id)
            ->where('allInquiries.data.0.number', 'KAZM-0726-0001')
            ->where('allInquiries.data.0.subject', 'Ethics hotline concern')
            ->where('allInquiries.data.0.categoryId', $activeCategory->id)
            ->where('allInquiries.data.0.categoryName', 'Ethics concern')
            ->where('allInquiries.data.0.daysLeft', 30)
            ->where('allInquiries.data.0.submittedDate', '2026-07-10')
            ->where('allInquiries.data.0.submittedAt', '10.07.2026, 19:13')
            ->where('allInquiries.data.0.anonymous', false)
            ->where('allInquiries.data.0.archived', false)
            ->has('anonymousInquiries.data', 0)
            ->has('archivedInquiries.data', 0)
        );
});

test('authenticated users can visit the inquiry detail page', function () {
    $user = inquiryUser(['inquiries.view']);
    $assignee = User::factory()->create([
        'name' => 'Compliance Officer',
        'email' => 'compliance@example.com',
        'status' => 'active',
    ]);
    $assignee->givePermissionTo([
        Permission::findOrCreate('inquiries.view'),
        Permission::findOrCreate('inquiries.view_assigned'),
        Permission::findOrCreate('inquiries.respond'),
    ]);
    $assignee->assignRole(Role::create([
        'name' => 'compliance',
        'fallback_label' => 'Compliance',
    ]));
    $category = InquiryCategory::factory()->create([
        'fallback_name' => 'Safety and occupational health',
        'review_days' => 15,
        'is_active' => true,
    ])->forceFill([
        'name_key' => 'inquiry_categories.safety.name',
        'description_key' => 'inquiry_categories.safety.description',
    ]);

    $category->save();

    $inquiry = app(CreateInquiry::class)->handle([
        'category' => $category,
        'creator' => $user,
        'title' => 'Safety guard missing on workshop equipment',
        'description' => 'The protective guard is missing after maintenance.',
        'submitted_at' => Carbon::parse('2026-07-10 19:13:00'),
    ]);

    $inquiry->forceFill(['assigned_to_id' => $assignee->id])->save();
    InquiryAttachment::factory()
        ->for($inquiry)
        ->create([
            'original_name' => 'IMG_4955.jpeg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpeg',
            'file_type' => InquiryAttachment::TYPE_PHOTO,
            'size_bytes' => 98_304,
        ]);
    InquiryAttachment::factory()
        ->for($inquiry)
        ->create([
            'original_name' => 'inspection-notes.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'file_type' => InquiryAttachment::TYPE_PDF,
            'size_bytes' => 240_128,
        ]);

    $this
        ->actingAs($user)
        ->withCookie('locale', 'ru')
        ->get(route('inquiries.show', $inquiry))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Inquiries/Show')
            ->where('inquiry.id', $inquiry->id)
            ->where('inquiry.number', 'KAZM-0726-0001')
            ->where('inquiry.subject', 'Safety guard missing on workshop equipment')
            ->where('inquiry.description', 'The protective guard is missing after maintenance.')
            ->where('inquiry.categoryId', $category->id)
            ->where('inquiry.categoryName', 'Безопасность и охрана труда')
            ->where('inquiry.submittedAt', '10.07.2026, 19:13')
            ->where('inquiry.reviewDueDate', '25.07.2026')
            ->where('inquiry.source', 'Website')
            ->where('inquiry.applicantName', $user->name)
            ->where('inquiry.applicantPhone', null)
            ->where('inquiry.assignee.id', $assignee->id)
            ->where('inquiry.assignee.name', 'Compliance Officer')
            ->where('inquiry.assignee.email', 'compliance@example.com')
            ->where('inquiry.assignee.role', 'Compliance')
            ->where('inquiry.location', null)
            ->where('inquiry.attachmentsCount', 2)
            ->has('inquiry.attachments', 2)
            ->where('inquiry.attachments.0.originalName', 'IMG_4955.jpeg')
            ->where('inquiry.attachments.0.mimeType', 'image/jpeg')
            ->where('inquiry.attachments.0.extension', 'jpeg')
            ->where('inquiry.attachments.0.fileType', InquiryAttachment::TYPE_PHOTO)
            ->where('inquiry.attachments.0.sizeBytes', 98304)
            ->where('inquiry.attachments.1.originalName', 'inspection-notes.pdf')
            ->where('inquiry.attachments.1.fileType', InquiryAttachment::TYPE_PDF)
            ->where('inquiry.historyCount', 1)
            ->has('inquiry.history', 1)
            ->where('inquiry.history.0.type', 'inquiry_created')
            ->where('systemUsers', fn (Collection $users): bool => $users->contains(
                fn (array $systemUser): bool => $systemUser['id'] === $assignee->id
                    && $systemUser['name'] === 'Compliance Officer'
                    && $systemUser['role'] === 'Compliance'
            ))
            ->has('categories', 1)
            ->where('categories.0.id', $category->id)
            ->where('categories.0.name', 'Безопасность и охрана труда')
            ->where('categories.0.reviewDays', 15)
        );
});

test('authenticated users can change an inquiry category and review deadline', function () {
    $user = inquiryUser(['inquiries.view', 'inquiries.update']);
    $oldCategory = InquiryCategory::factory()->create([
        'fallback_name' => 'Old category',
        'review_days' => 15,
        'is_active' => true,
    ]);
    $newCategory = InquiryCategory::factory()->create([
        'fallback_name' => 'New category',
        'review_days' => 45,
        'is_active' => true,
    ]);
    $inquiry = app(CreateInquiry::class)->handle([
        'category' => $oldCategory,
        'creator' => $user,
        'title' => 'Category change request',
        'submitted_at' => Carbon::parse('2026-07-10 09:00:00'),
    ]);

    $this
        ->actingAs($user)
        ->patch(route('inquiries.category.update', $inquiry), [
            'inquiry_category_id' => $newCategory->id,
        ])
        ->assertRedirect();

    $inquiry->refresh();

    expect($inquiry->inquiry_category_id)->toBe($newCategory->id)
        ->and($inquiry->review_days)->toBe(45)
        ->and($inquiry->review_due_date->toDateString())->toBe('2026-08-24');
});

test('authenticated users can assign an active system user as inquiry executor', function () {
    $user = inquiryUser(['inquiries.view', 'inquiries.assign']);
    $assignee = User::factory()->create([
        'status' => 'active',
    ]);
    $assignee->givePermissionTo([
        Permission::findOrCreate('inquiries.view'),
        Permission::findOrCreate('inquiries.view_assigned'),
        Permission::findOrCreate('inquiries.respond'),
    ]);
    $inquiry = Inquiry::factory()->create([
        'assigned_to_id' => null,
        'status' => Inquiry::STATUS_NEW,
    ]);

    $this
        ->actingAs($user)
        ->patch(route('inquiries.assignee.update', $inquiry), [
            'assigned_to_id' => $assignee->id,
        ])
        ->assertRedirect();

    expect($inquiry->fresh()->assigned_to_id)->toBe($assignee->id);
});

test('authenticated users can unassign an inquiry executor', function () {
    $user = inquiryUser(['inquiries.view', 'inquiries.assign']);
    $assignee = User::factory()->create([
        'status' => 'active',
    ]);
    $inquiry = Inquiry::factory()->create([
        'assigned_to_id' => $assignee->id,
        'status' => Inquiry::STATUS_IN_PROGRESS,
    ]);

    $this
        ->actingAs($user)
        ->patch(route('inquiries.assignee.update', $inquiry), [
            'assigned_to_id' => null,
        ])
        ->assertRedirect();

    expect($inquiry->fresh()->assigned_to_id)->toBeNull();
});

test('completed archived inquiries cannot be reassigned', function () {
    $user = inquiryUser(['inquiries.view', 'inquiries.assign']);
    $currentAssignee = User::factory()->create();
    $nextAssignee = User::factory()->create(['status' => 'active']);
    $nextAssignee->givePermissionTo([
        Permission::findOrCreate('inquiries.view'),
        Permission::findOrCreate('inquiries.view_assigned'),
        Permission::findOrCreate('inquiries.respond'),
    ]);
    $inquiry = Inquiry::factory()->create([
        'assigned_to_id' => $currentAssignee->id,
        'status' => Inquiry::STATUS_COMPLETED,
        'archived_at' => now(),
    ]);

    $this
        ->actingAs($user)
        ->get(route('inquiries.show', $inquiry))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('canAssignExecutor', false));

    $this
        ->actingAs($user)
        ->patch(route('inquiries.assignee.update', $inquiry), [
            'assigned_to_id' => $nextAssignee->id,
        ])
        ->assertForbidden();

    expect($inquiry->fresh()->assigned_to_id)->toBe($currentAssignee->id)
        ->and($inquiry->events()->count())->toBe(0);
});

test('manually archived inquiries cannot be reassigned', function () {
    $user = inquiryUser(['inquiries.view', 'inquiries.assign']);
    $currentAssignee = User::factory()->create();
    $nextAssignee = User::factory()->create(['status' => 'active']);
    $inquiry = Inquiry::factory()->create([
        'assigned_to_id' => $currentAssignee->id,
        'status' => Inquiry::STATUS_IN_PROGRESS,
        'archived_at' => now(),
    ]);

    $this
        ->actingAs($user)
        ->patch(route('inquiries.assignee.update', $inquiry), [
            'assigned_to_id' => $nextAssignee->id,
        ])
        ->assertForbidden();

    expect($inquiry->fresh()->assigned_to_id)->toBe($currentAssignee->id)
        ->and($inquiry->events()->count())->toBe(0);
});

test('blocked employees cannot be assigned as inquiry executors', function () {
    $user = inquiryUser(['inquiries.view', 'inquiries.assign']);
    $blockedUser = User::factory()->create([
        'status' => 'blocked',
    ]);
    $blockedUser->givePermissionTo(Permission::findOrCreate('inquiries.respond'));
    $inquiry = Inquiry::factory()->create([
        'assigned_to_id' => null,
        'status' => Inquiry::STATUS_NEW,
    ]);

    $this
        ->actingAs($user)
        ->patch(route('inquiries.assignee.update', $inquiry), [
            'assigned_to_id' => $blockedUser->id,
        ])
        ->assertInvalid(['assigned_to_id']);

    expect($inquiry->fresh()->assigned_to_id)->toBeNull();
});

test('the inquiries page paginates inquiry lists for infinite scroll', function () {
    $user = inquiryUser(['inquiries.view']);
    $category = InquiryCategory::factory()->create([
        'fallback_name' => 'Ethics concern',
        'review_days' => 30,
        'is_active' => true,
        'sort_order' => 10,
    ]);

    collect(range(1, 16))->each(function (int $index) use ($category, $user): void {
        app(CreateInquiry::class)->handle([
            'category' => $category,
            'creator' => $user,
            'title' => "Ethics hotline concern {$index}",
            'submitted_at' => Carbon::parse('2026-07-10 19:13:00')->subMinutes($index),
        ]);
    });

    app(CreateInquiry::class)->handle([
        'category' => $category,
        'creator' => null,
        'title' => 'Anonymous procurement concern',
        'anonymous' => true,
        'submitted_at' => Carbon::parse('2026-07-10 10:00:00'),
    ]);

    app(CreateInquiry::class)->handle([
        'category' => $category,
        'creator' => $user,
        'title' => 'Archived travel expense request',
        'submitted_at' => Carbon::parse('2026-07-09 10:00:00'),
    ])->forceFill([
        'status' => Inquiry::STATUS_COMPLETED,
        'archived_at' => Carbon::parse('2026-07-10 10:00:00'),
    ])->save();

    $this
        ->actingAs($user)
        ->get(route('inquiries.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Inquiries')
            ->has('allInquiries.data', 15)
            ->where('allInquiries.current_page', 1)
            ->where('allInquiries.per_page', 15)
            ->where('allInquiries.next_page_url', route('inquiries.index', ['allInquiries' => 2]))
            ->has('anonymousInquiries.data', 1)
            ->where('anonymousInquiries.data.0.anonymous', true)
            ->has('archivedInquiries.data', 1)
            ->where('archivedInquiries.data.0.archived', true)
        );
});

test('completed inquiries appear only in the archive', function () {
    $user = inquiryUser(['inquiries.view']);
    $completed = Inquiry::factory()->create([
        'status' => Inquiry::STATUS_COMPLETED,
        'archived_at' => null,
    ]);

    $this
        ->actingAs($user)
        ->get(route('inquiries.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('allInquiries.data', [])
            ->where('anonymousInquiries.data', [])
            ->has('archivedInquiries.data', 1)
            ->where('archivedInquiries.data.0.id', $completed->id)
            ->where('archivedInquiries.data.0.archived', true));
});

test('inquiry category names are resolved from base locale json', function () {
    $user = inquiryUser(['inquiries.view']);
    $category = InquiryCategory::factory()->create([
        'fallback_name' => 'Безопасность и охрана труда',
        'review_days' => 15,
        'is_active' => true,
    ])->forceFill([
        'name_key' => 'inquiry_categories.safety.name',
        'description_key' => 'inquiry_categories.safety.description',
    ]);

    $category->save();

    app(CreateInquiry::class)->handle([
        'category' => $category,
        'creator' => $user,
        'title' => 'Safety guard missing on workshop equipment',
        'submitted_at' => Carbon::parse('2026-07-10 19:13:00'),
    ]);

    $this
        ->actingAs($user)
        ->withCookie('locale', 'en')
        ->get(route('inquiries.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('categories.0.name', 'Safety and occupational health')
            ->where('allInquiries.data.0.categoryName', 'Safety and occupational health')
        );
});

test('seeded inquiry categories use english fallback and russian json translation', function () {
    $this->seed(InquirySeeder::class);

    $category = InquiryCategory::query()
        ->where('name_key', 'inquiry_categories.safety.name')
        ->firstOrFail();

    expect($category->fallback_name)->toBe('Safety and occupational health')
        ->and($category->fallback_description)->toBe('Health and safety risks, faulty equipment, missing briefings, or missing protective equipment.');

    $user = inquiryUser(['inquiries.view']);

    $this
        ->actingAs($user)
        ->withCookie('locale', 'ru')
        ->get(route('inquiries.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('categories.4.name', 'Безопасность и охрана труда')
            ->where('allInquiries.data.0.categoryName', 'Безопасность и охрана труда')
        );
});

test('authenticated employees can also visit the public inquiry page', function () {
    $user = inquiryUser([]);

    $this
        ->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/Inquiries/Create')
        );
});
