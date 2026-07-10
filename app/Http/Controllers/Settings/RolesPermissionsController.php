<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\RolePermissionUpdateRequest;
use App\Http\Requests\Settings\RoleStoreRequest;
use App\Http\Requests\Settings\RoleUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsController extends Controller
{
    private const DEFAULT_ROLES = [
        [
            'name' => 'admin',
            'fallback_label' => 'Administrator',
            'ai_description' => 'Has full access to manage users, roles, permissions, dictionaries, inquiries, and system settings.',
            'protected' => true,
        ],
        [
            'name' => 'user',
            'fallback_label' => 'User',
            'ai_description' => 'Default portal user role for people who submit and track their own inquiries.',
            'protected' => true,
        ],
        [
            'name' => 'legal_counsel',
            'fallback_label' => 'Legal Counsel',
            'ai_description' => 'Reviews inquiries with legal risk, contracts, regulatory issues, labor law questions, personal data concerns, and prepares legally safe response guidance.',
            'protected' => false,
        ],
        [
            'name' => 'hr_specialist',
            'fallback_label' => 'HR Specialist',
            'ai_description' => 'Handles inquiries about employment relations, workplace conduct, conflicts between employees, labor discipline, schedules, leave, onboarding, and HR policy questions.',
            'protected' => false,
        ],
        [
            'name' => 'security_investigator',
            'fallback_label' => 'Security Investigator',
            'ai_description' => 'Investigates incidents involving threats, misconduct, internal violations, suspicious behavior, access misuse, and coordinates fact-finding before response preparation.',
            'protected' => false,
        ],
        [
            'name' => 'physical_security_specialist',
            'fallback_label' => 'Physical Security Specialist',
            'ai_description' => 'Handles inquiries about site access, guards, badges, restricted areas, physical incidents, property protection, visitor control, and perimeter security.',
            'protected' => false,
        ],
        [
            'name' => 'information_security_specialist',
            'fallback_label' => 'Information Security Specialist',
            'ai_description' => 'Handles inquiries about cybersecurity, account access, data leaks, phishing, device misuse, system access violations, and information protection risks.',
            'protected' => false,
        ],
        [
            'name' => 'economic_security_specialist',
            'fallback_label' => 'Economic Security Specialist',
            'ai_description' => 'Handles inquiries about fraud, theft, financial abuse, conflicts of interest, supplier risks, asset misuse, and suspicious economic activity.',
            'protected' => false,
        ],
        [
            'name' => 'compliance_officer',
            'fallback_label' => 'Compliance Officer',
            'ai_description' => 'Triages ethics, compliance, corruption, conflict of interest, policy breach, and whistleblowing inquiries and decides appropriate assignment or escalation.',
            'protected' => false,
        ],
        [
            'name' => 'ethics_officer',
            'fallback_label' => 'Ethics Officer',
            'ai_description' => 'Handles inquiries about respectful conduct, discrimination, harassment, retaliation, ethical concerns, and workplace culture issues.',
            'protected' => false,
        ],
        [
            'name' => 'occupational_safety_specialist',
            'fallback_label' => 'Occupational Safety Specialist',
            'ai_description' => 'Handles inquiries about workplace safety, health risks, unsafe conditions, equipment hazards, PPE, incidents, and safety procedure violations.',
            'protected' => false,
        ],
        [
            'name' => 'procurement_control_specialist',
            'fallback_label' => 'Procurement Control Specialist',
            'ai_description' => 'Handles inquiries about procurement violations, supplier complaints, unfair tendering, delivery issues, conflicts of interest in purchasing, and contract execution risks.',
            'protected' => false,
        ],
    ];

    private const SYSTEM_PERMISSIONS = [
        ['name' => 'settings.access', 'group' => 'Settings', 'label' => 'Allow access to system settings'],
        ['name' => 'inquiries.view', 'group' => 'Inquiries', 'label' => 'Allow viewing inquiries page'],
        ['name' => 'inquiries.create', 'group' => 'Inquiries', 'label' => 'Allow creating inquiries'],
        ['name' => 'inquiries.update', 'group' => 'Inquiries', 'label' => 'Allow editing inquiries'],
        ['name' => 'inquiries.delete', 'group' => 'Inquiries', 'label' => 'Allow deleting inquiries'],
        ['name' => 'dictionaries.view', 'group' => 'Dictionaries', 'label' => 'Allow viewing dictionaries page'],
        ['name' => 'dictionaries.create', 'group' => 'Dictionaries', 'label' => 'Allow creating dictionary entries'],
        ['name' => 'dictionaries.update', 'group' => 'Dictionaries', 'label' => 'Allow editing dictionary entries'],
        ['name' => 'dictionaries.delete', 'group' => 'Dictionaries', 'label' => 'Allow deleting dictionary entries'],
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
            ->orderByRaw("case when name = 'admin' then 0 when name = 'user' then 1 else 2 end")
            ->orderBy('name')
            ->get(['id', 'uuid', 'name', 'label_key', 'fallback_label', 'ai_description', 'is_protected'])
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'uuid' => $role->uuid,
                'name' => $role->name,
                'label_key' => $role->label_key,
                'fallback_label' => $role->fallback_label,
                'ai_description' => $role->ai_description,
                'label' => $this->roleLabel($role),
                'protected' => (bool) $role->is_protected,
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
            'uuid' => $uuid = (string) Str::uuid(),
            'name' => $this->uniqueRoleName($request->validated('fallback_label')),
            'guard_name' => 'web',
            'label_key' => "roles.{$uuid}.label",
            'fallback_label' => $request->validated('fallback_label'),
            'ai_description' => $request->validated('ai_description'),
            'is_protected' => false,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role created.')]);

        return back();
    }

    public function update(RoleUpdateRequest $request, Role $role): RedirectResponse
    {
        abort_if($this->isProtectedRole($role), 403);

        $role->update([
            'fallback_label' => $request->validated('fallback_label'),
            'ai_description' => $request->validated('ai_description'),
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
        $permission = Permission::findByName($validated['permission'], 'web');

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
            ->each(function (array $role): Role {
                $model = Role::firstOrCreate([
                    'name' => $role['name'],
                    'guard_name' => 'web',
                ]);

                $uuid = $model->uuid ?: (string) Str::uuid();

                $model->forceFill([
                    'uuid' => $uuid,
                    'label_key' => "roles.{$uuid}.label",
                    'fallback_label' => $role['fallback_label'],
                    'ai_description' => $role['ai_description'],
                    'is_protected' => $role['protected'],
                ])->save();

                return $model;
            });

        collect(self::SYSTEM_PERMISSIONS)
            ->each(fn (array $permission): Permission => Permission::findOrCreate($permission['name'], 'web'));

        $this->syncProtectedRolePermissions();
    }

    private function roleLabel(Role $role): string
    {
        return $role->fallback_label ?: Str::headline($role->name);
    }

    private function isProtectedRole(Role $role): bool
    {
        return (bool) ($role->is_protected ?? false)
            || in_array($role->name, ['admin', 'user'], true);
    }

    private function syncProtectedRolePermissions(): void
    {
        $admin = Role::query()->where('name', 'admin')->first();

        if (! $admin) {
            return;
        }

        $admin->syncPermissions(
            Permission::query()
                ->where('guard_name', 'web')
                ->pluck('name')
                ->all()
        );
    }

    private function uniqueRoleName(string $fallbackLabel): string
    {
        $baseName = Str::slug($fallbackLabel) ?: 'role';
        $name = $baseName;
        $suffix = 2;

        while (Role::query()
            ->where('guard_name', 'web')
            ->where('name', $name)
            ->exists()) {
            $name = "{$baseName}-{$suffix}";
            $suffix++;
        }

        return $name;
    }
}
