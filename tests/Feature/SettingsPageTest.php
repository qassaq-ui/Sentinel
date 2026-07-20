<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function userWithRolesPermissions(array $permissions = []): User
{
    $permissionNames = array_values(array_unique(array_merge([
        'settings.access',
        'roles.view',
        'roles.create',
        'roles.update',
        'roles.delete',
        'roles.permissions.update',
    ], $permissions)));

    collect($permissionNames)->each(fn (string $permission): Permission => Permission::findOrCreate($permission));

    $user = User::factory()->create();
    $user->givePermissionTo($permissionNames);

    return $user;
}

test('guests are redirected from the settings page to the login page', function () {
    $response = $this->get(route('settings.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated users need permission to visit the settings page', function () {
    Permission::findOrCreate('settings.access');

    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->get(route('settings.index'))
        ->assertForbidden();

    $user->givePermissionTo('settings.access');

    $this
        ->actingAs($user)
        ->get(route('settings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Index')
        );
});

test('authenticated users can view localization settings on the general settings page', function () {
    Storage::fake('local');

    $user = userWithRolesPermissions();

    $this
        ->actingAs($user)
        ->get(route('settings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Index')
            ->where('localizationSettings.fallback', 'en')
            ->where('localizationSettings.locales.0.code', 'en')
            ->where('localizationSettings.locales.0.enabled', true)
            ->where('localizationSettings.locales.0.fallback', true)
            ->where('localizationSettings.locales.1.code', 'kk')
            ->where('localizationSettings.locales.2.code', 'ru')
        );
});

test('authenticated users can upload localization json', function () {
    Storage::fake('local');

    $user = userWithRolesPermissions();

    $this
        ->actingAs($user)
        ->post(route('settings.localization.store'), [
            'locale' => 'mn',
            'label' => 'Mongolian',
            'messages' => UploadedFile::fake()->createWithContent('mn.json', '{"Hello":"Сайн байна уу"}'),
        ])
        ->assertRedirect();

    Storage::disk('local')->assertExists('localizations/mn.json');
    Storage::disk('local')->assertExists('localizations/labels.json');

    $this
        ->actingAs($user)
        ->get(route('settings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('localizationSettings.locales.1.code', 'kk')
            ->where('localizationSettings.locales.2.code', 'mn')
            ->where('localizationSettings.locales.2.label', 'Mongolian')
            ->where('localizationSettings.locales.2.uploaded', true)
            ->where('localizationSettings.locales.2.enabled', true)
        );
});

test('authenticated users can disable and enable a language', function () {
    Storage::fake('local');

    $user = userWithRolesPermissions();

    $this
        ->actingAs($user)
        ->patch(route('settings.localization.update', 'kk'), [
            'enabled' => false,
        ])
        ->assertRedirect();

    $this
        ->actingAs($user)
        ->get(route('settings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('localizationSettings.locales.1.code', 'kk')
            ->where('localizationSettings.locales.1.enabled', false)
            ->has('locale.available', 2)
            ->where('locale.available.0.code', 'en')
            ->where('locale.available.1.code', 'ru')
        );

    $this
        ->actingAs($user)
        ->patch(route('settings.localization.update', 'kk'), [
            'enabled' => true,
        ])
        ->assertRedirect();

    $this
        ->actingAs($user)
        ->get(route('settings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('localizationSettings.locales.1.code', 'kk')
            ->where('localizationSettings.locales.1.enabled', true)
            ->where('locale.available.1.code', 'kk')
        );
});

test('fallback language cannot be disabled', function () {
    Storage::fake('local');

    $user = userWithRolesPermissions();

    $this
        ->actingAs($user)
        ->patch(route('settings.localization.update', 'en'), [
            'enabled' => false,
        ])
        ->assertSessionHasErrors('enabled');

    $this
        ->actingAs($user)
        ->get(route('settings.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('localizationSettings.locales.0.code', 'en')
            ->where('localizationSettings.locales.0.enabled', true)
        );
});

test('authenticated users can visit roles and permissions from the users page', function () {
    $user = userWithRolesPermissions();

    $response = $this
        ->actingAs($user)
        ->get(route('roles-permissions.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Users')
            ->where('initialTab', 'roles')
            ->where('roleCatalog.0.name', 'admin')
            ->where('roleCatalog.0.label', 'Administrator')
            ->where('roleCatalog.0.protected', true)
            ->has('roleCatalog.0.permissions', 23)
            ->where('permissions.0.name', 'settings.access')
            ->where('permissions.0.group', 'Settings')
            ->where('permissions.0.label', 'Allow access to system settings')
            ->where('permissions.1.name', 'inquiries.view')
            ->where('permissions.1.group', 'Inquiries')
            ->where('permissions.1.label', 'Allow viewing inquiries page')
            ->where('permissions.2.name', 'inquiries.view_all')
            ->where('permissions.3.name', 'inquiries.view_assigned')
            ->where('permissions.4.name', 'inquiries.update')
            ->where('permissions.22.name', 'users.delete')
            ->has('roleCatalog', 11)
            ->where('roleCatalog.1.name', 'compliance_officer')
            ->where('roleCatalog.10.name', 'security_investigator')
        );
});

test('authenticated users can update role permissions', function () {
    $user = userWithRolesPermissions(['roles.permissions.update']);
    $role = Role::create(['name' => 'manager']);
    Permission::create(['name' => 'users.view']);

    $this
        ->actingAs($user)
        ->patch(route('roles-permissions.permissions.update', $role), [
            'permission' => 'users.view',
            'enabled' => true,
        ])
        ->assertRedirect();

    expect($role->fresh()->hasPermissionTo('users.view'))->toBeTrue();

    $this
        ->actingAs($user)
        ->patch(route('roles-permissions.permissions.update', $role), [
            'permission' => 'users.view',
            'enabled' => false,
        ])
        ->assertRedirect();

    expect($role->fresh()->hasPermissionTo('users.view'))->toBeFalse();
});

test('administrator role permissions cannot be removed', function () {
    $user = userWithRolesPermissions(['roles.permissions.update']);
    $role = Role::create(['name' => 'admin']);
    $role->givePermissionTo(Permission::create(['name' => 'users.view']));

    $this
        ->actingAs($user)
        ->patch(route('roles-permissions.permissions.update', $role), [
            'permission' => 'users.view',
            'enabled' => false,
        ])
        ->assertRedirect();

    expect($role->fresh()->hasPermissionTo('users.view'))->toBeTrue();
});

test('authenticated users can create roles', function () {
    $user = userWithRolesPermissions(['roles.create']);

    $this
        ->actingAs($user)
        ->post(route('roles-permissions.store'), [
            'fallback_label' => 'Manager',
            'ai_description' => 'Reviews and assigns incoming inquiries to the correct team.',
        ])
        ->assertRedirect();

    $role = Role::query()->where('fallback_label', 'Manager')->firstOrFail();

    expect($role->name)->toBe('manager')
        ->and($role->uuid)->not->toBeEmpty()
        ->and($role->label_key)->toBe("roles.{$role->uuid}.label")
        ->and($role->ai_description)->toBe('Reviews and assigns incoming inquiries to the correct team.')
        ->and((bool) $role->is_protected)->toBeFalse();

    $this
        ->actingAs($user)
        ->get(route('roles-permissions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('roleCatalog.7.name', 'manager')
            ->where('roleCatalog.7.fallback_label', 'Manager')
            ->where('roleCatalog.7.ai_description', 'Reviews and assigns incoming inquiries to the correct team.')
            ->where('roleCatalog.7.label', 'Manager')
        );
});

test('role fallback labels must be unique', function () {
    $user = userWithRolesPermissions(['roles.create']);
    Role::create([
        'name' => 'manager',
        'guard_name' => 'web',
        'fallback_label' => 'Manager',
    ]);

    $this
        ->actingAs($user)
        ->post(route('roles-permissions.store'), [
            'fallback_label' => 'Manager',
        ])
        ->assertSessionHasErrors('fallback_label');
});

test('authenticated users can update role labels', function () {
    $user = userWithRolesPermissions(['roles.update']);
    $role = Role::create([
        'name' => 'manager',
        'guard_name' => 'web',
        'fallback_label' => 'Manager',
    ]);

    $this
        ->actingAs($user)
        ->patch(route('roles-permissions.update', $role), [
            'fallback_label' => 'Editor',
            'ai_description' => 'Edits inquiry content and prepares responses.',
        ])
        ->assertRedirect();

    expect($role->fresh()->name)->toBe('manager')
        ->and($role->fresh()->fallback_label)->toBe('Editor')
        ->and($role->fresh()->ai_description)->toBe('Edits inquiry content and prepares responses.');
});

test('the administrator role cannot be updated or deleted', function () {
    $user = userWithRolesPermissions(['roles.update', 'roles.delete']);
    $role = Role::create(['name' => 'admin']);

    $this
        ->actingAs($user)
        ->patch(route('roles-permissions.update', $role), [
            'fallback_label' => 'Owner',
            'ai_description' => 'Owns the entire system.',
        ])
        ->assertForbidden();

    expect($role->fresh()->name)->toBe('admin');

    $this
        ->actingAs($user)
        ->delete(route('roles-permissions.destroy', $role))
        ->assertForbidden();

    $this->assertModelExists($role);
});

test('authenticated users can delete non protected roles', function () {
    $user = userWithRolesPermissions(['roles.delete']);
    $role = Role::create(['name' => 'manager']);

    $this
        ->actingAs($user)
        ->delete(route('roles-permissions.destroy', $role))
        ->assertRedirect();

    $this->assertModelMissing($role);
});

test('appearance settings page is not available', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/settings/appearance');

    $response->assertNotFound();
});
