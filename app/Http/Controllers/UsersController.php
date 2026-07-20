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
            'initialTab' => 'users',
            'users' => Inertia::scroll($this->users()),
            'assignableRoles' => $this->roles(),
        ]);
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $user->syncRoles([Role::findById((int) $validated['role_id'])]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return to_route('users.index');
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $user->fill([
            'status' => $validated['status'],
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($validated['password'] ?? null) {
            $user->password = $validated['password'];
        }

        $user->save();

        $user->syncRoles([Role::findById((int) $validated['role_id'])]);

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
     * @return LengthAwarePaginator<int, array{id: int, name: string, email: string, status: string, role_id: int|null, roles: array<int, string>, created_at: string|null}>
     */
    private function users(): LengthAwarePaginator
    {
        $users = fn (?int $page = null): LengthAwarePaginator => User::query()
            ->with('roles:id,name,fallback_label')
            ->select(['id', 'name', 'email', 'status', 'created_at'])
            ->latest('id')
            ->paginate(self::USERS_PER_PAGE, ['*'], 'users', $page);

        $paginator = $users();

        if ($paginator->count() === 0 && $paginator->currentPage() > 1 && $paginator->total() > 0) {
            $paginator = $users(1);
        }

        return $paginator->through(fn (User $user): array => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'role_id' => $user->roles->first()?->id,
            'roles' => $user->roles->map(fn (Role $role): string => $this->roleLabel($role))->values()->all(),
            'created_at' => $user->created_at?->format('d.m.Y H:i'),
        ]);
    }

    /**
     * @return Collection<int, array{id: int, name: string, label: string}>
     */
    private function roles(): Collection
    {
        return Role::query()
            ->select(['id', 'name', 'fallback_label'])
            ->where('name', '!=', 'user')
            ->orderByRaw("case when name = 'admin' then 0 else 1 end")
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => $this->roleLabel($role),
            ]);
    }

    private function roleLabel(Role $role): string
    {
        return $role->fallback_label ?: match ($role->name) {
            'admin' => 'Administrator',
            default => Str::headline($role->name),
        };
    }
}
