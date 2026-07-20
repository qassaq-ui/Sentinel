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
        $permissionId = DB::table('permissions')
            ->where('name', 'inquiries.view')
            ->where('guard_name', 'web')
            ->value('id');

        if ($permissionId === null) {
            return;
        }

        $roleIds = DB::table('roles')
            ->whereIn('name', [
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
            ])
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The view permission and its assignments may have existed before this migration.
    }
};
