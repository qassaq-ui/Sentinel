<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\RolePermissionUpdateRequest;
use App\Http\Requests\Settings\RoleStoreRequest;
use App\Http\Requests\Settings\RoleUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsController extends Controller
{
    private const PROTECTED_ROLES = [
        'admin',
    ];

    private const DEFAULT_ROLES = [
        ['name' => 'admin', 'label' => 'Administrator'],
    ];

    private const SYSTEM_PERMISSIONS = [
        ['name' => 'settings.access', 'group' => 'Settings', 'label' => 'Allow access to system settings'],
        ['name' => 'roles.view', 'group' => 'Roles', 'label' => 'Allow viewing roles page'],
        ['name' => 'roles.create', 'group' => 'Roles', 'label' => 'Allow creating roles'],
        ['name' => 'roles.update', 'group' => 'Roles', 'label' => 'Allow editing roles'],
        ['name' => 'roles.delete', 'group' => 'Roles', 'label' => 'Allow deleting roles'],
        ['name' => 'roles.permissions.update', 'group' => 'Roles', 'label' => 'Allow managing role permissions'],
        ['name' => 'users.view', 'group' => 'Users', 'label' => 'Allow viewing users page'],
        ['name' => 'users.create', 'group' => 'Users', 'label' => 'Allow creating users'],
        ['name' => 'users.update', 'group' => 'Users', 'label' => 'Allow editing users'],
        ['name' => 'users.delete', 'group' => 'Users', 'label' => 'Allow deleting users'],
    ];

    public function edit(): Response
    {
        $this->ensureDefaultsExist();
        $this->syncProtectedRolePermissions();

        $roles = Role::query()
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => $this->roleLabel($role->name),
                'protected' => $this->isProtectedRole($role),
                'permissions' => $role->permissions->pluck('name')->values(),
            ]);

        return Inertia::render('settings/RolesPermissions', [
            'roles' => $roles,
            'permissions' => collect(self::SYSTEM_PERMISSIONS)
                ->map(fn (array $permission): array => [
                    'name' => $permission['name'],
                    'group' => __($permission['group']),
                    'label' => __($permission['label']),
                ])
                ->values(),
        ]);
    }

    public function store(RoleStoreRequest $request): RedirectResponse
    {
        Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role created.')]);

        return back();
    }

    public function update(RoleUpdateRequest $request, Role $role): RedirectResponse
    {
        abort_if($this->isProtectedRole($role), 403);

        $role->update([
            'name' => $request->validated('name'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role updated.')]);

        return back();
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if($this->isProtectedRole($role), 403);

        $role->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role deleted.')]);

        return back();
    }

    public function updatePermission(RolePermissionUpdateRequest $request, Role $role): RedirectResponse
    {
        if ($this->isProtectedRole($role)) {
            return back();
        }

        $validated = $request->validated();
        $permission = Permission::findByName($validated['permission']);

        if ($validated['enabled']) {
            $role->givePermissionTo($permission);
        } else {
            $role->revokePermissionTo($permission);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Permission updated.')]);

        return back();
    }

    private function ensureDefaultsExist(): void
    {
        collect(self::DEFAULT_ROLES)
            ->each(fn (array $role): Role => Role::firstOrCreate([
                'name' => $role['name'],
                'guard_name' => 'web',
            ]));

        collect(self::SYSTEM_PERMISSIONS)
            ->each(fn (array $permission): Permission => Permission::firstOrCreate([
                'name' => $permission['name'],
                'guard_name' => 'web',
            ]));

        $this->syncProtectedRolePermissions();
    }

    private function roleLabel(string $name): string
    {
        $label = collect(self::DEFAULT_ROLES)->firstWhere('name', $name)['label'] ?? $name;

        return __($label);
    }

    private function isProtectedRole(Role $role): bool
    {
        return in_array($role->name, self::PROTECTED_ROLES, true);
    }

    private function syncProtectedRolePermissions(): void
    {
        $admin = Role::query()->where('name', 'admin')->first();

        if (! $admin) {
            return;
        }

        $admin->syncPermissions(Permission::query()->pluck('name')->all());
    }
}
