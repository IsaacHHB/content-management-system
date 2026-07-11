<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Services\SiteChrome;
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
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? array_merge($user->toArray(), [
                    'roles' => $user->getRoleNames()->all(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->all(),
                ]) : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'settings' => fn () => Setting::allCached(),
            // Header/footer menus + partners, for the admin visual editor's page
            // preview. Only for authenticated users (public pages get their own
            // publicMenus/publicPartners); cached, so this is a cheap lookup.
            'siteChrome' => $user
                ? fn () => [
                    'menus' => app(SiteChrome::class)->menus(),
                    'partners' => app(SiteChrome::class)->partners(),
                ]
                : null,
            // Sample content so newly-added list blocks preview live in the
            // visual editor. Authenticated (admin) only.
            'blockPreviews' => $user ? fn () => app(SiteChrome::class)->blockPreviews() : null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
