<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UsersController extends Controller
{
    private const int USERS_PER_PAGE = 50;

    public function index(): Response
    {
        return Inertia::render('Users', [
            'regularUsers' => Inertia::scroll($this->usersByType('regular', 'regularUsers')),
            'systemUsers' => Inertia::scroll($this->usersByType('system', 'systemUsers')),
            'roles' => $this->roles(),
        ]);
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'type' => $validated['type'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $user->syncRoles([$this->assignedRole($validated['type'], $validated['role_id'] ?? null)]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return to_route('users.index');
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $user->fill([
            'type' => $validated['type'],
            'status' => $validated['status'],
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($validated['password'] ?? null) {
            $user->password = $validated['password'];
        }

        $user->save();

        $user->syncRoles([$this->assignedRole($validated['type'], $validated['role_id'] ?? null)]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('users.index');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deleted.')]);

        return to_route('users.index');
    }

    /**
     * @return LengthAwarePaginator<int, array{id: int, type: string, name: string, email: string, status: string, role_id: int|null, roles: array<int, string>, created_at: string|null}>
     */
    private function usersByType(string $type, string $pageName): LengthAwarePaginator
    {
        $userRoleId = Role::findOrCreate('user')->id;

        $users = fn (?int $page = null): LengthAwarePaginator => User::query()
            ->with('roles:id,name')
            ->select(['id', 'type', 'name', 'email', 'status', 'created_at'])
            ->where('type', $type)
            ->latest('id')
            ->paginate(self::USERS_PER_PAGE, ['*'], $pageName, $page);

        $paginator = $users();

        if ($paginator->count() === 0 && $paginator->currentPage() > 1 && $paginator->total() > 0) {
            $paginator = $users(1);
        }

        return $paginator->through(fn (User $user): array => [
            'id' => $user->id,
            'type' => $user->type,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'role_id' => $type === 'regular' ? $userRoleId : $user->roles->first()?->id,
            'roles' => $type === 'regular'
                ? [__('User')]
                : $user->roles->map(fn (Role $role): string => $this->roleLabel($role))->values()->all(),
            'created_at' => $user->created_at?->format('d.m.Y H:i'),
        ]);
    }

    /**
     * @return Collection<int, array{id: int, name: string, label: string}>
     */
    private function roles(): Collection
    {
        return Role::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => $this->roleLabel($role),
            ]);
    }

    private function assignedRole(string $type, int|string|null $roleId): Role
    {
        if ($type === 'regular') {
            return Role::findOrCreate('user');
        }

        return $roleId === null ? Role::findOrCreate('user') : Role::findById((int) $roleId);
    }

    private function roleLabel(Role $role): string
    {
        return match ($role->name) {
            'admin' => __('Administrator'),
            'user' => __('User'),
            default => Str::headline($role->name),
        };
    }
}
