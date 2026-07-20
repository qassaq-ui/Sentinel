<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            'inquiries.view_all',
            'inquiries.view_assigned',
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permission,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $viewAllRoles = ['admin', 'compliance_officer'];
        $viewAssignedRoles = [
            'admin',
            'compliance_officer',
            'legal_counsel',
            'hr_specialist',
            'security_investigator',
            'physical_security_specialist',
            'information_security_specialist',
            'economic_security_specialist',
            'ethics_officer',
            'occupational_safety_specialist',
            'procurement_control_specialist',
        ];

        $this->grantPermissionToRoles('inquiries.view_all', $viewAllRoles);
        $this->grantPermissionToRoles('inquiries.view_assigned', $viewAssignedRoles);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', [
                'inquiries.view_all',
                'inquiries.view_assigned',
            ])
            ->where('guard_name', 'web')
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }

    /** @param array<int, string> $roleNames */
    private function grantPermissionToRoles(string $permissionName, array $roleNames): void
    {
        $permissionId = DB::table('permissions')
            ->where('name', $permissionName)
            ->where('guard_name', 'web')
            ->value('id');

        if ($permissionId === null) {
            return;
        }

        $roleIds = DB::table('roles')
            ->whereIn('name', $roleNames)
            ->where('guard_name', 'web')
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }
};
