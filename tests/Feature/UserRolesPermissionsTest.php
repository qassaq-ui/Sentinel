<?php

use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('users can receive roles with permissions', function () {
    $user = User::factory()->create();
    $permission = Permission::create(['name' => 'manage roles']);
    $role = Role::create(['name' => 'admin']);

    $role->givePermissionTo($permission);
    $user->assignRole($role);

    expect($user->hasRole('admin'))->toBeTrue()
        ->and($user->can('manage roles'))->toBeTrue();
});

test('shared auth data includes permission flags', function () {
    collect(['settings.access', 'users.view', 'users.create', 'users.update', 'users.delete'])
        ->each(fn (string $permission): Permission => Permission::findOrCreate($permission));

    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.can.settingsAccess', false)
            ->where('auth.can.usersView', false)
            ->where('auth.can.usersCreate', false)
            ->where('auth.can.usersUpdate', false)
            ->where('auth.can.usersDelete', false)
        );

    $user->givePermissionTo(['settings.access', 'users.view', 'users.create', 'users.update', 'users.delete']);

    $this
        ->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.can.settingsAccess', true)
            ->where('auth.can.usersView', true)
            ->where('auth.can.usersCreate', true)
            ->where('auth.can.usersUpdate', true)
            ->where('auth.can.usersDelete', true)
        );
});

test('administrators can access roles and permissions without manual permission grants', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('admin'));

    $this
        ->actingAs($user)
        ->get(route('roles-permissions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/RolesPermissions')
        );
});

test('role and permission middleware aliases are registered', function () {
    $middleware = app(Kernel::class)->getMiddlewareAliases();

    expect($middleware['role'])->toBe(RoleMiddleware::class)
        ->and($middleware['permission'])->toBe(PermissionMiddleware::class)
        ->and($middleware['role_or_permission'])->toBe(RoleOrPermissionMiddleware::class);
});
