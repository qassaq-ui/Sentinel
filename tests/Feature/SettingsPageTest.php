<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function userWithRolesPermissions(array $permissions = []): User
{
    $permissionNames = array_values(array_unique(array_merge([
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

test('authenticated users can visit the settings page', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('settings.index'));

    $response
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
            ->where('roles.0.protected', true)
            ->has('roles.0.permissions', 10)
            ->where('permissions.0.name', 'settings.access')
            ->where('permissions.0.group', 'Settings')
            ->where('permissions.0.label', 'Allow access to system settings')
            ->where('permissions.1.name', 'roles.view')
            ->where('permissions.1.group', 'Roles')
            ->where('permissions.1.label', 'Allow viewing roles page')
            ->where('permissions.2.name', 'roles.create')
            ->where('permissions.2.group', 'Roles')
            ->where('permissions.2.label', 'Allow creating roles')
            ->where('permissions.3.name', 'roles.update')
            ->where('permissions.3.group', 'Roles')
            ->where('permissions.3.label', 'Allow editing roles')
            ->where('permissions.4.name', 'roles.delete')
            ->where('permissions.4.group', 'Roles')
            ->where('permissions.4.label', 'Allow deleting roles')
            ->where('permissions.5.name', 'roles.permissions.update')
            ->where('permissions.5.group', 'Roles')
            ->where('permissions.5.label', 'Allow managing role permissions')
            ->where('permissions.6.name', 'users.view')
            ->where('permissions.6.group', 'Users')
            ->where('permissions.6.label', 'Allow viewing users page')
            ->where('permissions.7.name', 'users.create')
            ->where('permissions.7.group', 'Users')
            ->where('permissions.7.label', 'Allow creating users')
            ->where('permissions.8.name', 'users.update')
            ->where('permissions.8.group', 'Users')
            ->where('permissions.8.label', 'Allow editing users')
            ->where('permissions.9.name', 'users.delete')
            ->where('permissions.9.group', 'Users')
            ->where('permissions.9.label', 'Allow deleting users')
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
            'name' => 'Менеджер',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('roles', [
        'name' => 'Менеджер',
        'guard_name' => 'web',
    ]);

    $this
        ->actingAs($user)
        ->get(route('roles-permissions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('roles.1.name', 'Менеджер')
            ->where('roles.1.label', 'Менеджер')
        );
});

test('role names must be unique', function () {
    $user = userWithRolesPermissions(['roles.create']);
    Role::create(['name' => 'Менеджер']);

    $this
        ->actingAs($user)
        ->post(route('roles-permissions.store'), [
            'name' => 'Менеджер',
        ])
        ->assertSessionHasErrors('name');
});

test('authenticated users can update role names', function () {
    $user = userWithRolesPermissions(['roles.update']);
    $role = Role::create(['name' => 'manager']);

    $this
        ->actingAs($user)
        ->patch(route('roles-permissions.update', $role), [
            'name' => 'editor',
        ])
        ->assertRedirect();

    expect($role->fresh()->name)->toBe('editor');
});

test('administrator role cannot be updated or deleted', function () {
    $user = userWithRolesPermissions(['roles.update', 'roles.delete']);
    $role = Role::create(['name' => 'admin']);

    $this
        ->actingAs($user)
        ->patch(route('roles-permissions.update', $role), [
            'name' => 'owner',
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
