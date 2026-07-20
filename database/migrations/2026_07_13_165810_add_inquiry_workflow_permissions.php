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
            'inquiries.assign',
            'inquiries.respond',
            'inquiries.approve',
            'inquiries.send',
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permission,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $rolePermissions = [
            'admin' => $permissions,
            'compliance_officer' => $permissions,
            'legal_counsel' => ['inquiries.respond', 'inquiries.approve'],
            'hr_specialist' => ['inquiries.respond'],
            'security_investigator' => ['inquiries.respond'],
            'physical_security_specialist' => ['inquiries.respond'],
            'information_security_specialist' => ['inquiries.respond'],
            'economic_security_specialist' => ['inquiries.respond'],
            'ethics_officer' => ['inquiries.respond'],
            'occupational_safety_specialist' => ['inquiries.respond'],
            'procurement_control_specialist' => ['inquiries.respond'],
        ];

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $roleId = DB::table('roles')->where('name', $roleName)->value('id');

            if ($roleId === null) {
                continue;
            }

            $permissionIds = DB::table('permissions')
                ->whereIn('name', $permissionNames)
                ->pluck('id');

            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', [
                'inquiries.assign',
                'inquiries.respond',
                'inquiries.approve',
                'inquiries.send',
            ])
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
