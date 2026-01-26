<?php

namespace App\Http\Middleware;

use App\Models\User;
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
        // Use the Vite manifest file's modification time as version
        // This ensures assets are refreshed when the build changes
        $manifestPath = public_path('build/manifest.json');

        if (file_exists($manifestPath)) {
            return md5_file($manifestPath);
        }

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
            'auth' => [
                'user' => $user ? $this->getUserData($user) : null,
            ],
            'tenant' => fn () => $user && $user->tenant ? $this->getTenantData($user->tenant) : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * Get user data for sharing.
     */
    protected function getUserData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'role_label' => $user->role_label,
            'initials' => $user->initials,
            'avatar' => $user->avatar,
            'is_super_admin' => $user->isSuperAdmin(),
            'is_owner' => $user->isOwner(),
            'is_admin' => $user->isAdmin(),
            'permissions' => User::ROLE_PERMISSIONS[$user->role] ?? [],
        ];
    }

    /**
     * Get tenant data for sharing.
     */
    protected function getTenantData($tenant): array
    {
        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'logo' => $tenant->logo,
            'status' => $tenant->status,
            'on_trial' => $tenant->onTrial(),
            'trial_ends_at' => $tenant->trial_ends_at?->toISOString(),
        ];
    }
}
