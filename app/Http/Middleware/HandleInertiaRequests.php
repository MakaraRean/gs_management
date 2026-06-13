<?php

namespace App\Http\Middleware;

use App\Models\Station;
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
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'locale' => app()->getLocale(),
            'translations' => (object) $this->loadTranslations(app()->getLocale()),
            'current_station' => function () {
                $s = app()->bound('current.station') ? app('current.station') : null;

                return $s ? ['id' => $s->id, 'name' => $s->name, 'business_id' => $s->business_id] : null;
            },
            'stations' => function () use ($request) {
                $user = $request->user();

                return $user?->business_id
                    ? Station::query()
                        ->where('business_id', $user->business_id)
                        ->orderBy('name')
                        ->get(['id', 'name', 'business_id'])
                    : [];
            },
            'has_business' => fn () => (bool) $request->user()?->business_id,
            'has_station' => fn () => (bool) $request->user()?->business?->stations()->exists(),
            'is_business_owner' => function () use ($request) {
                $user = $request->user();

                return (bool) ($user?->business && $user->business->user_id === $user->id);
            },
        ];
    }

    private function loadTranslations(string $locale): array
    {
        $path = lang_path("{$locale}.json");

        return file_exists($path) ? (json_decode(file_get_contents($path), true) ?? []) : [];
    }
}
