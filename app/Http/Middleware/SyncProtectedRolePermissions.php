<?php

namespace App\Http\Middleware;

use App\Http\Requests\Settings\RolePermissionUpdateRequest;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class SyncProtectedRolePermissions
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Schema::hasTable('roles') && Schema::hasTable('permissions')) {
            $this->sync();
        }

        return $next($request);
    }

    private function sync(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        collect(RolePermissionUpdateRequest::SYSTEM_PERMISSIONS)
            ->each(fn (string $permission): Permission => Permission::findOrCreate($permission));

        $adminRole = Role::findOrCreate('admin');
        $adminRole->syncPermissions(Permission::query()->pluck('name')->all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
