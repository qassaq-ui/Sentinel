<?php

namespace App\Http\Middleware;

use App\Support\Localization\LocalizationManager;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $localization = app(LocalizationManager::class);
        $locale = $localization->currentLocale();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'can' => [
                    'rolesView' => $request->user()?->can('roles.view') ?? false,
                    'rolesCreate' => $request->user()?->can('roles.create') ?? false,
                    'rolesUpdate' => $request->user()?->can('roles.update') ?? false,
                    'rolesDelete' => $request->user()?->can('roles.delete') ?? false,
                    'rolesPermissionsUpdate' => $request->user()?->can('roles.permissions.update') ?? false,
                    'usersView' => $request->user()?->can('users.view') ?? false,
                    'usersCreate' => $request->user()?->can('users.create') ?? false,
                    'usersUpdate' => $request->user()?->can('users.update') ?? false,
                    'usersDelete' => $request->user()?->can('users.delete') ?? false,
                ],
            ],
            'locale' => [
                'current' => $locale,
                'available' => $localization->availableLocales(),
                'messages' => $localization->messages($locale),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
