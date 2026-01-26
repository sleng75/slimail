<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->tenant_id) {
            $tenant = $user->tenant;

            if ($tenant) {
                // Store tenant in app container for global access
                App::instance('currentTenant', $tenant);

                // Set timezone and locale based on tenant settings
                config(['app.timezone' => $tenant->timezone]);
                config(['app.locale' => $tenant->locale]);
                date_default_timezone_set($tenant->timezone);
                App::setLocale($tenant->locale);

                // Share tenant data with all views/Inertia responses
                $request->attributes->set('tenant', $tenant);
            }
        }

        return $next($request);
    }
}
