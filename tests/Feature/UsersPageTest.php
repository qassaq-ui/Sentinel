<?php

use App\Models\User;
use Database\Seeders\SpecialistSeeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function userWithUsersPermissions(array $permissions = []): User
{
    $permissionNames = array_values(array_unique(array_merge(['users.view'], $permissions)));

    collect($permissionNames)->each(fn (string $permission): Permission => Permission::findOrCreate($permission));

    $user = User::factory()->create();
    $user->givePermissionTo($permissionNames);

    return $user;
}

test('guests are redirected from the users page to the login page', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
});

test('authenticated employees can visit the users tab', function () {
    $role = Role::create(['name' => 'manager', 'fallback_label' => 'Manager']);
    $user = userWithUsersPermissions();
    $user->assignRole($role);

    $this
        ->actingAs($user)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Users')
            ->where('initialTab', 'users')
            ->where('users.data.0.id', $user->id)
            ->where('users.data.0.name', $user->name)
            ->where('users.data.0.email', $user->email)
            ->where('users.data.0.status', 'active')
            ->where('users.data.0.roles.0', 'Manager')
            ->where('assignableRoles', function (Collection $roles) use ($role): bool {
                return $roles->pluck('id')->contains($role->id)
                    && ! $roles->pluck('name')->contains('user');
            })
        );
});

test('roles and permissions are rendered as the second users page tab', function () {
    Permission::findOrCreate('roles.view');
    $user = User::factory()->create();
    $user->givePermissionTo('roles.view');

    $this
        ->actingAs($user)
        ->get(route('roles-permissions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Users')
            ->where('initialTab', 'roles')
            ->where('roleCatalog.0.name', 'admin')
            ->where('roleCatalog.0.protected', true)
            ->has('permissions', 23)
            ->missing('users')
        );
});

test('users tabs preserve inertia state and use the shared segmented style', function () {
    $usersPage = file_get_contents(resource_path('js/pages/Users.vue'));
    $usersTable = file_get_contents(resource_path('js/pages/users/UsersTable.vue'));
    $roles = file_get_contents(resource_path('js/pages/settings/roles-permissions/RolesList.vue'));
    $permissions = file_get_contents(resource_path('js/pages/settings/roles-permissions/PermissionsList.vue'));

    expect($usersPage)
        ->toContain('preserve-state')
        ->toContain("@click=\"activeTab = 'users'\"")
        ->toContain("@click=\"activeTab = 'roles'\"")
        ->toContain('bg-white text-[#1d1d1f]')
        ->not->toContain('translateX(${activeTab')
        ->and($usersTable)
        ->toContain('bg-[#f7f7f8]')
        ->not->toContain('rounded-lg border border-border')
        ->and($roles)
        ->toContain('border-y border-black/8 bg-[#f7f7f8]')
        ->and($permissions)
        ->toContain('border-y border-black/8 bg-[#f7f7f8]');
});

test('roles tab displays a matching skeleton while its content loads', function () {
    $usersPage = file_get_contents(resource_path('js/pages/Users.vue'));
    $skeleton = file_get_contents(resource_path(
        'js/pages/settings/roles-permissions/RolesPermissionsSkeleton.vue',
    ));

    expect($usersPage)
        ->toContain('<RolesPermissionsSkeleton v-if="isRolesLoading" />')
        ->toContain('showRolesSkeleton()')
        ->and($skeleton)
        ->toContain('v-for="role in 6"')
        ->toContain('v-for="group in 3"');
});

test('database seeder creates employees for inquiry assignment', function () {
    $this->seed(SpecialistSeeder::class);

    $legal = User::query()->where('email', 'legal@speakup.test')->firstOrFail();
    $hr = User::query()->where('email', 'hr@speakup.test')->firstOrFail();

    expect($legal->status)->toBe('active')
        ->and($legal->hasRole('legal_counsel'))->toBeTrue()
        ->and($hr->hasRole('hr_specialist'))->toBeTrue()
        ->and(User::query()->count())->toBe(10);
});

test('users page paginates employees for infinite scroll', function () {
    $role = Role::create(['name' => 'manager']);

    User::factory()
        ->count(51)
        ->create()
        ->each(fn (User $user) => $user->assignRole($role));

    $user = userWithUsersPermissions();

    $this
        ->actingAs($user)
        ->get(route('users.index'))
        ->assertInertia(fn (Assert $page) => $page->has('users.data', 50));

    $this
        ->actingAs($user)
        ->get(route('users.index', ['users' => 2]))
        ->assertInertia(fn (Assert $page) => $page->has('users.data', 2));
});

test('users page falls back to the first page when an infinite scroll page is out of range', function () {
    $user = userWithUsersPermissions();

    $this
        ->actingAs($user)
        ->get(route('users.index', ['users' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.id', $user->id)
        );
});

test('authorized employees can create staff accounts with a role', function () {
    $admin = userWithUsersPermissions(['users.create']);
    $role = Role::create(['name' => 'manager']);

    $this
        ->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Ivan Petrov',
            'email' => 'ivan@example.com',
            'password' => 'generated-password',
            'role_id' => $role->id,
        ])
        ->assertRedirect(route('users.index'));

    $createdUser = User::query()->where('email', 'ivan@example.com')->firstOrFail();

    expect($createdUser->status)->toBe('active')
        ->and(Hash::check('generated-password', $createdUser->password))->toBeTrue()
        ->and($createdUser->hasRole($role))->toBeTrue();
});

test('authorized employees can update staff accounts and roles', function () {
    $admin = userWithUsersPermissions(['users.update']);
    $oldRole = Role::create(['name' => 'reviewer']);
    $newRole = Role::create(['name' => 'manager']);
    $user = User::factory()->create(['password' => 'current-password']);
    $user->assignRole($oldRole);

    $this
        ->actingAs($admin)
        ->patch(route('users.update', $user), [
            'status' => 'blocked',
            'name' => 'Updated Employee',
            'email' => 'updated@example.com',
            'password' => '',
            'role_id' => $newRole->id,
        ])
        ->assertRedirect(route('users.index'));

    $user->refresh();

    expect($user->status)->toBe('blocked')
        ->and($user->name)->toBe('Updated Employee')
        ->and(Hash::check('current-password', $user->password))->toBeTrue()
        ->and($user->hasRole($newRole))->toBeTrue()
        ->and($user->hasRole($oldRole))->toBeFalse();
});

test('authorized employees can block and unblock staff accounts', function () {
    $admin = userWithUsersPermissions(['users.update']);
    $role = Role::create(['name' => 'manager']);
    $user = User::factory()->create();
    $user->assignRole($role);

    foreach (['blocked', 'active'] as $status) {
        $this
            ->actingAs($admin)
            ->patch(route('users.update', $user), [
                'status' => $status,
                'name' => $user->name,
                'email' => $user->email,
                'password' => '',
                'role_id' => $role->id,
            ])
            ->assertRedirect(route('users.index'));

        expect($user->fresh()->status)->toBe($status);
    }
});

test('authorized employees can delete staff accounts', function () {
    $admin = userWithUsersPermissions(['users.delete']);
    $user = User::factory()->create();

    $this
        ->actingAs($admin)
        ->delete(route('users.destroy', $user))
        ->assertRedirect(route('users.index'));

    $this->assertModelMissing($user);
});

test('staff account creation and update require valid data and a role', function () {
    $admin = userWithUsersPermissions(['users.create', 'users.update']);
    $user = User::factory()->create();

    $this
        ->actingAs($admin)
        ->post(route('users.store'), [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
        ])
        ->assertSessionHasErrors(['name', 'email', 'password', 'role_id']);

    $this
        ->actingAs($admin)
        ->patch(route('users.update', $user), [
            'status' => 'unknown',
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
        ])
        ->assertSessionHasErrors(['status', 'name', 'email', 'password', 'role_id']);
});
