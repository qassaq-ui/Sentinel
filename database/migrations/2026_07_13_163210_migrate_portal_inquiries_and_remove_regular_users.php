<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('inquiries')
            ->join('users', 'inquiries.created_by_id', '=', 'users.id')
            ->where('inquiries.type', 'portal')
            ->where('users.type', 'regular')
            ->select(['inquiries.id as inquiry_id', 'users.name', 'users.email'])
            ->orderBy('inquiries.id')
            ->get()
            ->each(function (object $record): void {
                DB::table('inquiry_applicants')->insert([
                    'inquiry_id' => $record->inquiry_id,
                    'name' => Crypt::encryptString($record->name),
                    'email' => Crypt::encryptString($record->email),
                    'phone' => null,
                    'tracking_token_hash' => hash('sha256', Str::random(64)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        DB::table('inquiries')->where('type', 'portal')->update(['type' => 'identified']);
        DB::table('users')->where('type', 'regular')->delete();

        $userRoleId = DB::table('roles')
            ->where('name', 'user')
            ->where('guard_name', 'web')
            ->value('id');

        if ($userRoleId !== null) {
            DB::table('role_has_permissions')->where('role_id', $userRoleId)->delete();
            DB::table('model_has_roles')->where('role_id', $userRoleId)->delete();
            DB::table('roles')->where('id', $userRoleId)->delete();
        }

        $createInquiryPermissionId = DB::table('permissions')
            ->where('name', 'inquiries.create')
            ->where('guard_name', 'web')
            ->value('id');

        if ($createInquiryPermissionId !== null) {
            DB::table('role_has_permissions')->where('permission_id', $createInquiryPermissionId)->delete();
            DB::table('model_has_permissions')->where('permission_id', $createInquiryPermissionId)->delete();
            DB::table('permissions')->where('id', $createInquiryPermissionId)->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('inquiries')->where('type', 'identified')->update(['type' => 'portal']);
    }
};
