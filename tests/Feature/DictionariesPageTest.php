<?php

use App\Models\InquiryCategory;
use App\Models\InquiryOutcome;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;

function dictionaryUser(array $permissions = ['dictionaries.view']): User
{
    collect($permissions)->each(fn (string $permission): Permission => Permission::findOrCreate($permission));

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

test('guests are redirected from the dictionaries page to the login page', function () {
    $response = $this->get(route('dictionaries.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dictionaries page', function () {
    $user = dictionaryUser();
    $category = InquiryCategory::factory()->create([
        'fallback_name' => 'Corruption reports',
        'fallback_description' => 'Fallback description',
        'review_days' => 30,
        'sort_order' => 10,
    ]);

    $this
        ->actingAs($user)
        ->get(route('dictionaries.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dictionaries')
            ->has('categories', 1)
            ->has('outcomes', 12)
            ->where('categories.0.id', $category->id)
            ->where('categories.0.localized_name', 'Corruption reports')
            ->where('categories.0.review_days', 30)
            ->where('categories.0.name_key', $category->name_key)
            ->where('outcomes.0.code', 'confirmed')
            ->where('outcomes.0.localized_name', 'Confirmed')
            ->where('outcomes.0.is_active', true)
        );
});

test('category name is resolved from uploaded locale json when key exists', function () {
    Storage::fake('local');

    $user = dictionaryUser();
    $category = InquiryCategory::factory()->create([
        'fallback_name' => 'Corruption reports',
    ]);

    Storage::disk('local')->put('localizations/labels.json', json_encode([
        'zh' => 'Chinese',
    ], JSON_THROW_ON_ERROR));
    Storage::disk('local')->put('localizations/zh.json', json_encode([
        $category->name_key => '腐败举报',
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

    $this
        ->actingAs($user)
        ->withCookie('locale', 'zh')
        ->get(route('dictionaries.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('categories.0.localized_name', '腐败举报')
        );
});

test('review outcome name is resolved from uploaded locale json when key exists', function () {
    Storage::fake('local');

    $user = dictionaryUser();

    Storage::disk('local')->put('localizations/labels.json', json_encode([
        'zh' => 'Chinese',
    ], JSON_THROW_ON_ERROR));
    Storage::disk('local')->put('localizations/zh.json', json_encode([
        'inquiry_outcomes.confirmed.name' => '已确认',
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

    $this
        ->actingAs($user)
        ->withCookie('locale', 'zh')
        ->get(route('dictionaries.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('outcomes.0.code', 'confirmed')
            ->where('outcomes.0.localized_name', '已确认')
        );
});

test('authenticated users can create an inquiry category with json translation keys', function () {
    $user = dictionaryUser(['dictionaries.create']);

    $this
        ->actingAs($user)
        ->post(route('dictionaries.inquiry-categories.store'), [
            'fallback_name' => 'Corruption reports',
            'fallback_description' => 'Reports about corruption',
            'review_days' => 30,
            'is_active' => true,
            'sort_order' => 20,
        ])
        ->assertRedirect();

    $category = InquiryCategory::query()->firstOrFail();

    expect($category->uuid)->not->toBeEmpty()
        ->and($category->name_key)->toBe("inquiry_categories.{$category->uuid}.name")
        ->and($category->description_key)->toBe("inquiry_categories.{$category->uuid}.description")
        ->and($category->fallback_name)->toBe('Corruption reports')
        ->and($category->review_days)->toBe(30)
        ->and($category->sort_order)->toBe(20)
        ->and($category->is_active)->toBeTrue();
});

test('category validation requires a fallback name', function () {
    $user = dictionaryUser(['dictionaries.create']);

    $this
        ->actingAs($user)
        ->post(route('dictionaries.inquiry-categories.store'), [
            'fallback_name' => '',
            'fallback_description' => '',
            'review_days' => 15,
            'is_active' => true,
            'sort_order' => 0,
        ])
        ->assertInvalid(['fallback_name']);
});

test('authenticated users can update and delete an inquiry category', function () {
    $user = dictionaryUser(['dictionaries.update', 'dictionaries.delete']);
    $category = InquiryCategory::factory()->create([
        'fallback_name' => 'Old name',
        'fallback_description' => null,
    ]);

    $this
        ->actingAs($user)
        ->patch(route('dictionaries.inquiry-categories.update', $category), [
            'fallback_name' => 'New name',
            'fallback_description' => 'New description',
            'review_days' => 45,
            'is_active' => false,
            'sort_order' => 30,
        ])
        ->assertRedirect();

    $category->refresh();

    expect($category->fallback_name)->toBe('New name')
        ->and($category->fallback_description)->toBe('New description')
        ->and($category->review_days)->toBe(45)
        ->and($category->is_active)->toBeFalse()
        ->and($category->sort_order)->toBe(30);

    $this
        ->delete(route('dictionaries.inquiry-categories.destroy', $category))
        ->assertRedirect();

    $this->assertModelMissing($category);
});

test('authenticated users can update system review outcome settings', function () {
    $user = dictionaryUser(['dictionaries.view', 'dictionaries.update']);

    $this
        ->actingAs($user)
        ->get(route('dictionaries.index'))
        ->assertOk();

    $outcome = InquiryOutcome::query()->where('code', 'confirmed')->firstOrFail();

    $this
        ->actingAs($user)
        ->patch(route('dictionaries.inquiry-outcomes.update', $outcome), [
            'fallback_name' => 'Confirmed',
            'fallback_description' => 'Verified and confirmed.',
            'ai_instruction' => 'Generate a clear response that confirms the issue and says appropriate action will be taken.',
            'is_active' => false,
            'sort_order' => 15,
        ])
        ->assertRedirect();

    $outcome->refresh();

    expect($outcome->code)->toBe('confirmed')
        ->and($outcome->fallback_description)->toBe('Verified and confirmed.')
        ->and($outcome->ai_instruction)->toBe('Generate a clear response that confirms the issue and says appropriate action will be taken.')
        ->and($outcome->is_active)->toBeFalse()
        ->and($outcome->sort_order)->toBe(15);
});
