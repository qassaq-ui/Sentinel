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
    $permissionNames = array_values(array_unique(array_merge([
        'users.view',
    ], $permissions)));

    collect($permissionNames)->each(fn (string $permission): Permission => Permission::findOrCreate($permission));

    $user = User::factory()->create();
    $user->givePermissionTo($permissionNames);

    return $user;
}

test('guests are redirected from the users page to the login page', function () {
    $response = $this->get(route('users.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the users page', function () {
    $user = userWithUsersPermissions();
    $role = Role::create(['name' => 'manager']);

    $user->assignRole($role);

    $response = $this
        ->actingAs($user)
        ->get(route('users.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Users')
            ->where('regularUsers.data.0.id', $user->id)
            ->where('regularUsers.data.0.name', $user->name)
            ->where('regularUsers.data.0.email', $user->email)
            ->where('regularUsers.data.0.status', 'active')
            ->where('regularUsers.data.0.roles.0', 'User')
            ->where('systemUsers.data', [])
            ->where('roles', function (Collection $roles) use ($role): bool {
                return $roles->pluck('id')->contains($role->id);
            })
        );
});

test('database seeder creates system specialists for inquiry assignment', function () {
    $this->seed(SpecialistSeeder::class);

    $legal = User::query()->where('email', 'legal@speakup.test')->firstOrFail();
    $hr = User::query()->where('email', 'hr@speakup.test')->firstOrFail();
    $security = User::query()->where('email', 'economic.security@speakup.test')->firstOrFail();

    expect($legal->type)->toBe('system')
        ->and($legal->status)->toBe('active')
        ->and($legal->hasRole('legal_counsel'))->toBeTrue()
        ->and($hr->hasRole('hr_specialist'))->toBeTrue()
        ->and($security->hasRole('economic_security_specialist'))->toBeTrue()
        ->and(User::query()->whereIn('email', [
            'legal@speakup.test',
            'hr@speakup.test',
            'compliance@speakup.test',
            'ethics@speakup.test',
            'security.investigations@speakup.test',
            'physical.security@speakup.test',
            'information.security@speakup.test',
            'economic.security@speakup.test',
            'safety@speakup.test',
            'procurement.control@speakup.test',
        ])->count())->toBe(10);
});

test('users page loads the first page of users for infinite scroll', function () {
    Role::create(['name' => 'admin']);
    $systemRole = Role::create(['name' => 'manager']);

    User::factory()
        ->count(51)
        ->create([
            'type' => 'regular',
            'status' => 'active',
        ]);

    User::factory()
        ->count(51)
        ->create([
            'type' => 'system',
            'status' => 'active',
        ])
        ->each(fn (User $user) => $user->assignRole($systemRole));

    $user = userWithUsersPermissions();

    $this
        ->actingAs($user)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('regularUsers.data', 50)
            ->has('systemUsers.data', 50)
        );

    $this
        ->actingAs($user)
        ->get(route('users.index', [
            'regularUsers' => 2,
            'systemUsers' => 2,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('regularUsers.data', 2)
            ->has('systemUsers.data', 1)
        );
});

test('users page falls back to the first page when an infinite scroll page is out of range', function () {
    $user = userWithUsersPermissions();

    $this
        ->actingAs($user)
        ->get(route('users.index', [
            'regularUsers' => 2,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('regularUsers.data', 1)
            ->where('regularUsers.data.0.id', $user->id)
        );
});

test('authenticated users can create portal users', function () {
    $user = userWithUsersPermissions(['users.create']);

    $this
        ->actingAs($user)
        ->post(route('users.store'), [
            'type' => 'regular',
            'name' => 'Ivan Petrov',
            'email' => 'ivan@example.com',
            'password' => 'generated-password',
        ])
        ->assertRedirect(route('users.index'));

    $createdUser = User::where('email', 'ivan@example.com')->firstOrFail();

    expect($createdUser->type)->toBe('regular')
        ->and($createdUser->status)->toBe('active')
        ->and(Hash::check('generated-password', $createdUser->password))->toBeTrue()
        ->and($createdUser->hasRole('user'))->toBeTrue();
});

test('authenticated users can create system accounts', function () {
    $user = userWithUsersPermissions(['users.create']);
    $role = Role::create(['name' => 'manager']);

    $this
        ->actingAs($user)
        ->post(route('users.store'), [
            'type' => 'system',
            'name' => 'Integration Bot',
            'email' => 'integration@example.com',
            'password' => 'generated-password',
            'role_id' => $role->id,
        ])
        ->assertRedirect(route('users.index'));

    $this
        ->actingAs($user)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('systemUsers.data.0.name', 'Integration Bot')
            ->where('systemUsers.data.0.email', 'integration@example.com')
            ->where('regularUsers.data.0.id', $user->id)
        );
});

test('authenticated users can update users and move them between account types', function () {
    $admin = userWithUsersPermissions(['users.update']);
    $user = User::factory()->create([
        'type' => 'regular',
        'status' => 'active',
        'password' => 'current-password',
    ]);
    $userRole = Role::create(['name' => 'user']);
    $systemRole = Role::create(['name' => 'system']);

    $user->assignRole($userRole);

    $this
        ->actingAs($admin)
        ->patch(route('users.update', $user), [
            'type' => 'system',
            'status' => 'blocked',
            'name' => 'System Integration',
            'email' => 'system@example.com',
            'password' => '',
            'role_id' => $systemRole->id,
        ])
        ->assertRedirect(route('users.index'));

    $user->refresh();

    expect($user->type)->toBe('system')
        ->and($user->status)->toBe('blocked')
        ->and($user->name)->toBe('System Integration')
        ->and($user->email)->toBe('system@example.com')
        ->and(Hash::check('current-password', $user->password))->toBeTrue()
        ->and($user->hasRole($systemRole))->toBeTrue()
        ->and($user->hasRole($userRole))->toBeFalse();

    $this
        ->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('systemUsers.data.0.id', $user->id)
            ->where('systemUsers.data.0.type', 'system')
            ->where('systemUsers.data.0.status', 'blocked')
            ->where('regularUsers.data.0.id', $admin->id)
        );
});

test('authenticated users can block and unblock users', function () {
    $admin = userWithUsersPermissions(['users.update']);
    $user = User::factory()->create([
        'type' => 'regular',
        'status' => 'active',
    ]);

    $this
        ->actingAs($admin)
        ->patch(route('users.update', $user), [
            'type' => 'regular',
            'status' => 'blocked',
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'role_id' => null,
        ])
        ->assertRedirect(route('users.index'));

    expect($user->fresh()->status)->toBe('blocked');

    $this
        ->actingAs($admin)
        ->patch(route('users.update', $user), [
            'type' => 'regular',
            'status' => 'active',
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'role_id' => null,
        ])
        ->assertRedirect(route('users.index'));

    expect($user->fresh()->status)->toBe('active');
});

test('authenticated users can delete users', function () {
    $admin = userWithUsersPermissions(['users.delete']);
    $user = User::factory()->create();

    $this
        ->actingAs($admin)
        ->delete(route('users.destroy', $user))
        ->assertRedirect(route('users.index'));

    $this->assertModelMissing($user);
});

test('user creation validates required fields', function () {
    $user = userWithUsersPermissions(['users.create']);

    $this
        ->actingAs($user)
        ->post(route('users.store'), [
            'type' => 'external',
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
        ])
        ->assertSessionHasErrors(['type', 'name', 'email', 'password']);
});

test('user update validates required fields', function () {
    $admin = userWithUsersPermissions(['users.update']);
    $user = User::factory()->create();

    $this
        ->actingAs($admin)
        ->patch(route('users.update', $user), [
            'type' => 'external',
            'status' => 'unknown',
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
        ])
        ->assertSessionHasErrors(['type', 'status', 'name', 'email', 'password']);
});
