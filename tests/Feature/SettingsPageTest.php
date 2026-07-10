<?php

use App\Models\User;
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

test('authenticated users can visit the roles and permissions settings page', function () {
    $user = userWithRolesPermissions();

    $response = $this
        ->actingAs($user)
        ->get(route('roles-permissions.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/RolesPermissions')
            ->where('roles.0.name', 'admin')
            ->where('roles.0.label', 'Administrator')
            ->where('roles.0.ai_description', 'Has full access to manage users, roles, permissions, dictionaries, inquiries, and system settings.')
            ->where('roles.0.protected', true)
            ->has('roles.0.permissions', 18)
            ->where('permissions.0.name', 'settings.access')
            ->where('permissions.0.group', 'Settings')
            ->where('permissions.0.label', 'Allow access to system settings')
            ->where('permissions.1.name', 'inquiries.view')
            ->where('permissions.1.group', 'Inquiries')
            ->where('permissions.1.label', 'Allow viewing inquiries page')
            ->where('permissions.2.name', 'inquiries.create')
            ->where('permissions.2.group', 'Inquiries')
            ->where('permissions.2.label', 'Allow creating inquiries')
            ->where('permissions.3.name', 'inquiries.update')
            ->where('permissions.3.group', 'Inquiries')
            ->where('permissions.3.label', 'Allow editing inquiries')
            ->where('permissions.4.name', 'inquiries.delete')
            ->where('permissions.4.group', 'Inquiries')
            ->where('permissions.4.label', 'Allow deleting inquiries')
            ->where('permissions.5.name', 'dictionaries.view')
            ->where('permissions.5.group', 'Dictionaries')
            ->where('permissions.5.label', 'Allow viewing dictionaries page')
            ->where('permissions.6.name', 'dictionaries.create')
            ->where('permissions.6.group', 'Dictionaries')
            ->where('permissions.6.label', 'Allow creating dictionary entries')
            ->where('permissions.7.name', 'dictionaries.update')
            ->where('permissions.7.group', 'Dictionaries')
            ->where('permissions.7.label', 'Allow editing dictionary entries')
            ->where('permissions.8.name', 'dictionaries.delete')
            ->where('permissions.8.group', 'Dictionaries')
            ->where('permissions.8.label', 'Allow deleting dictionary entries')
            ->where('permissions.9.name', 'roles.view')
            ->where('permissions.9.group', 'Roles')
            ->where('permissions.9.label', 'Allow viewing roles page')
            ->where('permissions.10.name', 'roles.create')
            ->where('permissions.10.group', 'Roles')
            ->where('permissions.10.label', 'Allow creating roles')
            ->where('permissions.11.name', 'roles.update')
            ->where('permissions.11.group', 'Roles')
            ->where('permissions.11.label', 'Allow editing roles')
            ->where('permissions.12.name', 'roles.delete')
            ->where('permissions.12.group', 'Roles')
            ->where('permissions.12.label', 'Allow deleting roles')
            ->where('permissions.13.name', 'roles.permissions.update')
            ->where('permissions.13.group', 'Roles')
            ->where('permissions.13.label', 'Allow managing role permissions')
            ->where('permissions.14.name', 'users.view')
            ->where('permissions.14.group', 'Users')
            ->where('permissions.14.label', 'Allow viewing users page')
            ->where('permissions.15.name', 'users.create')
            ->where('permissions.15.group', 'Users')
            ->where('permissions.15.label', 'Allow creating users')
            ->where('permissions.16.name', 'users.update')
            ->where('permissions.16.group', 'Users')
            ->where('permissions.16.label', 'Allow editing users')
            ->where('permissions.17.name', 'users.delete')
            ->where('permissions.17.group', 'Users')
            ->where('permissions.17.label', 'Allow deleting users')
            ->where('roles.1.name', 'user')
            ->where('roles.1.label', 'User')
            ->where('roles.1.ai_description', 'Default portal user role for people who submit and track their own inquiries.')
            ->where('roles.1.protected', true)
            ->has('roles', 12)
            ->where('roles.2.name', 'compliance_officer')
            ->where('roles.2.label', 'Compliance Officer')
            ->where('roles.2.ai_description', 'Triages ethics, compliance, corruption, conflict of interest, policy breach, and whistleblowing inquiries and decides appropriate assignment or escalation.')
            ->where('roles.5.name', 'hr_specialist')
            ->where('roles.5.label', 'HR Specialist')
            ->where('roles.7.name', 'legal_counsel')
            ->where('roles.7.label', 'Legal Counsel')
            ->where('roles.11.name', 'security_investigator')
            ->where('roles.11.label', 'Security Investigator')
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
            ->where('roles.8.name', 'manager')
            ->where('roles.8.fallback_label', 'Manager')
            ->where('roles.8.ai_description', 'Reviews and assigns incoming inquiries to the correct team.')
            ->where('roles.8.label', 'Manager')
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

test('system roles cannot be updated or deleted', function (string $roleName) {
    $user = userWithRolesPermissions(['roles.update', 'roles.delete']);
    $role = Role::create(['name' => $roleName]);

    $this
        ->actingAs($user)
        ->patch(route('roles-permissions.update', $role), [
            'fallback_label' => 'Owner',
            'ai_description' => 'Owns the entire system.',
        ])
        ->assertForbidden();

    expect($role->fresh()->name)->toBe($roleName);

    $this
        ->actingAs($user)
        ->delete(route('roles-permissions.destroy', $role))
        ->assertForbidden();

    $this->assertModelExists($role);
})->with(['admin', 'user']);

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
