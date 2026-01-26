<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Super admins bypass tenant checks
        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has a tenant
        if ($user && $user->tenant_id) {
            $tenant = $user->tenant;

            if (!$tenant) {
                return $this->handleInactiveTenant($request, 'Compte non trouvé.');
            }

            if ($tenant->isSuspended()) {
                return $this->handleInactiveTenant(
                    $request,
                    'Votre compte a été suspendu. Veuillez contacter le support.'
                );
            }

            if ($tenant->status === 'cancelled') {
                return $this->handleInactiveTenant(
                    $request,
                    'Votre abonnement a été annulé. Veuillez renouveler votre abonnement.'
                );
            }
        }

        return $next($request);
    }

    /**
     * Handle inactive tenant response.
     */
    protected function handleInactiveTenant(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'tenant_inactive',
                'message' => $message,
            ], 403);
        }

        // For web requests, logout and redirect with error
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => $message,
        ]);
    }
}
